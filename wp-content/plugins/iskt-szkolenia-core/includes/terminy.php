<?php
/**
 * Terminy — zapytania, rozróżnienie nadchodzących i zakończonych, tytuł w panelu.
 *
 * §4.4: jedno szkolenie ma wiele terminów bez powielania opisu, zakończony termin
 * nie jest prezentowany jako nadchodzący, a archiwizacja terminu nie usuwa strony
 * szkolenia. To ostatnie wynika z modelu — termin i szkolenie to osobne rekordy.
 *
 * @package ISKT\Szkolenia\Core
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Zwraca dzisiejszą datę w strefie czasowej witryny.
 *
 * Świadomie nie używamy `date( 'Y-m-d' )` — to dałoby UTC. Dla polskiego odbiorcy
 * oznaczałoby, że termin kończący się dziś znika z listy o 1:00 albo 2:00 w nocy.
 */
function iskt_dzis(): string {
	return current_datetime()->format( 'Y-m-d' );
}

/**
 * Sprawdza, czy termin już się zakończył.
 *
 * Termin ustalany indywidualnie nigdy nie jest zakończony — nie ma daty, do której
 * można by go porównać, a jego sensem jest stała dostępność zapytania (§4.4).
 *
 * @param int $termin_id Identyfikator terminu.
 */
function iskt_termin_zakonczony( int $termin_id ): bool {
	if ( (bool) iskt_pole( $termin_id, '_iskt_termin_indywidualny' ) ) {
		return false;
	}

	$koniec = (string) iskt_pole( $termin_id, '_iskt_data_koniec' );

	if ( '' === $koniec ) {
		$koniec = (string) iskt_pole( $termin_id, '_iskt_data_start' );
	}

	// Termin bez dat traktujemy jako niezakończony — brak daty to brak podstawy do ukrycia.
	if ( '' === $koniec ) {
		return false;
	}

	return $koniec < iskt_dzis();
}

/**
 * Zwraca terminy szkolenia.
 *
 * @param int  $szkolenie_id       Identyfikator szkolenia.
 * @param bool $tylko_nadchodzace  Gdy `true`, pomija terminy zakończone.
 *
 * @return WP_Post[]
 */
function iskt_terminy_szkolenia( int $szkolenie_id, bool $tylko_nadchodzace = true ): array {
	if ( 0 === $szkolenie_id ) {
		return array();
	}

	$meta_query = array(
		'relation' => 'AND',
		'powiazanie' => array(
			'key'   => ISKT_META_TERMIN_SZKOLENIE,
			'value' => $szkolenie_id,
		),

		/*
		 * Klauzula nazwana wyłącznie po to, żeby móc po niej sortować. Nie używamy
		 * `meta_key` + `orderby => meta_value`, bo przy własnym `meta_query` WordPress
		 * dokłada wtedy kolejny warunek AND na istnienie tego klucza — i termin bez
		 * zapisanej daty startu zniknąłby z listy zamiast trafić na jej koniec.
		 */
		'sortowanie' => array(
			'key'     => '_iskt_data_start',
			'compare' => 'EXISTS',
			'type'    => 'CHAR',
		),
	);

	if ( $tylko_nadchodzace ) {
		/*
		 * Odsiew po stronie bazy, nie w PHP: filtrowanie po pobraniu obcięłoby wynik
		 * przez `posts_per_page` jeszcze przed odrzuceniem zakończonych terminów.
		 *
		 * Trzy dopuszczalne przypadki: termin indywidualny (bez daty), termin
		 * z datą zakończenia w przyszłości, termin jednodniowy (bez daty zakończenia)
		 * z datą startu w przyszłości.
		 */
		$meta_query['dostepnosc'] = array(
			'relation' => 'OR',
			array(
				'key'     => '_iskt_termin_indywidualny',
				'value'   => '1',
				'compare' => '=',
			),
			array(
				'key'     => '_iskt_data_koniec',
				'value'   => iskt_dzis(),
				'compare' => '>=',
				'type'    => 'DATE',
			),
			array(
				'relation' => 'AND',
				// Pusty ciąg i brak wiersza w bazie to ten sam przypadek dla redaktora.
				array(
					'relation' => 'OR',
					array(
						'key'     => '_iskt_data_koniec',
						'value'   => '',
						'compare' => '=',
					),
					array(
						'key'     => '_iskt_data_koniec',
						'compare' => 'NOT EXISTS',
					),
				),
				array(
					'key'     => '_iskt_data_start',
					'value'   => iskt_dzis(),
					'compare' => '>=',
					'type'    => 'DATE',
				),
			),
		);
	}

	$zapytanie = new WP_Query(
		array(
			'post_type'              => ISKT_CPT_TERMIN,
			'post_status'            => 'publish',
			'posts_per_page'         => 100,
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'meta_query'             => $meta_query,

			/*
			 * Format `RRRR-MM-DD` sortuje się poprawnie jako tekst, więc nie potrzebujemy
			 * rzutowania na datę w SQL. Termin bez daty ma pusty ciąg i trafia na początek —
			 * dlatego `date` jako drugie kryterium utrzymuje stabilną kolejność.
			 */
			'orderby'                => array(
				'sortowanie' => 'ASC',
				'date'       => 'ASC',
			),
		)
	);

	return $zapytanie->posts;
}

/**
 * Zwraca szkolenie powiązane z terminem.
 *
 * @param int $termin_id Identyfikator terminu.
 */
function iskt_szkolenie_terminu( int $termin_id ): ?WP_Post {
	$szkolenie_id = absint( iskt_pole( $termin_id, ISKT_META_TERMIN_SZKOLENIE ) );

	if ( 0 === $szkolenie_id ) {
		return null;
	}

	$szkolenie = get_post( $szkolenie_id );

	return ( $szkolenie instanceof WP_Post && ISKT_CPT_SZKOLENIE === $szkolenie->post_type ) ? $szkolenie : null;
}

/**
 * Buduje czytelny opis terminu — używany jako tytuł w panelu i etykieta w formularzu.
 *
 * @param int $termin_id Identyfikator terminu.
 */
function iskt_etykieta_terminu( int $termin_id ): string {
	if ( (bool) iskt_pole( $termin_id, '_iskt_termin_indywidualny' ) ) {
		return __( 'Termin ustalany indywidualnie', 'iskt-szkolenia-core' );
	}

	$start  = (string) iskt_pole( $termin_id, '_iskt_data_start' );
	$koniec = (string) iskt_pole( $termin_id, '_iskt_data_koniec' );

	if ( '' === $start ) {
		return __( 'Termin bez daty', 'iskt-szkolenia-core' );
	}

	$format = (string) get_option( 'date_format', 'j F Y' );
	$opis   = wp_date( $format, (int) strtotime( $start . ' 12:00:00' ) );

	if ( '' !== $koniec && $koniec !== $start ) {
		$opis .= ' – ' . wp_date( $format, (int) strtotime( $koniec . ' 12:00:00' ) );
	}

	return (string) $opis;
}

/**
 * Nadaje terminowi tytuł wyliczony ze szkolenia i daty.
 *
 * Redaktor nie wpisuje tytułu terminu ręcznie: gdyby wpisywał, lista terminów
 * w panelu rozjeżdżałaby się z faktycznymi datami przy pierwszej zmianie terminu.
 *
 * @param int     $post_id Identyfikator wpisu.
 * @param WP_Post $post    Wpis.
 */
function iskt_ustaw_tytul_terminu( int $post_id, WP_Post $post ): void {
	if ( ISKT_CPT_TERMIN !== $post->post_type ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	$szkolenie = iskt_szkolenie_terminu( $post_id );
	$tytul     = iskt_etykieta_terminu( $post_id );

	if ( $szkolenie instanceof WP_Post ) {
		$tytul = $szkolenie->post_title . ' — ' . $tytul;
	}

	if ( $tytul === $post->post_title ) {
		return;
	}

	// Odpinamy własny hook na czas zapisu, żeby nie wywołać rekurencji przez `wp_update_post`.
	remove_action( 'save_post', 'iskt_ustaw_tytul_terminu', 20 );

	wp_update_post(
		array(
			'ID'         => $post_id,
			'post_title' => $tytul,
		)
	);

	add_action( 'save_post', 'iskt_ustaw_tytul_terminu', 20, 2 );
}
add_action( 'save_post', 'iskt_ustaw_tytul_terminu', 20, 2 );
