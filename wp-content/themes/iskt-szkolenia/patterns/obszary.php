<?php
/**
 * Title: Obszary szkoleń
 * Slug: iskt-szkolenia/obszary
 * Categories: iskt-sekcje
 * Description: Siatka kategorii katalogu na jasnozielonym tle. Lista kategorii pochodzi z panelu — sekcja nie ma zapisanej treści do ręcznej aktualizacji.
 * Keywords: kategorie, obszary, szkolenia
 * Viewport Width: 1400
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/*
 * Atrybuty bloku to JSON w komentarzu, a nie atrybut HTML — `esc_attr()` zamieniłby
 * apostrofy i cudzysłowy na encje i uszkodził zapis bloku. Właściwym narzędziem
 * jest tu `wp_json_encode()`.
 */
$iskt_obszary = wp_json_encode(
	array(
		'nadtytul' => __( 'Obszary szkoleń', 'iskt-szkolenia' ),
		'tytul'    => __( 'Kompetencje, które budują przewagę', 'iskt-szkolenia' ),
		'wstep'    => __( 'Obszary szkoleniowe dla osób indywidualnych i zespołów — od sztucznej inteligencji po język angielski.', 'iskt-szkolenia' ),
	),
	JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

?>
<!-- wp:group {"align":"full","className":"iskt-section iskt-section--muted","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull iskt-section iskt-section--muted" id="obszary">

	<!-- wp:iskt/obszary-szkolen <?php echo $iskt_obszary; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON z wp_json_encode(), nie kontekst HTML. ?> /-->

</div>
<!-- /wp:group -->
