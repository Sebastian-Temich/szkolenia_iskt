<?php
/**
 * Archiwum kategorii szkoleń — ten sam katalog, z kategorią ustawioną w filtrze.
 *
 * Kafelek obszaru na stronie głównej prowadzi do kategorii. Gdyby ten adres
 * pokazywał inną, uboższą listę niż `/szkolenia/`, odwiedzający dostawałby dwa
 * różne katalogi tego samego serwisu — z których jeden nie ma filtrów.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

require get_theme_file_path( 'archive-iskt_szkolenie.php' );
