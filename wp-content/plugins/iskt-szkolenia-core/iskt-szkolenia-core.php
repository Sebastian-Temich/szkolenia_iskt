<?php
/**
 * Plugin Name:       ISKT Szkolenia — Core
 * Plugin URI:        https://szkolenia.iskt.pl
 * Description:       Model danych serwisu szkoleniowego ISKT: szkolenia, terminy, trenerzy, relacje, obsługa formularza zgłoszeniowego i dane strukturalne. Działa niezależnie od motywu — zmiana motywu nie usuwa danych katalogu.
 * Version:           0.1.0
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

const ISKT_CORE_VERSION = '0.1.0';
const ISKT_CORE_FILE    = __FILE__;
const ISKT_CORE_DIR     = __DIR__;

/*
 * Identyfikatory modelu danych. Trzymamy je w stałych, bo trafiają do bazy —
 * zmiana wartości po wdrożeniu odcięłaby istniejące rekordy.
 *
 * Model i uzasadnienia: docs/ADR-001-architektura.md §2.
 */
const ISKT_CPT_SZKOLENIE = 'iskt_szkolenie';
const ISKT_CPT_TRENER    = 'iskt_trener';
const ISKT_CPT_TERMIN    = 'iskt_termin';
const ISKT_CPT_ZGLOSZENIE = 'iskt_zgloszenie';
const ISKT_TAX_KATEGORIA = 'iskt_kategoria';
const ISKT_TAX_FORMA     = 'iskt_forma';

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

/*
 * Rejestracja modelu danych, obsługa formularza i dane strukturalne zostaną dodane
 * w zadaniach 2, 8 i 10 planu — po zatwierdzeniu startu realizacji przez ISKT
 * (bramka §12). Patrz docs/PLAN-I-ESTYMACJA.md.
 *
 * Wtyczka jest tu świadomie bezczynna: rejestrowanie niedokończonych typów treści
 * zapisałoby do bazy strukturę, którą trzeba by potem migrować.
 */
