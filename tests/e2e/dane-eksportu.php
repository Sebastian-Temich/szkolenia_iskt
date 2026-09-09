<?php
/**
 * Dane odniesienia dla testu odtworzenia z paczki eksportu (zadanie 13, M3).
 *
 * Test odtworzenia jest wart tyle, ile treść, na której go przeprowadzono. Katalog
 * demonstracyjny z `dane-katalogu.php` sprawdza wyszukiwanie i filtry, ale nie
 * dotyka trzech rzeczy, które eksport gubi najczęściej:
 *
 * 1. **relacji szkolenie ↔ trener** — `_iskt_trenerzy` to tablica zapisana
 *    serializowanie, a zapytanie zwrotne trenera szuka w niej wzorca `i:<id>;`.
 *    Zamiana adresów wykonana zwykłym `sed` na zrzucie SQL rozjeżdża długości
 *    w `s:<n>:"..."` i relacja przestaje się odnajdywać z żadnej ze stron —
 *    dlatego instrukcja migracji wymaga `wp search-replace`, a ten plik zasiewa
 *    powiązanie, na którym widać różnicę;
 * 2. **terminu zakończonego** — §11 pkt 5 wymaga, żeby przeszły termin nie
 *    pokazywał się jako nadchodzący. Wykrywa to wyłącznie dana z przeszłości,
 *    a takiej w katalogu demonstracyjnym nie ma;
 * 3. **mediów** — plik w bibliotece to wiersz w bazie ORAZ plik na dysku ORAZ
 *    rozmiary pochodne w `_wp_attachment_metadata`. Paczka bez któregokolwiek
 *    z tych trzech daje po imporcie pustą ramkę zamiast zdjęcia.
 *
 * Do kompletu skrypt nadpisuje kilka tekstów globalnych (zadanie 10). Domyślne
 * niczego nie dowodzą — po imporcie wyglądałyby tak samo, gdyby opcja `iskt_teksty`
 * w ogóle nie przetrwała.
 *
 * Wszystko, co skrypt zakłada, dostaje pole `_iskt_demo` — dane odniesienia są
 * treścią demonstracyjną w rozumieniu §9 i mają zniknąć razem z resztą przy
 * sprzątaniu z zadania 12.
 *
 * Skrypt jest idempotentny: rozpoznaje wpisy po slugu i aktualizuje je zamiast
 * mnożyć kopie.
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

if ( ! function_exists( 'iskt_oznacz_demo' ) ) {
	WP_CLI::error( 'Wtyczka iskt-szkolenia-core nie jest aktywna — dane wpadłyby do bazy bez oznaczenia demonstracyjnego.' );
}

require_once ABSPATH . 'wp-admin/includes/image.php';

/**
 * Zakłada w bibliotece obrazek o zadanej nazwie i zwraca jego identyfikator.
 *
 * Obrazek rysujemy zamiast wgrywać gotowy plik z repozytorium: paczka eksportu ma
 * przenieść bibliotekę mediów, więc dowód jest mocniejszy, gdy plik powstaje poza
 * repozytorium i nie może trafić do środowiska docelowego żadną inną drogą.
 *
 * Kolor i inicjały służą rozpoznaniu na zrzucie ekranu — po imporcie widać wtedy,
 * że to TEN plik, a nie zastępczy obrazek motywu.
 *
 * @param string $slug      Nazwa pliku bez rozszerzenia; służy też za klucz idempotencji.
 * @param string $inicjaly  Napis wypisywany na obrazku (wyłącznie ASCII — czcionka wbudowana GD).
 * @param array  $kolor     Trzy składowe RGB tła.
 *
 * @return int Identyfikator załącznika.
 */
function iskt_eksport_obrazek( $slug, $inicjaly, $kolor ) {
	$istniejace = get_posts(
		array(
			'post_type'        => 'attachment',
			'name'             => $slug,
			'post_status'      => 'inherit',
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'suppress_filters' => true,
		)
	);

	if ( array() !== $istniejace ) {
		return (int) $istniejace[0];
	}

	if ( ! function_exists( 'imagecreatetruecolor' ) ) {
		WP_CLI::error( 'Brak rozszerzenia GD — nie da się przygotować mediów do testu odtworzenia.' );
	}

	$plotno = imagecreatetruecolor( 800, 800 );
	imagefill( $plotno, 0, 0, imagecolorallocate( $plotno, $kolor[0], $kolor[1], $kolor[2] ) );

	$biel = imagecolorallocate( $plotno, 255, 255, 255 );
	imagestring( $plotno, 5, 360, 380, $inicjaly, $biel );
	imagerectangle( $plotno, 40, 40, 759, 759, $biel );

	ob_start();
	imagepng( $plotno );
	$bajty = ob_get_clean();
	imagedestroy( $plotno );

	$wgrane = wp_upload_bits( $slug . '.png', null, $bajty );

	if ( ! empty( $wgrane['error'] ) ) {
		WP_CLI::error( 'Nie udało się zapisać pliku ' . $slug . ': ' . $wgrane['error'] );
	}

	$zalacznik_id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/png',
			'post_title'     => 'Demo — zdjęcie ' . $inicjaly,
			'post_name'      => $slug,
			'post_status'    => 'inherit',
		),
		$wgrane['file'],
		0,
		true
	);

	if ( is_wp_error( $zalacznik_id ) ) {
		WP_CLI::error( 'Nie udało się dodać załącznika ' . $slug . ': ' . $zalacznik_id->get_error_message() );
	}

	wp_update_attachment_metadata(
		(int) $zalacznik_id,
		wp_generate_attachment_metadata( (int) $zalacznik_id, $wgrane['file'] )
	);

	update_post_meta( (int) $zalacznik_id, '_wp_attachment_image_alt', 'Zdjęcie profilowe trenera ' . $inicjaly );
	iskt_oznacz_demo( (int) $zalacznik_id );

	return (int) $zalacznik_id;
}

/**
 * Zakłada lub aktualizuje wpis rozpoznawany po slugu.
 *
 * @param string $typ  Typ treści.
 * @param array  $dane Pola wpisu; `post_name` jest kluczem idempotencji.
 *
 * @return int Identyfikator wpisu.
 */
function iskt_eksport_wpis( $typ, $dane ) {
	$istniejace = get_posts(
		array(
			'post_type'        => $typ,
			'name'             => $dane['post_name'],
			'post_status'      => 'any',
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'suppress_filters' => true,
		)
	);

	$dane['post_type']   = $typ;
	$dane['post_status'] = isset( $dane['post_status'] ) ? $dane['post_status'] : 'publish';

	if ( array() !== $istniejace ) {
		$dane['ID'] = (int) $istniejace[0];
		$id         = wp_update_post( $dane, true );
	} else {
		$id = wp_insert_post( $dane, true );
	}

	if ( is_wp_error( $id ) ) {
		WP_CLI::error( 'Nie udało się zapisać wpisu ' . $dane['post_name'] . ': ' . $id->get_error_message() );
	}

	iskt_oznacz_demo( (int) $id );

	return (int) $id;
}

/*
 * Trenerzy. Dwóch, nie jeden: profil z jednym szkoleniem nie odróżnia „relacja
 * przetrwała” od „trener przypadkiem trafił na pierwsze szkolenie z listy”.
 */
$iskt_trenerzy = array(
	'demo-anna-kowalczyk' => array(
		'tytul'    => 'Demo — Anna Kowalczyk',
		'tresc'    => 'Audytorka energetyczna z uprawnieniami, prowadzi warsztaty pomiarowe i szkolenia z raportowania ESG.',
		'rola'     => 'Audytorka energetyczna',
		'inicjaly' => 'AK',
		'kolor'    => array( 26, 87, 71 ),
		'pola'     => array(
			'_iskt_doswiadczenie' => '12 lat praktyki w audytach budynków użyteczności publicznej.',
			'_iskt_wyksztalcenie' => 'Politechnika Śląska, inżynieria środowiska.',
			'_iskt_specjalizacje' => "Audyt energetyczny\nTermowizja\nRaportowanie ESG",
		),
	),
	'demo-marek-zielinski' => array(
		'tytul'    => 'Demo — Marek Zieliński',
		'tresc'    => 'Programista i trener narzędzi generatywnych. Uczy zespoły korzystać z modeli językowych bez oddawania im danych firmy.',
		'rola'     => 'Trener narzędzi AI',
		'inicjaly' => 'MZ',
		'kolor'    => array( 31, 64, 104 ),
		'pola'     => array(
			'_iskt_doswiadczenie' => '9 lat wytwarzania oprogramowania, 4 lata prowadzenia szkoleń.',
			'_iskt_wyksztalcenie' => 'Uniwersytet Śląski, informatyka.',
			'_iskt_specjalizacje' => "Asystenci AI\nBezpieczeństwo danych\nAutomatyzacja pracy biurowej",
		),
	),
);

$iskt_id_trenerow = array();

foreach ( $iskt_trenerzy as $iskt_slug => $iskt_trener ) {
	$iskt_id = iskt_eksport_wpis(
		'iskt_trener',
		array(
			'post_name'    => $iskt_slug,
			'post_title'   => $iskt_trener['tytul'],
			'post_content' => $iskt_trener['tresc'],
			'post_excerpt' => $iskt_trener['rola'],
		)
	);

	foreach ( $iskt_trener['pola'] as $iskt_klucz => $iskt_wartosc ) {
		update_post_meta( $iskt_id, $iskt_klucz, $iskt_wartosc );
	}

	update_post_meta( $iskt_id, '_iskt_rola', $iskt_trener['rola'] );

	set_post_thumbnail(
		$iskt_id,
		iskt_eksport_obrazek( $iskt_slug . '-zdjecie', $iskt_trener['inicjaly'], $iskt_trener['kolor'] )
	);

	$iskt_id_trenerow[ $iskt_slug ] = $iskt_id;
}

/*
 * Powiązania. Anna prowadzi dwa szkolenia, Marek jedno, a jedno szkolenie ma obu —
 * dzięki temu widok szkolenia pokazuje listę, a nie pojedynczą pozycję, i widać
 * kolejność zapisaną w polu.
 */
$iskt_powiazania = array(
	'demo-audyt-energetyczny-budynkow'            => array( 'demo-anna-kowalczyk' ),
	'demo-raportowanie-esg-w-praktyce'            => array( 'demo-anna-kowalczyk', 'demo-marek-zielinski' ),
	'demo-bezpieczenstwo-danych-przy-narzedziach-ai' => array( 'demo-marek-zielinski' ),
);

$iskt_powiazane = 0;

foreach ( $iskt_powiazania as $iskt_szkolenie_slug => $iskt_slugi ) {
	$iskt_szkolenia = get_posts(
		array(
			'post_type'        => 'iskt_szkolenie',
			'name'             => $iskt_szkolenie_slug,
			'post_status'      => 'any',
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'suppress_filters' => true,
		)
	);

	if ( array() === $iskt_szkolenia ) {
		WP_CLI::error( 'Brak szkolenia ' . $iskt_szkolenie_slug . ' — najpierw uruchom dane-katalogu.php.' );
	}

	$iskt_lista = array();

	foreach ( $iskt_slugi as $iskt_slug ) {
		$iskt_lista[] = $iskt_id_trenerow[ $iskt_slug ];
	}

	update_post_meta( (int) $iskt_szkolenia[0], '_iskt_trenerzy', $iskt_lista );
	++$iskt_powiazane;
}

/*
 * Terminy. Jeden zakończony jest tu sednem: §11 pkt 5 wymaga, żeby taki termin nie
 * pokazywał się jako nadchodzący, a po imporcie sprawdzamy to na tej samej danej.
 * Daty liczymy względem chwili uruchomienia, więc dowód nie psuje się z upływem
 * czasu ani po odtworzeniu paczki tydzień później.
 */
$iskt_terminy = array(
	array(
		'slug'       => 'demo-termin-audyt-zakonczony',
		'tytul'      => 'Demo — termin zakończony',
		'szkolenie'  => 'demo-audyt-energetyczny-budynkow',
		'start'      => gmdate( 'Y-m-d', strtotime( '-40 days' ) ),
		'koniec'     => gmdate( 'Y-m-d', strtotime( '-39 days' ) ),
		'lokalizacja' => 'Katowice',
		'tryb'       => 'stacjonarnie',
	),
	array(
		'slug'       => 'demo-termin-audyt-najblizszy',
		'tytul'      => 'Demo — termin najbliższy',
		'szkolenie'  => 'demo-audyt-energetyczny-budynkow',
		'start'      => gmdate( 'Y-m-d', strtotime( '+21 days' ) ),
		'koniec'     => gmdate( 'Y-m-d', strtotime( '+22 days' ) ),
		'lokalizacja' => 'Katowice',
		'tryb'       => 'stacjonarnie',
	),
	array(
		'slug'       => 'demo-termin-audyt-kolejny',
		'tytul'      => 'Demo — termin kolejny',
		'szkolenie'  => 'demo-audyt-energetyczny-budynkow',
		'start'      => gmdate( 'Y-m-d', strtotime( '+70 days' ) ),
		'koniec'     => gmdate( 'Y-m-d', strtotime( '+71 days' ) ),
		'lokalizacja' => 'Gliwice',
		'tryb'       => 'hybrydowo',
	),
);

foreach ( $iskt_terminy as $iskt_termin ) {
	$iskt_szkolenia = get_posts(
		array(
			'post_type'        => 'iskt_szkolenie',
			'name'             => $iskt_termin['szkolenie'],
			'post_status'      => 'any',
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'suppress_filters' => true,
		)
	);

	if ( array() === $iskt_szkolenia ) {
		WP_CLI::error( 'Brak szkolenia ' . $iskt_termin['szkolenie'] . ' — najpierw uruchom dane-katalogu.php.' );
	}

	$iskt_id = iskt_eksport_wpis(
		'iskt_termin',
		array(
			'post_name'  => $iskt_termin['slug'],
			'post_title' => $iskt_termin['tytul'],
		)
	);

	// Powiązanie idzie polem, nie `post_parent` — tak czyta je `iskt_terminy_szkolenia()`.
	update_post_meta( $iskt_id, '_iskt_termin_szkolenie', (int) $iskt_szkolenia[0] );
	update_post_meta( $iskt_id, '_iskt_data_start', $iskt_termin['start'] );
	update_post_meta( $iskt_id, '_iskt_data_koniec', $iskt_termin['koniec'] );
	update_post_meta( $iskt_id, '_iskt_lokalizacja', $iskt_termin['lokalizacja'] );
	update_post_meta( $iskt_id, '_iskt_tryb', $iskt_termin['tryb'] );
	update_post_meta( $iskt_id, '_iskt_status_zgloszen', 'otwarte' );
	update_post_meta( $iskt_id, '_iskt_termin_indywidualny', false );
}

/*
 * Teksty globalne. Wartości celowo odbiegają od domyślnych i niosą w sobie ciąg
 * „ISK-34”, więc po imporcie widać różnicę między „opcja przetrwała” a „widok
 * wrócił do wartości domyślnej, bo opcji nie ma”.
 */
$iskt_teksty = get_option( 'iskt_teksty' );
$iskt_teksty = is_array( $iskt_teksty ) ? $iskt_teksty : array();

$iskt_teksty = array_merge(
	$iskt_teksty,
	array(
		'naglowek_menu'              => 'Menu ISK-34',
		'stopka_dopisek'             => 'Dopisek stopki ISK-34 — dowód, że teksty globalne przetrwały eksport.',
		'katalog_tytul'              => 'Katalog szkoleń ISK-34',
		'katalog_brak_wynikow_tytul' => 'Brak wyników ISK-34',
	)
);

update_option( 'iskt_teksty', $iskt_teksty );

WP_CLI::success(
	sprintf(
		'Dane odniesienia eksportu: %d trenerów, %d powiązań, %d terminów, teksty globalne nadpisane.',
		count( $iskt_id_trenerow ),
		$iskt_powiazane,
		count( $iskt_terminy )
	)
);
