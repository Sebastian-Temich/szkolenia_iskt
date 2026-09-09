<?php
/**
 * Strona główna.
 *
 * Cała treść pochodzi z bloków strony ustawionej jako strona główna — szablon nie
 * zawiera ani jednego tekstu redakcyjnego. To warunek §4.7: właściciel dodaje,
 * przestawia i usuwa sekcje w edytorze, bez dotykania PHP.
 *
 * Gotowe sekcje z załącznika czekają we wzorcach bloków (katalog `patterns/`).
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/*
 * Gdy właściciel nie ustawił strony statycznej, WordPress i tak wybiera ten
 * szablon dla strony głównej — a wtedy ma pokazać listę wpisów, nie pustą stronę.
 */
if ( 'page' !== get_option( 'show_on_front' ) ) {
	locate_template( 'index.php', true );

	return;
}

get_header();
?>

<main id="iskt-main" class="iskt-main iskt-main--strona-glowna" tabindex="-1">

	<?php
	while ( have_posts() ) :
		the_post();

		the_content();
	endwhile;
	?>

	<?php if ( current_user_can( 'edit_pages' ) && '' === trim( (string) get_post_field( 'post_content', (int) get_option( 'page_on_front' ) ) ) ) : ?>
		<div class="iskt-container iskt-section">
			<div class="iskt-notice">
				<p>
					<?php esc_html_e( 'Strona główna nie ma jeszcze treści. Otwórz ją w edytorze i wstaw wzorzec „Strona główna ISKT” albo pojedyncze sekcje z listy wzorców.', 'iskt-szkolenia' ); ?>
				</p>
				<p>
					<a class="iskt-button iskt-button--primary" href="<?php echo esc_url( (string) get_edit_post_link( (int) get_option( 'page_on_front' ) ) ); ?>">
						<?php esc_html_e( 'Edytuj stronę główną', 'iskt-szkolenia' ); ?>
					</a>
				</p>
			</div>
		</div>
	<?php endif; ?>

</main>

<?php
get_footer();
