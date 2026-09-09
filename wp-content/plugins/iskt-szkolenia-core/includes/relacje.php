<?php
/**
 * Relacja szkolenie ↔ trener.
 *
 * Powiązanie zapisujemy WYŁĄCZNIE na szkoleniu (`_iskt_trenerzy`). Profil trenera
 * nie ma własnej kopii — swoje szkolenia wylicza zapytaniem zwrotnym. §4.5 wymaga
 * zarządzania w jednym miejscu i spójności w obu widokach; jedno źródło prawdy daje
 * to z definicji, bez logiki synchronizacji, która mogłaby się rozjechać.
 *
 * @package ISKT\Szkolenia\Core
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Zwraca trenerów prowadzących dane szkolenie, w kolejności ustawionej przez redaktora.
 *
 * @param int $szkolenie_id Identyfikator szkolenia.
 *
 * @return WP_Post[]
 */
function iskt_trenerzy_szkolenia( int $szkolenie_id ): array {
	$identyfikatory = iskt_pole( $szkolenie_id, ISKT_META_TRENERZY );

	if ( ! is_array( $identyfikatory ) || array() === $identyfikatory ) {
		return array();
	}

	$zapytanie = new WP_Query(
		array(
			'post_type'              => ISKT_CPT_TRENER,
			'post_status'            => 'publish',
			'post__in'               => array_map( 'intval', $identyfikatory ),

			// Kolejność z pola, nie alfabetyczna — redaktor ustawia, kto jest prowadzącym głównym.
			'orderby'                => 'post__in',
			'posts_per_page'         => count( $identyfikatory ),
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		)
	);

	return $zapytanie->posts;
}

/**
 * Zwraca szkolenia prowadzone przez danego trenera.
 *
 * Zapytanie zwrotne po polu `_iskt_trenerzy`. Pole jest tablicą zapisaną jako jeden
 * wiersz serializowany, więc porównujemy wzorcem `LIKE` na dokładnym zapisie
 * `i:<id>;` — samo `LIKE "%<id>%"` dopasowałoby trenera 12 do trenera 121.
 *
 * @param int $trener_id       Identyfikator trenera.
 * @param int $ile             Maksymalna liczba wyników.
 *
 * @return WP_Post[]
 */
function iskt_szkolenia_trenera( int $trener_id, int $ile = 20 ): array {
	if ( 0 === $trener_id ) {
		return array();
	}

	$zapytanie = new WP_Query(
		array(
			'post_type'              => ISKT_CPT_SZKOLENIE,
			'post_status'            => 'publish',
			'posts_per_page'         => $ile,
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'meta_query'             => array(
				array(
					'key'     => ISKT_META_TRENERZY,
					'value'   => sprintf( 'i:%d;', $trener_id ),
					'compare' => 'LIKE',
				),
			),
		)
	);

	return $zapytanie->posts;
}

/**
 * Usuwa trenera z pól `_iskt_trenerzy` wszystkich szkoleń po jego skasowaniu.
 *
 * Bez tego szkolenie zachowałoby odwołanie do nieistniejącego wpisu. Odczyt i tak
 * odfiltrowałby je przy prezentacji, ale w bazie zostałby śmieć, który przy eksporcie
 * i imporcie potrafi wskazać przypadkowy wpis o tym samym identyfikatorze.
 *
 * @param int $post_id Identyfikator usuwanego wpisu.
 */
function iskt_posprzataj_po_trenerze( int $post_id ): void {
	if ( ISKT_CPT_TRENER !== get_post_type( $post_id ) ) {
		return;
	}

	foreach ( iskt_szkolenia_trenera( $post_id, -1 ) as $szkolenie ) {
		$obecni = iskt_pole( $szkolenie->ID, ISKT_META_TRENERZY );

		if ( ! is_array( $obecni ) ) {
			continue;
		}

		$pozostali = array_values(
			array_filter(
				array_map( 'intval', $obecni ),
				static fn ( int $id ): bool => $id !== $post_id
			)
		);

		update_post_meta( $szkolenie->ID, ISKT_META_TRENERZY, $pozostali );
	}
}
add_action( 'before_delete_post', 'iskt_posprzataj_po_trenerze' );

/**
 * Usuwa terminy powiązane ze szkoleniem po jego trwałym skasowaniu.
 *
 * Termin bez szkolenia nie ma czego opisywać ani gdzie się wyświetlić. Reagujemy
 * na trwałe usunięcie, nie na przeniesienie do kosza — przeniesienie do kosza jest
 * odwracalne i §4.4 wprost chroni stronę szkolenia przy archiwizacji.
 *
 * @param int $post_id Identyfikator usuwanego wpisu.
 */
function iskt_posprzataj_po_szkoleniu( int $post_id ): void {
	if ( ISKT_CPT_SZKOLENIE !== get_post_type( $post_id ) ) {
		return;
	}

	$terminy = get_posts(
		array(
			'post_type'      => ISKT_CPT_TERMIN,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'   => ISKT_META_TERMIN_SZKOLENIE,
					'value' => $post_id,
				),
			),
		)
	);

	foreach ( $terminy as $termin_id ) {
		wp_delete_post( (int) $termin_id, true );
	}
}
add_action( 'before_delete_post', 'iskt_posprzataj_po_szkoleniu' );
