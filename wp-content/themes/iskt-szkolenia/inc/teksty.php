<?php
/**
 * Dostęp motywu do tekstów globalnych.
 *
 * Rejestr tekstów należy do wtyczki (`includes/teksty.php`), żeby zmiana motywu
 * nie kasowała treści wpisanych przez właściciela — ta sama zasada, co przy
 * modelu danych (§6, ADR-001 §1).
 *
 * Motyw musi jednak działać także wtedy, gdy wtyczka jest wyłączona: strona 404,
 * wyszukiwarka i nagłówek renderują się na każdej instalacji. Wywołanie nieistniejącej
 * funkcji dałoby białą stronę zamiast serwisu bez katalogu. Stąd ten pomocnik
 * i drugi argument z treścią zapasową.
 *
 * Szablony katalogu (`single-iskt_szkolenie.php` i pochodne) wywołują `iskt_tekst()`
 * wprost — bez wtyczki nie istnieje typ treści, który miałyby wyświetlić, więc
 * zapasowa treść byłaby tam napisem, którego nikt nigdy nie zobaczy.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Zwraca tekst globalny albo treść zapasową, gdy wtyczka jest nieaktywna.
 *
 * @param string $klucz    Klucz z rejestru tekstów wtyczki.
 * @param string $zapasowa Treść używana bez wtyczki.
 */
function iskt_tekst_motywu( string $klucz, string $zapasowa = '' ): string {
	if ( ! function_exists( 'iskt_tekst' ) ) {
		return $zapasowa;
	}

	$tekst = iskt_tekst( $klucz );

	return '' !== $tekst ? $tekst : $zapasowa;
}
