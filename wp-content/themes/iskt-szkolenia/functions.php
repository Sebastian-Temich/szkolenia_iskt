<?php
/**
 * Motyw ISKT Szkolenia — konfiguracja.
 *
 * Zakres motywu to WYŁĄCZNIE prezentacja. Nie rejestrujemy tutaj typów treści,
 * taksonomii ani pól — należą do wtyczki iskt-szkolenia-core, aby zmiana motywu
 * nie usuwała danych katalogu (ISK-17 §6, docs/ADR-001-architektura.md §1).
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const ISKT_THEME_VERSION = '0.1.0';

/**
 * Kolejność wczytywania tokenów ma znaczenie: zmienne muszą być zdefiniowane,
 * zanim base.css ich użyje. Odwzorowuje styles.css z dostarczonego design systemu.
 */
const ISKT_TOKEN_FILES = array( 'fonts', 'colors', 'typography', 'spacing', 'effects', 'base' );

/**
 * Zwraca numer wersji zasobu na podstawie czasu modyfikacji pliku.
 *
 * Dzięki temu przeglądarka pobiera nową wersję po każdej zmianie, bez ręcznego
 * podbijania numerów. Gdy pliku nie ma, wracamy do wersji motywu.
 */
function iskt_asset_version( string $relative_path ): string {
	$absolute = get_theme_file_path( $relative_path );

	if ( ! file_exists( $absolute ) ) {
		return ISKT_THEME_VERSION;
	}

	return (string) filemtime( $absolute );
}

/**
 * Wczytuje arkusze stylów motywu.
 */
function iskt_enqueue_assets(): void {
	$handles = array();

	foreach ( ISKT_TOKEN_FILES as $token ) {
		$relative = "assets/css/tokens/{$token}.css";
		$handle   = "iskt-token-{$token}";

		wp_enqueue_style(
			$handle,
			get_theme_file_uri( $relative ),
			$handles,
			iskt_asset_version( $relative )
		);

		// Każdy kolejny token zależy od poprzednich, co utrwala kolejność kaskady.
		$handles[] = $handle;
	}
}
add_action( 'wp_enqueue_scripts', 'iskt_enqueue_assets' );

/**
 * Włącza wsparcie funkcji motywu.
 */
function iskt_theme_setup(): void {
	load_theme_textdomain( 'iskt-szkolenia', get_theme_file_path( 'languages' ) );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'script', 'style', 'navigation-widgets' ) );

	/*
	 * Menu jako obszary WordPress, a nie linki zapisane w szablonie — właściciel
	 * edytuje nawigację z panelu (§4.7) i nie zostają linki „#” (§11 pkt 10).
	 */
	register_nav_menus(
		array(
			'primary' => __( 'Menu główne', 'iskt-szkolenia' ),
			'footer'  => __( 'Menu w stopce', 'iskt-szkolenia' ),
		)
	);
}
add_action( 'after_setup_theme', 'iskt_theme_setup' );
