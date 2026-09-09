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
require_once $sciezka . 'kategorie.php';
require_once $sciezka . 'odbiorca.php';
require_once $sciezka . 'prezentacja.php';

/*
 * `bloki/odbiorca.php` renderuje pojemnik wariantu i korzysta z pomocnika
 * `iskt_atrybuty_bloku()`. Samego `bloki.php` nie wczytujemy — jego rejestracje
 * potrzebują funkcji rdzenia, których nie da się wiernie odwzorować atrapą.
 */
require_once $sciezka . 'bloki/odbiorca.php';

/**
 * Odpowiednik `iskt_atrybuty_bloku()` z `bloki.php`.
 *
 * Jedyna funkcja definiowana w teście zamiast wczytywana z wtyczki. Powód jest
 * jawny: `bloki.php` przy wczytaniu wywołuje `register_block_type()` na plikach
 * `block.json`, czego bez WordPressa zrobić się nie da.
 *
 * @param array<string, string> $dodatkowe Atrybuty opakowania.
 */
function iskt_atrybuty_bloku( array $dodatkowe = array() ): string {
	return get_block_wrapper_attributes( $dodatkowe );
}

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

// --- Wariant odbiorcy ------------------------------------------------------

sprawdz( 'znany wariant przechodzi', 'firmowy', iskt_sanitize_odbiorca( 'firmowy' ) );
sprawdz( 'wariant spoza listy odrzucony', '', iskt_sanitize_odbiorca( 'ktos-inny' ) );
sprawdz( 'wartość nietekstowa odrzucona', '', iskt_sanitize_odbiorca( array( 'firmowy' ) ) );
sprawdz( 'wielkie litery sprowadzone do znanego wariantu', 'firmowy', iskt_sanitize_odbiorca( 'FIRMOWY' ) );

$GLOBALS['iskt_test_opcje'] = array();
unset( $_GET[ ISKT_PARAM_ODBIORCA ] );
sprawdz( 'bez opcji i bez adresu wariant indywidualny', 'indywidualny', iskt_odbiorca_aktywny() );

$GLOBALS['iskt_test_opcje']['iskt_odbiorca_domyslny'] = 'firmowy';
sprawdz( 'opcja ustawia wariant domyślny', 'firmowy', iskt_odbiorca_aktywny() );

$_GET[ ISKT_PARAM_ODBIORCA ] = 'indywidualny';
sprawdz( 'adres ma pierwszeństwo przed opcją', 'indywidualny', iskt_odbiorca_aktywny() );

$_GET[ ISKT_PARAM_ODBIORCA ] = '<script>alert(1)</script>';
sprawdz( 'wstrzyknięcie w adresie wraca do opcji', 'firmowy', iskt_odbiorca_aktywny() );

$GLOBALS['iskt_test_opcje']['iskt_odbiorca_domyslny'] = 'nieznany';
unset( $_GET[ ISKT_PARAM_ODBIORCA ] );
sprawdz( 'uszkodzona opcja wraca do wariantu indywidualnego', 'indywidualny', iskt_odbiorca_domyslny() );

// --- Renderowanie pojemnika wariantu ---------------------------------------

$GLOBALS['iskt_test_opcje'] = array();
unset( $_GET[ ISKT_PARAM_ODBIORCA ] );

$iskt_widoczny = iskt_render_tresc_odbiorcy( array( 'odbiorca' => 'indywidualny' ), '<p>Dla Ciebie</p>' );
$iskt_ukryty   = iskt_render_tresc_odbiorcy( array( 'odbiorca' => 'firmowy' ), '<p>Dla firm</p>' );

sprawdz( 'aktywny wariant nie jest ukryty', false, str_contains( $iskt_widoczny, ' hidden>' ) );
sprawdz( 'nieaktywny wariant dostaje atrybut hidden', true, str_contains( $iskt_ukryty, ' hidden>' ) );
sprawdz( 'oba warianty trafiają do dokumentu', true, str_contains( $iskt_ukryty, 'Dla firm' ) );
sprawdz(
	'pojemnik ma znacznik dla skryptu',
	true,
	str_contains( $iskt_widoczny, 'data-iskt-switch-panel="indywidualny"' )
);

sprawdz( 'pusty pojemnik nie zostawia znacznika', '', iskt_render_tresc_odbiorcy( array( 'odbiorca' => 'firmowy' ), '   ' ) );
sprawdz( 'pusty akapit nie utrzymuje sekcji przy życiu', '', iskt_render_tresc_odbiorcy( array( 'odbiorca' => 'indywidualny' ), '<p></p>' ) );
sprawdz(
	'sekcja z samym zdjęciem nie znika',
	true,
	str_contains( iskt_render_tresc_odbiorcy( array( 'odbiorca' => 'indywidualny' ), '<figure><img src="a.jpg" alt="Sala szkoleniowa"></figure>' ), '<img' )
);
sprawdz( 'pojemnik bez atrybutu nie znika', true, str_contains( iskt_render_tresc_odbiorcy( array(), '<p>Treść</p>' ), 'Treść' ) );
sprawdz(
	'pojemnik z uszkodzonym wariantem nie jest ukrywany',
	false,
	str_contains( iskt_render_tresc_odbiorcy( array( 'odbiorca' => 'bzdura' ), '<p>Treść</p>' ), 'hidden' )
);

// --- Renderowanie przełącznika ---------------------------------------------

$iskt_przelacznik = iskt_render_przelacznik_odbiorcy( array() );

sprawdz( 'przełącznik działa bez JS jako formularz GET', true, str_contains( $iskt_przelacznik, 'method="get"' ) );
sprawdz( 'aktywny przycisk ma aria-pressed=true', true, str_contains( $iskt_przelacznik, 'value="indywidualny" class="iskt-switch__option" aria-pressed="true"' ) );
sprawdz( 'nieaktywny przycisk ma aria-pressed=false', true, str_contains( $iskt_przelacznik, 'value="firmowy" class="iskt-switch__option" aria-pressed="false"' ) );
sprawdz( 'domyślne etykiety pochodzą ze słownika', true, str_contains( $iskt_przelacznik, '>Dla Ciebie</button>' ) );

$iskt_wlasne = iskt_render_przelacznik_odbiorcy( array( 'etykietaFirmowy' => 'Dla zespołów' ) );
sprawdz( 'etykieta z panelu nadpisuje domyślną', true, str_contains( $iskt_wlasne, '>Dla zespołów</button>' ) );

// --- Symbol kategorii ------------------------------------------------------

sprawdz( 'znany symbol przechodzi', 'esg', iskt_sanitize_symbol( 'esg' ) );
sprawdz( 'symbol spoza listy odrzucony', '', iskt_sanitize_symbol( 'rakieta' ) );
sprawdz( 'pusty symbol jest dozwolony', '', iskt_sanitize_symbol( '' ) );
sprawdz( 'pusty symbol nie wywołuje filtru motywu', '', iskt_symbol_html( '' ) );

// --- Prezentacja ceny ------------------------------------------------------

$GLOBALS['iskt_test_pola'] = array(
	10 => array(
		'_iskt_cena'           => '1490.00',
		'_iskt_cena_jednostka' => 'osoba',
		'_iskt_cena_podatek'   => 'netto',
	),
	11 => array(
		'_iskt_cena'           => '',
		'_iskt_cena_jednostka' => 'osoba',
	),
	12 => array(
		'_iskt_cena'           => '2500.00',
		'_iskt_cena_jednostka' => 'do_ustalenia',
	),
	13 => array(
		'_iskt_cena'           => '990.50',
		'_iskt_cena_jednostka' => 'grupa',
		'_iskt_cena_podatek'   => 'zwolnione',
	),
	14 => array(
		'_iskt_cena'         => '1200.00',
		'_iskt_cena_podatek' => 'nieznany_klucz',
	),
);

$iskt_cena = iskt_cena_szkolenia( 10 );
sprawdz( 'kwota bez groszy nie pokazuje przecinka', "1\u{00A0}490 zł", $iskt_cena['kwota'] );
sprawdz( 'dopisek łączy podatek i jednostkę', 'netto (+ VAT) · za osobę', $iskt_cena['dopisek'] );

sprawdz( 'brak ceny nie generuje kwoty', '', iskt_cena_szkolenia( 11 )['kwota'] );

$iskt_indywidualna = iskt_cena_szkolenia( 12 );
sprawdz( 'wycena indywidualna nie pokazuje kwoty', 'Wycena indywidualna', $iskt_indywidualna['kwota'] );
sprawdz( 'wycena indywidualna nie ma dopisku', '', $iskt_indywidualna['dopisek'] );

$iskt_grosze = iskt_cena_szkolenia( 13 );
sprawdz( 'kwota z groszami zachowuje część dziesiętną', '990,50 zł', $iskt_grosze['kwota'] );
sprawdz( 'zwolnienie z VAT trafia do dopisku', 'zwolnione z VAT · za grupę', $iskt_grosze['dopisek'] );

sprawdz( 'nieznany klucz podatku nie trafia do dopisku', '', iskt_cena_szkolenia( 14 )['dopisek'] );

// --- Formy i kategoria szkolenia -------------------------------------------

$GLOBALS['iskt_test_terminy'] = array(
	20 => array(
		ISKT_TAX_FORMA     => array( new WP_Term( 1, 'Online' ), new WP_Term( 2, 'Stacjonarnie' ) ),
		ISKT_TAX_KATEGORIA => array( new WP_Term( 3, 'ESG' ), new WP_Term( 4, 'AI' ) ),
	),
	21 => array(),
);

sprawdz( 'formy zwracane w kolejności z bazy', array( 'Online', 'Stacjonarnie' ), iskt_formy_szkolenia( 20 ) );
sprawdz( 'brak form daje pustą listę', array(), iskt_formy_szkolenia( 21 ) );
sprawdz( 'kategoria wybierana alfabetycznie, żeby wynik był powtarzalny', 'AI', iskt_kategoria_szkolenia( 20 )->name );
sprawdz( 'brak kategorii zwraca null', null, iskt_kategoria_szkolenia( 21 ) );

// --- Opis terminu na stronie szkolenia -------------------------------------

$GLOBALS['iskt_test_opcje']['date_format'] = 'Y-m-d';
$GLOBALS['iskt_test_pola']                 = array(
	30 => array(
		'_iskt_data_start'      => '2026-10-03',
		'_iskt_data_koniec'     => '2026-10-04',
		'_iskt_tryb'            => 'stacjonarnie',
		'_iskt_lokalizacja'     => 'Żory',
		'_iskt_status_zgloszen' => 'otwarte',
	),
	31 => array(
		'_iskt_data_start'      => '2026-10-03',
		'_iskt_tryb'            => 'online',
		'_iskt_lokalizacja'     => 'Żory',
		'_iskt_status_zgloszen' => 'zamkniete',
	),
	32 => array(
		'_iskt_termin_indywidualny' => true,
		'_iskt_status_zgloszen'     => 'nieznany',
	),
);

$iskt_termin = iskt_opis_terminu( 30 );
sprawdz( 'zakres dat składany z obu pól', '2026-10-03 – 2026-10-04', $iskt_termin['etykieta'] );
sprawdz( 'miejsce łączy tryb i lokalizację', 'Stacjonarnie · Żory', $iskt_termin['miejsce'] );
sprawdz( 'status otwartych zgłoszeń nie jest zamknięty', false, $iskt_termin['zamkniety'] );

$iskt_online = iskt_opis_terminu( 31 );
sprawdz( 'przy terminie online lokalizacja nie udaje adresu', 'Online', $iskt_online['miejsce'] );
sprawdz( 'zamknięte zgłoszenia rozpoznane', true, $iskt_online['zamkniety'] );

$iskt_indywidualny = iskt_opis_terminu( 32 );
sprawdz( 'termin indywidualny ma własną etykietę', 'Termin ustalany indywidualnie', $iskt_indywidualny['etykieta'] );
sprawdz( 'nieznany status nie jest wyświetlany', '', $iskt_indywidualny['status'] );

// --- Sekcje bez treści -----------------------------------------------------

/*
 * Reguła z §4.1 w jednym zdaniu: pusta oprawa znika, ale sekcja złożona z samego
 * zdjęcia albo formularza zostaje. Brak tekstu to nie brak treści.
 */
require_once dirname( __DIR__ ) . '/wp-content/themes/iskt-szkolenia/inc/sekcje.php';

sprawdz( 'pusty ciąg to brak treści', false, iskt_sekcja_ma_tresc( '' ) );
sprawdz( 'same białe znaki to brak treści', false, iskt_sekcja_ma_tresc( "  \n\t " ) );
sprawdz( 'sama oprawa bez wnętrza to brak treści', false, iskt_sekcja_ma_tresc( '<div class="iskt-section"></div>' ) );
sprawdz( 'akapit z twardej spacji to brak treści', false, iskt_sekcja_ma_tresc( '<div><p>&nbsp;</p></div>' ) );
sprawdz( 'twarda spacja w bajtach też nie jest treścią', false, iskt_sekcja_ma_tresc( "<div><p>\u{00A0}</p></div>" ) );
sprawdz( 'tekst jest treścią', true, iskt_sekcja_ma_tresc( '<div><p>Obszary szkoleń</p></div>' ) );
sprawdz( 'samo zdjęcie jest treścią', true, iskt_sekcja_ma_tresc( '<div><img src="a.jpg" alt=""></div>' ) );
sprawdz( 'sam film jest treścią', true, iskt_sekcja_ma_tresc( '<div><video src="a.mp4"></video></div>' ) );
sprawdz( 'osadzona mapa jest treścią', true, iskt_sekcja_ma_tresc( '<div><iframe src="https://example.org"></iframe></div>' ) );
sprawdz( 'samo pole formularza jest treścią', true, iskt_sekcja_ma_tresc( '<div><input type="email"></div>' ) );
sprawdz( 'nazwa klasy nie udaje treści', false, iskt_sekcja_ma_tresc( '<div class="obrazek image video"></div>' ) );

// --- Wynik -----------------------------------------------------------------

printf( "\n%d przeszło, %d nie przeszło\n", $GLOBALS['iskt_ok'], $GLOBALS['iskt_bledy'] );

exit( $GLOBALS['iskt_bledy'] > 0 ? 1 : 0 );
