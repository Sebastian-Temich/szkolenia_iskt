<?php
/**
 * Title: Kontakt i zgłoszenie
 * Slug: iskt-szkolenia/kontakt
 * Categories: iskt-sekcje
 * Description: Sekcja kontaktowa z dwoma wariantami tekstu, danymi kontaktowymi i formularzem zgłoszeniowym.
 * Keywords: kontakt, zgłoszenie, formularz
 * Viewport Width: 1400
 *
 * Adres [szkolenia@iskt.pl](mailto:szkolenia@iskt.pl) pochodzi wprost ze zlecenia (§5) i jest pewny. Telefon
 * i adres siedziby z prototypu wymagają potwierdzenia (§9), więc wzorzec zostawia
 * w ich miejscu widoczny znacznik zamiast numeru, który wygląda na prawdziwy.
 *
 * Formularz jest blokiem wtyczki, a nie znacznikami zapisanymi w tym wzorcu.
 * Różnica jest praktyczna: etykiety, komunikaty i adres odbiorcy zmieniają się
 * z panelu i działają także na stronach założonych przed edycją wzorca (§4.7).
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Nagłówek i wstęp w obu wariantach odbiorcy.
 *
 * @var array<string, array{tytul: string, wstep: string}>
 */
$iskt_warianty = array(
	'indywidualny' => array(
		'tytul' => __( 'Zapisz się na szkolenie', 'iskt-szkolenia' ),
		'wstep' => __( 'Zostaw kontakt — dobierzemy szkolenie i sprawdzimy dostępne terminy.', 'iskt-szkolenia' ),
	),
	'firmowy'      => array(
		'tytul' => __( 'Zapytaj o szkolenie dla firmy', 'iskt-szkolenia' ),
		'wstep' => __( 'Opowiedz nam o zespole — przygotujemy program i wycenę.', 'iskt-szkolenia' ),
	),
);

?>
<!-- wp:group {"align":"full","className":"iskt-section iskt-section--muted","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull iskt-section iskt-section--muted" id="kontakt">

<?php foreach ( $iskt_warianty as $iskt_wariant => $iskt_dane ) : ?>

	<!-- wp:iskt/tresc-odbiorcy <?php echo wp_json_encode( array( 'odbiorca' => $iskt_wariant ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON atrybutów bloku. ?> -->
		<!-- wp:group {"className":"iskt-section__head","layout":{"type":"constrained"}} -->
		<div class="wp-block-group iskt-section__head">
			<!-- wp:paragraph {"className":"iskt-eyebrow"} -->
			<p class="iskt-eyebrow"><?php echo esc_html__( 'Zgłoszenie', 'iskt-szkolenia' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"className":"iskt-title-lg iskt-balance"} -->
			<h2 class="wp-block-heading iskt-title-lg iskt-balance"><?php echo esc_html( $iskt_dane['tytul'] ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"iskt-lead"} -->
			<p class="iskt-lead"><?php echo esc_html( $iskt_dane['wstep'] ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	<!-- /wp:iskt/tresc-odbiorcy -->

<?php endforeach; ?>

	<!-- wp:columns {"className":"iskt-contact"} -->
	<div class="wp-block-columns iskt-contact">

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:list {"className":"iskt-contact__list"} -->
			<ul class="wp-block-list iskt-contact__list">
				<!-- wp:list-item -->
				<li><a href="mailto:szkolenia@iskt.pl">szkolenia@iskt.pl</a></li>
				<!-- /wp:list-item -->

				<!-- wp:list-item -->
				<li><?php echo esc_html__( '[do potwierdzenia: numer telefonu]', 'iskt-szkolenia' ); ?></li>
				<!-- /wp:list-item -->

				<!-- wp:list-item -->
				<li><?php echo esc_html__( '[do potwierdzenia: adres i pełna nazwa firmy]', 'iskt-szkolenia' ); ?></li>
				<!-- /wp:list-item -->
			</ul>
			<!-- /wp:list -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"iskt-card iskt-card--padded","layout":{"type":"constrained"}} -->
			<div class="wp-block-group iskt-card iskt-card--padded">
				<?php
				/*
				 * Nagłówek formularza jest wyłączony: sekcja ma już własny tytuł i wstęp
				 * w obu wariantach odbiorcy powyżej, a drugi nagłówek pod nim czytałby się
				 * jak początek kolejnej sekcji.
				 */
				?>
				<!-- wp:iskt/formularz-zgloszeniowy {"pokazNaglowek":false} /-->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->
