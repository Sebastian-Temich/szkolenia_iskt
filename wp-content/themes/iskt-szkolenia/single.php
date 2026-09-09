<?php
/**
 * Artykuł aktualności.
 *
 * Układ jednokolumnowy o czytelnej szerokości wiersza — bez panelu bocznego.
 * Wpis nie ma danych, które uzasadniałyby drugą kolumnę, a jedna kolumna czyta się
 * lepiej na telefonie i nie wymaga osobnego układu dla 768 px
 * (`PROJEKT-AKTUALNOSCI.md` §4, zaakceptowany przez ISKT 2026-09-09).
 *
 * Szkolenia i trenerzy mają własne szablony (`single-iskt_szkolenie.php`,
 * `single-iskt_trener.php`), więc ten plik obsługuje wpisy i pozostałe typy
 * pojedynczych treści.
 *
 * Czego tu świadomie nie ma — podpisu autora, komentarzy, czasu czytania
 * i przycisków udostępniania. Uzasadnienie każdego z tych braków jest w §5
 * dokumentu projektowego; to decyzje, nie przeoczenia.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$iskt_wpis_id   = (int) get_the_ID();
	$iskt_kategoria = iskt_aktualnosc_kategoria( $iskt_wpis_id );
	$iskt_lista     = iskt_aktualnosci_url();
	$iskt_poprzedni = get_previous_post();
	$iskt_nastepny  = get_next_post();
	?>

<main id="iskt-main" class="iskt-main" tabindex="-1">

	<article <?php post_class( 'iskt-entry iskt-aktualnosc' ); ?>>

		<header class="iskt-section iskt-section--muted iskt-section--tight">
			<div class="iskt-container iskt-container--narrow">

				<nav class="iskt-breadcrumb" aria-label="<?php esc_attr_e( 'Ścieżka nawigacji', 'iskt-szkolenia' ); ?>">
					<a href="<?php echo esc_url( $iskt_lista ); ?>">
						<?php iskt_the_icon( 'arrow-left' ); ?>
						<?php echo esc_html( iskt_aktualnosci_tytul() ); ?>
					</a>

					<?php if ( $iskt_kategoria instanceof WP_Term ) : ?>
						<span class="iskt-breadcrumb__sep" aria-hidden="true">/</span>
						<a href="<?php echo esc_url( (string) get_category_link( $iskt_kategoria ) ); ?>">
							<?php echo esc_html( $iskt_kategoria->name ); ?>
						</a>
					<?php endif; ?>
				</nav>

				<div class="iskt-stack">

					<?php if ( $iskt_kategoria instanceof WP_Term ) : ?>
						<p class="iskt-cluster">
							<span class="iskt-badge iskt-badge--outline"><?php echo esc_html( $iskt_kategoria->name ); ?></span>
						</p>
					<?php endif; ?>

					<h1 class="iskt-title-lg iskt-balance"><?php the_title(); ?></h1>

					<p class="iskt-entry__meta iskt-aktualnosc__meta">
						<time datetime="<?php echo esc_attr( (string) get_the_date( DATE_W3C ) ); ?>">
							<?php echo esc_html( (string) get_the_date() ); ?>
						</time>

						<?php
						/*
						 * Data aktualizacji pokazuje się dopiero, gdy wypada w innym dniu
						 * niż publikacja. Poprawka literówki kwadrans po opublikowaniu
						 * dawałaby dwie identyczne daty obok siebie — czytelnik uznałby to
						 * za usterkę, a nie za informację (§4 projektu).
						 */
						if ( iskt_aktualnosc_pokaz_aktualizacje( $iskt_wpis_id ) ) :
							?>
							<span class="iskt-aktualnosc__aktualizacja">
								<?php
								/*
								 * Wzorzec jest edytowalny z panelu, więc nie może trafić do
								 * printf(): właściciel, który wpisze drugie „%s” albo znak
								 * procentu w zdaniu, wywróciłby stronę błędem PHP. Podmiana
								 * tekstowa zniesie każdą treść, a gdy właściciel usunie
								 * miejsce na datę — doklejamy ją, zamiast ją gubić.
								 */
								$iskt_wzorzec = iskt_tekst_motywu( 'aktualnosci_zaktualizowano' );
								$iskt_data    = '<time datetime="' . esc_attr( (string) get_the_modified_date( DATE_W3C ) ) . '">'
									. esc_html( (string) get_the_modified_date() )
									. '</time>';

								$iskt_zdanie = str_contains( $iskt_wzorzec, '%s' )
									? str_replace( '%s', $iskt_data, esc_html( $iskt_wzorzec ) )
									: trim( esc_html( $iskt_wzorzec ) . ' ' . $iskt_data );

								echo wp_kses( $iskt_zdanie, array( 'time' => array( 'datetime' => true ) ) );
								?>
							</span>
						<?php endif; ?>
					</p>

				</div>
			</div>
		</header>

		<div class="iskt-section iskt-section--tight">
			<div class="iskt-container iskt-container--narrow iskt-stack iskt-stack--loose">

				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="iskt-entry__media">
						<?php the_post_thumbnail( 'large' ); ?>

						<?php if ( '' !== trim( (string) get_the_post_thumbnail_caption() ) ) : ?>
							<figcaption><?php echo esc_html( (string) get_the_post_thumbnail_caption() ); ?></figcaption>
						<?php endif; ?>
					</figure>
				<?php endif; ?>

				<div class="iskt-prose">
					<?php
					the_content();

					wp_link_pages(
						array(
							'before' => '<nav class="iskt-entry__pages" aria-label="' . esc_attr__( 'Strony wpisu', 'iskt-szkolenia' ) . '">',
							'after'  => '</nav>',
						)
					);
					?>
				</div>

				<?php if ( $iskt_poprzedni instanceof WP_Post || $iskt_nastepny instanceof WP_Post ) : ?>
					<nav class="iskt-aktualnosc__sasiedzi" aria-label="<?php esc_attr_e( 'Inne wpisy', 'iskt-szkolenia' ); ?>">

						<?php if ( $iskt_poprzedni instanceof WP_Post ) : ?>
							<a class="iskt-aktualnosc__sasiad" href="<?php echo esc_url( (string) get_permalink( $iskt_poprzedni ) ); ?>">
								<span class="iskt-aktualnosc__sasiad-etykieta">
									<?php echo esc_html( iskt_tekst_motywu( 'aktualnosci_poprzedni' ) ); ?>
								</span>
								<span class="iskt-aktualnosc__sasiad-tytul">
									<?php echo esc_html( get_the_title( $iskt_poprzedni ) ); ?>
								</span>
							</a>
						<?php endif; ?>

						<?php if ( $iskt_nastepny instanceof WP_Post ) : ?>
							<a class="iskt-aktualnosc__sasiad iskt-aktualnosc__sasiad--nastepny" href="<?php echo esc_url( (string) get_permalink( $iskt_nastepny ) ); ?>">
								<span class="iskt-aktualnosc__sasiad-etykieta">
									<?php echo esc_html( iskt_tekst_motywu( 'aktualnosci_nastepny' ) ); ?>
								</span>
								<span class="iskt-aktualnosc__sasiad-tytul">
									<?php echo esc_html( get_the_title( $iskt_nastepny ) ); ?>
								</span>
							</a>
						<?php endif; ?>

					</nav>
				<?php endif; ?>

			</div>
		</div>

		<?php get_template_part( 'template-parts/aktualnosc/polecane' ); ?>

	</article>

</main>

	<?php
endwhile;

get_footer();
