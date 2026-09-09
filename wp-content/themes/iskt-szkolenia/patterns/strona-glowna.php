<?php
/**
 * Title: Strona główna ISKT — komplet sekcji
 * Slug: iskt-szkolenia/strona-glowna
 * Categories: iskt-sekcje, featured
 * Block Types: core/post-content
 * Description: Cała strona główna w układzie z załącznika: sekcja powitalna, obszary, wyróżnione szkolenia, przebieg współpracy, dofinansowania, wskaźniki i kontakt.
 * Keywords: strona główna, landing, komplet
 * Viewport Width: 1400
 *
 * Wzorzec składa się z tych samych plików, co pojedyncze sekcje — nie z ich kopii.
 * Gdyby układ powielić, poprawka w sekcji trafiłaby tylko w jedno z dwóch miejsc,
 * a właściciel dostałby dwie różne wersje tej samej sekcji.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

foreach ( iskt_sekcje_strony_glownej() as $iskt_sekcja ) {
	echo iskt_wzorzec_sekcji( $iskt_sekcja ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Znaczniki bloków z plików motywu; treść w nich jest już zabezpieczona.
}
