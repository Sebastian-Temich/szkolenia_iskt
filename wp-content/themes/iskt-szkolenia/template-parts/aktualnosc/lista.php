<?php
/**
 * Wspólne ciało listy aktualności: nagłówek, filtr kategorii, siatka, paginacja.
 *
 * Lista wpisów (`home.php`) i archiwum kategorii (`category.php`) różnią się
 * wyłącznie tytułem i wstępem — treść pod spodem jest ta sama. Trzymamy ją
 * w jednym pliku, żeby poprawka siatki albo komunikatu pustej listy nie musiała
 * być wpisana dwa razy i żeby archiwum kategorii nie zdryfowało wyglądem od listy
 * głównej (`PROJEKT-AKTUALNOSCI.md` §3).
 *
 * Argumenty:
 * - `tytul` (string) — nagłówek `h1`; wymagany.
 * - `wstep` (string) — tekst pod nagłówkiem; pomijany, gdy pusty.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

$iskt_tytul = trim( (string) ( $args['tytul'] ?? '' ) );
$iskt_wstep = trim( (string) ( $args['wstep'] ?? '' ) );
$iskt_brak  = iskt_tekst_motywu( 'aktualnosci_brak' );
?>

<div class="iskt-page-header">
	<div class="iskt-container">
		<div class="iskt-page-header__inner iskt-stack">
			<h1 class="iskt-title-lg iskt-balance"><?php echo esc_html( $iskt_tytul ); ?></h1>

			<?php if ( '' !== $iskt_wstep ) : ?>
				<p class="iskt-lead"><?php echo esc_html( $iskt_wstep ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</div>

<section class="iskt-section iskt-aktualnosci">
	<div class="iskt-container iskt-stack iskt-stack--loose">

		<?php get_template_part( 'template-parts/aktualnosc/filtry' ); ?>

		<?php if ( have_posts() ) : ?>

			<div class="iskt-grid iskt-grid--3">
				<?php
				while ( have_posts() ) :
					the_post();

					$iskt_wpis = get_post();

					if ( $iskt_wpis instanceof WP_Post ) {
						get_template_part( 'template-parts/aktualnosc/karta', null, array( 'wpis' => $iskt_wpis ) );
					}
				endwhile;
				?>
			</div>

			<?php iskt_pagination(); ?>

		<?php elseif ( '' !== $iskt_brak ) : ?>

			<?php
			/*
			 * Pusta lista dostaje zdanie, nie pusty ekran — i to zdanie edytowalne
			 * z panelu (§4.7). Serwis bez ani jednego wpisu wygląda wtedy na młody,
			 * a nie na zepsuty.
			 */
			?>
			<div class="iskt-notice iskt-measure">
				<p><?php echo esc_html( $iskt_brak ); ?></p>
			</div>

		<?php endif; ?>

	</div>
</section>
