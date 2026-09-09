<?php
/**
 * Plugin Name: ISKT — przechwytywanie poczty w środowisku testowym
 * Description: Zapisuje wiadomości z wp_mail() do pliku zamiast wysyłać je pocztą. WYŁĄCZNIE dla wp-env; nie należy do paczki przekazania.
 * Version: 1.0.0
 *
 * Kontener `wp-env` nie ma agenta pocztowego, więc `wp_mail()` zwracałoby tam
 * `false` niezależnie od poprawności kodu — a formularz pokazywałby komunikat
 * błędu przy każdej próbie. Bez przechwycenia nie da się sprawdzić ani ścieżki
 * powodzenia, ani treści wiadomości.
 *
 * Ten plik jest częścią środowiska testowego (`.wp-env.json` → `mappings`),
 * a nie wtyczki. Do paczki przekazania NIE trafia: katalog `tests/` zostaje poza nią.
 *
 * Co robi:
 * - zapisuje `to`, `subject`, `headers` i `message` do `uploads/iskt-poczta.jsonl`
 *   (na maszynie: `.wp-env/uploads/iskt-poczta.jsonl`), po jednym wpisie na wiersz;
 * - zwraca powodzenie wysyłki, chyba że opcja `iskt_test_poczta_awaria` jest
 *   ustawiona — wtedy zwraca `false` i pozwala sprawdzić ścieżkę błędu wysyłki,
 *   której na działającym serwerze pocztowym nie dałoby się wywołać na żądanie;
 * - pozwala testowi ustawić odstęp między zgłoszeniami z jednego adresu IP
 *   (`iskt_test_odstep`), żeby jeden przypadek sprawdzał limit, a pozostałe nie
 *   czekały na jego wygaśnięcie.
 *
 * @package ISKT\Szkolenia\Tests
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ścieżka dziennika przechwyconych wiadomości.
 */
function iskt_test_dziennik_poczty(): string {
	$katalog = wp_upload_dir();

	return trailingslashit( (string) ( $katalog['basedir'] ?? sys_get_temp_dir() ) ) . 'iskt-poczta.jsonl';
}

/**
 * Przechwytuje wysyłkę i zapisuje wiadomość do dziennika.
 *
 * @param null|bool            $wynik Wynik narzucony przez inny filtr.
 * @param array<string, mixed> $atts  Argumenty `wp_mail()`.
 *
 * @return bool
 */
function iskt_test_przechwyc_poczte( $wynik, array $atts ) {
	unset( $wynik );

	$wpis = array(
		'czas'        => gmdate( 'c' ),
		'to'          => $atts['to'] ?? '',
		'subject'     => $atts['subject'] ?? '',
		'headers'     => $atts['headers'] ?? array(),
		'message'     => $atts['message'] ?? '',
		'attachments' => $atts['attachments'] ?? array(),
	);

	file_put_contents(
		iskt_test_dziennik_poczty(),
		wp_json_encode( $wpis, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n",
		FILE_APPEND
	);

	// Zwrócenie `false` odwzorowuje serwer pocztowy, który wiadomości nie przyjął.
	return ! get_option( 'iskt_test_poczta_awaria', false );
}
add_filter( 'pre_wp_mail', 'iskt_test_przechwyc_poczte', 10, 2 );

/**
 * Odstęp między zgłoszeniami z jednego adresu IP, sterowany przez test.
 *
 * Domyślnie zero: kolejne przypadki testowe nie czekają minuty na siebie nawzajem.
 * Test limitu ustawia własną wartość i sprawdza zachowanie wprost.
 *
 * @param int $odstep Wartość domyślna wtyczki.
 */
function iskt_test_odstep_zgloszen( int $odstep ): int {
	unset( $odstep );

	return (int) get_option( 'iskt_test_odstep', 0 );
}
add_filter( 'iskt_odstep_zgloszen', 'iskt_test_odstep_zgloszen' );
