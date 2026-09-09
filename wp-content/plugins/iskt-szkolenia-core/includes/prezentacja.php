<?php
/**
 * Przygotowanie danych katalogu do wyświetlenia.
 *
 * Warstwa pomiędzy polami z bazy a szablonami: zamienia wartości słownikowe na
 * etykiety, składa cenę z jednostką i podatkiem, zbiera formę realizacji. Dzięki
 * temu ta sama informacja wygląda tak samo na stronie głównej, w katalogu i na
 * stronie szkolenia — a zmiana sposobu prezentacji to jedno miejsce w kodzie.
 *
 * @package ISKT\Szkolenia\Core
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Składa cenę szkolenia w jeden opis.
 *
 * §4.3 wymaga „jednoznacznego opisu jednostki i sposobu prezentowania podatku”
 * i **zabrania** wyliczania ceny po dofinansowaniu z założonego poziomu wsparcia.
 * Prototyp robił dokładnie to, czego zlecenie zakazuje: mnożył cenę przez 0,2
 * i podpisywał wynik „z dofinansowaniem”. Tutaj pokazujemy wyłącznie cenę wpisaną
 * przez właściciela — informacja o dofinansowaniu jest osobnym, opisowym polem.
 *
 * @param int $szkolenie_id Identyfikator szkolenia.
 *
 * @return array{kwota: string, dopisek: string} Puste wartości, gdy ceny nie podano.
 */
function iskt_cena_szkolenia( int $szkolenie_id ): array {
	$pusta = array(
		'kwota'   => '',
		'dopisek' => '',
	);

	$jednostka = (string) get_post_meta( $szkolenie_id, '_iskt_cena_jednostka', true );

	// Wycena indywidualna nie ma kwoty — i nie powinna udawać, że ją ma.
	if ( 'do_ustalenia' === $jednostka ) {
		return array(
			'kwota'   => __( 'Wycena indywidualna', 'iskt-szkolenia-core' ),
			'dopisek' => '',
		);
	}

	$surowa = (string) get_post_meta( $szkolenie_id, '_iskt_cena', true );

	if ( '' === $surowa || ! is_numeric( $surowa ) ) {
		return $pusta;
	}

	$kwota     = (float) $surowa;
	$dziesietne = 0.0 === fmod( $kwota, 1.0 ) ? 0 : 2;

	$kwota_tekst = sprintf(
		/* translators: %s: kwota. */
		__( '%s zł', 'iskt-szkolenia-core' ),
		number_format_i18n( $kwota, $dziesietne )
	);

	$dopiski = array();

	$podatek = (string) get_post_meta( $szkolenie_id, '_iskt_cena_podatek', true );
	$slownik = iskt_slownik_podatku();

	if ( isset( $slownik[ $podatek ] ) ) {
		$dopiski[] = $slownik[ $podatek ];
	}

	$slownik_jednostek = iskt_slownik_jednostek_ceny();

	if ( isset( $slownik_jednostek[ $jednostka ] ) ) {
		$dopiski[] = $slownik_jednostek[ $jednostka ];
	}

	return array(
		'kwota'   => $kwota_tekst,
		'dopisek' => implode( ' · ', $dopiski ),
	);
}

/**
 * Zwraca nazwy form realizacji szkolenia (online / stacjonarnie).
 *
 * @param int $szkolenie_id Identyfikator szkolenia.
 *
 * @return array<int, string>
 */
function iskt_formy_szkolenia( int $szkolenie_id ): array {
	$terminy = get_the_terms( $szkolenie_id, ISKT_TAX_FORMA );

	if ( ! is_array( $terminy ) ) {
		return array();
	}

	return array_values( array_map( static fn ( WP_Term $termin ): string => $termin->name, $terminy ) );
}

/**
 * Zwraca pierwszą kategorię szkolenia.
 *
 * Szkolenie może mieć ich kilka, ale karta w katalogu ma miejsce na jedną —
 * bierzemy pierwszą według nazwy, żeby wynik był powtarzalny między odsłonami.
 *
 * @param int $szkolenie_id Identyfikator szkolenia.
 */
function iskt_kategoria_szkolenia( int $szkolenie_id ): ?WP_Term {
	$terminy = get_the_terms( $szkolenie_id, ISKT_TAX_KATEGORIA );

	if ( ! is_array( $terminy ) || array() === $terminy ) {
		return null;
	}

	usort( $terminy, static fn ( WP_Term $a, WP_Term $b ): int => strcmp( $a->name, $b->name ) );

	return $terminy[0];
}

/**
 * Zwraca etykietę poziomu szkolenia.
 *
 * @param int $szkolenie_id Identyfikator szkolenia.
 */
function iskt_poziom_szkolenia( int $szkolenie_id ): string {
	$poziom  = (string) get_post_meta( $szkolenie_id, '_iskt_poziom', true );
	$slownik = iskt_slownik_poziomow();

	return $slownik[ $poziom ] ?? '';
}

/**
 * Zwraca symbol graficzny dla identyfikatora zapisanego przy kategorii.
 *
 * Wtyczka zna tylko identyfikator (patrz `includes/kategorie.php`); kształt
 * dostarcza motyw przez ten filtr. Gdy motyw go nie obsługuje, karta po prostu
 * nie ma symbolu — i nadal jest kompletna.
 *
 * @param string $symbol Identyfikator symbolu.
 */
function iskt_symbol_html( string $symbol ): string {
	if ( '' === $symbol ) {
		return '';
	}

	/**
	 * Pozwala motywowi dostarczyć znacznik symbolu.
	 *
	 * @param string $html   Znacznik symbolu. Pusty, dopóki motyw go nie ustawi.
	 * @param string $symbol Identyfikator symbolu.
	 */
	$html = apply_filters( 'iskt_symbol_html', '', $symbol );

	return is_string( $html ) ? $html : '';
}
