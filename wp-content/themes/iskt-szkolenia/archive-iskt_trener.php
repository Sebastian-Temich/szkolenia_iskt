<?php
/**
 * Lista trenerów pod `/trenerzy/`.
 *
 * Realizuje część §4.5: zespół trenerski jako osobny widok, z którego wchodzi się
 * na profil. Wszystkie napisy pochodzą z rejestru tekstów (Szkolenia → Teksty
 * serwisu), a treść kafla — z pól trenera. Szablon nie zawiera ani jednego zdania
 * od wykonawcy.
 *
 * Kolejność listy ustawia wtyczka (`includes/model.php`): najpierw atrybut
 * „Kolejność” z panelu, potem alfabetycznie.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

get_header();

$iskt_wstep = iskt_tekst( 'trenerzy_wstep' );
$iskt_brak  = iskt_tekst( 'trenerzy_brak' );
?>

<main id="iskt-main" class="iskt-main" tabindex="-1">

	<div class="iskt-page-header">
		<div class="iskt-container">
			<div class="iskt-page-header__inner iskt-stack">
				<h1 class="iskt-title-lg iskt-balance"><?php echo esc_html( iskt_tekst( 'trenerzy_tytul' ) ); ?></h1>

				<?php if ( '' !== $iskt_wstep ) : ?>
					<p class="iskt-lead"><?php echo esc_html( $iskt_wstep ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<section class="iskt-section">
		<div class="iskt-container">

			<?php if ( have_posts() ) : ?>

				<div class="iskt-grid iskt-grid--auto iskt-trenerzy-lista">
					<?php
					while ( have_posts() ) :
						the_post();

						/*
						 * Zajawka, a nie ręcznie skracana biografia: gdy redaktor nie wpisał
						 * krótkiego opisu, WordPress złoży go z początku biografii, więc kafel
						 * nigdy nie jest pustą ramką z samym nazwiskiem. Na profilu obowiązuje
						 * odwrotna zasada — tam pokazujemy wyłącznie opis wpisany ręcznie,
						 * żeby nie powtarzać pierwszego zdania biografii tuż nad nią.
						 */
						$iskt_opis = trim( (string) get_the_excerpt() );
						?>

						<article class="iskt-card iskt-card--interactive iskt-card--padded iskt-trener-karta">
							<div class="iskt-trener">
								<?php
								get_template_part(
									'template-parts/trener/kafel',
									null,
									array(
										'trener'          => get_post(),
										'klasa_odnosnika' => 'iskt-card__link',
									)
								);
								?>
							</div>

							<?php if ( '' !== $iskt_opis ) : ?>
								<p class="iskt-trener-karta__opis"><?php echo esc_html( $iskt_opis ); ?></p>
							<?php endif; ?>
						</article>

					<?php endwhile; ?>
				</div>

				<?php iskt_pagination(); ?>

			<?php elseif ( '' !== $iskt_brak ) : ?>

				<?php
				/*
				 * Pusta lista to normalny stan świeżej instalacji, a nie usterka. Mówimy
				 * wprost, że lista jest w przygotowaniu — komunikat jest edytowalny.
				 */
				?>
				<p class="iskt-lead iskt-muted"><?php echo esc_html( $iskt_brak ); ?></p>

			<?php endif; ?>

		</div>
	</section>

</main>

<?php
get_footer();
