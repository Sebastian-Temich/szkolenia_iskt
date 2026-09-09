<?php
/**
 * Title: Wyróżnione szkolenia
 * Slug: iskt-szkolenia/wyroznione
 * Categories: iskt-sekcje
 * Description: Karty szkoleń oznaczonych jako wyróżnione, z odnośnikiem do pełnego katalogu.
 * Keywords: szkolenia, wyróżnione, polecane, katalog
 * Viewport Width: 1400
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

$iskt_wyroznione = wp_json_encode(
	array(
		'nadtytul'         => __( 'Wyróżnione szkolenia', 'iskt-szkolenia' ),
		'tytul'            => __( 'Najczęściej wybierane', 'iskt-szkolenia' ),
		'liczba'           => 4,
		'etykietaKatalogu' => __( 'Cały katalog', 'iskt-szkolenia' ),
	),
	JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

?>
<!-- wp:group {"align":"full","className":"iskt-section","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull iskt-section">

	<!-- wp:iskt/wyroznione-szkolenia <?php echo $iskt_wyroznione; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON z wp_json_encode(), nie kontekst HTML. ?> /-->

</div>
<!-- /wp:group -->
