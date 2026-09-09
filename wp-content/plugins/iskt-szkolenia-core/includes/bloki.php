<?php
/**
 * Bloki dynamiczne strony głównej.
 *
 * ADR-002 §3.3 dzieli sekcje na dwie grupy:
 * - sekcje redakcyjne (hero, przebieg współpracy, dofinansowania, kontakt) —
 *   **wzorce bloków** w motywie, złożone z bloków rdzenia, bez kodu do utrzymania;
 * - sekcje z danymi (obszary, wyróżnione szkolenia, przełącznik odbiorcy) —
 *   **bloki dynamiczne** rejestrowane tutaj, bo muszą czytać katalog przy każdym
 *   wyświetleniu. Sekcja zapisana na sztywno pokazywałaby ofertę sprzed edycji.
 *
 * Każdy `render_callback` zwraca pusty ciąg, gdy nie ma czego pokazać. To realizuje
 * wymóg §4.1 „ukrywanie sekcji bez treści” bez żadnego przełącznika w panelu —
 * sekcja znika sama, kiedy jej treść zniknie.
 *
 * Znaczniki wychodzące z bloków używają klas `iskt-*`, których wygląd dostarcza
 * motyw (ADR-001 §1). Po zmianie motywu bloki dalej się renderują i dalej mają
 * poprawną semantykę — tracą tylko oprawę graficzną, a dane pozostają nietknięte.
 *
 * @package ISKT\Szkolenia\Core
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

require_once ISKT_CORE_DIR . '/includes/bloki/odbiorca.php';
require_once ISKT_CORE_DIR . '/includes/bloki/katalog.php';

/**
 * Uchwyt wspólnego skryptu edytora dla wszystkich bloków wtyczki.
 */
const ISKT_SKRYPT_BLOKOW = 'iskt-bloki';

/**
 * Bloki wtyczki: katalog z `block.json` → funkcja renderująca.
 *
 * @return array<string, callable-string>
 */
function iskt_bloki(): array {
	return array(
		'przelacznik-odbiorcy' => 'iskt_render_przelacznik_odbiorcy',
		'tresc-odbiorcy'       => 'iskt_render_tresc_odbiorcy',
		'obszary-szkolen'      => 'iskt_render_obszary_szkolen',
		'wyroznione-szkolenia' => 'iskt_render_wyroznione_szkolenia',
	);
}

/**
 * Rejestruje wspólny skrypt edytora.
 *
 * Jeden plik dla wszystkich bloków, rejestrowany raz i wskazywany w `block.json`
 * przez uchwyt. Zapis `file:` w każdym `block.json` utworzyłby cztery osobne
 * uchwyty na ten sam plik i wykonał go czterokrotnie.
 *
 * Skrypt jest zwykłym JavaScriptem — bez JSX i bez kroku budowania (ADR-002 §3.3),
 * więc w repozytorium nie ma `node_modules`, a paczka przekazania nie wymaga
 * odtwarzania środowiska Node po stronie właściciela.
 */
function iskt_rejestruj_skrypt_blokow(): void {
	$sciezka = ISKT_CORE_DIR . '/assets/bloki.js';

	wp_register_script(
		ISKT_SKRYPT_BLOKOW,
		plugins_url( 'assets/bloki.js', ISKT_CORE_FILE ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render' ),
		file_exists( $sciezka ) ? (string) filemtime( $sciezka ) : ISKT_CORE_VERSION,
		true
	);

	wp_set_script_translations( ISKT_SKRYPT_BLOKOW, 'iskt-szkolenia-core', ISKT_CORE_DIR . '/languages' );
}

/**
 * Rejestruje bloki dynamiczne.
 */
function iskt_rejestruj_bloki(): void {
	iskt_rejestruj_skrypt_blokow();

	foreach ( iskt_bloki() as $katalog => $render ) {
		register_block_type(
			ISKT_CORE_DIR . '/blocks/' . $katalog,
			array( 'render_callback' => $render )
		);
	}
}
add_action( 'init', 'iskt_rejestruj_bloki' );

/**
 * Dokłada kategorię bloków ISKT.
 *
 * Bez własnej kategorii bloki wtyczki rozpłynęłyby się wśród kilkudziesięciu
 * bloków rdzenia. Właściciel ma je znaleźć w jednym miejscu.
 *
 * @param array<int, array<string, mixed>> $kategorie Kategorie bloków.
 *
 * @return array<int, array<string, mixed>>
 */
function iskt_kategoria_blokow( array $kategorie ): array {
	array_unshift(
		$kategorie,
		array(
			'slug'  => 'iskt',
			'title' => __( 'Szkolenia ISKT', 'iskt-szkolenia-core' ),
			'icon'  => null,
		)
	);

	return $kategorie;
}
add_filter( 'block_categories_all', 'iskt_kategoria_blokow' );

/**
 * Buduje listę klas CSS dla opakowania bloku.
 *
 * `get_block_wrapper_attributes()` dokłada klasy z ustawień bloku wybranych przez
 * redaktora (wyrównanie, kolory, odstępy) — bez tego wywołania ustawienia z panelu
 * bocznego nie miałyby żadnego skutku na stronie.
 *
 * @param array<string, mixed> $dodatkowe Dodatkowe atrybuty, np. `class`.
 */
function iskt_atrybuty_bloku( array $dodatkowe = array() ): string {
	return get_block_wrapper_attributes( $dodatkowe );
}

/**
 * Nagłówek sekcji: nadtytuł, tytuł i wstęp.
 *
 * Wspólny dla bloków z danymi, bo trzy sekcje z załącznika mają identyczną budowę
 * nagłówka. Każdy element jest opcjonalny — pusty nie zostawia po sobie znacznika,
 * żeby nie generować pustych nagłówków, na które narzeka audyt dostępności.
 *
 * @param string $nadtytul Krótki tekst nad tytułem.
 * @param string $tytul    Tytuł sekcji.
 * @param string $wstep    Zdanie wprowadzające.
 * @param bool   $srodek   Czy wyśrodkować nagłówek.
 */
function iskt_naglowek_sekcji( string $nadtytul, string $tytul, string $wstep, bool $srodek = true ): string {
	if ( '' === $nadtytul && '' === $tytul && '' === $wstep ) {
		return '';
	}

	$html = '';

	if ( '' !== $nadtytul ) {
		$html .= '<p class="iskt-eyebrow">' . esc_html( $nadtytul ) . '</p>';
	}

	if ( '' !== $tytul ) {
		$html .= '<h2 class="iskt-title-lg iskt-balance">' . esc_html( $tytul ) . '</h2>';
	}

	if ( '' !== $wstep ) {
		$html .= '<p class="iskt-lead">' . esc_html( $wstep ) . '</p>';
	}

	$klasy = 'iskt-section__head' . ( $srodek ? ' iskt-section__head--center' : '' );

	return '<div class="' . esc_attr( $klasy ) . '">' . $html . '</div>';
}
