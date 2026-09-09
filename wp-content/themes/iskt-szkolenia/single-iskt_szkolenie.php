<?php
/**
 * Strona pojedynczego szkolenia.
 *
 * Realizuje §4.3: własny trwały adres i wspólny szablon wizualny dla każdego
 * szkolenia. Wszystkie pola pochodzą z panelu — szablon nie zawiera treści
 * ofertowej ani jednego zdania od wykonawcy.
 *
 * Każda sekcja pojawia się tylko wtedy, gdy ma treść. Szkolenie bez wpisanego
 * programu nie pokazuje pustego nagłówka „Program szkolenia”.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

get_header();

/*
 * Części szablonu czytają dane przez `get_the_ID()`. Zmienne lokalne stąd nie są
 * dla nich widoczne — `get_template_part()` wczytuje plik we własnym zakresie.
 */
while ( have_posts() ) :
	the_post();
	?>

	<main id="iskt-main" class="iskt-main" tabindex="-1">

		<article class="iskt-szkolenie">

			<?php get_template_part( 'template-parts/szkolenie/naglowek' ); ?>

			<div class="iskt-section">
				<div class="iskt-container">
					<div class="iskt-grid iskt-grid--sidebar">
						<div class="iskt-szkolenie__tresc iskt-stack iskt-stack--loose">
							<?php get_template_part( 'template-parts/szkolenie/tresc' ); ?>
						</div>

						<aside class="iskt-szkolenie__panel iskt-stack" aria-label="<?php esc_attr_e( 'Informacje o szkoleniu', 'iskt-szkolenia' ); ?>">
							<?php get_template_part( 'template-parts/szkolenie/panel' ); ?>
						</aside>
					</div>
				</div>
			</div>

		</article>

	</main>

	<?php
endwhile;

get_footer();
