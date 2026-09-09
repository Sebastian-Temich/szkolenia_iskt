<?php
/**
 * Aktywacja i dezaktywacja wtyczki.
 *
 * Dezaktywacja NIE usuwa treści katalogu — §6 wymaga, aby dane przetrwały zmianę
 * motywu, a właściciel może wyłączyć wtyczkę przy diagnostyce. Usuwane są wyłącznie
 * uprawnienia i reguły adresów, czyli rzeczy, które bez wtyczki nie mają pokrycia.
 *
 * @package ISKT\Szkolenia\Core
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Znacznik jednorazowego zasiania słowników.
 */
const ISKT_OPCJA_ZASIANO = 'iskt_slowniki_zasiane';

/**
 * Kategorie startowe wymienione w §4.2. Właściciel może je zmienić i usunąć —
 * dlatego zasiewamy je dokładnie raz i nigdy nie odtwarzamy.
 *
 * @return array<string, string> Slug => nazwa.
 */
function iskt_kategorie_startowe(): array {
	return array(
		'ai-i-narzedzia-generatywne'  => __( 'AI i narzędzia generatywne', 'iskt-szkolenia-core' ),
		'esg-i-zrownowazony-rozwoj'   => __( 'ESG i zrównoważony rozwój', 'iskt-szkolenia-core' ),
		'jezyk-angielski'             => __( 'Język angielski', 'iskt-szkolenia-core' ),
		'rozwoj-oprogramowania'       => __( 'Rozwój oprogramowania', 'iskt-szkolenia-core' ),
		'projekty-br'                 => __( 'Projekty B+R', 'iskt-szkolenia-core' ),
	);
}

/**
 * Formy realizacji startowe.
 *
 * @return array<string, string> Slug => nazwa.
 */
function iskt_formy_startowe(): array {
	return array(
		'online'       => __( 'Online', 'iskt-szkolenia-core' ),
		'stacjonarnie' => __( 'Stacjonarnie', 'iskt-szkolenia-core' ),
		'hybrydowo'    => __( 'Hybrydowo', 'iskt-szkolenia-core' ),
	);
}

/**
 * Zasiewa słowniki taksonomii dokładnie raz na instalację.
 *
 * Zasianie przy każdej aktywacji odtwarzałoby kategorie, które właściciel świadomie
 * usunął — a wtyczka bywa aktywowana ponownie po każdej aktualizacji ręcznej.
 */
function iskt_zasiej_slowniki(): void {
	if ( (bool) get_option( ISKT_OPCJA_ZASIANO, false ) ) {
		return;
	}

	$do_zasiania = array(
		ISKT_TAX_KATEGORIA => iskt_kategorie_startowe(),
		ISKT_TAX_FORMA     => iskt_formy_startowe(),
	);

	foreach ( $do_zasiania as $taksonomia => $terminy ) {
		foreach ( $terminy as $slug => $nazwa ) {
			if ( term_exists( $slug, $taksonomia ) ) {
				continue;
			}

			wp_insert_term( $nazwa, $taksonomia, array( 'slug' => $slug ) );
		}
	}

	update_option( ISKT_OPCJA_ZASIANO, true, false );
}

/**
 * Aktywacja wtyczki.
 */
function iskt_on_activate(): void {
	/*
	 * Typy treści i taksonomie muszą istnieć, zanim zasiejemy terminy i przeliczymy
	 * reguły adresów. Przy aktywacji hook `init` już przeszedł, więc rejestrujemy je wprost.
	 *
	 * Kolejność jest znacząca i taka sama jak na `init` (patrz `model.php`):
	 * taksonomie przed typami treści, inaczej adres kategorii wpada w regułę
	 * załączników szkolenia i kończy się stroną „nie znaleziono”.
	 */
	iskt_rejestruj_taksonomie();
	iskt_rejestruj_typy_tresci();

	iskt_nadaj_capabilities();
	iskt_zasiej_slowniki();

	flush_rewrite_rules();
}

/**
 * Dezaktywacja wtyczki.
 */
function iskt_on_deactivate(): void {
	iskt_odbierz_capabilities();

	/*
	 * Czyścimy reguły adresów, żeby po wyłączeniu wtyczki `/szkolenia/` nie zostawało
	 * jako martwy adres obsługiwany przez nieistniejący typ treści.
	 */
	flush_rewrite_rules();
}
