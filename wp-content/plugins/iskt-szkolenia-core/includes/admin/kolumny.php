<?php
/**
 * Kolumny list katalogu w panelu.
 *
 * Domyślne listy WordPressa pokazują tytuł, autora i datę publikacji — przy terminach
 * data publikacji nie mówi nic o tym, kiedy odbywa się szkolenie. Redaktor musi widzieć
 * powiązanie, datę realizacji i to, czy termin już minął (§4.4).
 *
 * @package ISKT\Szkolenia\Core
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Definiuje kolumny listy terminów.
 *
 * @param array<string, string> $kolumny Kolumny domyślne.
 *
 * @return array<string, string>
 */
function iskt_kolumny_terminow( array $kolumny ): array {
	unset( $kolumny['date'] );

	return array_merge(
		array_slice( $kolumny, 0, 2, true ),
		array(
			'iskt_szkolenie' => __( 'Szkolenie', 'iskt-szkolenia-core' ),
			'iskt_data'      => __( 'Data realizacji', 'iskt-szkolenia-core' ),
			'iskt_tryb'      => __( 'Tryb', 'iskt-szkolenia-core' ),
			'iskt_status'    => __( 'Zgłoszenia', 'iskt-szkolenia-core' ),
		),
		array_slice( $kolumny, 2, null, true )
	);
}
add_filter( 'manage_' . ISKT_CPT_TERMIN . '_posts_columns', 'iskt_kolumny_terminow' );

/**
 * Wypełnia kolumny listy terminów.
 *
 * @param string $kolumna Nazwa kolumny.
 * @param int    $post_id Identyfikator wpisu.
 */
function iskt_zawartosc_kolumn_terminow( string $kolumna, int $post_id ): void {
	switch ( $kolumna ) {
		case 'iskt_szkolenie':
			$szkolenie = iskt_szkolenie_terminu( $post_id );

			if ( ! $szkolenie instanceof WP_Post ) {
				printf(
					'<span class="iskt-ostrzezenie">%s</span>',
					esc_html__( 'Brak powiązania — termin się nie wyświetli', 'iskt-szkolenia-core' )
				);
				break;
			}

			printf(
				'<a href="%s">%s</a>',
				esc_url( (string) get_edit_post_link( $szkolenie->ID ) ),
				esc_html( $szkolenie->post_title )
			);
			break;

		case 'iskt_data':
			echo esc_html( iskt_etykieta_terminu( $post_id ) );

			if ( iskt_termin_zakonczony( $post_id ) ) {
				printf(
					' <span class="iskt-ostrzezenie">%s</span>',
					esc_html__( '(zakończony)', 'iskt-szkolenia-core' )
				);
			}
			break;

		case 'iskt_tryb':
			$tryb = (string) iskt_pole( $post_id, '_iskt_tryb' );
			$opis = iskt_slownik_trybow()[ $tryb ] ?? '—';

			echo esc_html( (string) $opis );

			$lokalizacja = (string) iskt_pole( $post_id, '_iskt_lokalizacja' );

			if ( '' !== $lokalizacja ) {
				echo '<br><small>' . esc_html( $lokalizacja ) . '</small>';
			}
			break;

		case 'iskt_status':
			$status = (string) iskt_pole( $post_id, '_iskt_status_zgloszen' );

			echo esc_html( (string) ( iskt_slownik_statusow_zgloszen()[ $status ] ?? '—' ) );
			break;
	}
}
add_action( 'manage_' . ISKT_CPT_TERMIN . '_posts_custom_column', 'iskt_zawartosc_kolumn_terminow', 10, 2 );

/**
 * Definiuje kolumny listy szkoleń.
 *
 * @param array<string, string> $kolumny Kolumny domyślne.
 *
 * @return array<string, string>
 */
function iskt_kolumny_szkolen( array $kolumny ): array {
	$kolumny['iskt_terminy']    = __( 'Nadchodzące terminy', 'iskt-szkolenia-core' );
	$kolumny['iskt_trenerzy']   = __( 'Trenerzy', 'iskt-szkolenia-core' );
	$kolumny['iskt_wyroznione'] = __( 'Wyróżnione', 'iskt-szkolenia-core' );

	return $kolumny;
}
add_filter( 'manage_' . ISKT_CPT_SZKOLENIE . '_posts_columns', 'iskt_kolumny_szkolen' );

/**
 * Wypełnia kolumny listy szkoleń.
 *
 * @param string $kolumna Nazwa kolumny.
 * @param int    $post_id Identyfikator wpisu.
 */
function iskt_zawartosc_kolumn_szkolen( string $kolumna, int $post_id ): void {
	switch ( $kolumna ) {
		case 'iskt_terminy':
			$terminy = iskt_terminy_szkolenia( $post_id );

			if ( array() === $terminy ) {
				printf(
					'<span class="iskt-ostrzezenie">%s</span>',
					esc_html__( 'brak', 'iskt-szkolenia-core' )
				);
				break;
			}

			echo esc_html( (string) count( $terminy ) );
			echo '<br><small>' . esc_html( iskt_etykieta_terminu( $terminy[0]->ID ) ) . '</small>';
			break;

		case 'iskt_trenerzy':
			$trenerzy = iskt_trenerzy_szkolenia( $post_id );

			if ( array() === $trenerzy ) {
				echo '—';
				break;
			}

			echo esc_html( implode( ', ', wp_list_pluck( $trenerzy, 'post_title' ) ) );
			break;

		case 'iskt_wyroznione':
			echo (bool) iskt_pole( $post_id, '_iskt_wyroznione' )
				? esc_html__( 'tak', 'iskt-szkolenia-core' )
				: '—';
			break;
	}
}
add_action( 'manage_' . ISKT_CPT_SZKOLENIE . '_posts_custom_column', 'iskt_zawartosc_kolumn_szkolen', 10, 2 );

/**
 * Definiuje kolumny listy trenerów.
 *
 * @param array<string, string> $kolumny Kolumny domyślne.
 *
 * @return array<string, string>
 */
function iskt_kolumny_trenerow( array $kolumny ): array {
	$kolumny['iskt_rola']      = __( 'Rola', 'iskt-szkolenia-core' );
	$kolumny['iskt_szkolenia'] = __( 'Szkolenia', 'iskt-szkolenia-core' );

	return $kolumny;
}
add_filter( 'manage_' . ISKT_CPT_TRENER . '_posts_columns', 'iskt_kolumny_trenerow' );

/**
 * Wypełnia kolumny listy trenerów.
 *
 * @param string $kolumna Nazwa kolumny.
 * @param int    $post_id Identyfikator wpisu.
 */
function iskt_zawartosc_kolumn_trenerow( string $kolumna, int $post_id ): void {
	switch ( $kolumna ) {
		case 'iskt_rola':
			$rola = (string) iskt_pole( $post_id, '_iskt_rola' );

			echo '' !== $rola ? esc_html( $rola ) : '—';
			break;

		case 'iskt_szkolenia':
			$szkolenia = iskt_szkolenia_trenera( $post_id );

			if ( array() === $szkolenia ) {
				echo '—';
				break;
			}

			/*
			 * Lista tylko do odczytu z odnośnikiem do szkolenia. Relacja jest zapisana
			 * na szkoleniu (§4.5), więc redaktor musi wiedzieć, gdzie ją zmienić —
			 * inaczej szukałby pola na profilu trenera.
			 */
			$odnosniki = array();

			foreach ( $szkolenia as $szkolenie ) {
				$odnosniki[] = sprintf(
					'<a href="%s">%s</a>',
					esc_url( (string) get_edit_post_link( $szkolenie->ID ) ),
					esc_html( $szkolenie->post_title )
				);
			}

			echo wp_kses_post( implode( ', ', $odnosniki ) );
			break;
	}
}
add_action( 'manage_' . ISKT_CPT_TRENER . '_posts_custom_column', 'iskt_zawartosc_kolumn_trenerow', 10, 2 );

/**
 * Dodaje na profilu trenera informację, gdzie zarządza się powiązaniem ze szkoleniami.
 *
 * @param WP_Post $post Edytowany wpis.
 */
function iskt_skrzynka_szkolen_trenera( WP_Post $post ): void {
	$szkolenia = iskt_szkolenia_trenera( $post->ID );

	if ( array() === $szkolenia ) {
		printf(
			'<p>%s</p>',
			esc_html__( 'Ten trener nie jest jeszcze przypisany do żadnego szkolenia.', 'iskt-szkolenia-core' )
		);
	} else {
		echo '<ul>';

		foreach ( $szkolenia as $szkolenie ) {
			printf(
				'<li><a href="%s">%s</a></li>',
				esc_url( (string) get_edit_post_link( $szkolenie->ID ) ),
				esc_html( $szkolenie->post_title )
			);
		}

		echo '</ul>';
	}

	printf(
		'<p class="description">%s</p>',
		esc_html__( 'Powiązanie ustawia się na stronie szkolenia, w polu „Trenerzy”. Dzięki temu istnieje tylko jedno miejsce, w którym może się rozjechać.', 'iskt-szkolenia-core' )
	);
}

/**
 * Rejestruje skrzynkę informacyjną na profilu trenera.
 */
function iskt_dodaj_skrzynke_trenera(): void {
	add_meta_box(
		'iskt-szkolenia-trenera',
		__( 'Prowadzone szkolenia', 'iskt-szkolenia-core' ),
		'iskt_skrzynka_szkolen_trenera',
		ISKT_CPT_TRENER,
		'side',
		'default',
		array( '__block_editor_compatible_meta_box' => true )
	);
}
add_action( 'add_meta_boxes', 'iskt_dodaj_skrzynke_trenera' );

/**
 * Wyróżnia ostrzeżenia w listach katalogu.
 */
function iskt_style_list(): void {
	$ekran = get_current_screen();

	if ( null === $ekran || 'edit' !== $ekran->base ) {
		return;
	}

	if ( ! in_array( $ekran->post_type, array( ISKT_CPT_SZKOLENIE, ISKT_CPT_TRENER, ISKT_CPT_TERMIN ), true ) ) {
		return;
	}

	wp_add_inline_style( 'list-tables', '.iskt-ostrzezenie{color:#b32d2e;font-weight:600}' );
}
add_action( 'admin_enqueue_scripts', 'iskt_style_list' );
