<?php
/**
 * Ekran „Teksty serwisu” w panelu.
 *
 * Jedno miejsce, w którym właściciel zmienia każdy napis spoza treści wpisów.
 * Ekran budujemy z rejestru (`includes/teksty.php`) — dodanie pola do rejestru
 * automatycznie dokłada je tutaj, więc nie da się dopisać tekstu, którego nie
 * można edytować.
 *
 * Formularz obsługujemy przez `admin-post.php`, a nie przez `options.php`.
 * Powód jest konkretny: `options.php` wymaga uprawnienia `manage_options`, którego
 * redaktor nie ma, a §6 wymaga dostępu dla administracji **i redakcji**. Własna
 * obsługa pozwala oprzeć dostęp na uprawnieniu katalogu.
 *
 * @package ISKT\Szkolenia\Core
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Identyfikator ekranu.
 */
const ISKT_EKRAN_TEKSTY = 'iskt-teksty';

/**
 * Akcja zapisu obsługiwana przez `admin-post.php`.
 */
const ISKT_AKCJA_TEKSTY = 'iskt_zapisz_teksty';

/**
 * Uprawnienie wymagane do edycji tekstów.
 *
 * To samo, które otwiera katalog szkoleń — kto redaguje ofertę, redaguje też
 * napisy wokół niej. Administrator i redaktor dostają je przy aktywacji wtyczki.
 */
function iskt_cap_teksty(): string {
	/**
	 * Pozwala zawęzić lub rozszerzyć dostęp do ekranu tekstów.
	 *
	 * @param string $cap Wymagane uprawnienie.
	 */
	return (string) apply_filters( 'iskt_cap_teksty', 'edit_iskt_szkolenia' );
}

/**
 * Dodaje ekran do menu „Szkolenia”.
 */
function iskt_dodaj_ekran_tekstow(): void {
	add_submenu_page(
		'edit.php?post_type=' . ISKT_CPT_SZKOLENIE,
		__( 'Teksty serwisu', 'iskt-szkolenia-core' ),
		__( 'Teksty serwisu', 'iskt-szkolenia-core' ),
		iskt_cap_teksty(),
		ISKT_EKRAN_TEKSTY,
		'iskt_renderuj_ekran_tekstow'
	);
}
add_action( 'admin_menu', 'iskt_dodaj_ekran_tekstow' );

/**
 * Adres ekranu tekstów.
 */
function iskt_adres_ekranu_tekstow(): string {
	return add_query_arg(
		array(
			'post_type' => ISKT_CPT_SZKOLENIE,
			'page'      => ISKT_EKRAN_TEKSTY,
		),
		admin_url( 'edit.php' )
	);
}

/**
 * Renderuje ekran tekstów.
 */
function iskt_renderuj_ekran_tekstow(): void {
	if ( ! current_user_can( iskt_cap_teksty() ) ) {
		wp_die( esc_html__( 'Nie masz uprawnień do edycji tekstów serwisu.', 'iskt-szkolenia-core' ) );
	}

	$zapisane = iskt_zapisane_teksty();

	echo '<div class="wrap iskt-teksty">';
	echo '<h1>' . esc_html__( 'Teksty serwisu', 'iskt-szkolenia-core' ) . '</h1>';

	printf(
		'<p class="description" style="max-width:46em">%s</p>',
		esc_html__( 'Każdy napis widoczny dla odwiedzającego, który nie należy do treści konkretnego wpisu. Pole pozostawione puste wraca do treści domyślnej — nie trzeba jej pamiętać, żeby cofnąć zmianę.', 'iskt-szkolenia-core' )
	);

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- odczyt komunikatu po przekierowaniu, bez skutków ubocznych.
	if ( isset( $_GET['zapisano'] ) ) {
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html__( 'Teksty zostały zapisane.', 'iskt-szkolenia-core' )
		);
	}

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="' . esc_attr( ISKT_AKCJA_TEKSTY ) . '">';
	wp_nonce_field( ISKT_AKCJA_TEKSTY );

	foreach ( iskt_rejestr_tekstow() as $id_grupy => $grupa ) {
		iskt_renderuj_grupe_tekstow( (string) $id_grupy, $grupa, $zapisane );
	}

	submit_button( __( 'Zapisz teksty', 'iskt-szkolenia-core' ) );

	echo '</form>';
	echo '</div>';
}

/**
 * Renderuje jedną grupę pól.
 *
 * @param string               $id_grupy Identyfikator grupy.
 * @param array<string, mixed> $grupa    Definicja grupy.
 * @param array<string, string> $zapisane Zapisane nadpisania.
 */
function iskt_renderuj_grupe_tekstow( string $id_grupy, array $grupa, array $zapisane ): void {
	$pola = $grupa['pola'] ?? array();

	if ( ! is_array( $pola ) || array() === $pola ) {
		return;
	}

	printf(
		'<h2 id="%s">%s</h2>',
		esc_attr( 'iskt-teksty-' . sanitize_key( $id_grupy ) ),
		esc_html( (string) ( $grupa['etykieta'] ?? $id_grupy ) )
	);

	$opis = (string) ( $grupa['opis'] ?? '' );

	if ( '' !== $opis ) {
		printf( '<p class="description" style="max-width:46em">%s</p>', esc_html( $opis ) );
	}

	echo '<table class="form-table" role="presentation"><tbody>';

	foreach ( $pola as $klucz => $definicja ) {
		iskt_renderuj_pole_tekstu( (string) $klucz, (array) $definicja, $zapisane );
	}

	echo '</tbody></table>';
}

/**
 * Renderuje jedno pole tekstu.
 *
 * @param string                $klucz     Klucz pola.
 * @param array<string, mixed>  $definicja Definicja pola.
 * @param array<string, string> $zapisane  Zapisane nadpisania.
 */
function iskt_renderuj_pole_tekstu( string $klucz, array $definicja, array $zapisane ): void {
	$id       = 'iskt-tekst-' . sanitize_key( $klucz );
	$nazwa    = ISKT_OPCJA_TEKSTY . '[' . $klucz . ']';
	$typ      = (string) ( $definicja['typ'] ?? 'linia' );
	$domyslna = (string) ( $definicja['domyslna'] ?? '' );
	$wartosc  = isset( $zapisane[ $klucz ] ) ? (string) $zapisane[ $klucz ] : '';

	echo '<tr>';
	printf(
		'<th scope="row"><label for="%s">%s</label></th>',
		esc_attr( $id ),
		esc_html( (string) ( $definicja['etykieta'] ?? $klucz ) )
	);
	echo '<td>';

	if ( 'obszar' === $typ ) {
		printf(
			'<textarea id="%s" name="%s" rows="3" class="large-text" placeholder="%s">%s</textarea>',
			esc_attr( $id ),
			esc_attr( $nazwa ),
			esc_attr( $domyslna ),
			esc_textarea( $wartosc )
		);
	} else {
		$typ_html = 'linia';

		if ( 'email' === $typ ) {
			$typ_html = 'email';
		} elseif ( 'adres_www' === $typ ) {
			$typ_html = 'url';
		} elseif ( 'telefon' === $typ ) {
			$typ_html = 'tel';
		}

		printf(
			'<input type="%s" id="%s" name="%s" value="%s" class="regular-text" placeholder="%s">',
			esc_attr( 'linia' === $typ_html ? 'text' : $typ_html ),
			esc_attr( $id ),
			esc_attr( $nazwa ),
			esc_attr( $wartosc ),
			esc_attr( $domyslna )
		);
	}

	$opis = (string) ( $definicja['opis'] ?? '' );

	if ( '' !== $opis ) {
		printf( '<p class="description">%s</p>', esc_html( $opis ) );
	}

	/*
	 * Treść domyślną pokazujemy tylko tam, gdzie pole jest wypełnione. Przy pustym
	 * polu widać ją już jako podpowiedź w kontrolce — powtórzenie pod spodem byłoby
	 * szumem na ekranie z kilkudziesięcioma polami.
	 */
	if ( '' !== $wartosc && '' !== $domyslna ) {
		printf(
			'<p class="description">%s <em>%s</em></p>',
			esc_html__( 'Treść domyślna:', 'iskt-szkolenia-core' ),
			esc_html( $domyslna )
		);
	}

	echo '</td></tr>';
}

/**
 * Przyjmuje zapis formularza tekstów.
 */
function iskt_zapisz_teksty(): void {
	if ( ! current_user_can( iskt_cap_teksty() ) ) {
		wp_die( esc_html__( 'Nie masz uprawnień do edycji tekstów serwisu.', 'iskt-szkolenia-core' ) );
	}

	check_admin_referer( ISKT_AKCJA_TEKSTY );

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanityzacja pole po polu w iskt_sanitize_teksty().
	$przeslane = isset( $_POST[ ISKT_OPCJA_TEKSTY ] ) ? wp_unslash( $_POST[ ISKT_OPCJA_TEKSTY ] ) : array();

	update_option( ISKT_OPCJA_TEKSTY, iskt_sanitize_teksty( $przeslane ) );

	wp_safe_redirect( add_query_arg( 'zapisano', '1', iskt_adres_ekranu_tekstow() ) );

	exit;
}
add_action( 'admin_post_' . ISKT_AKCJA_TEKSTY, 'iskt_zapisz_teksty' );
