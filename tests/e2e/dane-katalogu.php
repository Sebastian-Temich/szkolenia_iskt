<?php
/**
 * Dane demonstracyjne katalogu szkoleń dla testu przeglądarkowego.
 *
 * Uruchamiane przez `wp eval-file` (patrz `katalog.spec.js`). Katalogu nie da się
 * sprawdzić na jednym szkoleniu: paginacja, filtry i brak wyników pokazują się
 * dopiero wtedy, gdy jest z czego wybierać. Ścieżkę WŁAŚCICIELA — dodawanie
 * szkolenia przez panel — sprawdza `odbior-m1.spec.js`; ten plik zakłada dane dla
 * ścieżki ODWIEDZAJĄCEGO, więc może je wpisać wprost do bazy.
 *
 * Skrypt jest idempotentny: rozpoznaje wpisy po slugu i aktualizuje je zamiast
 * tworzyć kolejne kopie. Powtórne uruchomienie testu daje ten sam katalog.
 *
 * Każdy tytuł zaczyna się od „Demo —”, bo §9 zabrania przedstawiania
 * niezatwierdzonych treści jako oferty ISKT.
 *
 * Bez `declare( strict_types = 1 )` wbrew regule reszty repozytorium: `wp eval-file`
 * wykonuje treść pliku przez `eval()`, a deklaracja typów musi być pierwszą
 * instrukcją skryptu — czego w `eval()` nie da się spełnić.
 *
 * @package ISKT\Szkolenia\Tests
 */

if ( ! defined( 'WP_CLI' ) ) {
	exit( 1 );
}

/**
 * Katalog demonstracyjny.
 *
 * Fraza „termowizja” występuje wyłącznie w OPISIE jednego szkolenia i w żadnym
 * tytule — dzięki temu test wyszukiwania dowodzi, że katalog przeszukuje opis,
 * a nie tylko nazwę (§4.2).
 */
$iskt_katalog = array(
	array(
		'slug'       => 'demo-audyt-energetyczny-budynkow',
		'tytul'      => 'Demo — Audyt energetyczny budynków',
		'opis'       => 'Praktyczny warsztat pomiarowy: termowizja, szczelność powietrzna i czytanie świadectw charakterystyki energetycznej.',
		'kategoria'  => 'esg-i-zrownowazony-rozwoj',
		'forma'      => 'stacjonarnie',
		'wyroznione' => false,
	),
	array(
		'slug'       => 'demo-raportowanie-esg-w-praktyce',
		'tytul'      => 'Demo — Raportowanie ESG w praktyce',
		'opis'       => 'Zakres danych, wskaźniki i harmonogram raportu zrównoważonego rozwoju w małej i średniej firmie.',
		'kategoria'  => 'esg-i-zrownowazony-rozwoj',
		'forma'      => 'online',
		'wyroznione' => true,
	),
	array(
		'slug'       => 'demo-slad-weglowy-organizacji',
		'tytul'      => 'Demo — Ślad węglowy organizacji',
		'opis'       => 'Policz zakres 1, 2 i 3 na własnych danych. Warsztat z arkuszem, który zostaje u uczestnika.',
		'kategoria'  => 'esg-i-zrownowazony-rozwoj',
		'forma'      => 'hybrydowo',
		'wyroznione' => false,
	),
	array(
		'slug'       => 'demo-asystenci-ai-w-pracy-biurowej',
		'tytul'      => 'Demo — Asystenci AI w pracy biurowej',
		'opis'       => 'Codzienne zadania biurowe wykonane szybciej: korespondencja, notatki ze spotkań, porządkowanie danych.',
		'kategoria'  => 'ai-i-narzedzia-generatywne',
		'forma'      => 'online',
		'wyroznione' => true,
	),
	array(
		'slug'       => 'demo-generatywne-ai-dla-marketingu',
		'tytul'      => 'Demo — Generatywne AI dla marketingu',
		'opis'       => 'Materiały promocyjne, opisy produktów i grafiki — z zachowaniem praw autorskich i spójności marki.',
		'kategoria'  => 'ai-i-narzedzia-generatywne',
		'forma'      => 'stacjonarnie',
		'wyroznione' => false,
	),
	array(
		'slug'       => 'demo-bezpieczenstwo-danych-przy-narzedziach-ai',
		'tytul'      => 'Demo — Bezpieczeństwo danych przy narzędziach AI',
		'opis'       => 'Co wolno wkleić do modelu, a czego nie. Polityka firmowa, którą da się wdrożyć w tydzień.',
		'kategoria'  => 'ai-i-narzedzia-generatywne',
		'forma'      => 'online',
		'wyroznione' => false,
	),
	array(
		'slug'       => 'demo-angielski-techniczny-dla-inzynierow',
		'tytul'      => 'Demo — Angielski techniczny dla inżynierów',
		'opis'       => 'Dokumentacja, specyfikacje i rozmowa z dostawcą. Zajęcia w małych grupach, materiały branżowe.',
		'kategoria'  => 'jezyk-angielski',
		'forma'      => 'online',
		'wyroznione' => false,
	),
	array(
		'slug'       => 'demo-angielski-w-negocjacjach-handlowych',
		'tytul'      => 'Demo — Angielski w negocjacjach handlowych',
		'opis'       => 'Scenariusze rozmów, ustalanie warunków i domykanie kontraktu w języku angielskim.',
		'kategoria'  => 'jezyk-angielski',
		'forma'      => 'stacjonarnie',
		'wyroznione' => false,
	),
	array(
		'slug'       => 'demo-testowanie-automatyczne-aplikacji',
		'tytul'      => 'Demo — Testowanie automatyczne aplikacji',
		'opis'       => 'Od pierwszego testu do zestawu uruchamianego przy każdej zmianie kodu.',
		'kategoria'  => 'rozwoj-oprogramowania',
		'forma'      => 'online',
		'wyroznione' => false,
	),
	array(
		'slug'       => 'demo-czysty-kod-i-przeglady-kodu',
		'tytul'      => 'Demo — Czysty kod i przeglądy kodu',
		'opis'       => 'Jak czytać cudzy kod, jak pisać swój i jak prowadzić przegląd, który czegoś uczy.',
		'kategoria'  => 'rozwoj-oprogramowania',
		'forma'      => 'hybrydowo',
		'wyroznione' => false,
	),
	array(
		'slug'       => 'demo-wniosek-o-dofinansowanie-br',
		'tytul'      => 'Demo — Wniosek o dofinansowanie B+R',
		'opis'       => 'Opis projektu badawczego, kosztorys i wskaźniki, których oczekuje instytucja finansująca.',
		'kategoria'  => 'projekty-br',
		'forma'      => 'stacjonarnie',
		'wyroznione' => false,
	),
	array(
		'slug'       => 'demo-zarzadzanie-projektem-badawczym',
		'tytul'      => 'Demo — Zarządzanie projektem badawczym',
		'opis'       => 'Harmonogram, kamienie milowe i rozliczenie projektu prowadzonego z uczelnią.',
		'kategoria'  => 'projekty-br',
		'forma'      => 'online',
		'wyroznione' => false,
	),
);

$iskt_utworzone = 0;
$iskt_zmienione = 0;

foreach ( $iskt_katalog as $iskt_pozycja ) {
	$iskt_istniejace = get_posts(
		array(
			'post_type'        => 'iskt_szkolenie',
			'name'             => $iskt_pozycja['slug'],
			'post_status'      => 'any',
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'suppress_filters' => true,
		)
	);

	$iskt_dane = array(
		'post_type'    => 'iskt_szkolenie',
		'post_status'  => 'publish',
		'post_title'   => $iskt_pozycja['tytul'],
		'post_name'    => $iskt_pozycja['slug'],
		'post_content' => $iskt_pozycja['opis'],
		'post_excerpt' => $iskt_pozycja['opis'],
	);

	if ( array() !== $iskt_istniejace ) {
		$iskt_dane['ID'] = (int) $iskt_istniejace[0];
		$iskt_id         = wp_update_post( $iskt_dane, true );
		++$iskt_zmienione;
	} else {
		$iskt_id = wp_insert_post( $iskt_dane, true );
		++$iskt_utworzone;
	}

	if ( is_wp_error( $iskt_id ) ) {
		WP_CLI::error( 'Nie udało się zapisać szkolenia ' . $iskt_pozycja['slug'] . ': ' . $iskt_id->get_error_message() );
	}

	wp_set_object_terms( (int) $iskt_id, array( $iskt_pozycja['kategoria'] ), 'iskt_kategoria', false );
	wp_set_object_terms( (int) $iskt_id, array( $iskt_pozycja['forma'] ), 'iskt_forma', false );

	update_post_meta( (int) $iskt_id, '_iskt_czas_trwania', '1 dzień (8 godzin)' );
	update_post_meta( (int) $iskt_id, '_iskt_poziom', 'podstawowy' );
	update_post_meta( (int) $iskt_id, '_iskt_cena', '1200.00' );
	update_post_meta( (int) $iskt_id, '_iskt_cena_jednostka', 'osoba' );
	update_post_meta( (int) $iskt_id, '_iskt_cena_podatek', 'netto' );
	update_post_meta( (int) $iskt_id, '_iskt_wyroznione', $iskt_pozycja['wyroznione'] );
}

WP_CLI::success( sprintf( 'Katalog demonstracyjny: %d nowych, %d zaktualizowanych.', $iskt_utworzone, $iskt_zmienione ) );
