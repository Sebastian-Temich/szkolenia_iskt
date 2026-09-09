<?php
/**
 * Strona statyczna — np. „Kontakt” albo „Polityka prywatności”.
 *
 * Szkolenie, trener i artykuł aktualności mają własne szablony (zadania 5, 7
 * i 8), więc tutaj trafia wyłącznie treść, dla której układ redakcyjny w zupełności
 * wystarcza: tytuł, opcjonalne zdjęcie i treść z edytora blokowego.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

?>
<article <?php post_class( 'iskt-entry' ); ?>>
	<header class="iskt-page-header">
		<div class="iskt-container iskt-container--narrow">
			<div class="iskt-stack">
				<h1 class="iskt-title-lg iskt-balance"><?php the_title(); ?></h1>
				<?php iskt_entry_meta(); ?>
			</div>
		</div>
	</header>

	<div class="iskt-section iskt-section--tight">
		<div class="iskt-container iskt-container--narrow">
			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="iskt-entry__media">
					<?php the_post_thumbnail( 'large' ); ?>
				</figure>
			<?php endif; ?>

			<div class="iskt-prose">
				<?php
				the_content();

				wp_link_pages(
					array(
						'before' => '<nav class="iskt-entry__pages" aria-label="' . esc_attr__( 'Strony wpisu', 'iskt-szkolenia' ) . '">',
						'after'  => '</nav>',
					)
				);
				?>
			</div>
		</div>
	</div>
</article>
