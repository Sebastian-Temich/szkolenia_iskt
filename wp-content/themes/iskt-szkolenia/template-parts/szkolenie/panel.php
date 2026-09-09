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
		<p class="iskt-eyebrow"><?php esc_html_e( 'Cena szkolenia', 'iskt-szkolenia' ); ?></p>

		<p class="iskt-price iskt-price--lg">
			<span class="iskt-price__value"><?php echo esc_html( $iskt_cena['kwota'] ); ?></span>

			<?php if ( '' !== $iskt_cena['dopisek'] ) : ?>
				<span class="iskt-price__note"><?php echo esc_html( $iskt_cena['dopisek'] ); ?></span>
			<?php endif; ?>
		</p>
	<?php endif; ?>

	<p>
		<a class="iskt-button iskt-button--primary iskt-button--block" href="<?php echo esc_url( $iskt_adres_zgloszenia ); ?>">
			<?php esc_html_e( 'Zapytaj o to szkolenie', 'iskt-szkolenia' ); ?>
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
		<?php esc_html_e( 'Wysłanie zapytania nie rezerwuje miejsca na szkoleniu. Odpowiemy z potwierdzeniem dostępności.', 'iskt-szkolenia' ); ?>
	</p>
</div>

<?php if ( array() !== $iskt_terminy ) : ?>
	<section class="iskt-card iskt-card--padded" aria-labelledby="iskt-terminy">
		<h2 id="iskt-terminy" class="iskt-title-xs"><?php esc_html_e( 'Najbliższe terminy', 'iskt-szkolenia' ); ?></h2>

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
<?php endif; ?>

<?php if ( array() !== $iskt_trenerzy ) : ?>
	<section class="iskt-card iskt-card--padded" aria-labelledby="iskt-trenerzy">
		<h2 id="iskt-trenerzy" class="iskt-title-xs"><?php esc_html_e( 'Prowadzący', 'iskt-szkolenia' ); ?></h2>

		<?php
		/*
		 * Powiązanie zapisane jest wyłącznie na szkoleniu, a profil trenera wylicza
		 * swoje szkolenia tym samym zapytaniem w drugą stronę (ADR-001 §2.4). Stąd
		 * spójność obu widoków wymagana przez §4.5 — bez logiki synchronizacji.
		 */
		?>
		<ul class="iskt-trenerzy">
			<?php foreach ( $iskt_trenerzy as $iskt_trener ) : ?>
				<li class="iskt-trener">
					<?php if ( has_post_thumbnail( $iskt_trener ) ) : ?>
						<span class="iskt-trener__zdjecie">
							<?php echo get_the_post_thumbnail( $iskt_trener, 'thumbnail', array( 'loading' => 'lazy' ) ); ?>
						</span>
					<?php endif; ?>

					<span class="iskt-trener__opis">
						<a href="<?php echo esc_url( (string) get_permalink( $iskt_trener ) ); ?>">
							<?php echo esc_html( get_the_title( $iskt_trener ) ); ?>
						</a>

						<?php $iskt_rola = (string) get_post_meta( (int) $iskt_trener->ID, '_iskt_rola', true ); ?>

						<?php if ( '' !== $iskt_rola ) : ?>
							<span class="iskt-trener__rola"><?php echo esc_html( $iskt_rola ); ?></span>
						<?php endif; ?>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
<?php endif; ?>

<?php if ( $iskt_dofinansowanie ) : ?>
	<section class="iskt-card iskt-card--muted iskt-card--padded" aria-labelledby="iskt-dofinansowanie">
		<h2 id="iskt-dofinansowanie" class="iskt-title-xs">
			<?php iskt_the_icon( 'banknote' ); ?>
			<?php esc_html_e( 'Dofinansowanie', 'iskt-szkolenia' ); ?>
		</h2>

		<?php if ( '' !== $iskt_warunki ) : ?>
			<p><?php echo nl2br( esc_html( $iskt_warunki ) ); ?></p>
		<?php else : ?>
			<p><?php esc_html_e( 'To szkolenie może zostać objęte dofinansowaniem. Napisz do nas — sprawdzimy dostępne źródła i warunki.', 'iskt-szkolenia' ); ?></p>
		<?php endif; ?>
	</section>
<?php endif; ?>
