<?php
/**
 * Doznaczanie danych demonstracyjnych powstałych poza skryptami zasiewu.
 *
 * Uruchomienie:
 *   npx @wordpress/env run cli wp eval-file wp-content/iskt-tests/e2e/oznacz-demo.php
 *
 * Skrypty `dane-*.php` oznaczają swoje wpisy same. Zostają dwa źródła, których
 * one nie obejmują:
 *
 * 1. testy przechodzące ścieżkę WŁAŚCICIELA (`odbior-m1.spec.js`, `trenerzy.spec.js`)
 *    zakładają wpisy klikaniem w panelu — celowo, bo tylko to dowodzi, że właściciel
 *    poradzi sobie sam. Taki wpis powstaje więc dokładnie tak, jak wpis prawdziwy;
 * 2. dane zasiane w środowisku roboczym, zanim pole `_iskt_demo` w ogóle istniało.
 *
 * Ten skrypt domyka jedno i drugie. Jest to **jedyne** miejsce w całym rozwiązaniu,
 * w którym o przynależności do danych demonstracyjnych rozstrzyga tytuł. Wyszukiwanie
 * i usuwanie idą wyłącznie po polu — patrz `docs/DANE-DEMONSTRACYJNE.md` §4.
 *
 * Bez `declare( strict_types = 1 )`: `wp eval-file` wykonuje treść przez `eval()`,
 * a deklaracja musi być pierwszą instrukcją pliku.
 *
 * @package ISKT\Szkolenia\Tests
 */

defined( 'WP_CLI' ) || exit;

if ( ! function_exists( 'iskt_oznacz_demo' ) ) {
	WP_CLI::error( 'Wtyczka iskt-szkolenia-core nie jest aktywna.' );
}

/**
 * Prefiks tytułu, po którym rozpoznajemy treść założoną na pokaz.
 *
 * Wymagany przez §9 zlecenia w każdym teście i skrypcie tego repozytorium.
 */
$iskt_prefiks = 'Demo —';

/**
 * Rozstrzyga, czy tytuł nosi prefiks demonstracyjny.
 *
 * Porównujemy po obcięciu białych znaków z początku: edytor blokowy potrafi zapisać
 * tytuł ze spacją wiodącą, a wpis nie ma z tego powodu wypaść ze spisu.
 *
 * @param string $tytul    Tytuł wpisu lub nazwa hasła.
 * @param string $prefiks  Szukany prefiks.
 */
function iskt_test_tytul_demonstracyjny( $tytul, $prefiks ) {
	return 0 === strpos( ltrim( (string) $tytul ), $prefiks );
}

$iskt_oznaczone_wpisy = 0;
$iskt_oznaczone_hasla = 0;

$iskt_wpisy = get_posts(
	array(
		'post_type'        => iskt_typy_demo(),
		'post_status'      => iskt_statusy_demo(),
		'posts_per_page'   => -1,
		'suppress_filters' => true,
		'no_found_rows'    => true,
	)
);

foreach ( $iskt_wpisy as $iskt_wpis ) {
	if ( ! iskt_test_tytul_demonstracyjny( $iskt_wpis->post_title, $iskt_prefiks ) ) {
		continue;
	}

	if ( iskt_czy_demo( (int) $iskt_wpis->ID ) ) {
		continue;
	}

	iskt_oznacz_demo( (int) $iskt_wpis->ID );

	++$iskt_oznaczone_wpisy;

	WP_CLI::log( 'oznaczono wpis: ' . $iskt_wpis->post_title );
}

$iskt_hasla = get_terms(
	array(
		'taxonomy'   => iskt_taksonomie_demo(),
		'hide_empty' => false,
	)
);

if ( ! is_wp_error( $iskt_hasla ) ) {
	foreach ( $iskt_hasla as $iskt_haslo ) {
		if ( ! iskt_test_tytul_demonstracyjny( $iskt_haslo->name, $iskt_prefiks ) ) {
			continue;
		}

		if ( iskt_czy_demo_termin( (int) $iskt_haslo->term_id ) ) {
			continue;
		}

		iskt_oznacz_demo_termin( (int) $iskt_haslo->term_id );

		++$iskt_oznaczone_hasla;

		WP_CLI::log( 'oznaczono hasło: ' . $iskt_haslo->name );
	}
}

WP_CLI::success(
	sprintf(
		'Doznaczono — wpisy: %d, hasła taksonomii: %d.',
		$iskt_oznaczone_wpisy,
		$iskt_oznaczone_hasla
	)
);
