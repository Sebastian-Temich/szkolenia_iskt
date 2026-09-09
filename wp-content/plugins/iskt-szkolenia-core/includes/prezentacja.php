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
 * Składa termin w komplet danych do wyświetlenia.
 *
 * Jedno miejsce opisu terminu, wspólne dla strony szkolenia i listy wyboru
 * w formularzu zgłoszeniowym (zadanie 9) — dzięki temu odwiedzający wybiera
 * w formularzu dokładnie ten sam termin, który przeczytał na stronie.
 *
 * @param int $termin_id Identyfikator terminu.
 *
 * @return array{etykieta: string, miejsce: string, status: string, status_klucz: string, zamkniety: bool}
 */
function iskt_opis_terminu( int $termin_id ): array {
	$status_klucz = (string) get_post_meta( $termin_id, '_iskt_status_zgloszen', true );
	$statusy      = iskt_slownik_statusow_zgloszen();

	$tryb    = (string) get_post_meta( $termin_id, '_iskt_tryb', true );
	$tryby   = iskt_slownik_trybow();
	$miejsce = $tryby[ $tryb ] ?? '';

	$lokalizacja = (string) get_post_meta( $termin_id, '_iskt_lokalizacja', true );

	/*
	 * Lokalizacja ma sens tylko przy zajęciach na miejscu. Przy terminie online
	 * bywa wpisana omyłkowo i wyglądałaby jak adres, pod który trzeba przyjechać.
	 */
	if ( '' !== $lokalizacja && 'online' !== $tryb ) {
		$miejsce = '' !== $miejsce ? $miejsce . ' · ' . $lokalizacja : $lokalizacja;
	}

	return array(
		'etykieta'     => iskt_etykieta_terminu( $termin_id ),
		'miejsce'      => $miejsce,
		'status'       => $statusy[ $status_klucz ] ?? '',
		'status_klucz' => isset( $statusy[ $status_klucz ] ) ? $status_klucz : '',
		'zamkniety'    => 'zamkniete' === $status_klucz,
	);
}

/**
 * Zwraca inicjały z nazwy, do zastępczego znaku przy braku zdjęcia.
 *
 * Profil trenera bez zdjęcia ma wyglądać kompletnie, a nie jak strona z brakującym
 * obrazkiem — §9 zostawia zdjęcia do czasu potwierdzenia praw, więc stan „bez zdjęcia”
 * jest normalny i długotrwały. Pomijamy człony, które nie zaczynają się literą:
 * dopisek „Demo — ” z danych demonstracyjnych dałby inicjał „—”.
 *
 * @param string $nazwa Imię i nazwisko.
 */
function iskt_inicjaly( string $nazwa ): string {
	$czlony = preg_split( '/\s+/u', trim( wp_strip_all_tags( $nazwa ) ) );

	if ( ! is_array( $czlony ) ) {
		return '';
	}

	$litery = array();

	foreach ( $czlony as $czlon ) {
		if ( 1 !== preg_match( '/^\p{L}/u', $czlon ) ) {
			continue;
		}

		$litery[] = mb_substr( $czlon, 0, 1 );

		if ( 2 === count( $litery ) ) {
			break;
		}
	}

	return mb_strtoupper( implode( '', $litery ) );
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
