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
const ISKT_CPT_ZGLOSZENIE        = 'iskt_zgloszenie';
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
require_once $sciezka . 'relacje.php';
require_once $sciezka . 'terminy.php';
require_once $sciezka . 'kategorie.php';
require_once $sciezka . 'odbiorca.php';
require_once $sciezka . 'prezentacja.php';
require_once $sciezka . 'teksty.php';
require_once $sciezka . 'demo.php';
require_once $sciezka . 'formularz.php';
require_once $sciezka . 'katalog.php';

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

// --- Etykiety powiązań w panelu --------------------------------------------

/*
 * Regresja z ISK-27: lista wyboru szkolenia przy terminie pokazywała sam tytuł,
 * więc dwa szkolenia o identycznym tytule były dla właściciela nierozróżnialne.
 */

$iskt_rozne = array(
	new WP_Post( 41, 'ESG w praktyce', 'esg-w-praktyce' ),
	new WP_Post( 42, 'Audyt wewnętrzny', 'audyt-wewnetrzny' ),
);

sprawdz(
	'różne tytuły zostają bez dopisku',
	array(
		41 => 'ESG w praktyce',
		42 => 'Audyt wewnętrzny',
	),
	iskt_etykiety_relacji( $iskt_rozne )
);

$iskt_bliznieta = array(
	new WP_Post( 51, 'ESG w praktyce', 'esg-w-praktyce' ),
	new WP_Post( 52, 'ESG w praktyce', 'esg-w-praktyce-2' ),
);

sprawdz(
	'identyczne tytuły rozróżnia slug',
	array(
		51 => 'ESG w praktyce (esg-w-praktyce)',
		52 => 'ESG w praktyce (esg-w-praktyce-2)',
	),
	iskt_etykiety_relacji( $iskt_bliznieta )
);

$iskt_kopia = array(
	new WP_Post( 61, 'ESG w praktyce', 'esg-w-praktyce' ),
	new WP_Post( 62, 'ESG w praktyce', 'esg-w-praktyce-2', 'draft' ),
);

sprawdz(
	'kopia robocza jest opisana statusem',
	array(
		61 => 'ESG w praktyce (esg-w-praktyce)',
		62 => 'ESG w praktyce (szkic, esg-w-praktyce-2)',
	),
	iskt_etykiety_relacji( $iskt_kopia )
);

$iskt_bez_sluga = array(
	new WP_Post( 71, 'ESG w praktyce', 'esg-w-praktyce' ),
	new WP_Post( 72, 'ESG w praktyce', '', 'draft' ),
);

sprawdz(
	'wpis roboczy bez sluga opisuje sam status',
	array(
		71 => 'ESG w praktyce (esg-w-praktyce)',
		72 => 'ESG w praktyce (szkic)',
	),
	iskt_etykiety_relacji( $iskt_bez_sluga )
);

/*
 * Opublikowany wpis bez sluga nie powstanie z panelu, ale powstaje z importu
 * i z wpisu do bazy. Nie ma wtedy ani statusu, ani sluga do pokazania.
 */
$iskt_import = array(
	new WP_Post( 76, 'ESG w praktyce', '' ),
	new WP_Post( 77, 'ESG w praktyce', 'esg-w-praktyce' ),
);

sprawdz(
	'wpis bez sluga i bez statusu dostaje identyfikator',
	array(
		76 => 'ESG w praktyce (#76)',
		77 => 'ESG w praktyce (esg-w-praktyce)',
	),
	iskt_etykiety_relacji( $iskt_import )
);

sprawdz(
	'wpis bez tytułu jest nazwany',
	array( 81 => '(bez tytułu)' ),
	iskt_etykiety_relacji( array( new WP_Post( 81, '   ', 'nowe-szkolenie' ) ) )
);

/*
 * Przypadek graniczny, ale to on decyduje o tym, czy etykieta jest gwarancją,
 * czy tylko zwykle wystarcza: dwa szkice o tym samym tytule, bez slugów, więc
 * status ani slug niczego nie rozróżniają.
 */
$iskt_nierozroznialne = array(
	new WP_Post( 91, 'ESG w praktyce', '', 'draft' ),
	new WP_Post( 92, 'ESG w praktyce', '', 'draft' ),
);

sprawdz(
	'identyczne wpisy rozróżnia identyfikator',
	array(
		91 => 'ESG w praktyce (szkic, #91)',
		92 => 'ESG w praktyce (szkic, #92)',
	),
	iskt_etykiety_relacji( $iskt_nierozroznialne )
);

sprawdz(
	'żadna etykieta na liście nie powtarza się',
	true,
	( static function (): bool {
		$etykiety = iskt_etykiety_relacji(
			array(
				new WP_Post( 101, 'ESG w praktyce', 'esg-w-praktyce' ),
				new WP_Post( 102, 'ESG w praktyce', 'esg-w-praktyce-2', 'draft' ),
				new WP_Post( 103, 'ESG w praktyce', '', 'draft' ),
				new WP_Post( 104, 'ESG w praktyce', '', 'pending' ),
				new WP_Post( 105, '', '', 'draft' ),
				new WP_Post( 106, '', '', 'draft' ),
			)
		);

		return count( $etykiety ) === count( array_unique( $etykiety ) );
	} )()
);

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

// --- Teksty globalne -------------------------------------------------------

$GLOBALS['iskt_test_opcje'][ ISKT_OPCJA_TEKSTY ] = array();

sprawdz(
	'bez nadpisania wraca treść domyślna',
	'Katalog szkoleń',
	iskt_tekst( 'katalog_tytul' )
);

sprawdz(
	'klucz spoza rejestru nie wysadza szablonu',
	'',
	iskt_tekst( 'klucz_ktorego_nie_ma' )
);

$GLOBALS['iskt_test_opcje'][ ISKT_OPCJA_TEKSTY ] = array(
	'katalog_tytul' => 'Nasze szkolenia',
	'kontakt_email' => 'biuro@example.org',
);

sprawdz( 'nadpisanie ma pierwszeństwo', 'Nasze szkolenia', iskt_tekst( 'katalog_tytul' ) );
sprawdz( 'nadpisany adres e-mail wraca', 'biuro@example.org', iskt_tekst( 'kontakt_email' ) );
sprawdz( 'nienadpisany klucz nadal domyślny', 'Wyczyść filtry', iskt_tekst( 'katalog_filtr_wyczysc' ) );

$GLOBALS['iskt_test_opcje'][ ISKT_OPCJA_TEKSTY ] = array( 'katalog_tytul' => '   ' );

sprawdz(
	'nadpisanie z samych spacji nie kasuje napisu',
	'Katalog szkoleń',
	iskt_tekst( 'katalog_tytul' )
);

$GLOBALS['iskt_test_opcje'][ ISKT_OPCJA_TEKSTY ] = array();

sprawdz(
	'uszkodzona opcja nie przewraca serwisu',
	'Katalog szkoleń',
	( static function (): string {
		$GLOBALS['iskt_test_opcje'][ ISKT_OPCJA_TEKSTY ] = 'nie tablica';

		$wynik = iskt_tekst( 'katalog_tytul' );

		$GLOBALS['iskt_test_opcje'][ ISKT_OPCJA_TEKSTY ] = array();

		return $wynik;
	} )()
);

sprawdz( 'tekst jednowierszowy traci znaczniki', 'Zapisy otwarte', iskt_sanitize_tekst( '<b>Zapisy otwarte</b>', 'linia' ) );
sprawdz( 'obszar zachowuje wiersze', "Pierwszy\nDrugi", iskt_sanitize_tekst( "Pierwszy\nDrugi ", 'obszar' ) );
sprawdz( 'niepoprawny adres e-mail odrzucony', '', iskt_sanitize_tekst( 'to nie jest adres', 'email' ) );
sprawdz( 'poprawny adres e-mail przechodzi', 'szkolenia@iskt.pl', iskt_sanitize_tekst( ' szkolenia@iskt.pl ', 'email' ) );
sprawdz( 'adres javascript odrzucony', '', iskt_sanitize_tekst( 'javascript:alert(1)', 'adres_www' ) );
sprawdz( 'adres https przechodzi', 'https://iskt.pl/prywatnosc', iskt_sanitize_tekst( 'https://iskt.pl/prywatnosc', 'adres_www' ) );
sprawdz( 'telefon zachowuje format', '+48 32 000 00 00', iskt_sanitize_tekst( '+48 32 000 00 00', 'telefon' ) );
sprawdz( 'tekst w polu telefonu odsiany', '48 123', iskt_sanitize_tekst( 'tel 48 123', 'telefon' ) );
sprawdz( 'tablica w polu tekstowym nie wysadza zapisu', '', iskt_sanitize_tekst( array( 'a' ), 'linia' ) );
sprawdz( 'nieznany typ traktowany jak jeden wiersz', 'Tekst', iskt_sanitize_tekst( '<i>Tekst</i>', 'wymyslony' ) );

sprawdz(
	'zapis pomija klucze spoza rejestru',
	array( 'katalog_tytul' => 'Oferta' ),
	iskt_sanitize_teksty(
		array(
			'katalog_tytul' => 'Oferta',
			'cudzy_klucz'   => 'wartość',
		)
	)
);

sprawdz(
	'wartość równa domyślnej nie jest zapisywana',
	array(),
	iskt_sanitize_teksty( array( 'katalog_tytul' => 'Katalog szkoleń' ) )
);

sprawdz(
	'puste pole oznacza powrót do treści domyślnej',
	array(),
	iskt_sanitize_teksty( array( 'katalog_tytul' => '' ) )
);

sprawdz( 'zapis czegoś, co nie jest tablicą, daje pustkę', array(), iskt_sanitize_teksty( 'ciąg' ) );

sprawdz(
	'każdy klucz rejestru ma etykietę i typ',
	true,
	( static function (): bool {
		foreach ( iskt_definicje_tekstow() as $definicja ) {
			if ( '' === (string) ( $definicja['etykieta'] ?? '' ) || '' === (string) ( $definicja['typ'] ?? '' ) ) {
				return false;
			}
		}

		return true;
	} )()
);

sprawdz(
	'klucze tekstów są unikalne między grupami',
	true,
	( static function (): bool {
		$policzone = 0;

		foreach ( iskt_rejestr_tekstow() as $grupa ) {
			$policzone += count( $grupa['pola'] ?? array() );
		}

		return count( iskt_definicje_tekstow() ) === $policzone;
	} )()
);

// --- Katalog: parametry z adresu -------------------------------------------

/*
 * Parametry katalogu przychodzą z adresu, czyli od kogokolwiek. Sanityzacja jest
 * jedynym miejscem, w którym to sprawdzamy — dalej wartość idzie prosto do
 * zapytania i do pola formularza.
 */

sprawdz( 'slug filtra przechodzi bez zmian', 'esg-i-zrownowazony-rozwoj', iskt_sanitize_filtr_slug( 'esg-i-zrownowazony-rozwoj' ) );
sprawdz( 'wielkie litery sprowadzone do małych', 'online', iskt_sanitize_filtr_slug( 'ONLINE' ) );
sprawdz( 'polskie znaki w slugu zostają', 'język-angielski', iskt_sanitize_filtr_slug( 'język-angielski' ) );
sprawdz( 'znaczniki nie przechodzą przez filtr', 'scriptalert1script', iskt_sanitize_filtr_slug( '<script>alert(1)</script>' ) );
sprawdz( 'apostrof i cudzysłów wycięte', 'onlineor1', iskt_sanitize_filtr_slug( "online' OR 1" ) );
sprawdz( 'tablica w parametrze nie wysadza widoku', '', iskt_sanitize_filtr_slug( array( 'esg' ) ) );
sprawdz( 'pusty parametr to brak filtra', '', iskt_sanitize_filtr_slug( '' ) );
sprawdz( 'zbyt długi slug przycięty', ISKT_KATALOG_MAX_SLUG, mb_strlen( iskt_sanitize_filtr_slug( str_repeat( 'a', 500 ) ) ) );

sprawdz( 'fraza traci znaczniki', 'alert(1)', iskt_sanitize_filtr_fraza( '<b>alert(1)</b>' ) );
sprawdz( 'fraza traci skrajne spacje', 'esg', iskt_sanitize_filtr_fraza( '  esg  ' ) );
sprawdz( 'fraza z polskimi znakami zostaje', 'księgowość dla firm', iskt_sanitize_filtr_fraza( 'księgowość dla firm' ) );
sprawdz( 'zbyt długa fraza przycięta', ISKT_KATALOG_MAX_FRAZA, mb_strlen( iskt_sanitize_filtr_fraza( str_repeat( 'ą', 400 ) ) ) );
sprawdz( 'tablica w frazie nie wysadza widoku', '', iskt_sanitize_filtr_fraza( array( 'esg' ) ) );

sprawdz(
	'komplet parametrów czytany z adresu',
	array(
		'szukaj'    => 'ESG',
		'kategoria' => 'esg-i-zrownowazony-rozwoj',
		'forma'     => 'online',
	),
	iskt_parametry_katalogu(
		array(
			ISKT_PARAM_SZUKAJ    => 'ESG',
			ISKT_PARAM_KATEGORIA => 'esg-i-zrownowazony-rozwoj',
			ISKT_PARAM_FORMA     => 'online',
		)
	)
);

sprawdz(
	'brak parametrów daje pusty stan',
	array(
		'szukaj'    => '',
		'kategoria' => '',
		'forma'     => '',
	),
	iskt_parametry_katalogu( array() )
);

sprawdz(
	'cudzy parametr w adresie jest pomijany',
	array(
		'szukaj'    => '',
		'kategoria' => '',
		'forma'     => '',
	),
	iskt_parametry_katalogu( array( 'post_status' => 'draft' ) )
);

sprawdz( 'pusty stan to katalog niefiltrowany', false, iskt_katalog_jest_filtrowany( iskt_parametry_katalogu( array() ) ) );
sprawdz( 'sama fraza to już filtrowanie', true, iskt_katalog_jest_filtrowany( iskt_parametry_katalogu( array( ISKT_PARAM_SZUKAJ => 'ai' ) ) ) );
sprawdz( 'sama kategoria to już filtrowanie', true, iskt_katalog_jest_filtrowany( iskt_parametry_katalogu( array( ISKT_PARAM_KATEGORIA => 'ai' ) ) ) );
sprawdz( 'sama forma to już filtrowanie', true, iskt_katalog_jest_filtrowany( iskt_parametry_katalogu( array( ISKT_PARAM_FORMA => 'online' ) ) ) );
sprawdz( 'fraza z samych spacji nie jest filtrowaniem', false, iskt_katalog_jest_filtrowany( iskt_parametry_katalogu( array( ISKT_PARAM_SZUKAJ => '   ' ) ) ) );

// --- Katalog: argumenty zapytania ------------------------------------------

$iskt_katalog_pusty = iskt_argumenty_katalogu( iskt_parametry_katalogu( array() ) );

sprawdz( 'katalog bez filtrów nie szuka frazy', false, isset( $iskt_katalog_pusty['s'] ) );
sprawdz( 'katalog bez filtrów nie zawęża taksonomii', false, isset( $iskt_katalog_pusty['tax_query'] ) );
sprawdz( 'katalog stronicuje od pierwszego dnia', iskt_katalog_na_strone(), $iskt_katalog_pusty['posts_per_page'] );
sprawdz( 'wyróżnione idą przed resztą', 'DESC', $iskt_katalog_pusty['orderby']['wyroznione'] );
sprawdz( 'po wyróżnieniu decyduje kolejność właściciela', 'ASC', $iskt_katalog_pusty['orderby']['menu_order'] );

/*
 * Najważniejszy przypadek całego katalogu: szkolenie, którego nikt nigdy nie
 * wyróżnił, NIE MA pola `_iskt_wyroznione`. Zwykłe sortowanie po `meta_key`
 * wycięłoby je z listy — katalog pokazywałby wyłącznie szkolenia wyróżnione,
 * wyglądając przy tym na kompletny.
 */
sprawdz( 'sortowanie po wyróżnieniu nie wycina reszty katalogu', 'OR', $iskt_katalog_pusty['meta_query']['relation'] );
sprawdz( 'szkolenie bez pola wyróżnienia zostaje w wynikach', 'NOT EXISTS', $iskt_katalog_pusty['meta_query'][0]['compare'] );

$iskt_katalog_fraza = iskt_argumenty_katalogu( iskt_parametry_katalogu( array( ISKT_PARAM_SZUKAJ => 'automatyzacja' ) ) );

sprawdz( 'fraza trafia do wyszukiwania zapytania głównego', 'automatyzacja', $iskt_katalog_fraza['s'] );
sprawdz( 'sama fraza nie zawęża taksonomii', false, isset( $iskt_katalog_fraza['tax_query'] ) );

$iskt_katalog_kategoria = iskt_argumenty_katalogu( iskt_parametry_katalogu( array( ISKT_PARAM_KATEGORIA => 'projekty-br' ) ) );

sprawdz( 'filtr kategorii pyta o slug', 'slug', $iskt_katalog_kategoria['tax_query'][0]['field'] );
sprawdz( 'filtr kategorii trafia we właściwą taksonomię', ISKT_TAX_KATEGORIA, $iskt_katalog_kategoria['tax_query'][0]['taxonomy'] );
sprawdz( 'filtr kategorii przenosi slug z adresu', 'projekty-br', $iskt_katalog_kategoria['tax_query'][0]['terms'] );

$iskt_katalog_oba = iskt_argumenty_katalogu(
	iskt_parametry_katalogu(
		array(
			ISKT_PARAM_KATEGORIA => 'esg-i-zrownowazony-rozwoj',
			ISKT_PARAM_FORMA     => 'online',
		)
	)
);

sprawdz( 'dwa filtry zawężają razem, nie po jednym', 'AND', $iskt_katalog_oba['tax_query']['relation'] );
sprawdz( 'drugi filtr dotyczy formy realizacji', ISKT_TAX_FORMA, $iskt_katalog_oba['tax_query'][1]['taxonomy'] );

// --- Inicjały (znak zastępczy zamiast zdjęcia trenera) ----------------------

sprawdz( 'imię i nazwisko dają dwie litery', 'AK', iskt_inicjaly( 'Anna Kowalska' ) );
sprawdz( 'polskie znaki zostają polskimi znakami', 'ŻŚ', iskt_inicjaly( 'Żaneta Śliwa' ) );
sprawdz( 'małe litery idą na wersaliki', 'JN', iskt_inicjaly( 'jan nowak' ) );

/*
 * Dane demonstracyjne (§9) noszą przedrostek „Demo — ”. Myślnik nie jest imieniem,
 * więc inicjał „D—” byłby usterką widoczną na każdym kaflu listy trenerów.
 */
sprawdz( 'człon bez litery jest pomijany', 'DA', iskt_inicjaly( 'Demo — Anna Kowalska' ) );

sprawdz( 'trzeci człon nie wchodzi do inicjałów', 'AM', iskt_inicjaly( '  Anna   Maria  Kowalska ' ) );
sprawdz( 'jedno słowo daje jedną literę', 'J', iskt_inicjaly( 'Jan' ) );
sprawdz( 'pusta nazwa nie daje znaku zastępczego', '', iskt_inicjaly( '' ) );
sprawdz( 'sama interpunkcja nie daje znaku zastępczego', '', iskt_inicjaly( ' — · ' ) );

// --- Formularz zgłoszeniowy ------------------------------------------------

/*
 * Walidacja formularza. Sprawdzamy ją tutaj, a nie tylko w przeglądarce, bo tylko
 * ta warstwa jest zabezpieczeniem: żądanie POST da się złożyć bez `required`
 * i bez `type="email"`. Test odtwarza więc dane tak, jak przychodzą z sieci.
 */

$GLOBALS['iskt_test_opcje'][ ISKT_OPCJA_TEKSTY ] = array();

$iskt_komplet = array(
	'iskt_typ'       => 'firma',
	'iskt_imie'      => 'Anna Kowalska',
	'iskt_email'     => 'anna@example.org',
	'iskt_telefon'   => '+48 32 000 00 00',
	'iskt_firma'     => 'Przykład sp. z o.o.',
	'iskt_temat'     => 's-12',
	'iskt_termin'    => '34',
	'iskt_wiadomosc' => "Proszę o ofertę.\nDla ośmiu osób.",
);

$iskt_wynik = iskt_waliduj_zgloszenie( $iskt_komplet );

sprawdz( 'komplet danych przechodzi bez błędów', array(), $iskt_wynik['bledy'] );
sprawdz( 'rodzaj odbiorcy zachowany', 'firma', $iskt_wynik['dane']['typ'] );
sprawdz( 'adres e-mail znormalizowany', 'anna@example.org', $iskt_wynik['dane']['email'] );
sprawdz( 'telefon zachowuje zapis międzynarodowy', '+48 32 000 00 00', $iskt_wynik['dane']['telefon'] );
sprawdz( 'wiadomość zachowuje złamania wiersza', "Proszę o ofertę.\nDla ośmiu osób.", $iskt_wynik['dane']['wiadomosc'] );

$iskt_puste = iskt_waliduj_zgloszenie( array() );

sprawdz(
	'brak wymaganych pól daje trzy błędy przy polach',
	array( 'imie', 'wiadomosc', 'email' ),
	array_keys( $iskt_puste['bledy'] )
);
sprawdz( 'komunikat pola wymaganego pochodzi z rejestru', 'To pole jest wymagane.', $iskt_puste['bledy']['imie'] );
sprawdz( 'przy braku wyboru rodzaj wraca do osoby', 'osoba', $iskt_puste['dane']['typ'] );

/*
 * Same spacje przechodzą przez `required` przeglądarki, więc gdyby walidacja
 * serwerowa ich nie odsiewała, do skrzynki trafiłoby zgłoszenie bez nadawcy.
 */
$iskt_spacje = iskt_waliduj_zgloszenie(
	array(
		'iskt_imie'      => '   ',
		'iskt_email'     => 'anna@example.org',
		'iskt_wiadomosc' => "\t\n ",
	)
);

sprawdz( 'samo białe pole nie jest wypełnieniem', 'To pole jest wymagane.', $iskt_spacje['bledy']['imie'] );
sprawdz( 'pusta wiadomość odrzucona', 'To pole jest wymagane.', $iskt_spacje['bledy']['wiadomosc'] );

$iskt_zly_adres = iskt_waliduj_zgloszenie(
	array(
		'iskt_imie'      => 'Jan',
		'iskt_email'     => 'anna@example',
		'iskt_wiadomosc' => 'Pytanie',
	)
);

sprawdz(
	'niepoprawny adres ma własny komunikat, nie „pole wymagane”',
	'Podaj adres e-mail w formacie nazwa@domena.pl.',
	$iskt_zly_adres['bledy']['email']
);

/*
 * Najważniejsza asercja tej grupy: błędny adres WRACA do formularza. Wyczyszczenie
 * pola przy błędzie kazałoby wpisywać wszystko od nowa — a to jest powód, dla
 * którego formularze z ośmioma polami zostają niewysłane.
 */
sprawdz( 'wpisany adres zostaje mimo błędu', 'anna@example', $iskt_zly_adres['dane']['email'] );

$iskt_znaczniki = iskt_waliduj_zgloszenie(
	array(
		'iskt_imie'      => '<script>alert(1)</script>Jan Nowak',
		'iskt_email'     => 'jan@example.org',
		'iskt_wiadomosc' => 'Pytanie',
	)
);

sprawdz( 'znaczniki nie przechodzą przez walidację', 'alert(1)Jan Nowak', $iskt_znaczniki['dane']['imie'] );

sprawdz( 'rodzaj spoza listy wraca do wartości domyślnej', 'osoba', iskt_waliduj_zgloszenie( array( 'iskt_typ' => 'kosmita' ) )['dane']['typ'] );

sprawdz( 'poprawny wybór szkolenia przechodzi', 's-12', iskt_sanitize_temat( 's-12' ) );
sprawdz( 'poprawny wybór obszaru przechodzi', 'k-3', iskt_sanitize_temat( 'k-3' ) );
sprawdz( 'wybór z zerowym identyfikatorem odrzucony', '', iskt_sanitize_temat( 's-0' ) );
sprawdz( 'nieznany przedrostek odrzucony', '', iskt_sanitize_temat( 'x-9' ) );
sprawdz( 'wstrzyknięcie w polu wyboru odrzucone', '', iskt_sanitize_temat( '<script>alert(1)</script>' ) );
sprawdz( 'brak wyboru oznacza zapytanie ogólne', '', iskt_sanitize_temat( '' ) );
sprawdz( 'identyfikator terminu sprowadzony do liczby', '34', iskt_sanitize_identyfikator( '34' ) );
sprawdz( 'tekst w polu terminu odrzucony', '', iskt_sanitize_identyfikator( 'abc' ) );

// --- Treść wiadomości ------------------------------------------------------

$iskt_dane_min = array(
	'typ'             => 'osoba',
	'imie'            => 'Anna Kowalska',
	'email'           => 'anna@example.org',
	'telefon'         => '',
	'firma'           => '',
	'szkolenie'       => '',
	'termin_etykieta' => '',
	'wiadomosc'       => 'Proszę o ofertę.',
	'adres'           => '',
);

sprawdz(
	'wiadomość pomija pola, których nikt nie wypełnił',
	"Piszę jako: Osoba indywidualna\nImię i nazwisko: Anna Kowalska\nAdres e-mail: anna@example.org\n\nWiadomość:\nProszę o ofertę.\n",
	iskt_tresc_zgloszenia( $iskt_dane_min )
);

$iskt_dane_pelne = array_merge(
	$iskt_dane_min,
	array(
		'typ'             => 'firma',
		'telefon'         => '+48 32 000 00 00',
		'firma'           => 'Przykład sp. z o.o.',
		'szkolenie'       => 'ESG w praktyce',
		'termin_etykieta' => '2026-11-03',
		'adres'           => 'https://szkolenia.example/zgloszenie/',
	)
);

$iskt_tresc = iskt_tresc_zgloszenia( $iskt_dane_pelne );

sprawdz( 'wiadomość niesie rodzaj odbiorcy', true, str_contains( $iskt_tresc, 'Piszę jako: Firma' ) );
sprawdz( 'wiadomość niesie nazwę szkolenia', true, str_contains( $iskt_tresc, 'Szkolenie lub obszar zainteresowania: ESG w praktyce' ) );
sprawdz( 'wiadomość niesie termin', true, str_contains( $iskt_tresc, 'Wybrany termin: 2026-11-03' ) );
sprawdz( 'wiadomość niesie adres strony wysyłki', true, str_contains( $iskt_tresc, 'https://szkolenia.example/zgloszenie/' ) );

sprawdz(
	'temat wiadomości niesie kontekst szkolenia',
	'Zapytanie ze strony: ESG w praktyce',
	iskt_temat_zgloszenia( $iskt_dane_pelne )
);
sprawdz(
	'zapytanie bez wskazanej oferty ma czytelny temat',
	'Zapytanie ze strony: Zapytanie ogólne — opiszę w wiadomości',
	iskt_temat_zgloszenia( $iskt_dane_min )
);

/*
 * Etykiety w wiadomości pochodzą z tego samego rejestru, co etykiety pól — zmiana
 * napisu w panelu ma być widoczna w obu miejscach naraz (§4.7).
 */
$GLOBALS['iskt_test_opcje'][ ISKT_OPCJA_TEKSTY ] = array( 'formularz_imie' => 'Kto pisze' );

sprawdz(
	'etykieta z panelu wchodzi do wiadomości',
	true,
	str_contains( iskt_tresc_zgloszenia( $iskt_dane_min ), 'Kto pisze: Anna Kowalska' )
);

$GLOBALS['iskt_test_opcje'][ ISKT_OPCJA_TEKSTY ] = array();

// --- Kontekst zgłoszenia pobrany z katalogu --------------------------------

/*
 * Identyfikator szkolenia i terminu przychodzi z adresu oraz z pól formularza, więc
 * jest do podmienienia. Wiadomość ma opisywać wyłącznie to, co odwiedzający naprawdę
 * mógł wybrać na stronie — inaczej ISKT odpisuje na ustalenia, których nikt nie
 * proponował, albo potwierdza ofertę wycofaną z publikacji (§11 pkt 5).
 */

$GLOBALS['iskt_test_typy'] = array(
	201 => ISKT_CPT_SZKOLENIE,
	202 => ISKT_CPT_SZKOLENIE,
	301 => ISKT_CPT_TERMIN,
	302 => ISKT_CPT_TERMIN,
	303 => ISKT_CPT_TERMIN,
);

$GLOBALS['iskt_test_wpisy'] = array(
	201 => new WP_Post( 201, 'ESG w praktyce', 'esg-w-praktyce' ),
	202 => new WP_Post( 202, 'Szkolenie wycofane z oferty', 'wycofane', 'draft' ),
	301 => new WP_Post( 301, 'Termin szkolenia 201', 'termin-201' ),
	302 => new WP_Post( 302, 'Termin szkolenia 202', 'termin-202' ),
	303 => new WP_Post( 303, 'Termin niepublikowany', 'termin-roboczy', 'draft' ),
);

$GLOBALS['iskt_test_pojecia'] = array(
	5 => new WP_Term( 5, 'ESG i zrównoważony rozwój' ),
);

$GLOBALS['iskt_test_pola'] = array(
	301 => array(
		ISKT_META_TERMIN_SZKOLENIE  => 201,
		'_iskt_termin_indywidualny' => '1',
	),
	302 => array(
		ISKT_META_TERMIN_SZKOLENIE  => 202,
		'_iskt_termin_indywidualny' => '1',
	),
	303 => array(
		ISKT_META_TERMIN_SZKOLENIE  => 201,
		'_iskt_termin_indywidualny' => '1',
	),
);

/**
 * Zwraca kontekst zgłoszenia dla wybranego tematu i terminu.
 *
 * @param string $temat  Wartość pola „szkolenie lub obszar zainteresowania”.
 * @param string $termin Wartość pola terminu.
 *
 * @return array<string, string>
 */
$iskt_kontekst = static fn ( string $temat, string $termin = '' ): array => iskt_kontekst_zgloszenia(
	array(
		'temat'  => $temat,
		'termin' => $termin,
	)
);

sprawdz( 'opublikowane szkolenie wchodzi do wiadomości pod nazwą', 'ESG w praktyce', $iskt_kontekst( 's-201' )['szkolenie'] );
sprawdz( 'kategoria wchodzi do wiadomości pod nazwą', 'ESG i zrównoważony rozwój', $iskt_kontekst( 'k-5' )['szkolenie'] );

/*
 * Sedno grupy. Szkolenie wycofane z publikacji zniknęło z listy wyboru, więc nikt
 * nie mógł go zaznaczyć — nazwanie go w wiadomości opisywałoby ofertę, której na
 * stronie nie ma.
 */
sprawdz( 'szkolenie wycofane z publikacji nie trafia do wiadomości', '', $iskt_kontekst( 's-202' )['szkolenie'] );
sprawdz( 'nieistniejące szkolenie nie trafia do wiadomości', '', $iskt_kontekst( 's-999' )['szkolenie'] );
sprawdz( 'termin podstawiony jako temat nie udaje szkolenia', '', $iskt_kontekst( 's-301' )['szkolenie'] );

sprawdz(
	'termin wybranego szkolenia wchodzi do wiadomości',
	'Termin ustalany indywidualnie',
	$iskt_kontekst( 's-201', '301' )['termin_etykieta']
);

/*
 * Termin z innego szkolenia to przypadek podmiany, a nie pomyłki: pole terminu
 * pokazuje wyłącznie daty wybranego szkolenia.
 */
sprawdz( 'termin obcego szkolenia jest pomijany', '', $iskt_kontekst( 's-201', '302' )['termin_etykieta'] );
sprawdz( 'termin niepublikowany jest pomijany', '', $iskt_kontekst( 's-201', '303' )['termin_etykieta'] );
sprawdz( 'termin bez wybranego szkolenia jest pomijany', '', $iskt_kontekst( '', '301' )['termin_etykieta'] );
sprawdz( 'termin przy zapytaniu o kategorię jest pomijany', '', $iskt_kontekst( 'k-5', '301' )['termin_etykieta'] );

// --- Nagłówek Reply-To -----------------------------------------------------

sprawdz(
	'adres zgłaszającego trafia do Reply-To, nie do nadawcy',
	'Reply-To: "Anna Kowalska" <anna@example.org>',
	iskt_naglowek_reply_to( 'Anna Kowalska', 'anna@example.org' )
);

/*
 * Wstrzyknięcie do nagłówków poczty: gdyby złamanie wiersza przeszło, treść po nim
 * stałaby się kolejnym nagłówkiem i wiadomość poszłaby także pod obcy adres.
 */
sprawdz(
	'złamanie wiersza nie dokłada nagłówka',
	'Reply-To: "Anna Bcc  obcy@example.net" <anna@example.org>',
	iskt_naglowek_reply_to( "Anna\r\nBcc: obcy@example.net", 'anna@example.org' )
);

sprawdz( 'niepoprawny adres nie daje nagłówka', '', iskt_naglowek_reply_to( 'Anna', 'anna@example' ) );
sprawdz( 'brak imienia daje sam adres', 'Reply-To: anna@example.org', iskt_naglowek_reply_to( '  ', 'anna@example.org' ) );

// --- Ochrona antyspamowa ---------------------------------------------------

/**
 * Buduje stan żądania przechodzącego ochronę, z możliwością podmiany jednego pola.
 *
 * @param array<string, mixed> $zmiany Pola do nadpisania.
 *
 * @return array<string, mixed>
 */
function iskt_stan_antyspamowy( array $zmiany = array() ): array {
	return array_merge(
		array(
			'nonce_ok'       => true,
			'limit_aktywny'  => false,
			'pulapka'        => '',
			'otwarto'        => 1000,
			'podpis_ok'      => true,
			'teraz'          => 1010,
			'minimalny_czas' => 3,
		),
		$zmiany
	);
}

sprawdz( 'zwykłe zgłoszenie przechodzi ochronę', '', iskt_ocena_antyspamowa( iskt_stan_antyspamowy() ) );
sprawdz( 'brak poprawnego nonce zatrzymuje wysyłkę', 'nonce', iskt_ocena_antyspamowa( iskt_stan_antyspamowy( array( 'nonce_ok' => false ) ) ) );
sprawdz( 'limit z jednego adresu IP ma własny powód', 'limit', iskt_ocena_antyspamowa( iskt_stan_antyspamowy( array( 'limit_aktywny' => true ) ) ) );
sprawdz( 'wypełniona pułapka zatrzymuje wysyłkę', 'antyspam', iskt_ocena_antyspamowa( iskt_stan_antyspamowy( array( 'pulapka' => 'https://spam.example' ) ) ) );
sprawdz( 'sama spacja w pułapce nie jest wypełnieniem', '', iskt_ocena_antyspamowa( iskt_stan_antyspamowy( array( 'pulapka' => '  ' ) ) ) );
sprawdz( 'wysyłka szybsza niż minimalny czas odrzucona', 'antyspam', iskt_ocena_antyspamowa( iskt_stan_antyspamowy( array( 'teraz' => 1002 ) ) ) );
sprawdz( 'wysyłka dokładnie po minimalnym czasie przechodzi', '', iskt_ocena_antyspamowa( iskt_stan_antyspamowy( array( 'teraz' => 1003 ) ) ) );
sprawdz( 'podrobiony znacznik czasu odrzucony', 'antyspam', iskt_ocena_antyspamowa( iskt_stan_antyspamowy( array( 'podpis_ok' => false ) ) ) );
sprawdz( 'znacznik z przyszłości odrzucony', 'antyspam', iskt_ocena_antyspamowa( iskt_stan_antyspamowy( array( 'otwarto' => 2000 ) ) ) );
sprawdz( 'brak znacznika czasu odrzucony', 'antyspam', iskt_ocena_antyspamowa( iskt_stan_antyspamowy( array( 'otwarto' => 0 ) ) ) );

/*
 * Kolejność sprawdzeń jest częścią zachowania: wygasły formularz to najczęstszy
 * przypadek u człowieka i ma dostać komunikat o wygaśnięciu, a nie o spamie.
 */
sprawdz(
	'wygasły nonce ma pierwszeństwo przed innymi powodami',
	'nonce',
	iskt_ocena_antyspamowa( iskt_stan_antyspamowy( array( 'nonce_ok' => false, 'pulapka' => 'x' ) ) )
);

sprawdz( 'komunikat wygaśnięcia pochodzi z rejestru', iskt_tekst( 'formularz_blad_nonce' ), iskt_komunikat_odrzucenia( 'nonce' ) );
sprawdz( 'komunikat limitu pochodzi z rejestru', iskt_tekst( 'formularz_blad_limit' ), iskt_komunikat_odrzucenia( 'limit' ) );
sprawdz( 'nieznany powód dostaje komunikat antyspamowy', iskt_tekst( 'formularz_blad_antyspam' ), iskt_komunikat_odrzucenia( 'cokolwiek' ) );

/*
 * Każdy napis formularza musi mieć wpis w rejestrze — inaczej „edytowalne z panelu”
 * kończy się na tych kluczach, o których ktoś pamiętał.
 */
sprawdz(
	'rejestr zna każdy napis formularza używany w kodzie',
	true,
	( static function (): bool {
		$definicje = iskt_definicje_tekstow();

		$uzywane = array(
			'formularz_tytul', 'formularz_wstep', 'formularz_typ', 'formularz_typ_osoba',
			'formularz_typ_firma', 'formularz_imie', 'formularz_email', 'formularz_telefon',
			'formularz_firma', 'formularz_szkolenie', 'formularz_szkolenie_ogolne',
			'formularz_grupa_szkolenia', 'formularz_grupa_obszary', 'formularz_termin',
			'formularz_termin_dowolny', 'formularz_wiadomosc', 'formularz_wymagane_opis',
			'formularz_przycisk', 'formularz_zastrzezenie', 'formularz_blad_ogolny',
			'formularz_blad_wymagane', 'formularz_blad_email', 'formularz_blad_wysylki',
			'formularz_blad_nonce', 'formularz_blad_antyspam', 'formularz_blad_limit',
			'formularz_sukces', 'kontakt_email', 'szkolenie_termin_cta',
		);

		foreach ( $uzywane as $klucz ) {
			if ( ! isset( $definicje[ $klucz ] ) ) {
				return false;
			}
		}

		return true;
	} )()
);

sprawdz( 'adres odbiorcy zgłoszeń nie jest wpisany w kod', 'szkolenia@iskt.pl', iskt_tekst( 'kontakt_email' ) );

// --- Dane demonstracyjne (zadanie 12) --------------------------------------

/*
 * Oznaczenie ma dokładnie dwa stany. Trzeci — wartość, której nie przewidzieliśmy —
 * sterowałby kasowaniem treści, więc sanityzacja musi go sprowadzić do jednego z dwóch.
 */
sprawdz( 'jedynka jako ciąg jest oznaczeniem', '1', iskt_sanitize_demo( '1' ) );
sprawdz( 'jedynka jako liczba jest oznaczeniem', '1', iskt_sanitize_demo( 1 ) );
sprawdz( 'prawda jest oznaczeniem', '1', iskt_sanitize_demo( true ) );
sprawdz( 'pusta wartość nie jest oznaczeniem', '', iskt_sanitize_demo( '' ) );
sprawdz( 'fałsz nie jest oznaczeniem', '', iskt_sanitize_demo( false ) );
sprawdz( 'tablica nie wysadza sanityzacji oznaczenia', '', iskt_sanitize_demo( array( '1' ) ) );

/*
 * Najważniejsza asercja tej grupy. Gdyby „demo” znaczyło dowolną niepustą wartość,
 * wpis z polem ustawionym na `0` zostałby skasowany razem z danymi pokazowymi.
 */
sprawdz( 'zero nie jest oznaczeniem', '', iskt_sanitize_demo( '0' ) );
sprawdz( 'słowo „nie” nie jest oznaczeniem', '', iskt_sanitize_demo( 'nie' ) );
sprawdz( 'dowolny tekst nie jest oznaczeniem', '', iskt_sanitize_demo( 'demo' ) );

/*
 * Wpisy: 11 — właściciela, 12 i 13 — demonstracyjne, 14 — z oznaczeniem wyłączonym.
 * Rejestr pól jest wspólny dla całego pliku, więc bierzemy identyfikatory spoza
 * zakresu używanego przez wcześniejsze grupy testów.
 */
$GLOBALS['iskt_test_pola'][11] = array();
$GLOBALS['iskt_test_pola'][12] = array( ISKT_META_DEMO => '1' );
$GLOBALS['iskt_test_pola'][13] = array( ISKT_META_DEMO => '1' );
$GLOBALS['iskt_test_pola'][14] = array( ISKT_META_DEMO => '0' );

sprawdz( 'wpis bez pola nie jest demonstracyjny', false, iskt_czy_demo( 11 ) );
sprawdz( 'wpis z polem jest demonstracyjny', true, iskt_czy_demo( 12 ) );
sprawdz( 'wpis z polem wyłączonym nie jest demonstracyjny', false, iskt_czy_demo( 14 ) );

/*
 * §8 obiecuje właścicielowi, że sprzątanie nie ruszy jego wpisów. Ta asercja jest
 * dosłownym zapisem tej obietnicy: z listy wychodzą wyłącznie oznaczone.
 */
sprawdz(
	'do usunięcia idą tylko wpisy oznaczone',
	array( 12, 13 ),
	iskt_wpisy_do_usuniecia( array( 11, 12, 13, 14 ) )
);
sprawdz( 'pusta lista nie daje nic do usunięcia', array(), iskt_wpisy_do_usuniecia( array() ) );
sprawdz( 'identyfikator zerowy jest odrzucany', array(), iskt_wpisy_do_usuniecia( array( 0, -5 ) ) );

/*
 * Kategorie założone przez zasiew aktualności są danymi demonstracyjnymi tak samo
 * jak wpisy. Bez tego po sprzątaniu zostawałoby w menu puste „Demo — …”.
 */
$GLOBALS['iskt_test_pola_pojec'][21] = array( ISKT_META_DEMO => '1' );
$GLOBALS['iskt_test_pola_pojec'][22] = array();

sprawdz( 'hasło taksonomii z polem jest demonstracyjne', true, iskt_czy_demo_termin( 21 ) );
sprawdz( 'hasło taksonomii właściciela nie jest demonstracyjne', false, iskt_czy_demo_termin( 22 ) );

// Spis kosza wchodzi do sprzątania: wpis w koszu dalej leży w bazie i trafiłby do eksportu.
sprawdz( 'kosz jest objęty spisem', true, in_array( 'trash', iskt_statusy_demo(), true ) );

/*
 * Każdy typ treści katalogu musi być przeszukiwany. Test jest zabezpieczeniem na
 * przyszłość: dołożenie typu bez dopisania go do listy dałoby dane niewidoczne
 * dla ekranu sprzątania.
 */
sprawdz(
	'spis obejmuje wszystkie typy katalogu',
	true,
	array() === array_diff(
		array( ISKT_CPT_SZKOLENIE, ISKT_CPT_TRENER, ISKT_CPT_TERMIN, ISKT_CPT_ZGLOSZENIE ),
		iskt_typy_demo()
	)
);
sprawdz( 'spis obejmuje wpisy bloga', true, in_array( 'post', iskt_typy_demo(), true ) );
sprawdz( 'spis obejmuje załączniki', true, in_array( 'attachment', iskt_typy_demo(), true ) );
sprawdz( 'spis obejmuje kategorie wpisów', true, in_array( 'category', iskt_taksonomie_demo(), true ) );

/*
 * Oznaczenie nie może kolidować z żadnym polem katalogu — inaczej sprzątanie
 * zabierałoby wpisy właściciela na podstawie pola, które on sam wypełnił.
 */
sprawdz(
	'oznaczenie nie koliduje z polami katalogu',
	true,
	( static function (): bool {
		foreach ( iskt_definicje_pol() as $pola ) {
			if ( array_key_exists( ISKT_META_DEMO, $pola ) ) {
				return false;
			}
		}

		return true;
	} )()
);

// Podsumowania. Liczby podajemy wprost, bez odmiany przez przypadki — patrz komentarz przy funkcji.
sprawdz(
	'podsumowanie wymienia obie grupy',
	'Usunięto dane demonstracyjne — wpisy: 12, hasła taksonomii: 2.',
	iskt_podsumowanie_usuwania( array( 'wpisy' => 12, 'hasla' => 2 ) )
);
sprawdz(
	'pominięte pozycje trafiają do podsumowania',
	'Usunięto dane demonstracyjne — wpisy: 3, hasła taksonomii: 0. Pominięto z braku uprawnień: 1.',
	iskt_podsumowanie_usuwania( array( 'wpisy' => 3, 'hasla' => 0, 'pominiete' => 1 ) )
);
sprawdz(
	'brak danych demonstracyjnych ma własny komunikat',
	'Nie znaleziono danych demonstracyjnych — nie było czego usuwać.',
	iskt_podsumowanie_usuwania( array() )
);

sprawdz(
	'spis liczy pozycje w rozbiciu na grupy',
	array( 'iskt_szkolenie' => 2, 'post' => 1 ),
	iskt_policz_spis( array( 'iskt_szkolenie' => array( 'a', 'b' ), 'post' => array( 'c' ) ) )
);
sprawdz(
	'suma spisu liczy wszystkie grupy',
	3,
	iskt_suma_spisu( array( 'iskt_szkolenie' => array( 'a', 'b' ), 'post' => array( 'c' ) ) )
);
sprawdz( 'pusty spis sumuje się do zera', 0, iskt_suma_spisu( array() ) );

/*
 * §9 i zakres zadania 12 rozdzielają dwa mechanizmy: usuwanie wpisów i znacznik
 * „[do potwierdzenia: …]” w rejestrze tekstów. Ta asercja pilnuje, żeby nie zrosły
 * się w jeden — rejestr tekstów ma przeżyć sprzątanie nietknięty.
 */
sprawdz(
	'rejestr tekstów nie zna pola oznaczenia',
	false,
	array_key_exists( ISKT_META_DEMO, iskt_definicje_tekstow() )
);

// --- Wynik -----------------------------------------------------------------

printf( "\n%d przeszło, %d nie przeszło\n", $GLOBALS['iskt_ok'], $GLOBALS['iskt_bledy'] );

exit( $GLOBALS['iskt_bledy'] > 0 ? 1 : 0 );
