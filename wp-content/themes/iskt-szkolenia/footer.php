<?php
/**
 * Stopka serwisu.
 *
 * Treść stopki jest edytowalna z panelu: menu „Menu w stopce” oraz obszar
 * widżetów „Stopka — obszar treści”. Motyw nie wpisuje tu żadnych odnośników
 * na stałe, więc nie zostają robocze linki „#” (§11 pkt 10).
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

$iskt_name       = iskt_site_name();
$iskt_tagline    = iskt_site_tagline();
$iskt_logo_id    = (int) get_theme_mod( 'custom_logo' );
$iskt_has_widget = is_active_sidebar( 'iskt-footer' );
$iskt_host       = (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST );
?>
</div><!-- .iskt-site-content -->

<footer class="iskt-site-footer">
	<div class="iskt-container">
		<div class="iskt-site-footer__top<?php echo $iskt_has_widget ? '' : ' iskt-site-footer__top--single'; ?>">
			<div class="iskt-site-footer__identity">
				<a class="iskt-site-footer__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
					<?php
					if ( $iskt_logo_id > 0 ) {
						echo wp_get_attachment_image( $iskt_logo_id, 'medium', false, array( 'alt' => '' ) );
					} else {
						printf(
							'<img src="%s" width="193" height="160" alt="" loading="lazy" decoding="async">',
							esc_url( iskt_default_logo_url() )
						);
					}

					if ( '' !== $iskt_name ) {
						echo '<span>' . esc_html( $iskt_name ) . '</span>';
					}
					?>
				</a>

				<?php if ( '' !== $iskt_tagline ) : ?>
					<p class="iskt-site-footer__description"><?php echo esc_html( $iskt_tagline ); ?></p>
				<?php endif; ?>
			</div>

			<?php if ( $iskt_has_widget ) : ?>
				<div class="iskt-site-footer__widgets">
					<?php dynamic_sidebar( 'iskt-footer' ); ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="iskt-site-footer__bottom">
			<p class="iskt-site-footer__copyright">
				<?php
				printf(
					/* translators: 1: rok, 2: nazwa serwisu. */
					esc_html__( '© %1$s %2$s', 'iskt-szkolenia' ),
					esc_html( date_i18n( 'Y' ) ),
					esc_html( '' !== $iskt_name ? $iskt_name : $iskt_host )
				);
				?>
			</p>

			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'container'      => 'nav',
					'container_class' => 'iskt-site-footer__nav',
					'container_aria_label' => esc_attr__( 'Nawigacja w stopce', 'iskt-szkolenia' ),
					'menu_class'     => 'iskt-site-footer__menu',
					'depth'          => 1,
					'fallback_cb'    => '__return_empty_string',
				)
			);
			?>

			<?php if ( '' !== $iskt_host ) : ?>
				<p class="iskt-site-footer__domain"><?php echo esc_html( $iskt_host ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
