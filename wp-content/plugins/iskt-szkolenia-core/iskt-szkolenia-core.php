<?php
/**
 * Plugin Name:       ISKT Szkolenia — Core
 * Plugin URI:        https://szkolenia.iskt.pl
 * Description:       Model danych serwisu szkoleniowego ISKT: szkolenia, terminy, trenerzy, relacje i obsługa formularza zgłoszeniowego. Działa niezależnie od motywu — zmiana motywu nie usuwa danych katalogu.
 * Version:           0.2.0
 * Requires at least: 6.5
 * Requires PHP:      8.2
 * Author:            ISKT Software House
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       iskt-szkolenia-core
 * Domain Path:       /languages
 *
 * @package ISKT\Szkolenia\Core
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

const ISKT_CORE_VERSION = '0.2.0';
const ISKT_CORE_FILE    = __FILE__;
const ISKT_CORE_DIR     = __DIR__;

/*
 * Identyfikatory modelu danych. Trzymamy je w stałych, bo trafiają do bazy —
 * zmiana wartości po wdrożeniu odcięłaby istniejące rekordy.
 *
 * Model i uzasadnienia: docs/ADR-001-architektura.md §2, docs/ADR-002-*.md.
 */
const ISKT_CPT_SZKOLENIE  = 'iskt_szkolenie';
const ISKT_CPT_TRENER     = 'iskt_trener';
const ISKT_CPT_TERMIN     = 'iskt_termin';
const ISKT_CPT_ZGLOSZENIE = 'iskt_zgloszenie';
const ISKT_TAX_KATEGORIA  = 'iskt_kategoria';
const ISKT_TAX_FORMA      = 'iskt_forma';

/**
 * Pole relacji szkolenie → trenerzy.
 *
 * Powiązanie zapisujemy WYŁĄCZNIE na szkoleniu. Strona trenera wylicza swoje
 * szkolenia zapytaniem po tym polu, zamiast trzymać własną kopię — jedno źródło
 * prawdy gwarantuje spójność wymaganą przez §4.5 bez logiki synchronizacji.
 */
const ISKT_META_TRENERZY = '_iskt_trenerzy';

/**
 * Powiązanie terminu ze szkoleniem.
 */
const ISKT_META_TERMIN_SZKOLENIE = '_iskt_termin_szkolenie';

require_once ISKT_CORE_DIR . '/includes/capabilities.php';
require_once ISKT_CORE_DIR . '/includes/model.php';
require_once ISKT_CORE_DIR . '/includes/meta.php';
require_once ISKT_CORE_DIR . '/includes/kategorie.php';
require_once ISKT_CORE_DIR . '/includes/relacje.php';
require_once ISKT_CORE_DIR . '/includes/terminy.php';
require_once ISKT_CORE_DIR . '/includes/prezentacja.php';
require_once ISKT_CORE_DIR . '/includes/katalog.php';
require_once ISKT_CORE_DIR . '/includes/teksty.php';
require_once ISKT_CORE_DIR . '/includes/odbiorca.php';
require_once ISKT_CORE_DIR . '/includes/bloki.php';
require_once ISKT_CORE_DIR . '/includes/activation.php';

if ( is_admin() ) {
	require_once ISKT_CORE_DIR . '/includes/admin/pola.php';
	require_once ISKT_CORE_DIR . '/includes/admin/kolumny.php';
	require_once ISKT_CORE_DIR . '/includes/admin/teksty.php';
}

register_activation_hook( ISKT_CORE_FILE, 'iskt_on_activate' );
register_deactivation_hook( ISKT_CORE_FILE, 'iskt_on_deactivate' );

/**
 * Wczytuje tłumaczenia wtyczki.
 */
function iskt_core_load_textdomain(): void {
	load_plugin_textdomain( 'iskt-szkolenia-core', false, dirname( plugin_basename( ISKT_CORE_FILE ) ) . '/languages' );
}
add_action( 'init', 'iskt_core_load_textdomain' );
