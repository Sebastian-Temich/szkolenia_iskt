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

const ISKT_THEME_VERSION = '0.2.0';

/**
 * Kolejność wczytywania tokenów ma znaczenie: zmienne muszą być zdefiniowane,
 * zanim base.css ich użyje. Odwzorowuje styles.css z dostarczonego design systemu.
 */
const ISKT_TOKEN_FILES = array( 'fonts', 'colors', 'typography', 'spacing', 'effects', 'base' );

/**
 * Arkusze motywu, wczytywane po tokenach i również w ustalonej kolejności:
 * układ → typografia → komponenty → szkielet strony. Żaden z nich nie powtarza
 * wartości z tokenów; wszystkie korzystają ze zmiennych CSS.
 */
const ISKT_STYLE_FILES = array( 'uklad', 'typografia', 'komponenty', 'szkielet', 'sekcje', 'aktualnosci' );

require_once get_theme_file_path( 'inc/teksty.php' );
require_once get_theme_file_path( 'inc/ikony.php' );
require_once get_theme_file_path( 'inc/nawigacja.php' );
require_once get_theme_file_path( 'inc/szablony.php' );
require_once get_theme_file_path( 'inc/sekcje.php' );
require_once get_theme_file_path( 'inc/aktualnosci.php' );
require_once get_theme_file_path( 'inc/instalacja.php' );

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
 * Mapa uchwyt → ścieżka arkusza, w kolejności kaskady.
 *
 * Ta sama lista zasila stronę i podgląd w edytorze blokowym, więc redaktor widzi
 * w panelu to samo, co odwiedzający.
 *
 * @return array<string, string>
 */
function iskt_stylesheets(): array {
	$sheets = array();

	foreach ( ISKT_TOKEN_FILES as $token ) {
		$sheets[ "iskt-token-{$token}" ] = "assets/css/tokens/{$token}.css";
	}

	foreach ( ISKT_STYLE_FILES as $style ) {
		$sheets[ "iskt-{$style}" ] = "assets/css/{$style}.css";
	}

	return $sheets;
}

/**
 * Wczytuje arkusze stylów i skrypt nawigacji.
 */
function iskt_enqueue_assets(): void {
	$handles = array();

	foreach ( iskt_stylesheets() as $handle => $relative ) {
		wp_enqueue_style( $handle, get_theme_file_uri( $relative ), $handles, iskt_asset_version( $relative ) );

		// Każdy kolejny arkusz zależy od poprzednich, co utrwala kolejność kaskady.
		$handles[] = $handle;
	}

	/*
	 * Skrypt nawigacji wyłącznie ulepsza działające menu (patrz assets/js/nawigacja.js),
	 * więc może poczekać do końca dokumentu i nie blokuje wyświetlenia strony.
	 */
	wp_enqueue_script(
		'iskt-nawigacja',
		get_theme_file_uri( 'assets/js/nawigacja.js' ),
		array(),
		iskt_asset_version( 'assets/js/nawigacja.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	/*
	 * Skrypt przełącznika odbiorcy wczytujemy tylko tam, gdzie przełącznik może
	 * wystąpić — czyli na widokach z treścią blokową. Na archiwach i wynikach
	 * wyszukiwania byłby wyłącznie żądaniem bez zastosowania.
	 */
	if ( is_singular() || is_front_page() ) {
		wp_enqueue_script(
			'iskt-odbiorca',
			get_theme_file_uri( 'assets/js/odbiorca.js' ),
			array(),
			iskt_asset_version( 'assets/js/odbiorca.js' ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
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
	add_theme_support( 'align-wide' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'script', 'style', 'navigation-widgets' ) );

	/*
	 * Logo jako element panelu, a nie plik wpisany w szablon: właściciel podmienia
	 * je w Personalizacji bez dotykania kodu (§4.7). Do czasu wgrania własnego
	 * pliku motyw pokazuje znak ISKT z assets/img/.
	 */
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 160,
			'width'       => 193,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

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

	/*
	 * Edytor blokowy korzysta z tych samych arkuszy co strona — redaktor pisze
	 * w tej samej typografii, w jakiej treść się ukaże. Paleta, skala typografii
	 * i odstępy pochodzą z theme.json i wskazują na te same tokeny.
	 */
	add_theme_support( 'editor-styles' );
	add_editor_style( array_values( iskt_stylesheets() ) );

	/*
	 * Wyłączamy wzorce z katalogu WordPress.org. Powód nie jest kosmetyczny:
	 * pobiera je zewnętrzna usługa przy otwieraniu edytora, wyglądają obco na tle
	 * marki, a właściciel szukający „sekcji z cenami” trafiałby na kilkadziesiąt
	 * cudzych układów zamiast na siedem przygotowanych dla tego serwisu.
	 */
	remove_theme_support( 'core-block-patterns' );
}
add_action( 'after_setup_theme', 'iskt_theme_setup' );

/**
 * Rejestruje kategorię wzorców motywu i styl przycisku na ciemnym tle.
 *
 * Wzorce z katalogu `patterns/` WordPress znajduje sam; kategorię trzeba mu podać,
 * inaczej wszystkie wylądowałyby w koszu „Nieskategoryzowane”.
 */
function iskt_register_block_assets(): void {
	register_block_pattern_category(
		'iskt-sekcje',
		array(
			'label'       => __( 'Sekcje ISKT', 'iskt-szkolenia' ),
			'description' => __( 'Gotowe sekcje strony głównej w układzie z projektu.', 'iskt-szkolenia' ),
		)
	);

	/*
	 * Przycisk na tle marki. Rejestrujemy go jako styl bloku, a nie jako klasę
	 * do wpisania ręcznie — właściciel wybiera go z listy stylów i nie musi
	 * wiedzieć, jak nazywa się klasa CSS (§4.7).
	 */
	register_block_style(
		'core/button',
		array(
			'name'  => 'inverse',
			'label' => __( 'Na ciemnym tle', 'iskt-szkolenia' ),
		)
	);
}
add_action( 'init', 'iskt_register_block_assets', 9 );

/**
 * Rejestruje obszar widżetów stopki.
 *
 * Stopka ma według zlecenia zawierać edytowalną treść. Natywne widżety dają to
 * bez żadnej zależności: właściciel wstawia tekst, dane kontaktowe czy listę
 * odnośników z panelu. Docelowe teksty globalne dołoży zadanie 10.
 */
function iskt_register_sidebars(): void {
	register_sidebar(
		array(
			'name'          => __( 'Stopka — obszar treści', 'iskt-szkolenia' ),
			'id'            => 'iskt-footer',
			'description'   => __( 'Widżety wyświetlane w stopce, obok danych serwisu.', 'iskt-szkolenia' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'iskt_register_sidebars' );

/**
 * Zakończenie skróconego opisu.
 *
 * Domyślne „[…]” wygląda jak usterka; wielokropek czyta się naturalnie.
 */
function iskt_excerpt_more(): string {
	return '…';
}
add_filter( 'excerpt_more', 'iskt_excerpt_more' );
