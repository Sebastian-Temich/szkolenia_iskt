<?php
/**
 * Title: Wskaźniki (treść do potwierdzenia)
 * Slug: iskt-szkolenia/wskazniki
 * Categories: iskt-sekcje
 * Description: Ciemny pasek z czterema liczbami. Wszystkie wartości w tym wzorcu wymagają potwierdzenia przez ISKT — do tego czasu sekcji nie należy publikować.
 * Keywords: wskaźniki, liczby, statystyki
 * Viewport Width: 1400
 *
 * Cztery wskaźniki z prototypu („15+ lat”, „200+ szkoleń”, „4.9/5”, „do 80%
 * dofinansowania”) są w §9 wymienione wprost jako treść do potwierdzenia. Wzorzec
 * odtwarza układ, ale nie wpisuje żadnej z tych liczb — wykonawca nie ma podstaw,
 * żeby je twierdzić, a opublikowana średnia ocen bez źródła to problem, nie ozdoba.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Miejsca na wskaźniki.
 *
 * @var array<int, string>
 */
$iskt_wskazniki = array(
	__( 'lata doświadczenia', 'iskt-szkolenia' ),
	__( 'szkolenia w ofercie', 'iskt-szkolenia' ),
	__( 'średnia ocena', 'iskt-szkolenia' ),
	__( 'poziom dofinansowania', 'iskt-szkolenia' ),
);

?>
<!-- wp:group {"align":"full","className":"iskt-section iskt-section--flush-top","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull iskt-section iskt-section--flush-top">

	<!-- wp:group {"className":"iskt-panel iskt-panel--dark iskt-stats","layout":{"type":"constrained"}} -->
	<div class="wp-block-group iskt-panel iskt-panel--dark iskt-stats">

		<!-- wp:columns -->
		<div class="wp-block-columns">
		<?php foreach ( $iskt_wskazniki as $iskt_etykieta ) : ?>
			<!-- wp:column -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"iskt-stat","layout":{"type":"constrained"}} -->
				<div class="wp-block-group iskt-stat">
					<!-- wp:paragraph {"className":"iskt-stat__value"} -->
					<p class="iskt-stat__value"><?php echo esc_html__( '[uzupełnij]', 'iskt-szkolenia' ); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph {"className":"iskt-stat__label"} -->
					<p class="iskt-stat__label"><?php echo esc_html( $iskt_etykieta ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->
		<?php endforeach; ?>
		</div>
		<!-- /wp:columns -->

	</div>
	<!-- /wp:group -->

</div>
<!-- /wp:group -->
