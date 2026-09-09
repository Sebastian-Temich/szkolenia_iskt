<?php
/**
 * Panel: widoczność i usuwanie danych demonstracyjnych.
 *
 * Dwie rzeczy, których §8 i §9 wymagają od strony właściciela, a których sam
 * mechanizm oznaczania (`includes/demo.php`) jeszcze nie daje:
 *
 * 1. **poznanie wpisu bez otwierania go** — plakietka „Dane demonstracyjne”
 *    na listach w panelu, tam gdzie WordPress pokazuje „Szkic” czy „Prywatny”;
 * 2. **usunięcie jednym działaniem** — ekran „Szkolenia → Dane demonstracyjne”
 *    z pełnym spisem i jednym formularzem, który kasuje wszystko naraz.
 *
 * Ekran jest jednocześnie potwierdzeniem: pokazuje co do sztuki, co zniknie,
 * zanim cokolwiek zniknie. Osobne okno „na pewno?” nic by tu nie dodało poza
 * zależnością od JavaScriptu.
 *
 * @package ISKT\Szkolenia\Core
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Identyfikator ekranu.
 */
const ISKT_EKRAN_DEMO = 'iskt-dane-demonstracyjne';

/**
 * Akcja usuwania obsługiwana przez `admin-post.php`.
 */
const ISKT_AKCJA_USUN_DEMO = 'iskt_usun_dane_demonstracyjne';

/**
 * Dodaje ekran do menu „Szkolenia”.
 */
function iskt_dodaj_ekran_demo(): void {
	add_submenu_page(
		'edit.php?post_type=' . ISKT_CPT_SZKOLENIE,
		__( 'Dane demonstracyjne', 'iskt-szkolenia-core' ),
		__( 'Dane demonstracyjne', 'iskt-szkolenia-core' ),
		iskt_cap_demo(),
		ISKT_EKRAN_DEMO,
		'iskt_renderuj_ekran_demo'
	);
}
add_action( 'admin_menu', 'iskt_dodaj_ekran_demo' );

/**
 * Adres ekranu danych demonstracyjnych.
 */
function iskt_adres_ekranu_demo(): string {
	return add_query_arg(
		array(
			'post_type' => ISKT_CPT_SZKOLENIE,
			'page'      => ISKT_EKRAN_DEMO,
		),
		admin_url( 'edit.php' )
	);
}

/**
 * Zwraca czytelną nazwę typu treści.
 *
 * @param string $typ Nazwa typu.
 */
function iskt_nazwa_typu_demo( string $typ ): string {
	$obiekt = get_post_type_object( $typ );

	return $obiekt instanceof WP_Post_Type ? (string) $obiekt->labels->name : $typ;
}

/**
 * Zwraca czytelną nazwę taksonomii.
 *
 * @param string $taksonomia Nazwa taksonomii.
 */
function iskt_nazwa_taksonomii_demo( string $taksonomia ): string {
	$obiekt = get_taxonomy( $taksonomia );

	return $obiekt instanceof WP_Taxonomy ? (string) $obiekt->labels->name : $taksonomia;
}

/**
 * Renderuje ekran danych demonstracyjnych.
 */
function iskt_renderuj_ekran_demo(): void {
	if ( ! current_user_can( iskt_cap_demo() ) ) {
		wp_die( esc_html__( 'Nie masz uprawnień do zarządzania danymi demonstracyjnymi.', 'iskt-szkolenia-core' ) );
	}

	$wpisy = iskt_wpisy_demo();
	$hasla = iskt_hasla_demo();
	$razem = iskt_suma_spisu( $wpisy ) + iskt_suma_spisu( $hasla );

	echo '<div class="wrap iskt-demo">';
	echo '<h1>' . esc_html__( 'Dane demonstracyjne', 'iskt-szkolenia-core' ) . '</h1>';

	printf(
		'<p class="description" style="max-width:46em">%s</p>',
		esc_html__( 'Wpisy założone na potrzeby pokazu i testów. Nie są ofertą ISKT i nie powinny zostać w serwisie po przekazaniu. Rozpoznajemy je po ukrytym polu, a nie po tytule — zmiana tytułu nie wyprowadzi wpisu ze spisu.', 'iskt-szkolenia-core' )
	);

	iskt_komunikat_po_usunieciu();

	if ( 0 === $razem ) {
		printf(
			'<div class="notice notice-success inline"><p>%s</p></div>',
			esc_html__( 'Nie ma żadnych danych demonstracyjnych. W serwisie stoją wyłącznie treści założone przez Ciebie.', 'iskt-szkolenia-core' )
		);
		echo '</div>';

		return;
	}

	iskt_renderuj_spis_demo( $wpisy, $hasla );
	iskt_renderuj_formularz_usuwania( $razem );

	echo '</div>';
}

/**
 * Wypisuje komunikat po powrocie z akcji usuwania.
 */
function iskt_komunikat_po_usunieciu(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- odczyt komunikatu po przekierowaniu, bez skutków ubocznych.
	if ( ! isset( $_GET['usunieto'] ) ) {
		return;
	}

	$liczby = array(
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- jak wyżej.
		'wpisy'     => isset( $_GET['wpisy'] ) ? absint( wp_unslash( $_GET['wpisy'] ) ) : 0,
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- jak wyżej.
		'hasla'     => isset( $_GET['hasla'] ) ? absint( wp_unslash( $_GET['hasla'] ) ) : 0,
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- jak wyżej.
		'pominiete' => isset( $_GET['pominiete'] ) ? absint( wp_unslash( $_GET['pominiete'] ) ) : 0,
	);

	printf(
		'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
		esc_html( iskt_podsumowanie_usuwania( $liczby ) )
	);
}

/**
 * Renderuje spis tego, co zniknie.
 *
 * Spis jest imienny, nie zbiorczy. „12 szkoleń” nie pozwala właścicielowi
 * sprawdzić, czy przypadkiem nie wpisał czegoś swojego w wpis demonstracyjny —
 * lista tytułów pozwala.
 *
 * @param array<string, WP_Post[]> $wpisy Wpisy pogrupowane po typie.
 * @param array<string, WP_Term[]> $hasla Hasła pogrupowane po taksonomii.
 */
function iskt_renderuj_spis_demo( array $wpisy, array $hasla ): void {
	echo '<h2>' . esc_html__( 'Co zostanie usunięte', 'iskt-szkolenia-core' ) . '</h2>';

	foreach ( $wpisy as $typ => $lista ) {
		printf(
			'<h3>%s <span class="count">(%d)</span></h3>',
			esc_html( iskt_nazwa_typu_demo( (string) $typ ) ),
			count( $lista )
		);

		echo '<ul class="iskt-demo-spis">';

		foreach ( $lista as $wpis ) {
			$odnosnik = get_edit_post_link( $wpis->ID );
			$tytul    = '' !== $wpis->post_title ? $wpis->post_title : __( '(bez tytułu)', 'iskt-szkolenia-core' );

			printf(
				'<li>%s</li>',
				null === $odnosnik
					? esc_html( $tytul )
					: sprintf( '<a href="%s">%s</a>', esc_url( $odnosnik ), esc_html( $tytul ) )
			);
		}

		echo '</ul>';
	}

	foreach ( $hasla as $taksonomia => $lista ) {
		printf(
			'<h3>%s <span class="count">(%d)</span></h3>',
			esc_html( iskt_nazwa_taksonomii_demo( (string) $taksonomia ) ),
			count( $lista )
		);

		echo '<ul class="iskt-demo-spis">';

		foreach ( $lista as $haslo ) {
			printf( '<li>%s</li>', esc_html( $haslo->name ) );
		}

		echo '</ul>';
	}
}

/**
 * Renderuje formularz usuwania.
 *
 * @param int $razem Liczba pozycji objętych usunięciem.
 */
function iskt_renderuj_formularz_usuwania( int $razem ): void {
	echo '<h2>' . esc_html__( 'Usunięcie', 'iskt-szkolenia-core' ) . '</h2>';

	echo '<div class="notice notice-warning inline"><p>';
	echo esc_html__( 'Usuwamy z pominięciem kosza — wpisów nie da się przywrócić z panelu. Nie ruszamy niczego, co nie ma oznaczenia: Twoje szkolenia, trenerzy, aktualności i teksty serwisu zostają nietknięte.', 'iskt-szkolenia-core' );
	echo '</p><p>';
	echo esc_html__( 'Jeśli Twój termin był przypisany do szkolenia demonstracyjnego, po usunięciu straci powiązanie — lista terminów oznaczy go wtedy na czerwono.', 'iskt-szkolenia-core' );
	echo '</p></div>';

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="' . esc_attr( ISKT_AKCJA_USUN_DEMO ) . '">';
	wp_nonce_field( ISKT_AKCJA_USUN_DEMO );

	printf(
		'<p><label><input type="checkbox" name="potwierdzenie" value="1" required> %s</label></p>',
		esc_html(
			sprintf(
				/* translators: %d: liczba pozycji do usunięcia. */
				__( 'Rozumiem, że usunięcie jest nieodwracalne. Usuń wszystkie pozycje (%d).', 'iskt-szkolenia-core' ),
				$razem
			)
		)
	);

	printf(
		'<p><button type="submit" class="button button-primary">%s</button></p>',
		esc_html__( 'Usuń dane demonstracyjne', 'iskt-szkolenia-core' )
	);

	echo '</form>';
}

/**
 * Przyjmuje zgłoszenie usunięcia.
 */
function iskt_obsluz_usuwanie_demo(): void {
	if ( ! current_user_can( iskt_cap_demo() ) ) {
		wp_die( esc_html__( 'Nie masz uprawnień do usuwania danych demonstracyjnych.', 'iskt-szkolenia-core' ) );
	}

	check_admin_referer( ISKT_AKCJA_USUN_DEMO );

	/*
	 * Potwierdzenie sprawdzamy też po stronie serwera. Atrybut `required` chroni
	 * przed pomyłką w przeglądarce, ale żądanie da się wysłać z pominięciem
	 * formularza, a to jest akcja kasująca treść.
	 */
	if ( ! isset( $_POST['potwierdzenie'] ) || '1' !== (string) wp_unslash( $_POST['potwierdzenie'] ) ) {
		wp_safe_redirect( iskt_adres_ekranu_demo() );

		exit;
	}

	$liczby = iskt_usun_dane_demonstracyjne();

	wp_safe_redirect(
		add_query_arg(
			array(
				'usunieto'  => '1',
				'wpisy'     => $liczby['wpisy'],
				'hasla'     => $liczby['hasla'],
				'pominiete' => $liczby['pominiete'],
			),
			iskt_adres_ekranu_demo()
		)
	);

	exit;
}
add_action( 'admin_post_' . ISKT_AKCJA_USUN_DEMO, 'iskt_obsluz_usuwanie_demo' );

/**
 * Dokłada plakietkę „Dane demonstracyjne” na listach wpisów.
 *
 * Korzystamy z natywnego mechanizmu stanów wpisu — tego samego, który pokazuje
 * „Szkic” i „Strona główna”. Właściciel nie musi się uczyć nowego oznaczenia,
 * a plakietka pojawia się na każdej liście, także w wyszukiwarce panelu.
 *
 * @param array<string, string> $stany Stany wpisu.
 * @param WP_Post               $post  Wpis.
 *
 * @return array<string, string>
 */
function iskt_stan_wpisu_demo( array $stany, WP_Post $post ): array {
	if ( iskt_czy_demo( (int) $post->ID ) ) {
		$stany['iskt_demo'] = __( 'Dane demonstracyjne', 'iskt-szkolenia-core' );
	}

	return $stany;
}
add_filter( 'display_post_states', 'iskt_stan_wpisu_demo', 10, 2 );

/**
 * Dokłada kolumnę „Demo” do list haseł taksonomii.
 *
 * Hasła taksonomii nie mają odpowiednika `display_post_states`, a kategoria
 * „Demo — Dofinansowania” wygląda na liście dokładnie jak kategoria właściciela.
 *
 * @param array<string, string> $kolumny Kolumny listy.
 *
 * @return array<string, string>
 */
function iskt_kolumna_demo_taksonomii( array $kolumny ): array {
	$kolumny['iskt_demo'] = __( 'Demo', 'iskt-szkolenia-core' );

	return $kolumny;
}

/**
 * Wypełnia kolumnę „Demo” na liście haseł taksonomii.
 *
 * @param string $tresc   Dotychczasowa treść komórki.
 * @param string $kolumna Nazwa kolumny.
 * @param int    $term_id Identyfikator hasła.
 *
 * @return string
 */
function iskt_zawartosc_kolumny_demo_taksonomii( string $tresc, string $kolumna, int $term_id ): string {
	if ( 'iskt_demo' !== $kolumna ) {
		return $tresc;
	}

	return iskt_czy_demo_termin( $term_id )
		? '<span class="iskt-ostrzezenie">' . esc_html__( 'demonstracyjne', 'iskt-szkolenia-core' ) . '</span>'
		: '—';
}

/**
 * Podpina kolumnę „Demo” pod objęte taksonomie.
 */
function iskt_podepnij_kolumne_demo(): void {
	foreach ( iskt_taksonomie_demo() as $taksonomia ) {
		add_filter( 'manage_edit-' . $taksonomia . '_columns', 'iskt_kolumna_demo_taksonomii' );
		add_filter( 'manage_' . $taksonomia . '_custom_column', 'iskt_zawartosc_kolumny_demo_taksonomii', 10, 3 );
	}
}
add_action( 'admin_init', 'iskt_podepnij_kolumne_demo' );

/**
 * Style spisu i plakietki.
 */
function iskt_style_demo(): void {
	$ekran = get_current_screen();

	if ( null === $ekran ) {
		return;
	}

	if ( 'edit-tags' === $ekran->base ) {
		wp_add_inline_style( 'list-tables', '.iskt-ostrzezenie{color:#b32d2e;font-weight:600}' );

		return;
	}

	if ( false === strpos( (string) $ekran->id, ISKT_EKRAN_DEMO ) ) {
		return;
	}

	wp_add_inline_style(
		'common',
		'.iskt-demo-spis{margin:0 0 1.5em;columns:2;max-width:60em}.iskt-demo-spis li{margin:0 0 .25em;break-inside:avoid}'
	);
}
add_action( 'admin_enqueue_scripts', 'iskt_style_demo' );
