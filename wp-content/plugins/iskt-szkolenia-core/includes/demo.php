<?php
/**
 * Oznaczanie i usuwanie treści demonstracyjnych.
 *
 * §9 zlecenia mówi wprost: materiały z prototypu nie są zatwierdzoną ofertą ISKT,
 * a treści niepotwierdzone mają być oznaczone jako demonstracyjne albo ukryte przed
 * publikacją. §8 wymaga, żeby właściciel mógł je usunąć. Oba wymagania rozjeżdżają się
 * bez jednego warunku: musi istnieć sposób, żeby **znaleźć** wpis demonstracyjny
 * inaczej niż po tytule.
 *
 * Dlatego każdy taki wpis nosi pole `_iskt_demo` o wartości `1`. Prefiks „Demo —”
 * w tytule zostaje, ale jest tylko podpowiedzią dla człowieka — decyduje pole.
 * Uzasadnienie wyboru mechanizmu i odrzucone warianty: docs/DANE-DEMONSTRACYJNE.md.
 *
 * Usuwanie jest celowo wąskie. Kasujemy wyłącznie to, co nosi oznaczenie, i nic poza
 * tym: żadnych opcji, żadnego rejestru tekstów, żadnych wpisów założonych przez
 * właściciela. Znacznik `[do potwierdzenia: …]` w tekstach globalnych to osobny
 * mechanizm (`includes/teksty.php`) i ten plik go nie dotyka.
 *
 * @package ISKT\Szkolenia\Core
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Pole oznaczające wpis lub hasło taksonomii jako dane demonstracyjne.
 *
 * Nazwa zaczyna się od podkreślenia, więc pole jest chronione: nie pojawia się
 * w skrzynce „Pola własne” i nie da się go ustawić przypadkiem z poziomu edytora.
 */
const ISKT_META_DEMO = '_iskt_demo';

/**
 * Wartość zapisywana w polu oznaczenia.
 *
 * Trzymamy ciąg `'1'`, a nie wartość logiczną: `update_post_meta( $id, $klucz, false )`
 * zapisuje w bazie pusty ciąg, przez co zapytanie `meta_value = '1'` przestaje
 * odróżniać „nie demonstracyjne” od „oznaczone i wyłączone”.
 */
const ISKT_DEMO_TAK = '1';

/**
 * Typy treści przeszukiwane przy spisie i usuwaniu.
 *
 * `post` jest na liście, bo dane demonstracyjne aktualności to zwykłe wpisy;
 * `attachment`, bo do wpisu demonstracyjnego bywa podpięte zdjęcie, a plik bez
 * właściciela zostawałby na dysku po sprzątaniu.
 *
 * @return string[]
 */
function iskt_typy_demo(): array {
	/**
	 * Pozwala rozszerzyć zakres sprzątania o własny typ treści.
	 *
	 * @param string[] $typy Typy treści.
	 */
	return (array) apply_filters(
		'iskt_typy_demo',
		array(
			ISKT_CPT_SZKOLENIE,
			ISKT_CPT_TRENER,
			ISKT_CPT_TERMIN,
			ISKT_CPT_ZGLOSZENIE,
			'post',
			'page',
			'attachment',
		)
	);
}

/**
 * Taksonomie przeszukiwane przy spisie i usuwaniu.
 *
 * Dane demonstracyjne aktualności zakładają własne **kategorie** — bez tej listy
 * po sprzątaniu zostawałyby puste kategorie „Demo — …” w menu i na archiwach.
 *
 * @return string[]
 */
function iskt_taksonomie_demo(): array {
	/**
	 * Pozwala rozszerzyć zakres sprzątania o własną taksonomię.
	 *
	 * @param string[] $taksonomie Nazwy taksonomii.
	 */
	return (array) apply_filters(
		'iskt_taksonomie_demo',
		array(
			ISKT_TAX_KATEGORIA,
			ISKT_TAX_FORMA,
			'category',
			'post_tag',
		)
	);
}

/**
 * Statusy wpisów objęte spisem.
 *
 * `any` nie wystarcza: pomija kosz i `auto-draft`, a wpis demonstracyjny wrzucony
 * do kosza dalej leży w bazie i trafiłby do paczki przekazania (zadanie 13).
 *
 * @return string[]
 */
function iskt_statusy_demo(): array {
	return array( 'publish', 'future', 'draft', 'pending', 'private', 'trash', 'inherit' );
}

/**
 * Sanityzuje wartość pola oznaczenia.
 *
 * Wartość jest dwustanowa: `'1'` albo pusty ciąg. Cokolwiek innego wpadnie tu
 * z importu czy WP-CLI, zostanie sprowadzone do jednego z tych dwóch stanów —
 * pole sterujące kasowaniem nie może mieć stanu trzeciego.
 *
 * @param mixed $wartosc Wartość z zapisu.
 *
 * @return string
 */
function iskt_sanitize_demo( $wartosc ): string {
	return iskt_czy_oznaczenie_demo( $wartosc ) ? ISKT_DEMO_TAK : '';
}

/**
 * Rozstrzyga, czy surowa wartość pola oznacza wpis demonstracyjny.
 *
 * Za oznaczenie uznajemy `'1'`, `1` i `true`. Celowo NIE uznajemy `'0'`, `'false'`
 * ani pustego ciągu: gdyby dowolna niepusta wartość znaczyła „demo”, wpis
 * z polem ustawionym na `'0'` zostałby skasowany.
 *
 * @param mixed $wartosc Surowa wartość pola.
 *
 * @return bool
 */
function iskt_czy_oznaczenie_demo( $wartosc ): bool {
	if ( is_bool( $wartosc ) ) {
		return $wartosc;
	}

	if ( ! is_scalar( $wartosc ) ) {
		return false;
	}

	return ISKT_DEMO_TAK === (string) $wartosc;
}

/**
 * Sprawdza, czy wpis jest oznaczony jako demonstracyjny.
 *
 * @param int $post_id Identyfikator wpisu.
 *
 * @return bool
 */
function iskt_czy_demo( int $post_id ): bool {
	return iskt_czy_oznaczenie_demo( get_post_meta( $post_id, ISKT_META_DEMO, true ) );
}

/**
 * Sprawdza, czy hasło taksonomii jest oznaczone jako demonstracyjne.
 *
 * @param int $term_id Identyfikator hasła.
 *
 * @return bool
 */
function iskt_czy_demo_termin( int $term_id ): bool {
	return iskt_czy_oznaczenie_demo( get_term_meta( $term_id, ISKT_META_DEMO, true ) );
}

/**
 * Oznacza wpis jako demonstracyjny.
 *
 * Wywołują to skrypty zasiewu (`tests/e2e/dane-*.php`). Dzięki temu nowe dane
 * testowe wchodzą do spisu w chwili powstania, a nie dopiero wtedy, gdy ktoś
 * o nich sobie przypomni.
 *
 * @param int $post_id Identyfikator wpisu.
 *
 * @return bool
 */
function iskt_oznacz_demo( int $post_id ): bool {
	if ( $post_id <= 0 ) {
		return false;
	}

	return false !== update_post_meta( $post_id, ISKT_META_DEMO, ISKT_DEMO_TAK );
}

/**
 * Zdejmuje oznaczenie z wpisu.
 *
 * Potrzebne, gdy właściciel chce zachować wpis powstały jako demonstracyjny —
 * bez tej drogi jedynym sposobem byłoby przepisanie go ręcznie od nowa.
 *
 * @param int $post_id Identyfikator wpisu.
 *
 * @return bool
 */
function iskt_odznacz_demo( int $post_id ): bool {
	return delete_post_meta( $post_id, ISKT_META_DEMO );
}

/**
 * Oznacza hasło taksonomii jako demonstracyjne.
 *
 * @param int $term_id Identyfikator hasła.
 *
 * @return bool
 */
function iskt_oznacz_demo_termin( int $term_id ): bool {
	if ( $term_id <= 0 ) {
		return false;
	}

	return false !== update_term_meta( $term_id, ISKT_META_DEMO, ISKT_DEMO_TAK );
}

/**
 * Rejestruje pole oznaczenia dla wszystkich objętych typów i taksonomii.
 *
 * Rejestracja nie jest ozdobnikiem: `sanitize_callback` i `auth_callback` obowiązują
 * niezależnie od drogi zapisu (REST, import, WP-CLI), a bez nich dowolna wtyczka
 * mogłaby ustawić polu wartość, której nasza logika nie przewiduje.
 */
function iskt_rejestruj_pole_demo(): void {
	$argumenty = array(
		'type'              => 'string',
		'description'       => __( 'Dane demonstracyjne — do usunięcia przed przekazaniem serwisu', 'iskt-szkolenia-core' ),
		'single'            => true,
		'default'           => '',
		'sanitize_callback' => 'iskt_sanitize_demo',
		'show_in_rest'      => false,
	);

	foreach ( iskt_typy_demo() as $typ ) {
		register_post_meta(
			(string) $typ,
			ISKT_META_DEMO,
			array_merge( $argumenty, array( 'auth_callback' => 'iskt_moze_oznaczac_demo' ) )
		);
	}

	foreach ( iskt_taksonomie_demo() as $taksonomia ) {
		register_term_meta(
			(string) $taksonomia,
			ISKT_META_DEMO,
			array_merge( $argumenty, array( 'auth_callback' => 'iskt_moze_sprzatac_demo' ) )
		);
	}
}
add_action( 'init', 'iskt_rejestruj_pole_demo', 20 );

/**
 * Uprawnienie wymagane do obejrzenia spisu i usunięcia danych demonstracyjnych.
 *
 * Bierzemy uprawnienie **kasowania** katalogu, a nie edycji: ekran kasuje treść,
 * więc próg musi odpowiadać skutkowi. Administrator i redaktor dostają je przy
 * aktywacji wtyczki (`includes/capabilities.php`).
 */
function iskt_cap_demo(): string {
	/**
	 * Pozwala zawęzić dostęp do sprzątania danych demonstracyjnych.
	 *
	 * @param string $cap Wymagane uprawnienie.
	 */
	return (string) apply_filters( 'iskt_cap_demo', 'delete_iskt_szkolenia' );
}

/**
 * Sprawdza prawo do oznaczenia konkretnego wpisu.
 *
 * @param bool   $dozwolone Wartość domyślna WordPressa.
 * @param string $meta_key  Nazwa pola.
 * @param int    $post_id   Identyfikator wpisu.
 *
 * @return bool
 */
function iskt_moze_oznaczac_demo( bool $dozwolone, string $meta_key, int $post_id ): bool {
	unset( $dozwolone, $meta_key );

	return current_user_can( 'edit_post', $post_id );
}

/**
 * Sprawdza prawo do oznaczania haseł taksonomii.
 *
 * @return bool
 */
function iskt_moze_sprzatac_demo(): bool {
	return current_user_can( iskt_cap_demo() );
}

/**
 * Zwraca wpisy oznaczone jako demonstracyjne, pogrupowane po typie treści.
 *
 * @return array<string, WP_Post[]>
 */
function iskt_wpisy_demo(): array {
	$wpisy = get_posts(
		array(
			'post_type'        => iskt_typy_demo(),
			'post_status'      => iskt_statusy_demo(),
			'posts_per_page'   => -1,
			'orderby'          => 'title',
			'order'            => 'ASC',
			'suppress_filters' => true,
			'no_found_rows'    => true,
			'meta_query'       => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- spis liczy kilkadziesiąt wpisów i uruchamia się na żądanie, nie na froncie.
				array(
					'key'   => ISKT_META_DEMO,
					'value' => ISKT_DEMO_TAK,
				),
			),
		)
	);

	$pogrupowane = array();

	foreach ( $wpisy as $wpis ) {
		$pogrupowane[ $wpis->post_type ][] = $wpis;
	}

	return $pogrupowane;
}

/**
 * Zwraca hasła taksonomii oznaczone jako demonstracyjne, pogrupowane po taksonomii.
 *
 * @return array<string, WP_Term[]>
 */
function iskt_hasla_demo(): array {
	$hasla = get_terms(
		array(
			'taxonomy'   => iskt_taksonomie_demo(),
			'hide_empty' => false,
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- jak wyżej: zapytanie na żądanie, poza frontem.
				array(
					'key'   => ISKT_META_DEMO,
					'value' => ISKT_DEMO_TAK,
				),
			),
		)
	);

	if ( is_wp_error( $hasla ) ) {
		return array();
	}

	$pogrupowane = array();

	foreach ( $hasla as $haslo ) {
		$pogrupowane[ $haslo->taxonomy ][] = $haslo;
	}

	return $pogrupowane;
}

/**
 * Filtruje listę identyfikatorów, zostawiając wyłącznie wpisy oznaczone.
 *
 * Funkcja istnieje osobno, bo to na niej opiera się obietnica z §8: wpis
 * właściciela ma przeżyć sprzątanie. Trzymana z dala od zapytań do bazy daje się
 * sprawdzić testem (`tests/run.php`) bez uruchamiania WordPressa.
 *
 * @param int[] $identyfikatory Identyfikatory wpisów.
 *
 * @return int[]
 */
function iskt_wpisy_do_usuniecia( array $identyfikatory ): array {
	$do_usuniecia = array();

	foreach ( $identyfikatory as $id ) {
		$id = (int) $id;

		if ( $id > 0 && iskt_czy_demo( $id ) ) {
			$do_usuniecia[] = $id;
		}
	}

	return $do_usuniecia;
}

/**
 * Liczy pozycje spisu w rozbiciu na klucze grup.
 *
 * @param array<string, array<int, mixed>> $spis Spis pogrupowany po typie lub taksonomii.
 *
 * @return array<string, int>
 */
function iskt_policz_spis( array $spis ): array {
	$liczby = array();

	foreach ( $spis as $klucz => $pozycje ) {
		$liczby[ (string) $klucz ] = is_array( $pozycje ) ? count( $pozycje ) : 0;
	}

	return $liczby;
}

/**
 * Sumuje pozycje spisu.
 *
 * @param array<string, array<int, mixed>> $spis Spis pogrupowany.
 *
 * @return int
 */
function iskt_suma_spisu( array $spis ): int {
	return (int) array_sum( iskt_policz_spis( $spis ) );
}

/**
 * Buduje komunikat podsumowujący sprzątanie.
 *
 * Bez odmiany przez liczbę mnogą — w polszczyźnie „1 wpis / 2 wpisy / 5 wpisów”
 * wymagałoby trzech form dla każdej pozycji, a komunikat w postaci „wpisy: 12”
 * czyta się tak samo dobrze i nie da się w nim pomylić formy.
 *
 * @param array{wpisy?: int, hasla?: int, pominiete?: int} $liczby Wynik sprzątania.
 *
 * @return string
 */
function iskt_podsumowanie_usuwania( array $liczby ): string {
	$wpisy     = (int) ( $liczby['wpisy'] ?? 0 );
	$hasla     = (int) ( $liczby['hasla'] ?? 0 );
	$pominiete = (int) ( $liczby['pominiete'] ?? 0 );

	if ( 0 === $wpisy && 0 === $hasla && 0 === $pominiete ) {
		return __( 'Nie znaleziono danych demonstracyjnych — nie było czego usuwać.', 'iskt-szkolenia-core' );
	}

	$komunikat = sprintf(
		/* translators: 1: liczba usuniętych wpisów, 2: liczba usuniętych haseł taksonomii. */
		__( 'Usunięto dane demonstracyjne — wpisy: %1$d, hasła taksonomii: %2$d.', 'iskt-szkolenia-core' ),
		$wpisy,
		$hasla
	);

	if ( $pominiete > 0 ) {
		$komunikat .= ' ' . sprintf(
			/* translators: %d: liczba pozycji pominiętych z braku uprawnień. */
			__( 'Pominięto z braku uprawnień: %d.', 'iskt-szkolenia-core' ),
			$pominiete
		);
	}

	return $komunikat;
}

/**
 * Usuwa wszystkie dane demonstracyjne.
 *
 * Trzy decyzje, które warto znać przed użyciem:
 *
 * - kasujemy **z pominięciem kosza** (`wp_delete_post( $id, true )`). Kosz zostawiłby
 *   te same wpisy w bazie, a paczka przekazania z zadania 13 powstaje z eksportu
 *   bazy — więc „usunięte” trafiłoby do właściciela razem z resztą;
 * - każdy wpis przechodzi osobne sprawdzenie `delete_post`. Uprawnienie do ekranu
 *   nie znaczy uprawnienia do każdego wpisu — redaktor bez prawa do cudzych wpisów
 *   ma je pominąć, a nie skasować;
 * - nie ruszamy opcji. Rejestr tekstów (`ISKT_OPCJA_TEKSTY`) i znacznik
 *   `[do potwierdzenia: …]` przeżywają sprzątanie nietknięte.
 *
 * @return array{wpisy: int, hasla: int, pominiete: int}
 */
function iskt_usun_dane_demonstracyjne(): array {
	$usuniete_wpisy = 0;
	$usuniete_hasla = 0;
	$pominiete      = 0;

	foreach ( iskt_wpisy_demo() as $wpisy ) {
		foreach ( $wpisy as $wpis ) {
			if ( ! current_user_can( 'delete_post', $wpis->ID ) ) {
				++$pominiete;
				continue;
			}

			/*
			 * Powtórne sprawdzenie oznaczenia tuż przed skasowaniem. Między zbudowaniem
			 * spisu a tą pętlą ktoś mógł zdjąć oznaczenie — koszt sprawdzenia jest
			 * żaden, a cena pomyłki to skasowany wpis właściciela.
			 */
			if ( ! iskt_czy_demo( $wpis->ID ) ) {
				continue;
			}

			$wynik = wp_delete_post( $wpis->ID, true );

			if ( ! $wynik instanceof WP_Post ) {
				++$pominiete;
				continue;
			}

			++$usuniete_wpisy;
		}
	}

	foreach ( iskt_hasla_demo() as $taksonomia => $hasla ) {
		foreach ( $hasla as $haslo ) {
			if ( ! iskt_czy_demo_termin( (int) $haslo->term_id ) ) {
				continue;
			}

			$wynik = wp_delete_term( (int) $haslo->term_id, (string) $taksonomia );

			if ( true === $wynik ) {
				++$usuniete_hasla;
				continue;
			}

			++$pominiete;
		}
	}

	return array(
		'wpisy'     => $usuniete_wpisy,
		'hasla'     => $usuniete_hasla,
		'pominiete' => $pominiete,
	);
}
