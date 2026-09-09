<?php
/**
 * Sekcja domykająca artykuł: „Zobacz nasze szkolenia”.
 *
 * Jedyny element widoków aktualności wykraczający poza literalne §4.6 — dlatego
 * został wskazany osobno w `PROJEKT-AKTUALNOSCI.md` §4 i zaakceptowany przez ISKT
 * 2026-09-09 wraz z resztą projektu. Powód jest biznesowy: §2 zlecenia mówi, że
 * serwis ma pozyskiwać zgłoszenia, a artykuł bywa najczęstszym wejściem
 * z wyszukiwarki. Czytelnik, który skończył czytać, musi mieć dokąd pójść.
 *
 * Szkolenia bierzemy z `iskt_wyroznione_szkolenia()` we wtyczce — z tego samego
 * źródła co sekcja wyróżnionych na stronie głównej. Właściciel zarządza tą listą
 * jednym przełącznikiem przy szkoleniu i nie utrzymuje osobnego zestawu „pod
 * artykuły”.
 *
 * Gdy żadne szkolenie nie jest wyróżnione, sekcja nie pojawia się w ogóle:
 * nagłówek „Zobacz nasze szkolenia” nad pustym miejscem czytałby się jak usterka.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'iskt_wyroznione_szkolenia' ) || ! function_exists( 'iskt_karta_szkolenia' ) ) {
	return;
}

$iskt_szkolenia = iskt_wyroznione_szkolenia( 3 );

if ( array() === $iskt_szkolenia ) {
	return;
}

$iskt_polecane_tytul = iskt_tekst_motywu( 'aktualnosci_polecane_tytul' );
$iskt_katalog        = get_post_type_archive_link( 'iskt_szkolenie' );
$iskt_katalog_label  = iskt_tekst_motywu( 'aktualnosci_polecane_katalog' );
?>
<section class="iskt-section iskt-section--muted iskt-aktualnosc-polecane">
	<div class="iskt-container iskt-stack iskt-stack--loose">

		<?php if ( '' !== $iskt_polecane_tytul ) : ?>
			<h2 class="iskt-title-md iskt-balance"><?php echo esc_html( $iskt_polecane_tytul ); ?></h2>
		<?php endif; ?>

		<div class="iskt-grid iskt-grid--3">
			<?php
			foreach ( $iskt_szkolenia as $iskt_szkolenie ) {
				// Karta pochodzi z wtyczki i sama escape'uje każde pole (includes/bloki/katalog.php).
				echo iskt_karta_szkolenia( $iskt_szkolenie ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</div>

		<?php if ( is_string( $iskt_katalog ) && '' !== $iskt_katalog && '' !== $iskt_katalog_label ) : ?>
			<p class="iskt-section__action">
				<a class="iskt-button iskt-button--secondary" href="<?php echo esc_url( $iskt_katalog ); ?>">
					<?php echo esc_html( $iskt_katalog_label ); ?>
				</a>
			</p>
		<?php endif; ?>

	</div>
</section>
