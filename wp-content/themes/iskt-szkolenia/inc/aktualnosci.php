<?php
/**
 * Aktualności — wspólne dane listy, archiwum kategorii i artykułu.
 *
 * Trzy widoki (`home.php`, `category.php`, `single.php`) pytają o te same rzeczy:
 * jak nazywa się lista wpisów i pod jakim adresem stoi. Gdyby każdy liczył to
 * sam, zmiana nazwy strony „Aktualności” poprawiłaby nagłówek listy, a ścieżka
 * powrotu w artykule dalej mówiłaby po staremu.
 *
 * Kolejność źródeł tytułu i wstępu jest w obu wypadkach ta sama: najpierw
 * rejestr tekstów (Szkolenia → Teksty serwisu), potem strona ustawiona jako
 * lista wpisów. Puste pole w panelu znaczy „weź ze strony” — właściciel nie musi
 * wybierać jednego sposobu edycji, a pole w panelu nigdy nie jest martwe.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Identyfikator strony ustawionej jako lista wpisów.
 *
 * Zwraca 0, gdy lista wpisów jest stroną główną albo gdy właściciel nie wskazał
 * żadnej strony — wtedy nie ma skąd brać tytułu ani wstępu.
 */
function iskt_aktualnosci_strona(): int {
	if ( 'page' !== get_option( 'show_on_front' ) ) {
		return 0;
	}

	return (int) get_option( 'page_for_posts' );
}

/**
 * Adres listy aktualności.
 */
function iskt_aktualnosci_url(): string {
	$strona = iskt_aktualnosci_strona();

	if ( $strona > 0 ) {
		$adres = get_permalink( $strona );

		if ( is_string( $adres ) && '' !== $adres ) {
			return $adres;
		}
	}

	return home_url( '/' );
}

/**
 * Tytuł listy aktualności — nagłówek listy i etykieta ścieżki powrotu.
 */
function iskt_aktualnosci_tytul(): string {
	$tekst = iskt_tekst_motywu( 'aktualnosci_tytul' );

	if ( '' !== $tekst ) {
		return $tekst;
	}

	$strona = iskt_aktualnosci_strona();

	if ( $strona > 0 ) {
		$tytul = trim( (string) get_the_title( $strona ) );

		if ( '' !== $tytul ) {
			return $tytul;
		}
	}

	return __( 'Aktualności', 'iskt-szkolenia' );
}

/**
 * Tekst pod tytułem listy. Pusty, gdy nikt go nie napisał — nagłówek zostaje sam.
 */
function iskt_aktualnosci_wstep(): string {
	$tekst = iskt_tekst_motywu( 'aktualnosci_wstep' );

	if ( '' !== $tekst ) {
		return $tekst;
	}

	$strona = iskt_aktualnosci_strona();

	return $strona > 0 ? trim( (string) get_the_excerpt( $strona ) ) : '';
}

/**
 * Kategorie wpisów do filtra nad listą.
 *
 * Puste kategorie pomijamy: odnośnik prowadzący do pustego archiwum jest
 * obietnicą bez pokrycia. Filtr ma sens dopiero przy dwóch kategoriach — przy
 * jednej byłby wyborem między „wszystkim” a „wszystkim”, więc szablon go wtedy
 * nie pokazuje.
 *
 * @return array<int, WP_Term>
 */
function iskt_aktualnosci_kategorie(): array {
	$kategorie = get_categories(
		array(
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	return is_array( $kategorie ) ? $kategorie : array();
}

/**
 * Pierwsza kategoria wpisu — do plakietki i ścieżki nawigacji.
 */
function iskt_aktualnosc_kategoria( int $wpis_id ): ?WP_Term {
	$kategorie = get_the_category( $wpis_id );

	if ( ! is_array( $kategorie ) || array() === $kategorie ) {
		return null;
	}

	$pierwsza = $kategorie[0];

	return $pierwsza instanceof WP_Term ? $pierwsza : null;
}

/**
 * Czy pokazać datę aktualizacji wpisu.
 *
 * Porównujemy dni, nie sekundy. Poprawka literówki kwadrans po publikacji
 * dawałaby „Zaktualizowano” z tą samą datą, co publikacja — czytelnik zobaczyłby
 * dwie identyczne daty i uznał to za usterkę. Data aktualizacji ma znaczyć
 * „tekst zmienił się później”, a nie „ktoś kliknął Zapisz”.
 */
function iskt_aktualnosc_pokaz_aktualizacje( int $wpis_id ): bool {
	$publikacja   = (string) get_the_date( 'Ymd', $wpis_id );
	$modyfikacja  = (string) get_the_modified_date( 'Ymd', $wpis_id );

	return '' !== $publikacja && '' !== $modyfikacja && $modyfikacja > $publikacja;
}
