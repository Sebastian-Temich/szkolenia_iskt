<?php
/**
 * Wpis na liście — karta.
 *
 * Cała karta jest klikalna, ale prawdziwym odnośnikiem pozostaje wyłącznie
 * tytuł: obszar kliknięcia rozciąga CSS (.iskt-card__link::after). Klawiatura
 * i czytnik ekranu dostają więc jeden cel, a nie trzy prowadzące w to samo
 * miejsce. Napis „Czytaj dalej” jest z tego powodu tekstem, nie odnośnikiem.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

?>
<article <?php post_class( 'iskt-card iskt-card--interactive' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<div class="iskt-card__media">
			<?php the_post_thumbnail( 'medium_large', array( 'alt' => '' ) ); ?>
		</div>
	<?php endif; ?>

	<?php iskt_entry_meta(); ?>

	<h2 class="iskt-card__title">
		<a class="iskt-card__link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
	</h2>

	<?php if ( has_excerpt() || get_the_content() ) : ?>
		<p class="iskt-card__body"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24, '…' ) ); ?></p>
	<?php endif; ?>

	<div class="iskt-card__footer">
		<span class="iskt-card__more" aria-hidden="true">
			<?php
			echo esc_html( iskt_tekst_motywu( 'aktualnosci_czytaj', __( 'Czytaj dalej', 'iskt-szkolenia' ) ) );
			iskt_the_icon( 'arrow-right' );
			?>
		</span>
	</div>
</article>
