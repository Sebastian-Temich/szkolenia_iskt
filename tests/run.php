<?php
/**
 * Testy sanityzacji pól i logiki terminów.
 *
 * Uruchomienie: `php tests/run.php`. Bez PHPUnit i bez Composera — decyzja ISKT
 * wyklucza płatne zależności, a dokładanie menedżera pakietów dla kilkudziesięciu
 * asercji zwiększyłoby to, co właściciel musi utrzymać, bez zysku dla niego.
 *
 * Zakres celowo ograniczony do funkcji czystych. Rejestracja typów treści, uprawnienia
 * i zapisy do bazy wymagają działającego WordPressa — sprawdza je test odtworzenia (§11 pkt 12).
 *
 * @package ISKT\Szkolenia\Tests
 */

declare( strict_types = 1 );

require_once __DIR__ . '/stubs-wp.php';

const ISKT_CPT_SZKOLENIE         = 'iskt_szkolenie';
const ISKT_CPT_TRENER            = 'iskt_trener';
const ISKT_CPT_TERMIN            = 'iskt_termin';
const ISKT_TAX_KATEGORIA         = 'iskt_kategoria';
const ISKT_TAX_FORMA             = 'iskt_forma';
const ISKT_META_TRENERZY         = '_iskt_trenerzy';
const ISKT_META_TERMIN_SZKOLENIE = '_iskt_termin_szkolenie';

$sciezka = dirname( __DIR__ ) . '/wp-content/plugins/iskt-szkolenia-core/includes/';

/*
 * Wczytujemy prawdziwe pliki wtyczki, nie ich kopie. `add_action` i `add_filter`
 * są w atrapach puste, więc rejestracje na końcu plików nic nie robią, a `WP_Query`
 * nie jest potrzebne — testowane funkcje go nie wywołują.
 */
require_once $sciezka . 'meta.php';
require_once $sciezka . 'terminy.php';

$GLOBALS['iskt_bledy'] = 0;
$GLOBALS['iskt_ok']    = 0;

/**
 * Sprawdza równość wartości oczekiwanej i otrzymanej.
 *
 * @param string $opis       Opis przypadku.
 * @param mixed  $oczekiwane Wartość oczekiwana.
 * @param mixed  $otrzymane  Wartość otrzymana.
 */
function sprawdz( string $opis, $oczekiwane, $otrzymane ): void {
	if ( $oczekiwane === $otrzymane ) {
		++$GLOBALS['iskt_ok'];

		return;
	}

	++$GLOBALS['iskt_bledy'];

	printf(
		"BŁĄD: %s\n  oczekiwano: %s\n  otrzymano:  %s\n",
		$opis,
		var_export( $oczekiwane, true ),
		var_export( $otrzymane, true )
	);
}

// --- Daty ------------------------------------------------------------------

sprawdz( 'poprawna data przechodzi', '2026-11-03', iskt_sanitize_data( '2026-11-03' ) );
sprawdz( 'data nieistniejąca odrzucona', '', iskt_sanitize_data( '2026-02-30' ) );
sprawdz( 'zły format odrzucony', '', iskt_sanitize_data( '03.11.2026' ) );
sprawdz( 'tekst odrzucony', '', iskt_sanitize_data( 'jutro' ) );
sprawdz( 'pusta data dozwolona', '', iskt_sanitize_data( '' ) );
sprawdz( 'tablica nie wysadza sanityzacji', '', iskt_sanitize_data( array( '2026-11-03' ) ) );
sprawdz( 'data z czasem odrzucona', '', iskt_sanitize_data( '2026-11-03 10:00' ) );

// --- Cena ------------------------------------------------------------------

sprawdz( 'liczba całkowita normalizowana', '1200.00', iskt_sanitize_cena( '1200' ) );
sprawdz( 'przecinek dziesiętny akceptowany', '1199.99', iskt_sanitize_cena( '1199,99' ) );
sprawdz( 'spacje jako separator tysięcy', '12000.00', iskt_sanitize_cena( '12 000' ) );
sprawdz( 'twarda spacja jako separator', '12000.00', iskt_sanitize_cena( "12\u{00A0}000" ) );
sprawdz( 'cena ujemna odrzucona', '', iskt_sanitize_cena( '-100' ) );
sprawdz( 'tekst w cenie odrzucony', '', iskt_sanitize_cena( 'zapytaj o wycenę' ) );
sprawdz( 'pusta cena dozwolona', '', iskt_sanitize_cena( '' ) );
sprawdz( 'zero dozwolone', '0.00', iskt_sanitize_cena( '0' ) );

// --- Listy -----------------------------------------------------------------

sprawdz(
	'lista dzielona po wierszach',
	array( 'Pierwsza', 'Druga' ),
	iskt_sanitize_lista( "Pierwsza\nDruga" )
);
sprawdz(
	'puste wiersze pomijane',
	array( 'Pierwsza', 'Druga' ),
	iskt_sanitize_lista( "Pierwsza\n\n  \nDruga\n" )
);
sprawdz(
	'znaczniki usuwane z pozycji',
	array( 'alert(1)' ),
	iskt_sanitize_lista( '<script>alert(1)</script>' )
);
sprawdz( 'wartość nietablicowa daje pustą listę', array(), iskt_sanitize_lista( 42 ) );
sprawdz(
	'złamania wiersza w stylu Windows',
	array( 'A', 'B' ),
	iskt_sanitize_lista( "A\r\nB" )
);

// --- Program ---------------------------------------------------------------

sprawdz(
	'moduł bez tytułu odrzucony',
	array( array( 'tytul' => 'Moduł 1', 'opis' => 'Opis' ) ),
	iskt_sanitize_program(
		array(
			array( 'tytul' => 'Moduł 1', 'opis' => 'Opis' ),
			array( 'tytul' => '', 'opis' => 'Opis bez tytułu' ),
		)
	)
);
sprawdz(
	'moduł bez opisu zachowany',
	array( array( 'tytul' => 'Moduł', 'opis' => '' ) ),
	iskt_sanitize_program( array( array( 'tytul' => 'Moduł' ) ) )
);
sprawdz( 'program nie-tablica daje pustą listę', array(), iskt_sanitize_program( 'tekst' ) );

// --- Słowniki --------------------------------------------------------------

$sanitize_podatku = iskt_sanitize_ze_slownika( 'iskt_slownik_podatku' );

sprawdz( 'wartość ze słownika przechodzi', 'netto', $sanitize_podatku( 'netto' ) );
sprawdz( 'wartość spoza słownika czyszczona', '', $sanitize_podatku( 'netto-ish' ) );
sprawdz( 'pusta wartość dozwolona', '', $sanitize_podatku( '' ) );

/*
 * Kluczowy przypadek: wartość spoza słownika NIE jest podmieniana na pierwszą z listy.
 * Cicha podmiana na „netto” przy błędnym imporcie pokazałaby klientowi cenę bez VAT
 * jako cenę końcową.
 */
sprawdz( 'brak cichej podmiany na wartość domyślną', '', $sanitize_podatku( 'brutto z rabatem' ) );

// --- Trenerzy --------------------------------------------------------------

$GLOBALS['iskt_test_typy'] = array(
	11 => ISKT_CPT_TRENER,
	12 => ISKT_CPT_TRENER,
	21 => ISKT_CPT_SZKOLENIE,
);

sprawdz( 'istniejący trenerzy przechodzą', array( 11, 12 ), iskt_sanitize_trenerzy( array( '11', 12 ) ) );
sprawdz( 'duplikaty usuwane', array( 11 ), iskt_sanitize_trenerzy( array( 11, '11' ) ) );
sprawdz( 'wpis innego typu odrzucony', array(), iskt_sanitize_trenerzy( array( 21 ) ) );
sprawdz( 'nieistniejący wpis odrzucony', array(), iskt_sanitize_trenerzy( array( 999 ) ) );
sprawdz( 'zero i tekst odrzucone', array(), iskt_sanitize_trenerzy( array( 0, 'abc' ) ) );
sprawdz( 'kolejność redaktora zachowana', array( 12, 11 ), iskt_sanitize_trenerzy( array( 12, 11 ) ) );

// --- Zakończenie terminu ---------------------------------------------------

$GLOBALS['iskt_test_dzis'] = new DateTimeImmutable( '2026-09-09 12:00:00' );
$GLOBALS['iskt_test_pola'] = array(
	1 => array( '_iskt_data_start' => '2026-09-01', '_iskt_data_koniec' => '2026-09-02' ),
	2 => array( '_iskt_data_start' => '2026-12-01', '_iskt_data_koniec' => '2026-12-02' ),
	3 => array( '_iskt_data_start' => '2026-09-09', '_iskt_data_koniec' => '' ),
	4 => array( '_iskt_data_start' => '2026-09-08', '_iskt_data_koniec' => '' ),
	5 => array( '_iskt_termin_indywidualny' => true, '_iskt_data_start' => '', '_iskt_data_koniec' => '' ),
	6 => array( '_iskt_data_start' => '', '_iskt_data_koniec' => '' ),
	7 => array( '_iskt_data_start' => '2026-09-01', '_iskt_data_koniec' => '2026-09-09' ),
);

sprawdz( 'termin z przeszłości jest zakończony', true, iskt_termin_zakonczony( 1 ) );
sprawdz( 'termin z przyszłości nie jest zakończony', false, iskt_termin_zakonczony( 2 ) );
sprawdz( 'termin jednodniowy dziś jeszcze trwa', false, iskt_termin_zakonczony( 3 ) );
sprawdz( 'termin jednodniowy wczoraj jest zakończony', true, iskt_termin_zakonczony( 4 ) );
sprawdz( 'termin indywidualny nigdy nie jest zakończony', false, iskt_termin_zakonczony( 5 ) );
sprawdz( 'termin bez dat nie jest ukrywany', false, iskt_termin_zakonczony( 6 ) );
sprawdz( 'termin kończący się dziś jeszcze trwa', false, iskt_termin_zakonczony( 7 ) );

// --- Wynik -----------------------------------------------------------------

printf( "\n%d przeszło, %d nie przeszło\n", $GLOBALS['iskt_ok'], $GLOBALS['iskt_bledy'] );

exit( $GLOBALS['iskt_bledy'] > 0 ? 1 : 0 );
