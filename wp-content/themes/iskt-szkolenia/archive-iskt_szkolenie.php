<?php
/**
 * Katalog szkoleń — archiwum `/szkolenia/`.
 *
 * Realizuje §4.2: pełna lista oferty z wyszukiwaniem po nazwie i opisie,
 * filtrowaniem po kategorii i formie realizacji, wyróżnianiem wybranych szkoleń
 * i paginacją, która pozwala katalogowi rosnąć bez przebudowy widoku.
 *
 * Co JEST pokazane, ustala wtyczka (`includes/katalog.php`) — filtry zmieniają
 * zapytanie główne, więc paginacja i adresy stron pochodzą z WordPressa. Ten plik
 * odpowiada wyłącznie za to, JAK wygląda katalog.
 *
 * Ten sam szablon obsługuje archiwa kategorii i formy (`taxonomy-*.php`), żeby
 * odwiedzający, który trafił z kafelka obszaru na stronie głównej, dostał katalog
 * z widocznymi filtrami, a nie inną, uboższą listę.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

get_header();

$iskt_stan   = iskt_stan_katalogu();
$iskt_termin = get_queried_object();

/*
 * Na archiwum kategorii albo formy tytułem jest nazwa terminu wraz z jego opisem —
 * jedno i drugie właściciel edytuje przy kategorii, więc widok nie potrzebuje
 * osobnego napisu.
 */
if ( $iskt_termin instanceof WP_Term ) {
	$iskt_tytul = $iskt_termin->name;
	$iskt_wstep = trim( wp_strip_all_tags( $iskt_termin->description ) );
} else {
	$iskt_tytul = iskt_tekst( 'katalog_tytul' );
	$iskt_wstep = iskt_tekst( 'katalog_wstep' );
}
?>

<main id="iskt-main" class="iskt-main" tabindex="-1">

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

	<section class="iskt-section iskt-katalog">
		<div class="iskt-container iskt-stack iskt-stack--loose">

			<?php get_template_part( 'template-parts/katalog/filtry', null, array( 'stan' => $iskt_stan ) ); ?>

			<?php if ( have_posts() ) : ?>

				<div class="iskt-grid iskt-grid--3">
					<?php
					while ( have_posts() ) :
						the_post();

						$iskt_szkolenie = get_post();

						if ( $iskt_szkolenie instanceof WP_Post ) {
							// Karta pochodzi z wtyczki i sama escape'uje każde pole (includes/bloki/katalog.php).
							echo iskt_karta_szkolenia( $iskt_szkolenie, true, 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
					endwhile;
					?>
				</div>

				<?php iskt_pagination(); ?>

			<?php elseif ( ! iskt_katalog_ma_szkolenia() ) : ?>

				<?php
				/*
				 * Pusty katalog to inna sytuacja niż filtry bez trafień: nie ma czego
				 * szukać, więc nie proponujemy zmiany kryteriów ani czyszczenia filtrów.
				 */
				?>
				<div class="iskt-notice iskt-measure">
					<p><?php echo esc_html( iskt_tekst( 'katalog_brak_oferty' ) ); ?></p>
				</div>

			<?php else : ?>

				<div class="iskt-stack iskt-measure iskt-katalog__pusto">
					<h2 class="iskt-title-sm"><?php echo esc_html( iskt_tekst( 'katalog_brak_wynikow_tytul' ) ); ?></h2>

					<?php if ( '' !== iskt_tekst( 'katalog_brak_wynikow_opis' ) ) : ?>
						<p><?php echo esc_html( iskt_tekst( 'katalog_brak_wynikow_opis' ) ); ?></p>
					<?php endif; ?>

					<p>
						<a class="iskt-button iskt-button--secondary" href="<?php echo esc_url( iskt_adres_katalogu() ); ?>">
							<?php echo esc_html( iskt_tekst( 'katalog_filtr_wyczysc' ) ); ?>
						</a>
					</p>
				</div>

			<?php endif; ?>

		</div>
	</section>

</main>

<?php
get_footer();
