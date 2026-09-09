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
 * Zwraca etykiety wpisów na liście wyboru powiązania w panelu.
 *
 * Sam `post_title` nie wystarcza: dwa szkolenia o identycznym tytule są dla
 * właściciela nierozróżnialne, a wybór zapisuje identyfikator, więc pomyłki nie
 * widać po zapisie. Etykieta powstaje tutaj, przy relacji, a nie w widoku — widok
 * ma ją tylko wypisać.
 *
 * Rozróżnienie dopisujemy WYŁĄCZNIE przy kolizji tytułów. Dopisek przy każdej
 * pozycji byłby szumem na liście, na którą właściciel patrzy przy każdym terminie,
 * a dodatkowa treść pojawia się dokładnie wtedy, gdy jest do czegoś potrzebna.
 *
 * Kolejność dopisków jest celowa i idzie od najbardziej ludzkiego do najbardziej
 * technicznego: status (bo najczęstsza przyczyna bliźniaczych tytułów to robocza
 * kopia obok opublikowanej), potem slug (właściciel rozpozna go z adresu strony),
 * a identyfikator dopiero wtedy, gdy tamte dwa nie rozróżniły pozycji. Funkcja
 * gwarantuje, że zwrócone etykiety są parami różne.
 *
 * @param WP_Post[] $wpisy Wpisy do wyboru.
 *
 * @return array<int, string> Etykieta w kluczu identyfikatora wpisu.
 */
function iskt_etykiety_relacji( array $wpisy ): array {
	$etykiety = array();

	foreach ( $wpisy as $wpis ) {
		$etykiety[ (int) $wpis->ID ] = iskt_tytul_wpisu_relacji( $wpis );
	}

	/*
	 * Dwa przebiegi zamiast jednego: najpierw dopisek czytelny (status i slug),
	 * a identyfikator dopiero, gdy tamten nie rozróżnił pozycji. Drugi przebieg
	 * kończy pracę zawsze, bo identyfikator wpisu jest z definicji niepowtarzalny.
	 */
	foreach ( array( false, true ) as $z_identyfikatorem ) {
		$powtorzone = iskt_powtorzone_etykiety( $etykiety );

		if ( array() === $powtorzone ) {
			break;
		}

		foreach ( $wpisy as $wpis ) {
			$id = (int) $wpis->ID;

			if ( ! in_array( $etykiety[ $id ], $powtorzone, true ) ) {
				continue;
			}

			// Sam nawias nie idzie do tłumaczenia — to interpunkcja, nie treść.
			$etykiety[ $id ] = sprintf(
				'%s (%s)',
				iskt_tytul_wpisu_relacji( $wpis ),
				implode( ', ', iskt_cechy_wpisu_relacji( $wpis, $z_identyfikatorem ) )
			);
		}
	}

	return $etykiety;
}

/**
 * Zwraca tytuł wpisu w postaci nadającej się na etykietę.
 *
 * @param WP_Post $wpis Wpis do wyboru.
 */
function iskt_tytul_wpisu_relacji( WP_Post $wpis ): string {
	$tytul = trim( (string) $wpis->post_title );

	return '' !== $tytul ? $tytul : __( '(bez tytułu)', 'iskt-szkolenia-core' );
}

/**
 * Zwraca cechy odróżniające wpis od innego o tym samym tytule.
 *
 * @param WP_Post $wpis              Wpis do wyboru.
 * @param bool    $z_identyfikatorem Czy dopisać identyfikator wpisu.
 *
 * @return string[]
 */
function iskt_cechy_wpisu_relacji( WP_Post $wpis, bool $z_identyfikatorem ): array {
	$cechy  = array();
	$status = iskt_nazwa_statusu_wpisu( (string) $wpis->post_status );
	$slug   = trim( (string) $wpis->post_name );

	if ( '' !== $status ) {
		$cechy[] = $status;
	}

	if ( '' !== $slug ) {
		$cechy[] = $slug;
	}

	// Wpis roboczy bywa bez sluga — wtedy identyfikator jest jedyną cechą, jaka została.
	if ( $z_identyfikatorem || array() === $cechy ) {
		$cechy[] = '#' . (int) $wpis->ID;
	}

	return $cechy;
}

/**
 * Nazywa status wpisu po polsku na potrzeby etykiety.
 *
 * Własny słownik zamiast `get_post_status_object()`: rdzeń nazywa status roboczy
 * „Szkic — wersja robocza”, co w nawiasie przy tytule czyta się gorzej niż samo
 * „szkic”. Status opublikowany nie ma nazwy — jest stanem domyślnym i dopisek
 * „opublikowane” przy każdej pozycji niczego by nie rozróżnił.
 *
 * @param string $status Status wpisu.
 */
function iskt_nazwa_statusu_wpisu( string $status ): string {
	$nazwy = array(
		'draft'   => __( 'szkic', 'iskt-szkolenia-core' ),
		'pending' => __( 'do przejrzenia', 'iskt-szkolenia-core' ),
		'private' => __( 'prywatne', 'iskt-szkolenia-core' ),
		'future'  => __( 'zaplanowane', 'iskt-szkolenia-core' ),
	);

	return (string) ( $nazwy[ $status ] ?? '' );
}

/**
 * Zwraca etykiety występujące na liście więcej niż raz.
 *
 * @param array<int, string> $etykiety Etykiety w kluczu identyfikatora.
 *
 * @return string[]
 */
function iskt_powtorzone_etykiety( array $etykiety ): array {
	$ile = array_count_values( $etykiety );

	return array_keys( array_filter( $ile, static fn ( int $liczba ): bool => $liczba > 1 ) );
}

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
