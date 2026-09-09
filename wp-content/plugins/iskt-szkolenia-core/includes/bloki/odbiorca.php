<?php
/**
 * Bloki przełącznika odbiorcy: „Dla Ciebie” / „Dla firm”.
 *
 * Dwa bloki, celowo rozdzielone:
 * - `iskt/przelacznik-odbiorcy` — sam przełącznik, wstawiany raz na stronie;
 * - `iskt/tresc-odbiorcy` — pojemnik na treść jednego wariantu, wstawiany tyle razy,
 *   ile sekcji ma się różnić. Wewnątrz działa cały edytor blokowy, więc właściciel
 *   pisze oba warianty tak samo, jak każdą inną treść (§4.1).
 *
 * Dlaczego nie jedno pole z dwiema wersjami tekstu: sekcje różniące się między
 * odbiorcami to w załączniku nagłówek, przyciski, opis procesu i tekst kontaktu —
 * cztery różne miejsca strony. Pojemnik można wstawić w każde z nich, pole nie.
 *
 * @package ISKT\Szkolenia\Core
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Wypisuje przełącznik wariantu odbiorcy.
 *
 * Formularz `GET` bez JavaScriptu przeładowuje stronę z parametrem `?odbiorca=…`,
 * a serwer pokazuje właściwy wariant. Skrypt motywu przechwytuje kliknięcie
 * i przełącza treść na miejscu — działa więc szybciej, ale nie jest do niczego
 * potrzebny. Prototyp działał wyłącznie w drugą stronę i po odświeżeniu gubił wybór.
 *
 * @param array<string, mixed> $atrybuty Atrybuty bloku.
 */
function iskt_render_przelacznik_odbiorcy( array $atrybuty ): string {
	$aktywny  = iskt_odbiorca_aktywny();
	$warianty = iskt_warianty_odbiorcy();

	$etykiety = array(
		'indywidualny' => isset( $atrybuty['etykietaIndywidualny'] ) ? (string) $atrybuty['etykietaIndywidualny'] : '',
		'firmowy'      => isset( $atrybuty['etykietaFirmowy'] ) ? (string) $atrybuty['etykietaFirmowy'] : '',
	);

	$opis = isset( $atrybuty['opis'] ) && '' !== (string) $atrybuty['opis']
		? (string) $atrybuty['opis']
		: __( 'Wybierz wariant komunikacji', 'iskt-szkolenia-core' );

	$przyciski = '';

	foreach ( $warianty as $wariant => $domyslna ) {
		$etykieta = '' !== $etykiety[ $wariant ] ? $etykiety[ $wariant ] : $domyslna;
		$wybrany  = $wariant === $aktywny;

		$przyciski .= sprintf(
			'<button type="submit" name="%1$s" value="%2$s" class="iskt-switch__option" aria-pressed="%3$s">%4$s</button>',
			esc_attr( ISKT_PARAM_ODBIORCA ),
			esc_attr( $wariant ),
			$wybrany ? 'true' : 'false',
			esc_html( $etykieta )
		);
	}

	return sprintf(
		'<form %1$s method="get" action="%2$s" role="group" aria-label="%3$s" data-iskt-switch data-iskt-switch-active="%4$s">%5$s</form>',
		iskt_atrybuty_bloku( array( 'class' => 'iskt-switch' ) ),
		esc_url( iskt_adres_biezacej_strony() ),
		esc_attr( $opis ),
		esc_attr( $aktywny ),
		$przyciski
	);
}

/**
 * Sprawdza, czy wyrenderowana treść bloku w ogóle coś pokazuje.
 *
 * Samo `wp_strip_all_tags()` tu nie wystarcza: sekcja złożona wyłącznie ze zdjęcia,
 * filmu albo separatora nie ma ani jednego znaku tekstu, a mimo to jest treścią.
 * Ukrycie jej byłoby błędem trudnym do zrozumienia dla właściciela — sekcja
 * zniknęłaby ze strony bez żadnego komunikatu.
 *
 * @param string $tresc Wyrenderowana treść bloków zagnieżdżonych.
 */
function iskt_ma_tresc( string $tresc ): bool {
	if ( '' !== trim( wp_strip_all_tags( $tresc ) ) ) {
		return true;
	}

	// Znaczniki, które same z siebie coś rysują, mimo braku tekstu.
	return 1 === preg_match(
		'/<(img|picture|video|audio|iframe|svg|canvas|hr|embed|object|form|input|button|table)\b/i',
		$tresc
	);
}

/**
 * Wypisuje treść przypisaną do jednego wariantu odbiorcy.
 *
 * Oba warianty trafiają do dokumentu, a nieaktywny dostaje atrybut `hidden`.
 * Powód: bez tego przełączenie bez przeładowania nie miałoby czego pokazać.
 * `hidden` — a nie `display:none` z klasy — jest tu istotne, bo czytniki ekranu
 * pomijają treść ukrytą atrybutem, więc odwiedzający nie słyszy dwóch wersji
 * tego samego nagłówka.
 *
 * @param array<string, mixed> $atrybuty Atrybuty bloku.
 * @param string               $tresc    Treść bloków zagnieżdżonych.
 */
function iskt_render_tresc_odbiorcy( array $atrybuty, string $tresc = '' ): string {
	// Pojemnik bez treści nie zostawia po sobie pustego znacznika (§4.1).
	if ( ! iskt_ma_tresc( $tresc ) ) {
		return '';
	}

	$wariant = iskt_sanitize_odbiorca( $atrybuty['odbiorca'] ?? '' );

	/*
	 * Nieznany wariant oznacza uszkodzony atrybut — pokazujemy treść zawsze,
	 * zamiast ukrywać ją przed wszystkimi. Utrata przełączania jest widoczna
	 * i naprawialna; zniknięcie sekcji wygląda jak brak treści.
	 */
	if ( '' === $wariant ) {
		return sprintf(
			'<div %1$s>%2$s</div>',
			iskt_atrybuty_bloku( array( 'class' => 'iskt-odbiorca' ) ),
			$tresc
		);
	}

	$aktywny = $wariant === iskt_odbiorca_aktywny();

	/*
	 * W dokumencie może istnieć tylko jeden znacznik h1. Nieaktywny wariant nadal
	 * musi pozostać w DOM, aby przełącznik działał bez przeładowania, dlatego jego
	 * h1 zachowuje semantykę nagłówka poziomu 1 przez ARIA. Po przełączeniu jest
	 * jedynym nagłówkiem poziomu 1 widocznym dla technologii asystujących, podczas
	 * gdy h1 poprzedniego wariantu jest ukryty razem z panelem.
	 */
	if ( ! $aktywny ) {
		$tresc = preg_replace(
			array( '/<h1\b([^>]*)>/i', '/<\/h1>/i' ),
			array( '<div role="heading" aria-level="1"$1>', '</div>' ),
			$tresc
		) ?? $tresc;
	}

	return sprintf(
		'<div %1$s data-iskt-switch-panel="%2$s"%3$s>%4$s</div>',
		iskt_atrybuty_bloku( array( 'class' => 'iskt-odbiorca' ) ),
		esc_attr( $wariant ),
		$aktywny ? '' : ' hidden',
		$tresc
	);
}
