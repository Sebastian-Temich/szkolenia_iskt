<?php
/**
 * Panel boczny strony szkolenia: cena, terminy, trenerzy, dofinansowanie.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

$iskt_id       = (int) get_the_ID();
$iskt_cena     = iskt_cena_szkolenia( $iskt_id );
$iskt_terminy  = iskt_terminy_szkolenia( $iskt_id );
$iskt_trenerzy = iskt_trenerzy_szkolenia( $iskt_id );

$iskt_dofinansowanie = (bool) get_post_meta( $iskt_id, '_iskt_dofinansowanie', true );
$iskt_warunki        = trim( (string) get_post_meta( $iskt_id, '_iskt_dofinansowanie_opis', true ) );

/*
 * Adres zgłoszenia. Do czasu wykonania formularza (zadanie 9) prowadzi do sekcji
 * kontaktu na stronie głównej — czyli do czegoś, co istnieje i działa. Zaślepka
 * „#” byłaby złamaniem §11 pkt 10 nawet na etapie pośrednim.
 */
$iskt_adres_zgloszenia = home_url( '/#kontakt' );

?>

<div class="iskt-card iskt-card--padded iskt-panel-oferta">

	<?php if ( '' !== $iskt_cena['kwota'] ) : ?>
		<p class="iskt-eyebrow"><?php echo esc_html( iskt_tekst( 'szkolenie_naglowek_cena' ) ); ?></p>

		<p class="iskt-price iskt-price--lg">
			<span class="iskt-price__value"><?php echo esc_html( $iskt_cena['kwota'] ); ?></span>

			<?php if ( '' !== $iskt_cena['dopisek'] ) : ?>
				<span class="iskt-price__note"><?php echo esc_html( $iskt_cena['dopisek'] ); ?></span>
			<?php endif; ?>
		</p>
	<?php endif; ?>

	<p>
		<a class="iskt-button iskt-button--primary iskt-button--block" href="<?php echo esc_url( $iskt_adres_zgloszenia ); ?>">
			<?php echo esc_html( iskt_tekst( 'szkolenie_cta' ) ); ?>
		</a>
	</p>

	<?php
	/*
	 * §5 zlecenia: zgłoszenie nie jest potwierdzeniem rezerwacji miejsca. Zdanie
	 * stoi przy przycisku, a nie w regulaminie — tam, gdzie ktoś je przeczyta,
	 * zanim kliknie.
	 */
	?>
	<p class="iskt-text-xs iskt-muted">
		<?php echo esc_html( iskt_tekst( 'szkolenie_zastrzezenie' ) ); ?>
	</p>
</div>

<?php if ( array() !== $iskt_terminy ) : ?>
	<section class="iskt-card iskt-card--padded" aria-labelledby="iskt-terminy">
		<h2 id="iskt-terminy" class="iskt-title-xs"><?php echo esc_html( iskt_tekst( 'szkolenie_naglowek_terminy' ) ); ?></h2>

		<?php
		/*
		 * `iskt_terminy_szkolenia()` odsiewa terminy zakończone po stronie bazy —
		 * §4.4 wymaga, żeby zakończony termin nie był pokazywany jako nadchodzący.
		 */
		?>
		<ul class="iskt-terminy">
			<?php
			foreach ( $iskt_terminy as $iskt_termin ) :
				$iskt_opis = iskt_opis_terminu( (int) $iskt_termin->ID );
				?>
				<li class="iskt-termin">
					<p class="iskt-termin__data">
						<?php iskt_the_icon( 'calendar' ); ?>
						<?php echo esc_html( $iskt_opis['etykieta'] ); ?>
					</p>

					<?php if ( '' !== $iskt_opis['miejsce'] ) : ?>
						<p class="iskt-termin__miejsce"><?php echo esc_html( $iskt_opis['miejsce'] ); ?></p>
					<?php endif; ?>

					<?php if ( '' !== $iskt_opis['status'] ) : ?>
						<p class="iskt-badge <?php echo $iskt_opis['zamkniety'] ? 'iskt-badge--neutral' : 'iskt-badge--success'; ?>">
							<?php echo esc_html( $iskt_opis['status'] ); ?>
						</p>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
<?php else : ?>
	<?php
	/*
	 * Brak nadchodzących terminów to nie brak oferty. §4.4 wymaga obsługi terminu
	 * ustalanego indywidualnie, a §4.4 zdanie ostatnie — żeby archiwizacja terminu
	 * nie usuwała strony szkolenia. Milczenie w tym miejscu czytałoby się jak
	 * wycofane szkolenie, więc mówimy wprost, co dalej. Treść jest edytowalna
	 * (Szkolenia → Teksty serwisu).
	 */
	$iskt_bez_terminow = iskt_tekst( 'szkolenie_brak_terminow' );
	?>

	<?php if ( '' !== $iskt_bez_terminow ) : ?>
		<section class="iskt-card iskt-card--padded" aria-labelledby="iskt-terminy">
			<h2 id="iskt-terminy" class="iskt-title-xs"><?php echo esc_html( iskt_tekst( 'szkolenie_naglowek_terminy' ) ); ?></h2>
			<p class="iskt-muted"><?php echo esc_html( $iskt_bez_terminow ); ?></p>
		</section>
	<?php endif; ?>
<?php endif; ?>

<?php if ( array() !== $iskt_trenerzy ) : ?>
	<section class="iskt-card iskt-card--padded" aria-labelledby="iskt-trenerzy">
		<h2 id="iskt-trenerzy" class="iskt-title-xs"><?php echo esc_html( iskt_tekst( 'szkolenie_naglowek_trenerzy' ) ); ?></h2>

		<?php
		/*
		 * Powiązanie zapisane jest wyłącznie na szkoleniu, a profil trenera wylicza
		 * swoje szkolenia tym samym zapytaniem w drugą stronę (ADR-001 §2.4). Stąd
		 * spójność obu widoków wymagana przez §4.5 — bez logiki synchronizacji.
		 *
		 * Sam kafel składa wspólna część szablonu, z której korzysta też lista
		 * trenerów — żeby spójność była widoczna również w wyglądzie.
		 */
		?>
		<ul class="iskt-trenerzy">
			<?php foreach ( $iskt_trenerzy as $iskt_trener ) : ?>
				<li class="iskt-trener">
					<?php get_template_part( 'template-parts/trener/kafel', null, array( 'trener' => $iskt_trener ) ); ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
<?php endif; ?>

<?php if ( $iskt_dofinansowanie ) : ?>
	<section class="iskt-card iskt-card--muted iskt-card--padded" aria-labelledby="iskt-dofinansowanie">
		<h2 id="iskt-dofinansowanie" class="iskt-title-xs">
			<?php iskt_the_icon( 'banknote' ); ?>
			<?php echo esc_html( iskt_tekst( 'szkolenie_naglowek_dofinansowanie' ) ); ?>
		</h2>

		<?php if ( '' !== $iskt_warunki ) : ?>
			<p><?php echo nl2br( esc_html( $iskt_warunki ) ); ?></p>
		<?php else : ?>
			<p><?php echo esc_html( iskt_tekst( 'szkolenie_dofinansowanie_opis' ) ); ?></p>
		<?php endif; ?>
	</section>
<?php endif; ?>
