<?php
/**
 * Dane demonstracyjne dla testu formularza zgłoszeniowego.
 *
 * Uruchamiane przez `wp eval-file` (patrz `formularz.spec.js`). Formularza nie da
 * się sprawdzić bez szkolenia z terminem: wejście ze strony szkolenia ma uzupełnić
 * oba pola, a bez danych nie ma czego uzupełniać.
 *
 * Skrypt jest idempotentny — rozpoznaje wpisy po slugu i aktualizuje je zamiast
 * dokładać kolejne kopie.
 *
 * Tytuły zaczynają się od „Demo —”, bo §9 zabrania przedstawiania niezatwierdzonych
 * treści jako oferty ISKT.
 *
 * Bez `declare( strict_types = 1 )`: `wp eval-file` wykonuje treść przez `eval()`,
 * a deklaracja musi być pierwszą instrukcją skryptu.
 *
 * @package ISKT\Szkolenia\Tests
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit( 1 );
}

/**
 * Zwraca identyfikator wpisu o podanym slugu, tworząc go, gdy nie istnieje.
 *
 * @param string $slug  Slug wpisu.
 * @param string $typ   Typ treści.
 * @param string $tytul Tytuł wpisu.
 * @param string $tresc Treść wpisu.
 */
function iskt_test_zapewnij_wpis( $slug, $typ, $tytul, $tresc = '' ) {
	$istniejacy = get_page_by_path( $slug, OBJECT, $typ );

	$dane = array(
		'post_type'    => $typ,
		'post_name'    => $slug,
		'post_title'   => $tytul,
		'post_content' => $tresc,
		'post_status'  => 'publish',
	);

	if ( $istniejacy instanceof WP_Post ) {
		$dane['ID'] = (int) $istniejacy->ID;

		wp_update_post( $dane );

		return (int) $istniejacy->ID;
	}

	return (int) wp_insert_post( $dane );
}

$iskt_szkolenie = iskt_test_zapewnij_wpis(
	'demo-formularz-esg-w-praktyce',
	ISKT_CPT_SZKOLENIE,
	'Demo — ESG w praktyce',
	'Szkolenie demonstracyjne założone na potrzeby testu formularza zgłoszeniowego.'
);

wp_set_object_terms( $iskt_szkolenie, array( 'esg-i-zrownowazony-rozwoj' ), ISKT_TAX_KATEGORIA );

/*
 * Termin daleko w przyszłości. Data „za rok” liczona od dziś zamiast wpisanej na
 * sztywno: test uruchomiony za dwa lata nie ma zaczynać od terminu, który zdążył
 * się zakończyć i słusznie zniknął z listy (§4.4).
 */
$iskt_start = gmdate( 'Y-m-d', strtotime( '+1 year' ) );

$iskt_termin = iskt_test_zapewnij_wpis(
	'demo-formularz-termin',
	ISKT_CPT_TERMIN,
	'Termin demonstracyjny'
);

update_post_meta( $iskt_termin, ISKT_META_TERMIN_SZKOLENIE, $iskt_szkolenie );
update_post_meta( $iskt_termin, '_iskt_data_start', $iskt_start );
update_post_meta( $iskt_termin, '_iskt_data_koniec', $iskt_start );
update_post_meta( $iskt_termin, '_iskt_tryb', 'online' );
update_post_meta( $iskt_termin, '_iskt_status_zgloszen', 'otwarte' );

// Tytuł terminu wylicza wtyczka z daty i szkolenia — wymuszamy przeliczenie.
$iskt_wpis_terminu = get_post( $iskt_termin );

if ( $iskt_wpis_terminu instanceof WP_Post ) {
	iskt_ustaw_tytul_terminu( $iskt_termin, $iskt_wpis_terminu );
}

// Czysty stan poczty i liczników przed przebiegiem testu.
delete_option( 'iskt_test_poczta_awaria' );
delete_option( 'iskt_test_odstep' );

$iskt_dziennik = trailingslashit( wp_upload_dir()['basedir'] ) . 'iskt-poczta.jsonl';

if ( file_exists( $iskt_dziennik ) ) {
	unlink( $iskt_dziennik );
}

WP_CLI::log( 'szkolenie=' . get_permalink( $iskt_szkolenie ) );
WP_CLI::log( 'termin=' . $iskt_termin );
WP_CLI::log( 'data=' . $iskt_start );
