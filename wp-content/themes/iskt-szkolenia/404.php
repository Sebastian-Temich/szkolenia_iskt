<?php
/**
 * Strona nieznalezionego adresu.
 *
 * Wszystkie odnośniki prowadzą do istniejących adresów — brak roboczych „#”
 * (§11 pkt 10). Odnośniki do dalszych stron bierzemy z menu głównego, więc
 * pozostają zgodne z tym, co właściciel ustawił w panelu.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="iskt-main" class="iskt-main" tabindex="-1">
	<div class="iskt-page-header">
		<div class="iskt-container">
			<div class="iskt-page-header__inner iskt-stack iskt-stack--loose">
				<p class="iskt-error-404__code" aria-hidden="true">404</p>
				<h1 class="iskt-title-lg iskt-balance"><?php esc_html_e( 'Nie znaleźliśmy tej strony', 'iskt-szkolenia' ); ?></h1>
				<p class="iskt-lead">
					<?php esc_html_e( 'Adres mógł się zmienić albo zawiera literówkę. Poniżej znajdziesz wyszukiwarkę i drogę powrotną.', 'iskt-szkolenia' ); ?>
				</p>
			</div>
		</div>
	</div>

	<section class="iskt-section">
		<div class="iskt-container">
			<div class="iskt-stack iskt-stack--loose iskt-measure">
				<?php get_search_form(); ?>

				<p>
					<a class="iskt-button iskt-button--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>">
						<?php esc_html_e( 'Wróć na stronę główną', 'iskt-szkolenia' ); ?>
						<?php iskt_the_icon( 'arrow-right', 'iskt-button__icon iskt-button__icon--forward' ); ?>
					</a>
				</p>

				<?php if ( has_nav_menu( 'primary' ) ) : ?>
					<nav class="iskt-error-404__links" aria-label="<?php esc_attr_e( 'Sekcje serwisu', 'iskt-szkolenia' ); ?>">
						<h2 class="iskt-title-xs"><?php esc_html_e( 'Sekcje serwisu', 'iskt-szkolenia' ); ?></h2>
						<?php
						wp_nav_menu(
							array(
								'theme_location' => 'primary',
								'container'      => false,
								'menu_class'     => 'iskt-error-404__menu',
								'depth'          => 1,
								'fallback_cb'    => '__return_empty_string',
							)
						);
						?>
					</nav>
				<?php endif; ?>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
