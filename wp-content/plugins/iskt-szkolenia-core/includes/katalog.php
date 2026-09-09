<?php
/**
 * Katalog szkoleń — wyszukiwanie, filtry i paginacja archiwum `/szkolenia/`.
 *
 * §4.2 wymaga katalogu z wyszukiwaniem po nazwie i opisie, filtrowaniem po
 * kategorii i formie realizacji oraz wyróżnianiem wybranych szkoleń. Ten plik
 * odpowiada za to, CO katalog pokazuje; motyw odpowiada za to, JAK to wygląda.
 * Podział jest ten sam co przy modelu danych (§6, ADR-001 §1): zmiana motywu nie
 * może zabrać katalogowi filtrów.
 *
 * Trzy decyzje, które warto znać czytając ten plik:
 *
 * 1. **Filtry zmieniają zapytanie główne** (`pre_get_posts`), a nie tworzą drugiego
 *    obok niego. Dzięki temu paginacja, adresy stron `/szkolenia/strona/2/`,
 *    kanoniczne adresy i obsługa nieistniejącej strony działają tak, jak je
 *    napisano w WordPressie — nie odtwarzamy ich własnym kodem.
 * 2. **Fraza jedzie w parametrze `szukaj`, nie w `s`.** Parametr `s` włącza w
 *    WordPressie tryb wyszukiwania w całym serwisie: widok przestaje być archiwum
 *    szkoleń i dostaje szablon wyników wyszukiwania, razem z wpisami i stronami.
 *    Katalog ma zostać katalogiem, więc ma własny parametr.
 * 3. **Stan filtrów siedzi w adresie** (zwykły formularz GET). §6 wymaga, aby
 *    „Wstecz” działało i aby wynik dało się odesłać linkiem — a lekcja z ISK-21
 *    mówi, że mechanizm zależny od JavaScriptu pada pierwszy.
 *
 * @package ISKT\Szkolenia\Core
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Nazwy parametrów adresu. Trafiają do linków, które ludzie zapisują i wysyłają —
 * zmiana wartości unieważnia zapisane adresy, więc trzymamy je w stałych.
 */
const ISKT_PARAM_SZUKAJ    = 'szukaj';
const ISKT_PARAM_KATEGORIA = 'kategoria';
const ISKT_PARAM_FORMA     = 'forma';

/**
 * Górna granica długości frazy i sluga z adresu.
 *
 * Nie jest to zabezpieczenie — sanityzacja i tak przepuszcza wyłącznie znane
 * znaki. Chodzi o to, żeby doklejony do adresu kilobajt tekstu nie trafiał do
 * zapytania i do pola formularza.
 */
const ISKT_KATALOG_MAX_FRAZA = 120;
const ISKT_KATALOG_MAX_SLUG  = 100;

/**
 * Liczba szkoleń na stronie katalogu.
 *
 * Katalog ma rosnąć bez przebudowy widoku, więc paginacja jest od pierwszego dnia,
 * także wtedy, gdy szkoleń jest mniej niż jedna strona.
 */
function iskt_katalog_na_strone(): int {
	/**
	 * Pozwala zmienić liczbę szkoleń na stronie katalogu.
	 *
	 * @param int $na_strone Liczba szkoleń.
	 */
	$na_strone = (int) apply_filters( 'iskt_katalog_na_strone', 9 );

	return max( 1, min( 100, $na_strone ) );
}

/**
 * Sprowadza wartość filtra do sluga taksonomii.
 *
 * Zostawiamy litery (także z ogonkami — slug „bhp-w-magazynie” i „księgowość”
 * są równie prawdziwe), cyfry, myślnik i podkreślenie. Wszystko inne odpada,
 * więc do zapytania nie trafia ani znacznik, ani spacja, ani znak sterujący.
 *
 * Slug nieistniejącej kategorii przechodzi przez tę funkcję bez zmian i daje
 * pustą listę wyników — czyli komunikat „nie znaleźliśmy szkoleń”. To celowe:
 * odwiedzający z popsutym linkiem dostaje wyjaśnienie i przycisk czyszczenia
 * filtrów, a nie stronę błędu.
 *
 * @param mixed $wartosc Wartość z adresu.
 */
function iskt_sanitize_filtr_slug( $wartosc ): string {
	if ( ! is_scalar( $wartosc ) ) {
		return '';
	}

	$slug = mb_strtolower( trim( (string) $wartosc ), 'UTF-8' );
	$slug = preg_replace( '/[^\p{L}\p{N}_-]+/u', '', $slug ) ?? '';

	return mb_substr( $slug, 0, ISKT_KATALOG_MAX_SLUG, 'UTF-8' );
}

/**
 * Sprowadza frazę wyszukiwania do postaci nadającej się do zapytania i do pola.
 *
 * @param mixed $wartosc Wartość z adresu.
 */
function iskt_sanitize_filtr_fraza( $wartosc ): string {
	if ( ! is_scalar( $wartosc ) ) {
		return '';
	}

	$fraza = sanitize_text_field( (string) $wartosc );

	return trim( mb_substr( $fraza, 0, ISKT_KATALOG_MAX_FRAZA, 'UTF-8' ) );
}

/**
 * Czyta parametry katalogu z adresu.
 *
 * Funkcja czysta: źródło można podać wprost, więc testy nie potrzebują ani
 * WordPressa, ani superglobalnych.
 *
 * @param array<string, mixed>|null $zrodlo Dane wejściowe; domyślnie `$_GET`.
 *
 * @return array{szukaj: string, kategoria: string, forma: string}
 */
function iskt_parametry_katalogu( ?array $zrodlo = null ): array {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- odczyt publicznego widoku, bez skutków ubocznych.
	$zrodlo = null === $zrodlo ? wp_unslash( $_GET ) : $zrodlo;

	return array(
		'szukaj'    => iskt_sanitize_filtr_fraza( $zrodlo[ ISKT_PARAM_SZUKAJ ] ?? '' ),
		'kategoria' => iskt_sanitize_filtr_slug( $zrodlo[ ISKT_PARAM_KATEGORIA ] ?? '' ),
		'forma'     => iskt_sanitize_filtr_slug( $zrodlo[ ISKT_PARAM_FORMA ] ?? '' ),
	);
}

/**
 * Czy odwiedzający zawęził katalog.
 *
 * Rozstrzyga, który z dwóch komunikatów pustego widoku pokazać, i czy w ogóle
 * pokazywać przycisk czyszczenia filtrów.
 *
 * @param array<string, string> $parametry Parametry katalogu.
 */
function iskt_katalog_jest_filtrowany( array $parametry ): bool {
	foreach ( array( 'szukaj', 'kategoria', 'forma' ) as $klucz ) {
		if ( '' !== (string) ( $parametry[ $klucz ] ?? '' ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Składa argumenty zapytania katalogu z parametrów adresu.
 *
 * Wydzielone z `pre_get_posts` i czyste, bo to jest miejsce, w którym najłatwiej
 * o pomyłkę: filtr, który nie zawęża, albo zawęża do niczego, wygląda na stronie
 * tak samo jak brak oferty.
 *
 * Kolejność: najpierw szkolenia wyróżnione (§4.2), potem kolejność ustawiona
 * przez właściciela w polu „Kolejność”, na końcu najnowsze. Wyróżnienie jest
 * polem `_iskt_wyroznione`, a szkolenia bez tego pola muszą zostać w wynikach —
 * stąd para warunków „pole istnieje LUB nie istnieje” zamiast zwykłego
 * `meta_key`, który po cichu wyciąłby z katalogu wszystko, czego nikt nigdy nie
 * wyróżnił.
 *
 * @param array{szukaj: string, kategoria: string, forma: string} $parametry Parametry katalogu.
 *
 * @return array<string, mixed>
 */
function iskt_argumenty_katalogu( array $parametry ): array {
	$argumenty = array(
		'posts_per_page' => iskt_katalog_na_strone(),
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'relation'   => 'OR',
			'wyroznione' => array(
				'key'     => '_iskt_wyroznione',
				'compare' => 'EXISTS',
			),
			array(
				'key'     => '_iskt_wyroznione',
				'compare' => 'NOT EXISTS',
			),
		),
		'orderby'        => array(
			'wyroznione' => 'DESC',
			'menu_order' => 'ASC',
			'date'       => 'DESC',
		),
	);

	if ( '' !== $parametry['szukaj'] ) {
		$argumenty['s'] = $parametry['szukaj'];
	}

	$taksonomie = array();

	if ( '' !== $parametry['kategoria'] ) {
		$taksonomie[] = array(
			'taxonomy' => ISKT_TAX_KATEGORIA,
			'field'    => 'slug',
			'terms'    => $parametry['kategoria'],
		);
	}

	if ( '' !== $parametry['forma'] ) {
		$taksonomie[] = array(
			'taxonomy' => ISKT_TAX_FORMA,
			'field'    => 'slug',
			'terms'    => $parametry['forma'],
		);
	}

	if ( array() !== $taksonomie ) {
		// Dwa filtry zawężają razem: „ESG” i „online” to szkolenia ESG prowadzone online.
		$taksonomie['relation'] = 'AND';

		$argumenty['tax_query'] = $taksonomie; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	}

	return $argumenty;
}

/**
 * Czy bieżące żądanie to widok katalogu.
 *
 * Archiwum typu treści oraz archiwa kategorii i formy — wszystkie trzy pokazują
 * ten sam katalog, więc wszystkie trzy dostają te same zasady.
 */
function iskt_jest_widok_katalogu( WP_Query $zapytanie ): bool {
	return $zapytanie->is_post_type_archive( ISKT_CPT_SZKOLENIE )
		|| $zapytanie->is_tax( array( ISKT_TAX_KATEGORIA, ISKT_TAX_FORMA ) );
}

/**
 * Nakłada wyszukiwanie, filtry i paginację na zapytanie katalogu.
 *
 * Wycofane szkolenie znika z katalogu bez ani jednej linijki kodu: zapytanie
 * główne archiwum czyta wyłącznie wpisy opublikowane, więc przeniesienie
 * szkolenia do szkiców zabiera je z listy, z wyszukiwania i z filtrów.
 *
 * @param WP_Query $zapytanie Zapytanie WordPressa.
 */
function iskt_filtruj_katalog( WP_Query $zapytanie ): void {
	if ( is_admin() || ! $zapytanie->is_main_query() || ! iskt_jest_widok_katalogu( $zapytanie ) ) {
		return;
	}

	foreach ( iskt_argumenty_katalogu( iskt_parametry_katalogu() ) as $klucz => $wartosc ) {
		$zapytanie->set( $klucz, $wartosc );
	}
}
add_action( 'pre_get_posts', 'iskt_filtruj_katalog' );

/**
 * Zwraca pozycje listy filtra dla taksonomii katalogu.
 *
 * `hide_empty` nie jest tu oszczędnością zapytania, tylko wymogiem §4.2: po
 * wycofaniu ostatniego szkolenia z kategorii ta kategoria ma zniknąć z filtrów.
 * Pozycja prowadząca zawsze do pustej listy jest gorsza niż jej brak.
 *
 * @param string $taksonomia Nazwa taksonomii.
 *
 * @return array<string, string> Slug => nazwa.
 */
function iskt_opcje_filtra( string $taksonomia ): array {
	$terminy = get_terms(
		array(
			'taxonomy'   => $taksonomia,
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $terminy ) ) {
		return array();
	}

	$opcje = array();

	foreach ( $terminy as $termin ) {
		$opcje[ $termin->slug ] = $termin->name;
	}

	return $opcje;
}

/**
 * Czy katalog zawiera jakiekolwiek opublikowane szkolenie.
 *
 * Pusty katalog i brak trafień filtrowania to dwie różne sytuacje dla
 * odwiedzającego: w pierwszej nie ma czego szukać, w drugiej wystarczy zmienić
 * kryteria. Zapytanie wykonuje się wyłącznie wtedy, gdy widok nie ma wyników.
 */
function iskt_katalog_ma_szkolenia(): bool {
	$zapytanie = new WP_Query(
		array(
			'post_type'              => ISKT_CPT_SZKOLENIE,
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	return array() !== $zapytanie->posts;
}

/**
 * Buduje adres katalogu z podanym stanem filtrów.
 *
 * Bez parametrów zwraca adres pełnego katalogu — tego używa przycisk czyszczenia
 * filtrów i akcja formularza.
 *
 * @param array<string, string> $parametry Parametry katalogu.
 */
function iskt_adres_katalogu( array $parametry = array() ): string {
	$adres = get_post_type_archive_link( ISKT_CPT_SZKOLENIE );

	if ( ! is_string( $adres ) || '' === $adres ) {
		$adres = home_url( '/' );
	}

	$doklejane = array();

	foreach ( array(
		ISKT_PARAM_SZUKAJ    => (string) ( $parametry['szukaj'] ?? '' ),
		ISKT_PARAM_KATEGORIA => (string) ( $parametry['kategoria'] ?? '' ),
		ISKT_PARAM_FORMA     => (string) ( $parametry['forma'] ?? '' ),
	) as $nazwa => $wartosc ) {
		if ( '' !== $wartosc ) {
			$doklejane[ $nazwa ] = $wartosc;
		}
	}

	return array() === $doklejane ? $adres : add_query_arg( $doklejane, $adres );
}

/**
 * Zwraca stan filtrów do pokazania w formularzu.
 *
 * Na archiwum kategorii i formy filtr wynika z adresu strony, a nie z parametru —
 * i formularz musi to pokazywać, inaczej odwiedzający widzi listę zawężoną przez
 * coś, czego nie widać w kontrolkach.
 *
 * @return array{szukaj: string, kategoria: string, forma: string}
 */
function iskt_stan_katalogu(): array {
	$stan = iskt_parametry_katalogu();

	$termin = get_queried_object();

	if ( $termin instanceof WP_Term ) {
		if ( ISKT_TAX_KATEGORIA === $termin->taxonomy ) {
			$stan['kategoria'] = $termin->slug;
		}

		if ( ISKT_TAX_FORMA === $termin->taxonomy ) {
			$stan['forma'] = $termin->slug;
		}
	}

	return $stan;
}

/**
 * Wyłącza indeksowanie widoków zawężonych parametrami filtrów.
 *
 * §7 wymaga rozstrzygnięcia, co robimy z widokami filtrowanymi. Zakres nie
 * obejmuje wtyczki SEO (ADR-002), więc rozstrzygamy to jednym filtrem rdzenia:
 * kombinacje frazy i filtrów tworzą praktycznie nieskończoną liczbę adresów o tej
 * samej treści, więc zostają poza indeksem. Dokładamy wyłącznie `noindex` —
 * podążanie za odnośnikami jest zachowaniem domyślnym, więc roboty dojdą z tych
 * widoków do stron szkoleń, a ustawienie „Proś wyszukiwarki o nieindeksowanie”
 * z panelu zostaje nietknięte. Widok katalogu bez parametrów, archiwa kategorii
 * i formy oraz kolejne strony paginacji indeksują się normalnie.
 * Uzasadnienie: `docs/KATALOG-SZKOLEN.md` §5.
 *
 * @param array<string, bool|string> $robots Dyrektywy robotów.
 *
 * @return array<string, bool|string>
 */
function iskt_katalog_robots( array $robots ): array {
	if ( ! is_post_type_archive( ISKT_CPT_SZKOLENIE ) && ! is_tax( array( ISKT_TAX_KATEGORIA, ISKT_TAX_FORMA ) ) ) {
		return $robots;
	}

	if ( ! iskt_katalog_jest_filtrowany( iskt_parametry_katalogu() ) ) {
		return $robots;
	}

	$robots['noindex'] = true;

	return $robots;
}
add_filter( 'wp_robots', 'iskt_katalog_robots' );
