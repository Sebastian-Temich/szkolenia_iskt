<?php
/**
 * Bloki strony głównej czytające katalog: obszary szkoleń i wyróżnione szkolenia.
 *
 * Obie sekcje muszą pokazywać stan katalogu z chwili wyświetlenia. Gdyby ich treść
 * była zapisana w bloku, opublikowanie nowego szkolenia nie zmieniłoby strony
 * głównej, a wycofanie oferty zostawiłoby na niej martwy odnośnik. Dlatego dane
 * pobieramy w `render_callback`, a w edytorze pokazujemy podgląd z serwera.
 *
 * @package ISKT\Szkolenia\Core
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Wypisuje siatkę obszarów szkoleń zbudowaną z kategorii katalogu.
 *
 * Kategorie są edytowalne (§4.2), więc sekcja nie ma zapisanej listy — bierze ją
 * z taksonomii. Dodanie kategorii w panelu dokłada kafelek, usunięcie go zabiera.
 *
 * @param array<string, mixed> $atrybuty Atrybuty bloku.
 */
function iskt_render_obszary_szkolen( array $atrybuty ): string {
	$limit = isset( $atrybuty['liczba'] ) ? (int) $atrybuty['liczba'] : 0;

	$kategorie = get_terms(
		array(
			'taxonomy'   => ISKT_TAX_KATEGORIA,
			// Kategoria bez opublikowanego szkolenia prowadziłaby na pustą listę.
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
			'number'     => $limit > 0 ? $limit : 0,
		)
	);

	if ( is_wp_error( $kategorie ) || array() === $kategorie ) {
		return '';
	}

	$karty = '';

	foreach ( $kategorie as $kategoria ) {
		$adres = get_term_link( $kategoria );

		if ( is_wp_error( $adres ) ) {
			continue;
		}

		$symbol = iskt_symbol_html( iskt_symbol_kategorii( $kategoria->term_id ) );
		$opis   = trim( wp_strip_all_tags( $kategoria->description ) );

		$karty .= '<article class="iskt-card iskt-card--interactive iskt-card--padded">';

		if ( '' !== $symbol ) {
			$karty .= '<span class="iskt-card__icon">' . $symbol . '</span>';
		}

		$karty .= '<h3 class="iskt-card__title">';
		$karty .= '<a class="iskt-card__link" href="' . esc_url( $adres ) . '">' . esc_html( $kategoria->name ) . '</a>';
		$karty .= '</h3>';

		if ( '' !== $opis ) {
			$karty .= '<div class="iskt-card__body"><p>' . esc_html( $opis ) . '</p></div>';
		}

		$karty .= '<p class="iskt-card__more" aria-hidden="true">' . esc_html__( 'Zobacz szkolenia', 'iskt-szkolenia-core' ) . '</p>';
		$karty .= '</article>';
	}

	if ( '' === $karty ) {
		return '';
	}

	$naglowek = iskt_naglowek_sekcji(
		(string) ( $atrybuty['nadtytul'] ?? '' ),
		(string) ( $atrybuty['tytul'] ?? '' ),
		(string) ( $atrybuty['wstep'] ?? '' )
	);

	return sprintf(
		'<section %1$s>%2$s<div class="iskt-grid iskt-grid--3">%3$s</div></section>',
		iskt_atrybuty_bloku( array( 'class' => 'iskt-obszary' ) ),
		$naglowek,
		$karty
	);
}

/**
 * Wyróżnione szkolenia — jedno źródło listy dla wszystkich widoków.
 *
 * Sekcja na stronie głównej i sekcja domykająca artykuł aktualności pokazują tę
 * samą ofertę. Gdyby każda z nich składała własne zapytanie, wystarczyłaby jedna
 * poprawka sortowania po jednej stronie, żeby te same szkolenia ustawiły się
 * w innej kolejności w dwóch miejscach serwisu.
 *
 * @param int $limit Ile szkoleń pobrać; poza zakresem 1–12 przycinamy.
 *
 * @return array<int, WP_Post>
 */
function iskt_wyroznione_szkolenia( int $limit = 4 ): array {
	$limit = max( 1, min( 12, $limit ) );

	$zapytanie = new WP_Query(
		array(
			'post_type'              => ISKT_CPT_SZKOLENIE,
			'post_status'            => 'publish',
			'posts_per_page'         => $limit,
			'meta_key'               => '_iskt_wyroznione', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'             => '1',                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'orderby'                => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_term_cache' => true,
		)
	);

	return array_values( array_filter( $zapytanie->posts, static fn ( $wpis ): bool => $wpis instanceof WP_Post ) );
}

/**
 * Wypisuje siatkę wyróżnionych szkoleń.
 *
 * Wyróżnienie to przełącznik przy szkoleniu (`_iskt_wyroznione`), a nie osobna
 * lista utrzymywana obok katalogu — §4.2 wymaga wyróżniania wybranych szkoleń
 * w jednym miejscu. Gdy właściciel nie wyróżni żadnego, sekcja się nie pokazuje.
 *
 * @param array<string, mixed> $atrybuty Atrybuty bloku.
 */
function iskt_render_wyroznione_szkolenia( array $atrybuty ): string {
	$limit = isset( $atrybuty['liczba'] ) ? (int) $atrybuty['liczba'] : 4;

	$szkolenia = iskt_wyroznione_szkolenia( $limit );

	if ( array() === $szkolenia ) {
		return '';
	}

	$karty = '';

	foreach ( $szkolenia as $szkolenie ) {
		$karty .= iskt_karta_szkolenia( $szkolenie );
	}

	$naglowek = iskt_naglowek_sekcji(
		(string) ( $atrybuty['nadtytul'] ?? '' ),
		(string) ( $atrybuty['tytul'] ?? '' ),
		(string) ( $atrybuty['wstep'] ?? '' ),
		false
	);

	$stopka = '';
	$archiwum = get_post_type_archive_link( ISKT_CPT_SZKOLENIE );

	if ( is_string( $archiwum ) && '' !== $archiwum ) {
		$etykieta = isset( $atrybuty['etykietaKatalogu'] ) && '' !== (string) $atrybuty['etykietaKatalogu']
			? (string) $atrybuty['etykietaKatalogu']
			: __( 'Cały katalog', 'iskt-szkolenia-core' );

		$stopka = sprintf(
			'<p class="iskt-section__action"><a class="iskt-button iskt-button--secondary" href="%1$s">%2$s</a></p>',
			esc_url( $archiwum ),
			esc_html( $etykieta )
		);
	}

	return sprintf(
		'<section %1$s>%2$s<div class="iskt-grid iskt-grid--4">%3$s</div>%4$s</section>',
		iskt_atrybuty_bloku( array( 'class' => 'iskt-wyroznione' ) ),
		$naglowek,
		$karty,
		$stopka
	);
}

/**
 * Buduje kartę pojedynczego szkolenia.
 *
 * Ten sam znacznik zasila stronę główną i katalog (zadanie 6) — karta wygląda
 * i zachowuje się identycznie w obu miejscach, bo pochodzi z jednej funkcji.
 *
 * Wyróżnienie pokazujemy tylko tam, gdzie coś znaczy. W sekcji wyróżnionych na
 * stronie głównej każda karta jest wyróżniona, więc odznaka na każdej z nich nie
 * niosłaby żadnej informacji; w katalogu obok szkoleń zwykłych — owszem.
 *
 * @param WP_Post $szkolenie         Szkolenie.
 * @param bool    $pokaz_wyroznienie Czy oznaczyć szkolenie wyróżnione.
 */
function iskt_karta_szkolenia( WP_Post $szkolenie, bool $pokaz_wyroznienie = false ): string {
	$id    = (int) $szkolenie->ID;
	$adres = get_permalink( $id );

	if ( ! is_string( $adres ) || '' === $adres ) {
		return '';
	}

	$wyroznione = $pokaz_wyroznienie && (bool) get_post_meta( $id, '_iskt_wyroznione', true );

	$html = '<article class="iskt-card iskt-card--interactive' . ( $wyroznione ? ' iskt-card--featured' : '' ) . '">';

	if ( has_post_thumbnail( $id ) ) {
		$html .= '<div class="iskt-card__media">' . get_the_post_thumbnail( $id, 'medium_large', array( 'loading' => 'lazy' ) ) . '</div>';
	}

	$html .= '<div class="iskt-card__body">';

	$kategoria = iskt_kategoria_szkolenia( $id );

	if ( $kategoria instanceof WP_Term ) {
		$html .= '<p class="iskt-card__meta iskt-mono">' . esc_html( $kategoria->name ) . '</p>';
	}

	if ( $wyroznione ) {
		$html .= '<p class="iskt-badge iskt-badge--solid iskt-card__flag">' . esc_html( iskt_tekst( 'szkolenie_odznaka_wyroznione' ) ) . '</p>';
	}

	$html .= '<h3 class="iskt-card__title">';
	$html .= '<a class="iskt-card__link" href="' . esc_url( $adres ) . '">' . esc_html( get_the_title( $id ) ) . '</a>';
	$html .= '</h3>';

	$fakty = array_filter(
		array(
			(string) get_post_meta( $id, '_iskt_czas_trwania', true ),
			implode( ' / ', iskt_formy_szkolenia( $id ) ),
			iskt_poziom_szkolenia( $id ),
		),
		static fn ( string $fakt ): bool => '' !== $fakt
	);

	if ( array() !== $fakty ) {
		$html .= '<ul class="iskt-card__facts">';

		foreach ( $fakty as $fakt ) {
			$html .= '<li>' . esc_html( $fakt ) . '</li>';
		}

		$html .= '</ul>';
	}

	$html .= '</div>';

	$cena = iskt_cena_szkolenia( $id );

	if ( '' !== $cena['kwota'] ) {
		$html .= '<div class="iskt-card__footer">';
		$html .= '<p class="iskt-price"><span class="iskt-price__value">' . esc_html( $cena['kwota'] ) . '</span>';

		if ( '' !== $cena['dopisek'] ) {
			$html .= ' <span class="iskt-price__note">' . esc_html( $cena['dopisek'] ) . '</span>';
		}

		$html .= '</p>';

		/*
		 * Informacja o dofinansowaniu jest jawnie opisowa: mówi, że szkolenie może
		 * być dofinansowane, i nie sugeruje żadnej kwoty po wsparciu (§4.3).
		 */
		if ( (bool) get_post_meta( $id, '_iskt_dofinansowanie', true ) ) {
			$html .= '<p class="iskt-badge iskt-badge--accent">' . esc_html( iskt_tekst( 'szkolenie_odznaka_dofinansowanie' ) ) . '</p>';
		}

		$html .= '</div>';
	}

	$html .= '</article>';

	return $html;
}
