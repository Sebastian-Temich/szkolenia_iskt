<?php
/**
 * Szkolenia prowadzone przez trenera.
 *
 * Lista powstaje z zapytania zwrotnego po polu `_iskt_trenerzy` zapisanym przy
 * szkoleniu — trener nie ma własnej kopii powiązania (ADR-001 §2.4). Dzięki temu
 * §4.5 „spójność w obu widokach” jest własnością modelu, a nie czymś, co trzeba
 * utrzymywać: przypisanie trenera na stronie szkolenia od razu widać tutaj,
 * a odpięcie go — od razu stąd znika.
 *
 * Z tego samego powodu szkolenie wycofane z publikacji przestaje się tu pokazywać:
 * zapytanie bierze wyłącznie wpisy opublikowane.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

$iskt_szkolenia = iskt_szkolenia_trenera( (int) get_the_ID() );
$iskt_brak      = iskt_tekst( 'trener_brak_szkolen' );

?>
<section class="iskt-section iskt-section--muted iskt-trener-profil__szkolenia" aria-labelledby="iskt-szkolenia-trenera">
	<div class="iskt-container">
		<h2 id="iskt-szkolenia-trenera" class="iskt-title-md">
			<?php echo esc_html( iskt_tekst( 'trener_naglowek_szkolenia' ) ); ?>
		</h2>

		<?php if ( array() !== $iskt_szkolenia ) : ?>

			<div class="iskt-grid iskt-grid--3">
				<?php
				foreach ( $iskt_szkolenia as $iskt_szkolenie ) {
					/*
					 * Ta sama karta, co na stronie głównej i w katalogu — jedna funkcja
					 * składa znacznik, więc szkolenie wygląda wszędzie tak samo. Wartości
					 * są w niej escapowane przy składaniu.
					 */
					echo iskt_karta_szkolenia( $iskt_szkolenie, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
			</div>

		<?php elseif ( '' !== $iskt_brak ) : ?>

			<?php
			/*
			 * Trener bez przypisanego szkolenia to normalny stan — nowa osoba w zespole
			 * albo oferta jeszcze nieopublikowana. Milczenie w tym miejscu wyglądałoby
			 * jak urwany szablon, więc mówimy wprost. Komunikat jest edytowalny.
			 */
			?>
			<p class="iskt-muted"><?php echo esc_html( $iskt_brak ); ?></p>

		<?php endif; ?>
	</div>
</section>
