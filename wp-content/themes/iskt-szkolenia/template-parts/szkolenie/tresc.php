<?php
/**
 * Treść strony szkolenia: pełny opis, korzyści, program i grupa docelowa.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

$iskt_id = (int) get_the_ID();

$iskt_korzysci = get_post_meta( $iskt_id, '_iskt_korzysci', true );
$iskt_korzysci = is_array( $iskt_korzysci ) ? $iskt_korzysci : array();

$iskt_program = get_post_meta( $iskt_id, '_iskt_program', true );
$iskt_program = is_array( $iskt_program ) ? $iskt_program : array();

$iskt_grupa = trim( (string) get_post_meta( $iskt_id, '_iskt_grupa_docelowa', true ) );

?>

<?php if ( '' !== trim( get_the_content() ) ) : ?>
	<section class="iskt-prose" aria-label="<?php echo esc_attr( iskt_tekst( 'szkolenie_naglowek_opis' ) ); ?>">
		<?php the_content(); ?>
	</section>
<?php endif; ?>

<?php if ( array() !== $iskt_korzysci ) : ?>
	<section aria-labelledby="iskt-korzysci">
		<h2 id="iskt-korzysci" class="iskt-title-sm"><?php echo esc_html( iskt_tekst( 'szkolenie_naglowek_korzysci' ) ); ?></h2>

		<ul class="iskt-list--check iskt-korzysci">
			<?php foreach ( $iskt_korzysci as $iskt_korzysc ) : ?>
				<li><?php echo esc_html( (string) $iskt_korzysc ); ?></li>
			<?php endforeach; ?>
		</ul>
	</section>
<?php endif; ?>

<?php if ( array() !== $iskt_program ) : ?>
	<section aria-labelledby="iskt-program">
		<h2 id="iskt-program" class="iskt-title-sm"><?php echo esc_html( iskt_tekst( 'szkolenie_naglowek_program' ) ); ?></h2>

		<?php
		/*
		 * Lista uporządkowana, a nie zwykłe kafelki z ręcznie wpisanym numerem:
		 * numeracja modułów jest znaczeniem, nie ozdobą, więc niesie ją znacznik.
		 * Czytnik ekranu ogłosi „lista, 4 elementy” i numer każdego modułu.
		 */
		?>
		<ol class="iskt-program">
			<?php foreach ( $iskt_program as $iskt_modul ) : ?>
				<li class="iskt-program__modul">
					<h3 class="iskt-title-xs"><?php echo esc_html( (string) ( $iskt_modul['tytul'] ?? '' ) ); ?></h3>

					<?php if ( '' !== (string) ( $iskt_modul['opis'] ?? '' ) ) : ?>
						<p><?php echo esc_html( (string) $iskt_modul['opis'] ); ?></p>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</section>
<?php endif; ?>

<?php if ( '' !== $iskt_grupa ) : ?>
	<section class="iskt-card iskt-card--muted iskt-card--padded" aria-labelledby="iskt-grupa">
		<h2 id="iskt-grupa" class="iskt-title-xs"><?php echo esc_html( iskt_tekst( 'szkolenie_naglowek_grupa' ) ); ?></h2>
		<p><?php echo nl2br( esc_html( $iskt_grupa ) ); ?></p>
	</section>
<?php endif; ?>
