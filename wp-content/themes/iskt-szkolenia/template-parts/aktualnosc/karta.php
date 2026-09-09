<?php
/**
 * Karta wpisu na liście aktualności.
 *
 * Świadomie ten sam szkielet znaczników co karta szkolenia
 * (`iskt_karta_szkolenia()` we wtyczce): `iskt-card` → media → body → footer.
 * `PROJEKT-AKTUALNOSCI.md` §2 przyjmuje, że aktualności nie są drugim serwisem —
 * dzięki wspólnym klasom zmiana tokenu koloru albo odstępu obejmuje wpisy
 * automatycznie, bez pamiętania o drugim zestawie reguł.
 *
 * Klikalny jest wyłącznie tytuł; resztę karty obejmuje `.iskt-card__link::after`.
 * Klawiatura i czytnik ekranu dostają więc jeden cel zamiast czterech
 * prowadzących w to samo miejsce — stąd „Czytaj dalej” jest tekstem, nie
 * odnośnikiem.
 *
 * Argumenty:
 * - `wpis` (WP_Post) — wymagany.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

$iskt_wpis = $args['wpis'] ?? null;

if ( ! $iskt_wpis instanceof WP_Post ) {
	return;
}

$iskt_wpis_id = (int) $iskt_wpis->ID;
$iskt_adres   = (string) get_permalink( $iskt_wpis );

if ( '' === $iskt_adres ) {
	return;
}

$iskt_kategoria = iskt_aktualnosc_kategoria( $iskt_wpis_id );
$iskt_zajawka   = trim( (string) get_the_excerpt( $iskt_wpis ) );
$iskt_czytaj    = iskt_tekst_motywu( 'aktualnosci_czytaj' );
?>
<article class="iskt-card iskt-card--interactive iskt-aktualnosc-karta">

	<?php if ( has_post_thumbnail( $iskt_wpis_id ) ) : ?>
		<div class="iskt-card__media">
			<?php echo wp_kses_post( get_the_post_thumbnail( $iskt_wpis_id, 'medium_large', array( 'loading' => 'lazy' ) ) ); ?>
		</div>
	<?php endif; ?>

	<div class="iskt-card__body">

		<p class="iskt-card__meta iskt-mono iskt-aktualnosc-karta__meta">
			<time datetime="<?php echo esc_attr( (string) get_the_date( DATE_W3C, $iskt_wpis ) ); ?>">
				<?php echo esc_html( (string) get_the_date( '', $iskt_wpis ) ); ?>
			</time>

			<?php if ( $iskt_kategoria instanceof WP_Term ) : ?>
				<span class="iskt-aktualnosc-karta__kategoria"><?php echo esc_html( $iskt_kategoria->name ); ?></span>
			<?php endif; ?>
		</p>

		<h2 class="iskt-card__title">
			<a class="iskt-card__link" href="<?php echo esc_url( $iskt_adres ); ?>">
				<?php echo esc_html( get_the_title( $iskt_wpis ) ); ?>
			</a>
		</h2>

		<?php if ( '' !== $iskt_zajawka ) : ?>
			<p class="iskt-aktualnosc-karta__zajawka"><?php echo esc_html( wp_trim_words( $iskt_zajawka, 24, '…' ) ); ?></p>
		<?php endif; ?>

	</div>

	<?php if ( '' !== $iskt_czytaj ) : ?>
		<div class="iskt-card__footer">
			<span class="iskt-card__more" aria-hidden="true">
				<?php
				echo esc_html( $iskt_czytaj );
				iskt_the_icon( 'arrow-right' );
				?>
			</span>
		</div>
	<?php endif; ?>

</article>
