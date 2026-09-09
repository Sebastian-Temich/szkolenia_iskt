<?php
/**
 * Nagłówek profilu trenera: ścieżka powrotu, zdjęcie, rola, nazwisko, specjalizacje.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

$iskt_id            = (int) get_the_ID();
$iskt_lista         = get_post_type_archive_link( (string) get_post_type() );
$iskt_rola          = trim( (string) get_post_meta( $iskt_id, '_iskt_rola', true ) );
$iskt_specjalizacje = iskt_lista_pola( $iskt_id, '_iskt_specjalizacje' );

/*
 * Zdjęcie profilowe ładujemy zwyczajnie, nie leniwie — jest u góry strony, więc
 * odroczenie tylko opóźniłoby to, co odwiedzający widzi jako pierwsze. Gdy zdjęcia
 * nie ma (§9 — prawa do wizerunku do potwierdzenia), wraca znak z inicjałami.
 */
$iskt_zdjecie = iskt_zdjecie_osoby( get_post(), 'medium', 'eager' );

?>
<header class="iskt-trener-profil__naglowek iskt-section iskt-section--muted iskt-section--tight">
	<div class="iskt-container">

		<?php if ( is_string( $iskt_lista ) && '' !== $iskt_lista ) : ?>
			<nav class="iskt-breadcrumb" aria-label="<?php esc_attr_e( 'Ścieżka nawigacji', 'iskt-szkolenia' ); ?>">
				<a href="<?php echo esc_url( $iskt_lista ); ?>">
					<?php iskt_the_icon( 'arrow-left' ); ?>
					<?php echo esc_html( iskt_tekst( 'trenerzy_tytul' ) ); ?>
				</a>
			</nav>
		<?php endif; ?>

		<div class="iskt-trener-profil__osoba">

			<?php if ( '' !== $iskt_zdjecie ) : ?>
				<div class="iskt-trener-profil__zdjecie">
					<?php echo wp_kses_post( $iskt_zdjecie ); ?>
				</div>
			<?php endif; ?>

			<div class="iskt-trener-profil__intro iskt-stack">

				<?php if ( '' !== $iskt_rola ) : ?>
					<p class="iskt-eyebrow"><?php echo esc_html( $iskt_rola ); ?></p>
				<?php endif; ?>

				<h1 class="iskt-title-lg iskt-balance"><?php the_title(); ?></h1>

				<?php
				/*
				 * Tylko krótki opis wpisany ręcznie. Zajawka generowana automatycznie
				 * powtórzyłaby tu pierwsze zdanie biografii, która stoi kilka centymetrów
				 * niżej — i wyglądałaby jak pomyłka redaktora, a nie jak wprowadzenie.
				 */
				?>
				<?php if ( has_excerpt() ) : ?>
					<p class="iskt-lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>

				<?php if ( array() !== $iskt_specjalizacje ) : ?>
					<div class="iskt-trener-profil__specjalizacje iskt-stack iskt-stack--tight">
						<h2 class="iskt-eyebrow" id="iskt-specjalizacje">
							<?php echo esc_html( iskt_tekst( 'trener_naglowek_specjalizacje' ) ); ?>
						</h2>

						<?php
						/*
						 * Specjalizacje jako lista, nie ciąg oddzielony przecinkami: czytnik
						 * ekranu ogłasza ich liczbę, a przy zawijaniu na telefonie widać,
						 * gdzie kończy się jedna, a zaczyna druga.
						 */
						?>
						<ul class="iskt-cluster iskt-badges" aria-labelledby="iskt-specjalizacje">
							<?php foreach ( $iskt_specjalizacje as $iskt_specjalizacja ) : ?>
								<li class="iskt-badge iskt-badge--outline"><?php echo esc_html( $iskt_specjalizacja ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

			</div>
		</div>

	</div>
</header>
