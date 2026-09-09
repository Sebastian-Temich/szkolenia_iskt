<?php
/**
 * Title: Dofinansowania
 * Slug: iskt-szkolenia/dofinansowania
 * Categories: iskt-sekcje
 * Description: Panel o źródłach finansowania szkoleń na tle marki. Konkretne poziomy wsparcia i warunki wymagają potwierdzenia przez ISKT.
 * Keywords: dofinansowanie, KFS, BUR, fundusze
 * Viewport Width: 1400
 *
 * Wszystkie liczby dotyczące wysokości wsparcia są w zleceniu wymienione jako
 * treść do potwierdzenia (§9). Wzorzec zostawia w ich miejscu widoczny znacznik —
 * wpisanie tu „do 80%” oznaczałoby opublikowanie deklaracji, której nikt nie
 * zatwierdził, i której wykonawca nie ma jak sprawdzić.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Źródła finansowania.
 *
 * @var array<int, array{tag: string, tytul: string, opis: string}>
 */
$iskt_zrodla = array(
	array(
		'tag'   => 'KFS',
		'tytul' => __( 'Krajowy Fundusz Szkoleniowy', 'iskt-szkolenia' ),
		'opis'  => __( 'Wsparcie kształcenia pracowników ze środków Funduszu Pracy. [do potwierdzenia: poziom dofinansowania i warunki]', 'iskt-szkolenia' ),
	),
	array(
		'tag'   => 'BUR',
		'tytul' => __( 'Baza Usług Rozwojowych', 'iskt-szkolenia' ),
		'opis'  => __( 'Dofinansowanie usług rozwojowych z funduszy europejskich. [do potwierdzenia: poziom dofinansowania i warunki]', 'iskt-szkolenia' ),
	),
	array(
		'tag'   => 'EFS',
		'tytul' => __( 'Fundusze Europejskie', 'iskt-szkolenia' ),
		'opis'  => __( 'Projekty regionalne finansujące rozwój kompetencji pracowników. [do potwierdzenia: dostępne nabory]', 'iskt-szkolenia' ),
	),
);

?>
<!-- wp:group {"align":"full","className":"iskt-section","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull iskt-section" id="dofinansowania">

	<!-- wp:group {"className":"iskt-panel iskt-panel--brand","layout":{"type":"constrained"}} -->
	<div class="wp-block-group iskt-panel iskt-panel--brand">

		<!-- wp:paragraph {"className":"iskt-eyebrow"} -->
		<p class="iskt-eyebrow"><?php echo esc_html__( 'Dofinansowania', 'iskt-szkolenia' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"className":"iskt-title-lg iskt-balance"} -->
		<h2 class="wp-block-heading iskt-title-lg iskt-balance"><?php echo esc_html__( 'Sprawdź, czy Twoje szkolenie można sfinansować', 'iskt-szkolenia' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"iskt-lead"} -->
		<p class="iskt-lead"><?php echo esc_html__( 'Część naszych szkoleń kwalifikuje się do publicznych źródeł finansowania. Pomożemy sprawdzić dostępne nabory i przejść przez formalności — od wniosku po rozliczenie.', 'iskt-szkolenia' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:columns {"className":"iskt-funding"} -->
		<div class="wp-block-columns iskt-funding">
		<?php foreach ( $iskt_zrodla as $iskt_zrodlo ) : ?>
			<!-- wp:column -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"iskt-funding__item","layout":{"type":"constrained"}} -->
				<div class="wp-block-group iskt-funding__item">
					<!-- wp:paragraph {"className":"iskt-funding__tag iskt-mono"} -->
					<p class="iskt-funding__tag iskt-mono"><?php echo esc_html( $iskt_zrodlo['tag'] ); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:heading {"level":3,"className":"iskt-title-xs"} -->
					<h3 class="wp-block-heading iskt-title-xs"><?php echo esc_html( $iskt_zrodlo['tytul'] ); ?></h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph -->
					<p><?php echo esc_html( $iskt_zrodlo['opis'] ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->
		<?php endforeach; ?>
		</div>
		<!-- /wp:columns -->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-inverse"} -->
			<div class="wp-block-button is-style-inverse"><a class="wp-block-button__link wp-element-button" href="#kontakt"><?php echo esc_html__( 'Zapytaj o dofinansowanie', 'iskt-szkolenia' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->

	</div>
	<!-- /wp:group -->

</div>
<!-- /wp:group -->
