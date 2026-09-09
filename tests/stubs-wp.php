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
