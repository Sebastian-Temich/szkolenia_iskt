<?php
/**
 * Formularz wyszukiwania.
 *
 * Etykieta jest widoczna i powiązana z polem przez for/id — wymóg §7 zlecenia.
 * Znak zastępczy (placeholder) podpowiada przykład, ale nie zastępuje etykiety:
 * znika po rozpoczęciu pisania i nie jest czytany przez część czytników ekranu.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

$iskt_field_id = wp_unique_id( 'iskt-szukaj-' );
?>
<form class="iskt-search-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<div class="iskt-field">
		<label class="iskt-field__label" for="<?php echo esc_attr( $iskt_field_id ); ?>">
			<?php echo esc_html( iskt_tekst_motywu( 'wyszukiwanie_etykieta', __( 'Szukaj w serwisie', 'iskt-szkolenia' ) ) ); ?>
		</label>

		<div class="iskt-search-form__row">
			<input
				class="iskt-field__control"
				id="<?php echo esc_attr( $iskt_field_id ); ?>"
				type="search"
				name="s"
				value="<?php echo esc_attr( get_search_query() ); ?>"
				placeholder="<?php echo esc_attr( iskt_tekst_motywu( 'wyszukiwanie_podpowiedz', __( 'np. ESG, AI, angielski', 'iskt-szkolenia' ) ) ); ?>"
			>
			<button class="iskt-button iskt-button--primary" type="submit">
				<?php iskt_the_icon( 'search', 'iskt-button__icon' ); ?>
				<?php echo esc_html( iskt_tekst_motywu( 'wyszukiwanie_przycisk', __( 'Szukaj', 'iskt-szkolenia' ) ) ); ?>
			</button>
		</div>
	</div>
</form>
