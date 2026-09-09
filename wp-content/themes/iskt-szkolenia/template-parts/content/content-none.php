<?php
/**
 * Brak wyników.
 *
 * Komunikat mówi, co się stało i co można zrobić dalej — nie zostawia
 * odwiedzającego na pustej stronie.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

?>
<div class="iskt-stack iskt-stack--loose iskt-measure">
	<?php if ( is_search() ) : ?>

		<div class="iskt-notice">
			<p>
				<?php
				printf(
					/* translators: %s: szukana fraza. */
					esc_html__( 'Nie znaleźliśmy nic dla frazy „%s”. Spróbuj innego słowa albo krótszego zapytania.', 'iskt-szkolenia' ),
					esc_html( get_search_query() )
				);
				?>
			</p>
		</div>

		<?php get_search_form(); ?>

	<?php else : ?>

		<div class="iskt-notice">
			<p><?php esc_html_e( 'Nie ma tu jeszcze żadnych treści.', 'iskt-szkolenia' ); ?></p>
		</div>

		<?php if ( current_user_can( 'publish_posts' ) ) : ?>
			<p>
				<a class="iskt-button iskt-button--secondary" href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>">
					<?php esc_html_e( 'Dodaj pierwszy wpis', 'iskt-szkolenia' ); ?>
				</a>
			</p>
		<?php else : ?>
			<?php get_search_form(); ?>
		<?php endif; ?>

	<?php endif; ?>
</div>
