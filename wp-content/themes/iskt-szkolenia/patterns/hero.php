<?php
/**
 * Title: Sekcja powitalna z przełącznikiem odbiorcy
 * Slug: iskt-szkolenia/hero
 * Categories: iskt-sekcje
 * Description: Nagłówek strony głównej z przełącznikiem „Dla Ciebie” / „Dla firm”, dwoma wariantami tekstu i dwiema ścieżkami wyboru.
 * Keywords: hero, nagłówek, odbiorca, strona główna
 * Viewport Width: 1400
 *
 * Teksty pochodzą z załączonego prototypu. Deklaracje wymagające potwierdzenia
 * przez ISKT (§9) — poziom dofinansowania, obietnice czasu odpowiedzi, wskaźniki —
 * zostały zastąpione widocznym znacznikiem „[do potwierdzenia: …]”. Tekst, którego
 * nikt nie zatwierdził, nie może wyglądać jak gotowy do publikacji.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

?>
<!-- wp:group {"align":"full","className":"iskt-section iskt-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull iskt-section iskt-hero">

	<!-- wp:iskt/przelacznik-odbiorcy {"align":"center"} /-->

	<!-- wp:iskt/tresc-odbiorcy {"odbiorca":"indywidualny"} -->
		<!-- wp:paragraph {"className":"iskt-eyebrow"} -->
		<p class="iskt-eyebrow"><?php echo esc_html__( 'Szkolenia ISKT', 'iskt-szkolenia' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"level":1,"className":"iskt-title-xl iskt-balance"} -->
		<h1 class="wp-block-heading iskt-title-xl iskt-balance"><?php echo esc_html__( 'Rozwijaj kompetencje przyszłości.', 'iskt-szkolenia' ); ?></h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"iskt-lead"} -->
		<p class="iskt-lead"><?php echo esc_html__( 'Praktyczne szkolenia z AI, ESG, oprogramowania i języka angielskiego — prowadzone przez ekspertów. Uczysz się w swoim tempie, online lub stacjonarnie.', 'iskt-szkolenia' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"className":"iskt-hero__actions"} -->
		<div class="wp-block-buttons iskt-hero__actions">
			<!-- wp:button -->
			<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#kontakt"><?php echo esc_html__( 'Zapisz się na szkolenie', 'iskt-szkolenia' ); ?></a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-outline"} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( (string) get_post_type_archive_link( 'iskt_szkolenie' ) ); ?>"><?php echo esc_html__( 'Przeglądaj katalog', 'iskt-szkolenia' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	<!-- /wp:iskt/tresc-odbiorcy -->

	<!-- wp:iskt/tresc-odbiorcy {"odbiorca":"firmowy"} -->
		<!-- wp:paragraph {"className":"iskt-eyebrow"} -->
		<p class="iskt-eyebrow"><?php echo esc_html__( 'Szkolenia dla firm', 'iskt-szkolenia' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"level":1,"className":"iskt-title-xl iskt-balance"} -->
		<h1 class="wp-block-heading iskt-title-xl iskt-balance"><?php echo esc_html__( 'Podnieś kompetencje całego zespołu.', 'iskt-szkolenia' ); ?></h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"iskt-lead"} -->
		<p class="iskt-lead"><?php echo esc_html__( 'Szkolenia szyte na miarę Twojej organizacji — AI, ESG, oprogramowanie i angielski biznesowy. Stacjonarnie u Ciebie albo online.', 'iskt-szkolenia' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"className":"iskt-hero__actions"} -->
		<div class="wp-block-buttons iskt-hero__actions">
			<!-- wp:button -->
			<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#kontakt"><?php echo esc_html__( 'Zapytaj o szkolenie dla firmy', 'iskt-szkolenia' ); ?></a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-outline"} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( (string) get_post_type_archive_link( 'iskt_szkolenie' ) ); ?>"><?php echo esc_html__( 'Przeglądaj katalog', 'iskt-szkolenia' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	<!-- /wp:iskt/tresc-odbiorcy -->

	<!-- wp:columns {"className":"iskt-hero__paths"} -->
	<div class="wp-block-columns iskt-hero__paths">
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"iskt-card iskt-card--padded","layout":{"type":"constrained"}} -->
			<div class="wp-block-group iskt-card iskt-card--padded">
				<!-- wp:heading {"level":2,"className":"iskt-card__title"} -->
				<h2 class="wp-block-heading iskt-card__title"><?php echo esc_html__( 'Dla osób indywidualnych', 'iskt-szkolenia' ); ?></h2>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p><?php echo esc_html__( 'Zdobądź certyfikat i realne umiejętności. Elastyczne terminy, zajęcia online, materiały do pracy własnej.', 'iskt-szkolenia' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:list {"className":"iskt-list--check"} -->
				<ul class="wp-block-list iskt-list--check">
					<!-- wp:list-item --><li><?php echo esc_html__( 'Certyfikat ukończenia', 'iskt-szkolenia' ); ?></li><!-- /wp:list-item -->
					<!-- wp:list-item --><li><?php echo esc_html__( 'Materiały i nagrania', 'iskt-szkolenia' ); ?></li><!-- /wp:list-item -->
					<!-- wp:list-item --><li><?php echo esc_html__( '[do potwierdzenia: dofinansowanie i warunki płatności]', 'iskt-szkolenia' ); ?></li><!-- /wp:list-item -->
				</ul>
				<!-- /wp:list -->

				<!-- wp:buttons -->
				<div class="wp-block-buttons">
					<!-- wp:button {"className":"is-style-outline"} -->
					<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( (string) get_post_type_archive_link( 'iskt_szkolenie' ) ); ?>"><?php echo esc_html__( 'Szkolenia otwarte', 'iskt-szkolenia' ); ?></a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"iskt-card iskt-card--padded","layout":{"type":"constrained"}} -->
			<div class="wp-block-group iskt-card iskt-card--padded">
				<!-- wp:heading {"level":2,"className":"iskt-card__title"} -->
				<h2 class="wp-block-heading iskt-card__title"><?php echo esc_html__( 'Dla firm i zespołów', 'iskt-szkolenia' ); ?></h2>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p><?php echo esc_html__( 'Program dopasowany do celów biznesowych. Szkolenia zamknięte, audyt kompetencji, raport z efektów.', 'iskt-szkolenia' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:list {"className":"iskt-list--check"} -->
				<ul class="wp-block-list iskt-list--check">
					<!-- wp:list-item --><li><?php echo esc_html__( 'Program na zamówienie', 'iskt-szkolenia' ); ?></li><!-- /wp:list-item -->
					<!-- wp:list-item --><li><?php echo esc_html__( 'Raport efektów', 'iskt-szkolenia' ); ?></li><!-- /wp:list-item -->
					<!-- wp:list-item --><li><?php echo esc_html__( '[do potwierdzenia: rozliczenie KFS / BUR]', 'iskt-szkolenia' ); ?></li><!-- /wp:list-item -->
				</ul>
				<!-- /wp:list -->

				<!-- wp:buttons -->
				<div class="wp-block-buttons">
					<!-- wp:button {"className":"is-style-outline"} -->
					<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#kontakt"><?php echo esc_html__( 'Szkolenie zamknięte', 'iskt-szkolenia' ); ?></a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->
