<?php
/**
 * Title: Przebieg współpracy w czterech krokach
 * Slug: iskt-szkolenia/przebieg
 * Categories: iskt-sekcje
 * Description: Cztery kroki współpracy, w dwóch wariantach: dla osoby indywidualnej i dla firmy. Wariant przełącza blok „Przełącznik odbiorcy”.
 * Keywords: proces, kroki, jak to działa, współpraca
 * Viewport Width: 1400
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Kroki obu wariantów.
 *
 * Trzymamy je w tablicy, bo oba warianty mają identyczną budowę — powtórzenie
 * tego samego znacznika osiem razy byłoby ośmioma miejscami do poprawienia przy
 * każdej zmianie układu.
 *
 * @var array<string, array{tytul: string, wstep: string, kroki: array<int, array{numer: string, tytul: string, opis: string}>}>
 */
$iskt_warianty = array(
	'indywidualny' => array(
		'tytul' => __( 'Od zapisu do certyfikatu w czterech krokach', 'iskt-szkolenia' ),
		'wstep' => __( 'Prosty proces, który przeprowadzi Cię od wyboru tematu po nowe kompetencje.', 'iskt-szkolenia' ),
		'kroki' => array(
			array(
				'numer' => '01',
				'tytul' => __( 'Wybierz szkolenie', 'iskt-szkolenia' ),
				'opis'  => __( 'Przeglądaj katalog i wybierz temat dopasowany do swoich celów.', 'iskt-szkolenia' ),
			),
			array(
				'numer' => '02',
				'tytul' => __( 'Wyślij zgłoszenie', 'iskt-szkolenia' ),
				'opis'  => __( 'Wypełnij formularz i wskaż termin, który Ci odpowiada. Potwierdzimy dostępność miejsc.', 'iskt-szkolenia' ),
			),
			array(
				'numer' => '03',
				'tytul' => __( 'Ucz się z ekspertem', 'iskt-szkolenia' ),
				'opis'  => __( 'Praktyczne zajęcia online lub stacjonarnie, wraz z materiałami.', 'iskt-szkolenia' ),
			),
			array(
				'numer' => '04',
				'tytul' => __( 'Odbierz certyfikat', 'iskt-szkolenia' ),
				'opis'  => __( 'Potwierdź nowe kompetencje certyfikatem ISKT.', 'iskt-szkolenia' ),
			),
		),
	),
	'firmowy'      => array(
		'tytul' => __( 'Szkolenie dla zespołu w czterech krokach', 'iskt-szkolenia' ),
		'wstep' => __( 'Prowadzimy Twoją firmę od analizy potrzeb po pomiar efektów.', 'iskt-szkolenia' ),
		'kroki' => array(
			array(
				'numer' => '01',
				'tytul' => __( 'Analiza potrzeb', 'iskt-szkolenia' ),
				'opis'  => __( 'Rozmowa o celach i audyt kompetencji zespołu.', 'iskt-szkolenia' ),
			),
			array(
				'numer' => '02',
				'tytul' => __( 'Program na miarę', 'iskt-szkolenia' ),
				'opis'  => __( 'Projektujemy zakres, format i harmonogram pod cele biznesowe.', 'iskt-szkolenia' ),
			),
			array(
				'numer' => '03',
				'tytul' => __( 'Realizacja', 'iskt-szkolenia' ),
				'opis'  => __( 'Szkolenie zamknięte u Ciebie albo online. [do potwierdzenia: rozliczenie KFS / BUR]', 'iskt-szkolenia' ),
			),
			array(
				'numer' => '04',
				'tytul' => __( 'Raport efektów', 'iskt-szkolenia' ),
				'opis'  => __( 'Podsumowujemy rezultaty i rekomendujemy dalsze ścieżki rozwoju.', 'iskt-szkolenia' ),
			),
		),
	),
);

?>
<!-- wp:group {"align":"full","className":"iskt-section iskt-section--subtle","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull iskt-section iskt-section--subtle" id="przebieg">

<?php foreach ( $iskt_warianty as $iskt_wariant => $iskt_dane ) : ?>

	<!-- wp:iskt/tresc-odbiorcy <?php echo wp_json_encode( array( 'odbiorca' => $iskt_wariant ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON atrybutów bloku. ?> -->

		<!-- wp:group {"className":"iskt-section__head iskt-section__head--center","layout":{"type":"constrained"}} -->
		<div class="wp-block-group iskt-section__head iskt-section__head--center">
			<!-- wp:paragraph {"className":"iskt-eyebrow"} -->
			<p class="iskt-eyebrow"><?php echo esc_html__( 'Jak to działa', 'iskt-szkolenia' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"className":"iskt-title-lg iskt-balance"} -->
			<h2 class="wp-block-heading iskt-title-lg iskt-balance"><?php echo esc_html( $iskt_dane['tytul'] ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"iskt-lead"} -->
			<p class="iskt-lead"><?php echo esc_html( $iskt_dane['wstep'] ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:columns {"className":"iskt-steps"} -->
		<div class="wp-block-columns iskt-steps">
		<?php foreach ( $iskt_dane['kroki'] as $iskt_krok ) : ?>
			<!-- wp:column -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"iskt-step","layout":{"type":"constrained"}} -->
				<div class="wp-block-group iskt-step">
					<!-- wp:paragraph {"className":"iskt-step__number iskt-mono"} -->
					<p class="iskt-step__number iskt-mono"><?php echo esc_html( $iskt_krok['numer'] ); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:heading {"level":3,"className":"iskt-title-xs"} -->
					<h3 class="wp-block-heading iskt-title-xs"><?php echo esc_html( $iskt_krok['tytul'] ); ?></h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph -->
					<p><?php echo esc_html( $iskt_krok['opis'] ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->
		<?php endforeach; ?>
		</div>
		<!-- /wp:columns -->

	<!-- /wp:iskt/tresc-odbiorcy -->

<?php endforeach; ?>

</div>
<!-- /wp:group -->
