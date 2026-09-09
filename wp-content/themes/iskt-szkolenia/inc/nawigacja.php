<?php
/**
 * Nawigacja motywu: obsługa natywnych menu WordPressa.
 *
 * §11 pkt 10 zlecenia zabrania roboczych linków „#”. Dlatego motyw nie wypisuje
 * żadnej listy odnośników z palca — korzysta wyłącznie z menu ustawionych
 * w panelu, a gdy menu jeszcze nie ma, pokazuje prawdziwe strony serwisu.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Zawartość zastępcza menu głównego.
 *
 * Kolejność decyzji:
 *   1. są opublikowane strony → wypisujemy je (prawdziwe adresy),
 *   2. nie ma stron, a odwiedzający może zarządzać wyglądem → odnośnik do
 *      ekranu menu w panelu, żeby wiedział, gdzie to ustawić,
 *   3. w pozostałych przypadkach nie renderujemy nic. Pusty nagłówek jest
 *      lepszy niż nagłówek z martwymi odnośnikami.
 */
function iskt_primary_menu_fallback(): void {
	$pages = wp_list_pages(
		array(
			'title_li' => '',
			'depth'    => 2,
			'echo'     => false,
		)
	);

	if ( ! empty( $pages ) ) {
		printf( '<ul class="iskt-menu">%s</ul>', $pages ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_list_pages zwraca gotowy, zabezpieczony znacznik.

		return;
	}

	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	printf(
		'<ul class="iskt-menu"><li><a href="%1$s">%2$s</a></li></ul>',
		esc_url( admin_url( 'nav-menus.php' ) ),
		esc_html__( 'Ustaw menu główne', 'iskt-szkolenia' )
	);
}

/**
 * Dokłada aria-current do pozycji menu wskazującej bieżącą stronę.
 *
 * Czytnik ekranu ogłasza wtedy „bieżąca strona”, a nie tylko sam odnośnik.
 *
 * @param array<string, string> $atts Atrybuty odnośnika.
 * @param WP_Post               $item Pozycja menu.
 * @return array<string, string>
 */
function iskt_nav_menu_link_attributes( array $atts, $item ): array {
	if ( ! empty( $item->current ) ) {
		$atts['aria-current'] = 'page';
	}

	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'iskt_nav_menu_link_attributes', 10, 2 );

/**
 * Zwraca nazwę serwisu przygotowaną do wypisania albo pusty ciąg.
 *
 * Osobna funkcja, bo nazwa pojawia się w nagłówku i w stopce, a obie muszą
 * zachowywać się tak samo, gdy właściciel jej nie ustawił.
 */
function iskt_site_name(): string {
	return trim( (string) get_bloginfo( 'name', 'display' ) );
}

/**
 * Zwraca hasło serwisu albo pusty ciąg.
 *
 * Domyślne hasło świeżej instalacji („Just another WordPress site”) traktujemy
 * jak brak treści — §11 pkt 10 nie dopuszcza pozostałości instalacyjnych.
 */
function iskt_site_tagline(): string {
	$tagline = trim( (string) get_bloginfo( 'description', 'display' ) );

	if ( '' === $tagline || 'Just another WordPress site' === $tagline ) {
		return '';
	}

	return $tagline;
}

/**
 * Adres domyślnego logo motywu.
 *
 * Używany, dopóki właściciel nie wgra własnego logo w Personalizacji.
 */
function iskt_default_logo_url(): string {
	return get_theme_file_uri( 'assets/img/logo-iskt.png' );
}
