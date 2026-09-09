<?php
/**
 * Symbol kategorii szkoleń.
 *
 * Sekcja „obszary szkoleń” z §4.1 pokazuje każdą kategorię z własnym symbolem.
 * Kategorie są edytowalne (§4.2), więc symbolu nie da się przypisać na sztywno
 * po nazwie ani po slugu — właściciel może je zmienić albo dodać własne.
 *
 * Wtyczka przechowuje wyłącznie **identyfikator** symbolu, np. `ai`. Jaki kształt
 * mu odpowiada, decyduje motyw — to podział z ADR-001 §1: dane w bazie należą do
 * wtyczki, wygląd do motywu. Zmiana motywu nie unieważnia zapisanych wartości.
 *
 * @package ISKT\Szkolenia\Core
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Pole terminu taksonomii z identyfikatorem symbolu.
 */
const ISKT_META_IKONA = 'iskt_ikona';

/**
 * Dostępne symbole: identyfikator → etykieta w panelu.
 *
 * Lista jest zamknięta, bo motyw musi znać każdą wartość. Rozszerzenie wymaga
 * dołożenia kształtu po stronie motywu (inc/ikony.php) — dlatego nie otwieramy jej
 * na dowolny tekst od redaktora.
 *
 * @return array<string, string>
 */
function iskt_symbole_kategorii(): array {
	return array(
		''            => __( 'Bez symbolu', 'iskt-szkolenia-core' ),
		'ai'          => __( 'Sztuczna inteligencja', 'iskt-szkolenia-core' ),
		'esg'         => __( 'Zrównoważony rozwój', 'iskt-szkolenia-core' ),
		'jezyk'       => __( 'Język obcy', 'iskt-szkolenia-core' ),
		'kod'         => __( 'Oprogramowanie', 'iskt-szkolenia-core' ),
		'badania'     => __( 'Badania i rozwój', 'iskt-szkolenia-core' ),
		'edukacja'    => __( 'Edukacja', 'iskt-szkolenia-core' ),
		'firma'       => __( 'Firma i zespół', 'iskt-szkolenia-core' ),
		'finansowanie' => __( 'Finansowanie', 'iskt-szkolenia-core' ),
	);
}

/**
 * Sprowadza wartość do znanego identyfikatora symbolu.
 *
 * @param mixed $wartosc Wartość do sprawdzenia.
 */
function iskt_sanitize_symbol( $wartosc ): string {
	if ( ! is_string( $wartosc ) ) {
		return '';
	}

	$wartosc = sanitize_key( $wartosc );

	return isset( iskt_symbole_kategorii()[ $wartosc ] ) ? $wartosc : '';
}

/**
 * Rejestruje pole symbolu na terminach kategorii.
 */
function iskt_rejestruj_meta_kategorii(): void {
	register_term_meta(
		ISKT_TAX_KATEGORIA,
		ISKT_META_IKONA,
		array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'description'       => __( 'Identyfikator symbolu kategorii. Kształt dostarcza motyw.', 'iskt-szkolenia-core' ),
			'sanitize_callback' => 'iskt_sanitize_symbol',
			'auth_callback'     => static fn (): bool => current_user_can( 'manage_categories' ),
		)
	);
}
add_action( 'init', 'iskt_rejestruj_meta_kategorii' );

/**
 * Zwraca identyfikator symbolu kategorii.
 *
 * @param int $term_id Identyfikator terminu.
 */
function iskt_symbol_kategorii( int $term_id ): string {
	return iskt_sanitize_symbol( get_term_meta( $term_id, ISKT_META_IKONA, true ) );
}

/**
 * Pole wyboru symbolu na formularzu dodawania kategorii.
 */
function iskt_pole_symbolu_dodawanie(): void {
	?>
	<div class="form-field">
		<label for="iskt-symbol"><?php esc_html_e( 'Symbol', 'iskt-szkolenia-core' ); ?></label>
		<?php iskt_wypisz_wybor_symbolu( '' ); ?>
		<p><?php esc_html_e( 'Symbol pokazywany przy kategorii w sekcji „obszary szkoleń”.', 'iskt-szkolenia-core' ); ?></p>
	</div>
	<?php
}
add_action( ISKT_TAX_KATEGORIA . '_add_form_fields', 'iskt_pole_symbolu_dodawanie' );

/**
 * Pole wyboru symbolu na formularzu edycji kategorii.
 *
 * @param WP_Term $term Edytowany termin.
 */
function iskt_pole_symbolu_edycja( WP_Term $term ): void {
	?>
	<tr class="form-field">
		<th scope="row">
			<label for="iskt-symbol"><?php esc_html_e( 'Symbol', 'iskt-szkolenia-core' ); ?></label>
		</th>
		<td>
			<?php iskt_wypisz_wybor_symbolu( iskt_symbol_kategorii( $term->term_id ) ); ?>
			<p class="description"><?php esc_html_e( 'Symbol pokazywany przy kategorii w sekcji „obszary szkoleń”.', 'iskt-szkolenia-core' ); ?></p>
		</td>
	</tr>
	<?php
}
add_action( ISKT_TAX_KATEGORIA . '_edit_form_fields', 'iskt_pole_symbolu_edycja' );

/**
 * Wypisuje listę wyboru symbolu.
 *
 * @param string $wybrany Aktualnie wybrany identyfikator.
 */
function iskt_wypisz_wybor_symbolu( string $wybrany ): void {
	?>
	<select name="<?php echo esc_attr( ISKT_META_IKONA ); ?>" id="iskt-symbol">
		<?php foreach ( iskt_symbole_kategorii() as $identyfikator => $etykieta ) : ?>
			<option value="<?php echo esc_attr( $identyfikator ); ?>" <?php selected( $wybrany, $identyfikator ); ?>>
				<?php echo esc_html( $etykieta ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php
}

/**
 * Zapisuje symbol kategorii.
 *
 * WordPress sprawdza `nonce` i uprawnienia zanim wywoła te akcje, a `auth_callback`
 * z rejestracji pola jest drugą, niezależną kontrolą przy samym zapisie.
 *
 * @param int $term_id Identyfikator terminu.
 */
function iskt_zapisz_symbol_kategorii( int $term_id ): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- weryfikuje WordPress przed wywołaniem akcji.
	if ( ! isset( $_POST[ ISKT_META_IKONA ] ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_categories' ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$symbol = iskt_sanitize_symbol( wp_unslash( $_POST[ ISKT_META_IKONA ] ) );

	if ( '' === $symbol ) {
		delete_term_meta( $term_id, ISKT_META_IKONA );

		return;
	}

	update_term_meta( $term_id, ISKT_META_IKONA, $symbol );
}
add_action( 'created_' . ISKT_TAX_KATEGORIA, 'iskt_zapisz_symbol_kategorii' );
add_action( 'edited_' . ISKT_TAX_KATEGORIA, 'iskt_zapisz_symbol_kategorii' );
