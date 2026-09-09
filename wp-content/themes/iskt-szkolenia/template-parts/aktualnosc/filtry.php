<?php
/**
 * Filtr kategorii nad listą aktualności.
 *
 * Prostszy niż filtry katalogu i celowo: §4.6 wymienia kategorię wśród pól
 * wpisu, ale nie żąda wyszukiwania po wpisach — a wyszukiwarka serwisu i tak
 * je obejmuje. Zamiast formularza mamy więc zwykłe odnośniki do archiwów
 * kategorii: działają bez JavaScriptu, dają się dodać do zakładek i wysłać.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

$iskt_kategorie = iskt_aktualnosci_kategorie();

if ( count( $iskt_kategorie ) < 2 ) {
	return;
}

$iskt_biezaca = is_category() ? (int) get_queried_object_id() : 0;
?>
<nav class="iskt-aktualnosci-filtry" aria-label="<?php echo esc_attr( iskt_tekst_motywu( 'aktualnosci_kategorie', __( 'Kategorie', 'iskt-szkolenia' ) ) ); ?>">
	<ul class="iskt-aktualnosci-filtry__lista">
		<li>
			<a
				class="iskt-aktualnosci-filtry__pozycja"
				href="<?php echo esc_url( iskt_aktualnosci_url() ); ?>"
				<?php echo 0 === $iskt_biezaca ? 'aria-current="page"' : ''; ?>
			>
				<?php echo esc_html( iskt_tekst_motywu( 'aktualnosci_kategorie_wszystkie', __( 'Wszystkie', 'iskt-szkolenia' ) ) ); ?>
			</a>
		</li>

		<?php foreach ( $iskt_kategorie as $iskt_kategoria ) : ?>
			<li>
				<a
					class="iskt-aktualnosci-filtry__pozycja"
					href="<?php echo esc_url( (string) get_category_link( $iskt_kategoria ) ); ?>"
					<?php echo $iskt_biezaca === (int) $iskt_kategoria->term_id ? 'aria-current="page"' : ''; ?>
				>
					<?php echo esc_html( $iskt_kategoria->name ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
