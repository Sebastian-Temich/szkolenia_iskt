<?php
/**
 * Ukrywanie sekcji bez treści.
 *
 * Bloki dynamiczne wtyczki zwracają pusty ciąg, gdy nie mają czego pokazać — ale
 * oprawa sekcji (tło, marginesy, zakotwiczenie) siedzi w bloku grupy zapisanym we
 * wzorcu, a ten renderuje się zawsze. Skutek widoczny na świeżej instalacji: pod
 * sekcją powitalną zostają szerokie, kolorowe pasy bez ani jednego słowa w środku.
 *
 * §4.1 wymaga ukrywania sekcji bez treści, więc pustą oprawę usuwamy tutaj, przy
 * renderowaniu grupy. Filtr działa dla każdej sekcji z klasą `iskt-section`, także
 * dla tych, które właściciel złoży sam — nie tylko dla siedmiu wzorców z motywu.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Rozstrzyga, czy wyrenderowana sekcja ma cokolwiek do pokazania.
 *
 * Brak tekstu to nie to samo, co brak treści: sekcja złożona z samego zdjęcia,
 * filmu albo mapy jest pełnoprawną sekcją i musi zostać. Dlatego zanim policzymy
 * znaki, sprawdzamy obecność elementów, które niosą treść same z siebie.
 *
 * @param string $html Wyrenderowane znaczniki sekcji.
 */
function iskt_sekcja_ma_tresc( string $html ): bool {
	if ( '' === trim( $html ) ) {
		return false;
	}

	if ( preg_match( '/<(?:img|picture|video|audio|iframe|svg|canvas|object|embed|input|button|select|textarea|hr)[\s\/>]/i', $html ) ) {
		return true;
	}

	/*
	 * Twarda spacja jest znakiem drukowalnym, więc `trim()` jej nie usunie —
	 * a akapit złożony z samej `&nbsp;` to pusty akapit, nie treść.
	 */
	$tekst = str_replace( array( "\xc2\xa0", '&nbsp;' ), ' ', wp_strip_all_tags( $html ) );

	return '' !== trim( $tekst );
}

/**
 * Usuwa oprawę sekcji, w której nic nie zostało do pokazania.
 *
 * @param string               $content Wyrenderowany blok.
 * @param array<string, mixed> $block   Rozłożony blok.
 */
function iskt_ukryj_puste_sekcje( string $content, array $block ): string {
	if ( 'core/group' !== ( $block['blockName'] ?? '' ) ) {
		return $content;
	}

	$klasy = (string) ( $block['attrs']['className'] ?? '' );

	if ( ! str_contains( $klasy, 'iskt-section' ) ) {
		return $content;
	}

	/*
	 * W panelu i w podglądzie edytora zostawiamy sekcję nietkniętą. Redaktor, który
	 * właśnie wstawił pustą sekcję, musi ją widzieć, żeby móc ją wypełnić albo usunąć;
	 * zniknięcie bloku pod kursorem wyglądałoby jak awaria edytora.
	 */
	if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return $content;
	}

	return iskt_sekcja_ma_tresc( $content ) ? $content : '';
}
add_filter( 'render_block', 'iskt_ukryj_puste_sekcje', 10, 2 );
