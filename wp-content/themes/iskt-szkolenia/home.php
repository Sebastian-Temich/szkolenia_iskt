<?php
/**
 * Lista aktualności — strona ustawiona jako lista wpisów.
 *
 * `home.php`, a nie `index.php`: gdy właściciel ustawi stronę główną na stronę
 * statyczną (tak działa nasza instalacja), WordPress kieruje listę wpisów tutaj.
 * `index.php` zostaje szablonem awaryjnym dla wyszukiwania i archiwów, których
 * nie obsługuje żaden inny plik.
 *
 * Realizuje §4.6 zlecenia i `PROJEKT-AKTUALNOSCI.md` §3 — projekt zaakceptowany
 * przez ISKT 2026-09-09 (bramka 2).
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="iskt-main" class="iskt-main" tabindex="-1">

	<?php
	get_template_part(
		'template-parts/aktualnosc/lista',
		null,
		array(
			'tytul' => iskt_aktualnosci_tytul(),
			'wstep' => iskt_aktualnosci_wstep(),
		)
	);
	?>

</main>

<?php
get_footer();
