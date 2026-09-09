<?php
/**
 * Pomocnicze funkcje szablonów.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Zwraca znaczniki jednej sekcji strony głównej.
 *
 * Wzorzec „Strona główna ISKT” składa się z plików pojedynczych sekcji, zamiast
 * powielać ich treść. Include wykonuje się w zakresie tej funkcji, więc zmienne
 * pomocnicze poszczególnych sekcji nie nadpisują się nawzajem.
 *
 * @param string $nazwa Nazwa pliku sekcji z katalogu `patterns/`, bez rozszerzenia.
 */
function iskt_wzorzec_sekcji( string $nazwa ): string {
	$plik = get_theme_file_path( 'patterns/' . sanitize_file_name( $nazwa ) . '.php' );

	if ( ! file_exists( $plik ) ) {
		return '';
	}

	ob_start();
	include $plik;

	return (string) ob_get_clean();
}

/**
 * Nagłówek listy wpisów: tytuł i opis.
 *
 * Świeża instalacja WordPressa nazywa listę wpisów nazwą serwisu — a domyślna
 * nazwa to „My Blog”, czyli dokładnie to, czego zabrania §11 pkt 10. Dlatego
 * tytuł bierzemy ze strony wpisów, a gdy jej nie ma, nazywamy listę wprost.
 *
 * @return array{title: string, description: string}
 */
function iskt_archive_heading(): array {
	$title       = '';
	$description = '';

	if ( is_search() ) {
		$title = sprintf(
			/* translators: %s: szukana fraza. */
			__( 'Wyniki wyszukiwania: %s', 'iskt-szkolenia' ),
			get_search_query()
		);
	} elseif ( is_home() ) {
		$posts_page = (int) get_option( 'page_for_posts' );

		$title = $posts_page > 0
			? get_the_title( $posts_page )
			: __( 'Aktualności', 'iskt-szkolenia' );

		if ( $posts_page > 0 ) {
			$description = get_the_excerpt( $posts_page );
		}
	} elseif ( is_archive() ) {
		$title       = wp_strip_all_tags( get_the_archive_title() );
		$description = wp_strip_all_tags( get_the_archive_description() );
	}

	return array(
		'title'       => $title,
		'description' => trim( (string) $description ),
	);
}

/**
 * Metadane wpisu: data publikacji i kategorie.
 *
 * Wypisywane tylko dla wpisów. Strona nie ma daty publikacji w sensie, który
 * cokolwiek mówi odwiedzającemu.
 */
function iskt_entry_meta(): void {
	if ( 'post' !== get_post_type() ) {
		return;
	}

	$categories = get_the_category_list( ', ' );
	?>
	<p class="iskt-entry__meta">
		<time datetime="<?php echo esc_attr( (string) get_the_date( DATE_W3C ) ); ?>">
			<?php echo esc_html( (string) get_the_date() ); ?>
		</time>

		<?php if ( ! empty( $categories ) ) : ?>
			<span class="iskt-entry__categories">
				<?php echo wp_kses_post( $categories ); ?>
			</span>
		<?php endif; ?>
	</p>
	<?php
}

/**
 * Paginacja listy wpisów w stylistyce motywu.
 */
function iskt_pagination(): void {
	the_posts_pagination(
		array(
			'class'              => 'iskt-pagination',
			'mid_size'           => 1,
			'prev_text'          => esc_html__( 'Poprzednia', 'iskt-szkolenia' ),
			'next_text'          => esc_html__( 'Następna', 'iskt-szkolenia' ),
			'screen_reader_text' => esc_html__( 'Nawigacja po stronach wyników', 'iskt-szkolenia' ),
			'aria_label'         => esc_html__( 'Strony wyników', 'iskt-szkolenia' ),
		)
	);
}
