<?php
/**
 * Pierwsze uruchomienie serwisu.
 *
 * Bez tego kroku świeży WordPress z włączonym motywem pokazuje pod adresem głównym
 * listę wpisów z „Hello world!”, a nie stronę główną ISKT — `front-page.php` oddaje
 * sterowanie do `index.php`, dopóki właściciel nie ustawi strony statycznej. §2 mówi
 * wprost, że rezultatem ma być kompletna, działająca witryna, a §11 pkt 10 zabrania
 * domyślnych treści WordPressa. Dlatego motyw zakłada stronę główną sam.
 *
 * Wszystko dzieje się DOKŁADNIE RAZ na instalację i nigdy nie nadpisuje decyzji
 * właściciela: ponowne włączenie motywu po zmianie ustawień niczego nie cofa.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Znacznik wykonanej instalacji.
 */
const ISKT_OPCJA_INSTALACJA = 'iskt_instalacja_wykonana';

/**
 * Składa treść strony głównej z tych samych wzorców sekcji, co wzorzec z edytora.
 *
 * Strona powstaje jako zwykła treść blokowa, więc od pierwszej chwili właściciel
 * przestawia i usuwa sekcje w edytorze — zgodnie z §4.7 nie ma tu nic, czego nie
 * dałoby się zmienić bez dotykania PHP.
 */
function iskt_tresc_strony_glownej(): string {
	$tresc = '';

	foreach ( iskt_sekcje_strony_glownej() as $sekcja ) {
		$tresc .= iskt_wzorzec_sekcji( $sekcja );
	}

	return trim( $tresc );
}

/**
 * Zwraca identyfikator strony o podanym adresie, tworząc ją, gdy jeszcze nie istnieje.
 *
 * Wyszukanie po slugu przed wstawieniem jest tu istotne: gdyby instalacja tworzyła
 * stronę bezwarunkowo, powtórne wywołanie zostawiłoby drugą „Stronę główną”
 * pod adresem `strona-glowna-2`.
 *
 * @param string $slug   Adres strony.
 * @param string $tytul  Tytuł strony.
 * @param string $tresc  Treść blokowa strony.
 * @return int Identyfikator strony albo 0, gdy nie udało się jej utworzyć.
 */
function iskt_zapewnij_strone( string $slug, string $tytul, string $tresc = '' ): int {
	$istniejaca = get_page_by_path( $slug, OBJECT, 'page' );

	if ( $istniejaca instanceof WP_Post ) {
		return (int) $istniejaca->ID;
	}

	$id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_name'    => $slug,
			'post_title'   => $tytul,
			'post_content' => $tresc,
			'post_status'  => 'publish',
		),
		true
	);

	if ( is_wp_error( $id ) ) {
		return 0;
	}

	return (int) $id;
}

/**
 * Usuwa przykładową treść WordPressa, ale wyłącznie nietkniętą.
 *
 * §8 wymaga możliwości usunięcia danych demonstracyjnych, a §11 pkt 10 zabrania
 * zostawiania domyślnych wpisów. Kasujemy jednak tylko wtedy, gdy data modyfikacji
 * równa się dacie utworzenia — wpis, który ktokolwiek otworzył i zapisał, mógł już
 * zostać przerobiony na prawdziwą treść i nie jest nasz do usuwania. Trafia do kosza,
 * nie do bezpowrotnego skasowania.
 */
function iskt_usun_tresc_przykladowa(): void {
	foreach ( array( 'hello-world' => 'post', 'sample-page' => 'page' ) as $slug => $typ ) {
		$wpis = get_page_by_path( $slug, OBJECT, $typ );

		if ( ! $wpis instanceof WP_Post ) {
			continue;
		}

		if ( $wpis->post_modified_gmt !== $wpis->post_date_gmt ) {
			continue;
		}

		wp_trash_post( (int) $wpis->ID );
	}
}

/**
 * Odkłada widżety, które WordPress sam przeniósł do stopki motywu.
 *
 * Przy przejściu na nowy motyw WordPress przepisuje widżety ze starego obszaru do
 * nowego. Na świeżej instalacji oznacza to, że w stopce ISKT lądują domyślne
 * „Archives”, „Categories” i „Meta” — po angielsku, z linkiem do logowania.
 * Dokładnie to, czego zabrania §11 pkt 10.
 *
 * Uruchamiamy to wyłącznie przy pierwszym włączeniu motywu, więc w stopce nie może
 * jeszcze być niczego, co ułożył właściciel. Widżety trafiają do „nieaktywnych”,
 * a nie do kosza — gdyby któryś był potrzebny, wraca przeciągnięciem w panelu.
 */
function iskt_odloz_widzety_domyslne(): void {
	$obszary = get_option( 'sidebars_widgets' );

	if ( ! is_array( $obszary ) || empty( $obszary['iskt-footer'] ) ) {
		return;
	}

	$nieaktywne = isset( $obszary['wp_inactive_widgets'] ) && is_array( $obszary['wp_inactive_widgets'] )
		? $obszary['wp_inactive_widgets']
		: array();

	$obszary['wp_inactive_widgets'] = array_merge( $nieaktywne, (array) $obszary['iskt-footer'] );
	$obszary['iskt-footer']         = array();

	update_option( 'sidebars_widgets', $obszary );
}

/**
 * Zakłada stronę główną i listę aktualności przy pierwszym włączeniu motywu.
 */
function iskt_pierwsze_uruchomienie(): void {
	if ( (bool) get_option( ISKT_OPCJA_INSTALACJA, false ) ) {
		return;
	}

	/*
	 * Znacznik ustawiamy na wejściu, nie na wyjściu. Gdyby któryś krok skończył się
	 * błędem, powtórne włączenie motywu nie próbowałoby zakładać stron od nowa.
	 */
	update_option( ISKT_OPCJA_INSTALACJA, true, false );

	$strona_glowna = iskt_zapewnij_strone(
		'strona-glowna',
		__( 'Strona główna', 'iskt-szkolenia' ),
		iskt_tresc_strony_glownej()
	);

	$aktualnosci = iskt_zapewnij_strone( 'aktualnosci', __( 'Aktualności', 'iskt-szkolenia' ) );

	/*
	 * Ustawienia strony startowej ruszamy tylko wtedy, gdy właściciel jeszcze nic tu
	 * nie wybrał. Instalacja ma dołożyć brakującą konfigurację, nie przestawiać cudzą.
	 */
	if ( $strona_glowna > 0 && 0 === (int) get_option( 'page_on_front' ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $strona_glowna );

		if ( $aktualnosci > 0 ) {
			update_option( 'page_for_posts', $aktualnosci );
		}
	}

	/*
	 * §7: środowisko wewnętrzne pozostaje poza indeksem. Włączenie indeksowania na
	 * docelowej domenie jest punktem instrukcji publikacji, a nie czynnością
	 * wykonywaną tutaj — dlatego domyślnie zamykamy, a nie otwieramy.
	 */
	update_option( 'blog_public', '0' );

	iskt_usun_tresc_przykladowa();
	iskt_odloz_widzety_domyslne();
}
add_action( 'after_switch_theme', 'iskt_pierwsze_uruchomienie' );
