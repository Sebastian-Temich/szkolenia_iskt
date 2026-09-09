<?php
/**
 * Pomocnicze funkcje szablonów.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Sekcje strony głównej w kolejności z załącznika.
 *
 * Jedno źródło listy: korzysta z niej wzorzec „Strona główna ISKT” i instalacja,
 * która zakłada stronę główną przy pierwszym uruchomieniu. Gdyby lista istniała
 * w dwóch miejscach, dołożenie sekcji trafiłoby tylko do jednego z nich —
 * i świeża instalacja dostałaby inny układ niż wzorzec z edytora.
 *
 * @return array<int, string> Nazwy plików z katalogu `patterns/`, bez rozszerzenia.
 */
function iskt_sekcje_strony_glownej(): array {
	return array( 'hero', 'obszary', 'wyroznione', 'przebieg', 'dofinansowania', 'wskazniki', 'kontakt' );
}

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
 * Zwraca pole listowe wpisu jako tablicę niepustych wierszy.
 *
 * Pola `lista` (doświadczenie, wykształcenie, specjalizacje, korzyści) trzymane są
 * jako tablica, ale w bazie może zostać wartość po starszym zapisie albo pusty
 * wiersz po skasowaniu treści. Szablon ma decydować „pokazać sekcję czy nie” na
 * podstawie tego, czy jest co pokazać — a nie na podstawie samego istnienia pola.
 *
 * @param int    $post_id Identyfikator wpisu.
 * @param string $klucz   Nazwa pola.
 *
 * @return array<int, string>
 */
function iskt_lista_pola( int $post_id, string $klucz ): array {
	$wartosc = get_post_meta( $post_id, $klucz, true );

	if ( ! is_array( $wartosc ) ) {
		return array();
	}

	return array_values(
		array_filter(
			array_map( static fn ( $pozycja ): string => trim( (string) $pozycja ), $wartosc ),
			static fn ( string $pozycja ): bool => '' !== $pozycja
		)
	);
}

/**
 * Zwraca znacznik zdjęcia osoby albo znak zastępczy z inicjałami.
 *
 * Dwie rzeczy załatwione w jednym miejscu, żeby wyglądały tak samo w panelu
 * szkolenia, na liście trenerów i na profilu:
 *
 * 1. Tekst alternatywny. Gdy redaktor opisał zdjęcie w bibliotece mediów, wygrywa
 *    jego opis; gdy nie — wstawiamy imię i nazwisko, zamiast zostawić obraz bez
 *    opisu albo z nazwą pliku.
 * 2. Brak zdjęcia. §9 zostawia zdjęcia trenerów do czasu potwierdzenia praw, więc
 *    profil bez zdjęcia to stan normalny i długotrwały. Znak zastępczy jest ozdobą
 *    (`aria-hidden`) — imię i nazwisko stoi obok w tekście.
 *
 * @param WP_Post $osoba     Wpis osoby.
 * @param string  $rozmiar   Rozmiar obrazu WordPressa.
 * @param string  $ladowanie Wartość atrybutu `loading`.
 */
function iskt_zdjecie_osoby( WP_Post $osoba, string $rozmiar = 'thumbnail', string $ladowanie = 'lazy' ): string {
	$id    = (int) $osoba->ID;
	$nazwa = (string) get_the_title( $osoba );

	if ( has_post_thumbnail( $id ) ) {
		$opis = trim( (string) get_post_meta( (int) get_post_thumbnail_id( $id ), '_wp_attachment_image_alt', true ) );

		return (string) get_the_post_thumbnail(
			$id,
			$rozmiar,
			array(
				'loading' => $ladowanie,
				'alt'     => '' !== $opis ? $opis : $nazwa,
			)
		);
	}

	$inicjaly = function_exists( 'iskt_inicjaly' ) ? iskt_inicjaly( $nazwa ) : '';

	if ( '' === $inicjaly ) {
		return '';
	}

	return '<span class="iskt-avatar" aria-hidden="true">' . esc_html( $inicjaly ) . '</span>';
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
