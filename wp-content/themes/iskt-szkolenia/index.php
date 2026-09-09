<?php
/**
 * Szablon podstawowy.
 *
 * Po zadaniach 5, 7 i 8 własne pliki mają: szkolenie, trener, lista aktualności,
 * archiwum kategorii i artykuł. `index.php` zostaje szablonem awaryjnym dla
 * widoków, których nie obsługuje żaden inny plik — wyników wyszukiwania, archiwów
 * dat i autorów oraz stron. Dzięki temu każdy adres serwisu ma wygląd motywu,
 * także taki, o którym nikt nie pomyślał osobno.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

get_header();

$iskt_heading = iskt_archive_heading();
?>

<main id="iskt-main" class="iskt-main" tabindex="-1">

	<?php if ( is_singular() ) : ?>

		<?php
		while ( have_posts() ) :
			the_post();

			get_template_part( 'template-parts/content/content', 'single' );
		endwhile;
		?>

	<?php else : ?>

		<?php if ( '' !== $iskt_heading['title'] ) : ?>
			<div class="iskt-page-header">
				<div class="iskt-container">
					<div class="iskt-page-header__inner iskt-stack">
						<h1 class="iskt-title-lg iskt-balance"><?php echo esc_html( $iskt_heading['title'] ); ?></h1>

						<?php if ( '' !== $iskt_heading['description'] ) : ?>
							<p class="iskt-lead"><?php echo esc_html( $iskt_heading['description'] ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<section class="iskt-section">
			<div class="iskt-container">
				<?php if ( have_posts() ) : ?>

					<div class="iskt-grid iskt-grid--3">
						<?php
						while ( have_posts() ) :
							the_post();

							get_template_part( 'template-parts/content/content', get_post_type() );
						endwhile;
						?>
					</div>

					<?php iskt_pagination(); ?>

				<?php else : ?>

					<?php get_template_part( 'template-parts/content/content', 'none' ); ?>

				<?php endif; ?>
			</div>
		</section>

	<?php endif; ?>

</main>

<?php
get_footer();
