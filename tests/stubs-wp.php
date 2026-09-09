<?php
/**
 * Minimalne atrapy funkcji WordPressa na potrzeby testów sanityzacji.
 *
 * Testujemy wyłącznie funkcje czyste — sanityzację pól i logikę dat. Nie udajemy
 * całego WordPressa: atrapa, która zachowuje się inaczej niż oryginał, daje testy
 * przechodzące na czymś, czego nie ma w produkcji. Zaimplementowane są tylko te
 * funkcje, których zachowanie w tym zakresie da się odwzorować wiernie.
 *
 * @package ISKT\Szkolenia\Tests
 */

declare( strict_types = 1 );

define( 'ABSPATH', __DIR__ );

/**
 * Rejestr typów wpisów używany przez atrapę `get_post_type()`.
 *
 * @var array<int, string>
 */
$GLOBALS['iskt_test_typy'] = array();

/**
 * Rejestr terminów taksonomii: id wpisu → taksonomia → lista terminów.
 *
 * @var array<int, array<string, array<int, WP_Term>>>
 */
$GLOBALS['iskt_test_terminy'] = array();

/**
 * Atrapa `WP_Term` w zakresie pól, z których korzystają testowane funkcje.
 *
 * Nie odwzorowujemy całej klasy — atrapa z polami, których nikt nie czyta, tylko
 * sugerowałaby, że test sprawdza więcej, niż sprawdza.
 */
// phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound
class WP_Term {
	/**
	 * Identyfikator terminu.
	 *
	 * @var int
	 */
	public int $term_id;

	/**
	 * Nazwa terminu.
	 *
	 * @var string
	 */
	public string $name;

	/**
	 * Opis terminu.
	 *
	 * @var string
	 */
	public string $description;

	/**
	 * @param int    $term_id     Identyfikator.
	 * @param string $name        Nazwa.
	 * @param string $description Opis.
	 */
	public function __construct( int $term_id, string $name, string $description = '' ) {
		$this->term_id     = $term_id;
		$this->name        = $name;
		$this->description = $description;
	}
}

/**
 * Atrapa `WP_Post` w zakresie pól czytanych przy budowaniu etykiet powiązań.
 *
 * Jak przy `WP_Term`: tylko te pola, które testowany kod naprawdę czyta.
 */
// phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound
class WP_Post {
	/**
	 * Identyfikator wpisu.
	 *
	 * @var int
	 */
	public int $ID;

	/**
	 * Tytuł wpisu.
	 *
	 * @var string
	 */
	public string $post_title;

	/**
	 * Slug wpisu.
	 *
	 * @var string
	 */
	public string $post_name;

	/**
	 * Status wpisu.
	 *
	 * @var string
	 */
	public string $post_status;

	/**
	 * Typ wpisu. Uzupełnia go atrapa `get_post()` z rejestru `iskt_test_typy`.
	 *
	 * Pole jest zadeklarowane, a nie dopisywane w locie: od PHP 8.2 własność
	 * nadana dynamicznie wypisuje ostrzeżenie i test przestaje być czytelny.
	 *
	 * @var string
	 */
	public string $post_type = '';

	/**
	 * @param int    $id     Identyfikator.
	 * @param string $tytul  Tytuł.
	 * @param string $slug   Slug.
	 * @param string $status Status.
	 */
	public function __construct( int $id, string $tytul, string $slug = '', string $status = 'publish' ) {
		$this->ID          = $id;
		$this->post_title  = $tytul;
		$this->post_name   = $slug;
		$this->post_status = $status;
	}
}

/**
 * Rejestr pól używany przez atrapę `get_post_meta()`.
 *
 * @var array<int, array<string, mixed>>
 */
$GLOBALS['iskt_test_pola'] = array();

/**
 * Odwzorowuje `sanitize_text_field()`: usuwa znaczniki, znaki sterujące i skrajne spacje.
 *
 * @param string $tekst Tekst wejściowy.
 */
function sanitize_text_field( string $tekst ): string {
	$tekst = strip_tags( $tekst );
	$tekst = preg_replace( '/[\r\n\t ]+/', ' ', $tekst ) ?? '';
	$tekst = preg_replace( '/[\x00-\x1F\x7F]/u', '', $tekst ) ?? '';

	return trim( $tekst );
}

/**
 * Odwzorowuje `sanitize_textarea_field()`: jak wyżej, ale zachowuje złamania wiersza.
 *
 * @param string $tekst Tekst wejściowy.
 */
function sanitize_textarea_field( string $tekst ): string {
	$tekst = strip_tags( $tekst );
	$tekst = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $tekst ) ?? '';

	return trim( $tekst );
}

/**
 * Odwzorowuje `absint()`.
 *
 * @param mixed $wartosc Wartość wejściowa.
 */
function absint( $wartosc ): int {
	return abs( (int) $wartosc );
}

/**
 * Odwzorowuje `rest_sanitize_boolean()`.
 *
 * @param mixed $wartosc Wartość wejściowa.
 */
function rest_sanitize_boolean( $wartosc ): bool {
	if ( is_string( $wartosc ) ) {
		$wartosc = strtolower( $wartosc );

		if ( in_array( $wartosc, array( 'false', '0', '' ), true ) ) {
			return false;
		}
	}

	return (bool) $wartosc;
}

/**
 * Zwraca typ wpisu z rejestru testowego.
 *
 * @param int $post_id Identyfikator wpisu.
 *
 * @return string|false
 */
function get_post_type( $post_id = null ) {
	return $GLOBALS['iskt_test_typy'][ (int) $post_id ] ?? false;
}

/**
 * Rejestr wpisów używany przez atrapę `get_post()`.
 *
 * Klucz to identyfikator, wartość to `WP_Post`. Typ wpisu bierzemy z `iskt_test_typy`,
 * czyli z tego samego rejestru co `get_post_type()` — dwa źródła tej samej informacji
 * rozjechałyby się w pierwszym teście, który ustawi tylko jedno z nich.
 *
 * @var array<int, WP_Post>
 */
$GLOBALS['iskt_test_wpisy'] = array();

/**
 * Zwraca wpis z rejestru testowego wraz z jego typem.
 *
 * @param int $post_id Identyfikator wpisu.
 *
 * @return WP_Post|null
 */
function get_post( $post_id = null ) {
	$wpis = $GLOBALS['iskt_test_wpisy'][ (int) $post_id ] ?? null;

	if ( $wpis instanceof WP_Post ) {
		$wpis->post_type = (string) ( $GLOBALS['iskt_test_typy'][ (int) $post_id ] ?? '' );
	}

	return $wpis;
}

/**
 * Zwraca pojęcie taksonomii z rejestru testowego.
 *
 * @param int    $term_id     Identyfikator pojęcia.
 * @param string $taksonomia  Nazwa taksonomii.
 *
 * @return WP_Term|null
 */
function get_term( $term_id, $taksonomia = '' ) {
	unset( $taksonomia );

	return $GLOBALS['iskt_test_pojecia'][ (int) $term_id ] ?? null;
}

/**
 * Rejestr pojęć używany przez atrapę `get_term()`.
 *
 * @var array<int, WP_Term>
 */
$GLOBALS['iskt_test_pojecia'] = array();

/**
 * Zwraca pole wpisu z rejestru testowego.
 *
 * @param int    $post_id Identyfikator wpisu.
 * @param string $klucz   Nazwa pola.
 * @param bool   $single  Czy zwrócić pojedynczą wartość.
 *
 * @return mixed
 */
function get_post_meta( $post_id, $klucz = '', $single = false ) {
	unset( $single );

	return $GLOBALS['iskt_test_pola'][ (int) $post_id ][ $klucz ] ?? '';
}

/**
 * Rejestr pól haseł taksonomii używany przez atrapę `get_term_meta()`.
 *
 * @var array<int, array<string, mixed>>
 */
$GLOBALS['iskt_test_pola_pojec'] = array();

/**
 * Zwraca pole hasła taksonomii z rejestru testowego.
 *
 * @param int    $term_id Identyfikator hasła.
 * @param string $klucz   Nazwa pola.
 * @param bool   $single  Czy zwrócić pojedynczą wartość.
 *
 * @return mixed
 */
function get_term_meta( $term_id, $klucz = '', $single = false ) {
	unset( $single );

	return $GLOBALS['iskt_test_pola_pojec'][ (int) $term_id ][ $klucz ] ?? '';
}

/**
 * Odwzorowuje `__()` — tłumaczenia nie wpływają na testowaną logikę.
 *
 * @param string $tekst  Tekst.
 * @param string $domena Domena tłumaczeń.
 */
function __( string $tekst, string $domena = 'default' ): string {
	unset( $domena );

	return $tekst;
}

/**
 * Odwzorowuje `current_datetime()` — data ustawiana przez test.
 */
function current_datetime(): DateTimeImmutable {
	return $GLOBALS['iskt_test_dzis'] ?? new DateTimeImmutable( '2026-09-09 12:00:00' );
}

/*
 * Puste `add_action` i `add_filter` pozwalają wczytać prawdziwe pliki wtyczki zamiast
 * kopiować z nich funkcje do testu. Kopia przechodziłaby testy nawet wtedy, gdyby
 * oryginał się zepsuł — czyli testowałaby sama siebie.
 */

/**
 * Atrapa `add_action()`.
 *
 * @param mixed ...$argumenty Argumenty pomijane.
 */
function add_action( ...$argumenty ): bool {
	unset( $argumenty );

	return true;
}

/**
 * Atrapa `add_filter()`.
 *
 * @param mixed ...$argumenty Argumenty pomijane.
 */
function add_filter( ...$argumenty ): bool {
	unset( $argumenty );

	return true;
}

/**
 * Odwzorowuje `wp_unslash()`.
 *
 * WordPress dokłada ukośniki do `$_GET` i `$_POST`; `wp_unslash()` je zdejmuje.
 *
 * @param mixed $wartosc Wartość wejściowa.
 *
 * @return mixed
 */
function wp_unslash( $wartosc ) {
	if ( is_array( $wartosc ) ) {
		return array_map( 'wp_unslash', $wartosc );
	}

	return is_string( $wartosc ) ? stripslashes( $wartosc ) : $wartosc;
}

/**
 * Atrapa `is_front_page()`.
 *
 * Testy przełącznika odbiorcy sprawdzają go w kontekście strony głównej — to
 * jedyne miejsce, w którym występuje we wzorcach.
 */
function is_front_page(): bool {
	return true;
}

/**
 * Atrapa `home_url()`.
 *
 * @param string $sciezka Ścieżka doklejana do adresu.
 */
function home_url( string $sciezka = '' ): string {
	return 'https://szkolenia.example/' . ltrim( $sciezka, '/' );
}

/**
 * Odwzorowuje `esc_url()` w zakresie potrzebnym testom.
 *
 * @param string $adres Adres wejściowy.
 */
function esc_url( string $adres ): string {
	return htmlspecialchars( $adres, ENT_QUOTES, 'UTF-8' );
}

/**
 * Odwzorowuje `sanitize_key()`: małe litery, cyfry, myślnik i podkreślenie.
 *
 * @param string $klucz Wartość wejściowa.
 */
function sanitize_key( string $klucz ): string {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $klucz ) ) ?? '';
}

/**
 * Rejestr opcji używany przez atrapy `get_option()` i `update_option()`.
 *
 * @var array<string, mixed>
 */
$GLOBALS['iskt_test_opcje'] = array();

/**
 * Atrapa `get_option()`.
 *
 * @param string $nazwa    Nazwa opcji.
 * @param mixed  $domyslna Wartość zwracana, gdy opcji nie ma.
 *
 * @return mixed
 */
function get_option( string $nazwa, $domyslna = false ) {
	return $GLOBALS['iskt_test_opcje'][ $nazwa ] ?? $domyslna;
}

/**
 * Odwzorowuje `esc_html()` w zakresie, w jakim korzystają z niego testowane funkcje.
 *
 * @param string $tekst Tekst wejściowy.
 */
function esc_html( string $tekst ): string {
	return htmlspecialchars( $tekst, ENT_QUOTES, 'UTF-8' );
}

/**
 * Odwzorowuje `esc_attr()`.
 *
 * @param string $tekst Tekst wejściowy.
 */
function esc_attr( string $tekst ): string {
	return htmlspecialchars( $tekst, ENT_QUOTES, 'UTF-8' );
}

/**
 * Odwzorowuje `wp_strip_all_tags()`.
 *
 * @param string $tekst Tekst wejściowy.
 */
function wp_strip_all_tags( string $tekst ): string {
	return trim( strip_tags( $tekst ) );
}

/**
 * Atrapa `get_block_wrapper_attributes()`.
 *
 * Oryginał dokłada klasy z ustawień bloku wybranych w edytorze; poza WordPressem
 * nie ma skąd ich wziąć, więc zwracamy wyłącznie klasy przekazane wprost. Testy
 * sprawdzają logikę renderowania, nie sposób składania atrybutów przez rdzeń.
 *
 * @param array<string, string> $dodatkowe Atrybuty przekazane przez blok.
 */
function get_block_wrapper_attributes( array $dodatkowe = array() ): string {
	$klasa = $dodatkowe['class'] ?? '';

	return 'class="' . esc_attr( $klasa ) . '"';
}

/**
 * Odwzorowuje `number_format_i18n()` dla ustawień polskich.
 *
 * Separator tysięcy to spacja nierozdzielająca, część dziesiętna po przecinku —
 * dokładnie tak, jak formatuje polska lokalizacja WordPressa.
 *
 * @param float $liczba     Liczba do sformatowania.
 * @param int   $dziesietne Liczba miejsc po przecinku.
 */
function number_format_i18n( float $liczba, int $dziesietne = 0 ): string {
	return number_format( $liczba, $dziesietne, ',', "\u{00A0}" );
}

/**
 * Atrapa `apply_filters()` — zwraca wartość bez zmian.
 *
 * W testach nie ma zarejestrowanych filtrów, bo `add_filter()` jest pusty.
 *
 * @param string $nazwa   Nazwa filtru, pomijana.
 * @param mixed  $wartosc Wartość przekazywana dalej.
 * @param mixed  ...$dodatkowe Pozostałe argumenty, pomijane.
 *
 * @return mixed
 */
function apply_filters( string $nazwa, $wartosc, ...$dodatkowe ) {
	unset( $nazwa, $dodatkowe );

	return $wartosc;
}

/**
 * Atrapa `get_the_terms()` czytająca z rejestru testowego.
 *
 * @param int    $post_id   Identyfikator wpisu.
 * @param string $taksonomia Nazwa taksonomii.
 *
 * @return array<int, WP_Term>|false
 */
function get_the_terms( int $post_id, string $taksonomia ) {
	$terminy = $GLOBALS['iskt_test_terminy'][ $post_id ][ $taksonomia ] ?? false;

	return array() === $terminy ? false : $terminy;
}

/**
 * Odwzorowuje `wp_date()` w zakresie potrzebnym testom etykiety terminu.
 *
 * Oryginał uwzględnia strefę czasową i tłumaczenia; tutaj wystarczy format,
 * bo testy sprawdzają składanie opisu terminu, a nie lokalizację dat.
 *
 * @param string   $format     Format daty.
 * @param int|null $znacznik   Znacznik czasu.
 *
 * @return string|false
 */
function wp_date( string $format, ?int $znacznik = null ) {
	return gmdate( $format, $znacznik ?? 0 );
}

/**
 * Odwzorowuje `sanitize_email()` w zakresie potrzebnym testom tekstów globalnych.
 *
 * Oryginał rozbiera adres na część lokalną i domenę i odrzuca niedozwolone znaki.
 * Atrapa robi to samo filtrem `FILTER_VALIDATE_EMAIL` — dla adresów, które trafiają
 * do pola kontaktowego, obie drogi dają ten sam wynik: poprawny adres albo pustkę.
 *
 * @param string $adres Adres wejściowy.
 */
function sanitize_email( string $adres ): string {
	$czysty = filter_var( trim( $adres ), FILTER_VALIDATE_EMAIL );

	return is_string( $czysty ) ? $czysty : '';
}

/**
 * Odwzorowuje `esc_url_raw()` w zakresie potrzebnym testom.
 *
 * Sprawdzamy to, co sprawdza oryginał w tym zastosowaniu: adres musi mieć
 * dozwolony schemat, inaczej nie trafia do bazy. `javascript:` odpada.
 *
 * @param string $adres Adres wejściowy.
 */
function esc_url_raw( string $adres ): string {
	$adres = trim( $adres );

	if ( '' === $adres ) {
		return '';
	}

	$schemat = strtolower( (string) wp_parse_url( $adres, PHP_URL_SCHEME ) );

	if ( ! in_array( $schemat, array( 'http', 'https', 'mailto' ), true ) ) {
		return '';
	}

	return $adres;
}

/**
 * Odwzorowuje `wp_parse_url()` przez `parse_url()`.
 *
 * @param string $adres     Adres wejściowy.
 * @param int    $skladowa  Składowa do zwrócenia.
 *
 * @return mixed
 */
function wp_parse_url( string $adres, int $skladowa = -1 ) {
	return parse_url( $adres, $skladowa );
}

/**
 * Odwzorowuje `is_email()` w zakresie, w jakim korzysta z niego walidacja formularza.
 *
 * Oryginał sprawdza długość, obecność `@`, dozwolone znaki części lokalnej oraz
 * domenę złożoną z co najmniej dwóch członów. Atrapa sprawdza to samo — świadomie
 * nie filtrem `FILTER_VALIDATE_EMAIL`, bo ten przepuszcza `a@b`, a WordPress nie.
 *
 * @param string $adres Adres wejściowy.
 */
function is_email( string $adres ): bool {
	if ( strlen( $adres ) < 6 || ! str_contains( $adres, '@' ) ) {
		return false;
	}

	$czesci = explode( '@', $adres );

	if ( 2 !== count( $czesci ) ) {
		return false;
	}

	if ( 1 !== preg_match( '/^[a-zA-Z0-9!#$%&\'*+\/=?^_`{|}~.-]+$/', $czesci[0] ) ) {
		return false;
	}

	$domena = trim( $czesci[1], '.' );
	$czlony = explode( '.', $domena );

	if ( count( $czlony ) < 2 ) {
		return false;
	}

	foreach ( $czlony as $czlon ) {
		if ( 1 !== preg_match( '/^[a-z0-9-]+$/i', trim( $czlon, '-' ) ) ) {
			return false;
		}
	}

	return true;
}
