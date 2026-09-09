<?php
/**
 * Wariant odbiorcy: „Dla Ciebie” / „Dla firm”.
 *
 * Zlecenie §4.1 wymaga przełączania komunikacji między dwoma odbiorcami. Prototyp
 * trzymał wybór w stanie komponentu React — po odświeżeniu strony wracał do wartości
 * początkowej, a przycisk „Wstecz” nie działał. Tego zachowania nie odtwarzamy.
 *
 * Wybór jest częścią adresu (`?odbiorca=firmowy`), więc:
 * - strona działa bez JavaScriptu — przełącznik to zwykły formularz GET;
 * - odnośnik do wariantu firmowego da się wysłać i dodać do zakładek;
 * - przycisk „Wstecz” wraca do poprzedniego wariantu.
 *
 * JavaScript tę mechanikę wyłącznie ulepsza: przełącza widoczność bez przeładowania
 * i podmienia adres przez History API (assets/js/odbiorca.js w motywie).
 *
 * @package ISKT\Szkolenia\Core
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Nazwa parametru adresu i pola formularza przełącznika.
 */
const ISKT_PARAM_ODBIORCA = 'odbiorca';

/**
 * Opcja z domyślnym wariantem odbiorcy.
 *
 * Ekran ustawień dokłada zadanie 10; do tego czasu wartość da się zmienić przez
 * `update_option()`, a brak opcji oznacza wariant indywidualny.
 */
const ISKT_OPCJA_ODBIORCA_DOMYSLNY = 'iskt_odbiorca_domyslny';

/**
 * Warianty odbiorcy: identyfikator zapisywany w bazie → domyślna etykieta.
 *
 * Identyfikatory trafiają do treści wpisów (atrybut bloku), więc ich zmiana po
 * wdrożeniu odcięłaby istniejące sekcje. Etykiety są tylko wartością początkową —
 * właściciel nadpisuje je w atrybutach bloku przełącznika.
 *
 * @return array<string, string>
 */
function iskt_warianty_odbiorcy(): array {
	return array(
		'indywidualny' => __( 'Dla Ciebie', 'iskt-szkolenia-core' ),
		'firmowy'      => __( 'Dla firm', 'iskt-szkolenia-core' ),
	);
}

/**
 * Sprowadza dowolną wartość do znanego wariantu odbiorcy.
 *
 * Zwraca pusty ciąg dla wartości spoza listy. Wywołujący decyduje, czym zastąpić
 * nieznany wariant — dzięki temu ta funkcja nadaje się zarówno do walidacji danych
 * z adresu, jak i do sanityzacji atrybutu bloku.
 *
 * @param mixed $wartosc Wartość do sprawdzenia.
 */
function iskt_sanitize_odbiorca( $wartosc ): string {
	if ( ! is_string( $wartosc ) ) {
		return '';
	}

	$wartosc = sanitize_key( $wartosc );

	return isset( iskt_warianty_odbiorcy()[ $wartosc ] ) ? $wartosc : '';
}

/**
 * Wariant pokazywany, gdy odwiedzający nie wybrał żadnego.
 */
function iskt_odbiorca_domyslny(): string {
	$zapisany = iskt_sanitize_odbiorca( get_option( ISKT_OPCJA_ODBIORCA_DOMYSLNY, '' ) );

	return '' !== $zapisany ? $zapisany : 'indywidualny';
}

/**
 * Wariant odbiorcy obowiązujący dla bieżącego żądania.
 *
 * Kolejność: parametr adresu → opcja → wariant indywidualny.
 */
function iskt_odbiorca_aktywny(): string {
	/*
	 * Odczyt bez `nonce` jest tu poprawny: parametr nie wykonuje żadnego działania,
	 * nie zmienia stanu i nie dotyczy danych użytkownika — wybiera wyłącznie wariant
	 * tekstu do wyświetlenia. Wartość i tak przechodzi przez listę dozwolonych.
	 */
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$zadany = isset( $_GET[ ISKT_PARAM_ODBIORCA ] ) ? wp_unslash( $_GET[ ISKT_PARAM_ODBIORCA ] ) : '';

	$wariant = iskt_sanitize_odbiorca( $zadany );

	return '' !== $wariant ? $wariant : iskt_odbiorca_domyslny();
}

/**
 * Adres bieżącej strony z ustawionym wariantem odbiorcy.
 *
 * Używany jako `action` formularza przełącznika oraz jako adres, który JavaScript
 * wpisuje do historii przeglądarki.
 *
 * @param string $wariant Identyfikator wariantu.
 */
function iskt_adres_wariantu( string $wariant ): string {
	$wariant = iskt_sanitize_odbiorca( $wariant );

	if ( '' === $wariant ) {
		return '';
	}

	$podstawa = iskt_adres_biezacej_strony();

	return add_query_arg( ISKT_PARAM_ODBIORCA, $wariant, $podstawa );
}

/**
 * Adres bieżącego widoku bez parametru odbiorcy.
 *
 * Budujemy go z danych WordPressa, a nie z `$_SERVER['REQUEST_URI']` — adres
 * z żądania pochodzi od odwiedzającego i nie nadaje się do wstawienia w dokument
 * bez dodatkowych zabezpieczeń.
 */
function iskt_adres_biezacej_strony(): string {
	if ( is_front_page() ) {
		return home_url( '/' );
	}

	if ( is_singular() ) {
		$adres = get_permalink();

		if ( is_string( $adres ) && '' !== $adres ) {
			return $adres;
		}
	}

	if ( is_post_type_archive() ) {
		$typ = get_query_var( 'post_type' );
		$typ = is_array( $typ ) ? (string) reset( $typ ) : (string) $typ;

		$adres = get_post_type_archive_link( $typ );

		if ( is_string( $adres ) && '' !== $adres ) {
			return $adres;
		}
	}

	return home_url( '/' );
}
