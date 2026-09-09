<?php
/**
 * Aktualności zawężone do jednej kategorii.
 *
 * Ten sam widok co lista główna, tylko z innym nagłówkiem — filtr kategorii jest
 * zwykłym odnośnikiem do archiwum, więc działa bez JavaScriptu, daje się dodać do
 * zakładek i wysłać (`PROJEKT-AKTUALNOSCI.md` §3).
 *
 * Tytuł i opis biorą się z samej kategorii, bo właściciel edytuje je przy
 * kategorii w panelu. Osobny napis w rejestrze tekstów byłby drugim miejscem na tę
 * samą rzecz — i pierwszym, o którym ktoś by zapomniał.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

get_header();

$iskt_kategoria = get_queried_object();
$iskt_tytul     = $iskt_kategoria instanceof WP_Term ? $iskt_kategoria->name : iskt_aktualnosci_tytul();
$iskt_wstep     = $iskt_kategoria instanceof WP_Term
	? trim( wp_strip_all_tags( $iskt_kategoria->description ) )
	: '';
?>

<main id="iskt-main" class="iskt-main" tabindex="-1">

	<?php
	get_template_part(
		'template-parts/aktualnosc/lista',
		null,
		array(
			'tytul' => $iskt_tytul,
			'wstep' => $iskt_wstep,
		)
	);
	?>

</main>

<?php
get_footer();
