<?php
/**
 * Treść profilu trenera: biografia, doświadczenie, wykształcenie i certyfikaty.
 *
 * Część szablonu wypisuje własną sekcję albo nie wypisuje nic. §9 zostawia
 * biografie i certyfikaty do potwierdzenia praw, więc profil bez nich jest
 * na tym etapie stanem typowym — pusta sekcja zostawiłaby wtedy na stronie
 * biały pas między nagłówkiem a listą szkoleń i wyglądała jak usterka.
 *
 * Poszczególne bloki też pojawiają się wyłącznie z treścią: trener bez wpisanego
 * wykształcenia nie pokazuje pustej ramki z samym nagłówkiem.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

$iskt_id        = (int) get_the_ID();
$iskt_biografia = trim( get_the_content() );

/**
 * Sekcje listowe profilu: identyfikator nagłówka → klucz tekstu i pozycje.
 *
 * Obie mają ten sam kształt, więc składamy je jedną pętlą — dołożenie trzeciej
 * listy to jeden wiersz tablicy, a nie kopia znaczników.
 *
 * @var array<string, array{tekst: string, pozycje: array<int, string>}>
 */
$iskt_sekcje = array_filter(
	array(
		'iskt-doswiadczenie' => array(
			'tekst'   => 'trener_naglowek_doswiadczenie',
			'pozycje' => iskt_lista_pola( $iskt_id, '_iskt_doswiadczenie' ),
		),
		'iskt-wyksztalcenie' => array(
			'tekst'   => 'trener_naglowek_wyksztalcenie',
			'pozycje' => iskt_lista_pola( $iskt_id, '_iskt_wyksztalcenie' ),
		),
	),
	static fn ( array $sekcja ): bool => array() !== $sekcja['pozycje']
);

if ( '' === $iskt_biografia && array() === $iskt_sekcje ) {
	return;
}

?>
<div class="iskt-section">
	<div class="iskt-container">
		<div class="iskt-trener-profil__tresc iskt-stack iskt-stack--loose">

			<?php if ( '' !== $iskt_biografia ) : ?>
				<section class="iskt-prose">
					<?php the_content(); ?>
				</section>
			<?php endif; ?>

			<?php if ( array() !== $iskt_sekcje ) : ?>
				<div class="iskt-grid iskt-grid--auto iskt-trener-profil__sekcje">
					<?php foreach ( $iskt_sekcje as $iskt_identyfikator => $iskt_sekcja ) : ?>
						<section class="iskt-card iskt-card--padded" aria-labelledby="<?php echo esc_attr( $iskt_identyfikator ); ?>">
							<h2 id="<?php echo esc_attr( $iskt_identyfikator ); ?>" class="iskt-title-xs">
								<?php echo esc_html( iskt_tekst( $iskt_sekcja['tekst'] ) ); ?>
							</h2>

							<ul class="iskt-list--bullet">
								<?php foreach ( $iskt_sekcja['pozycje'] as $iskt_pozycja ) : ?>
									<li><?php echo esc_html( $iskt_pozycja ); ?></li>
								<?php endforeach; ?>
							</ul>
						</section>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

		</div>
	</div>
</div>
