<?php
/**
 * Typy treści i taksonomie katalogu.
 *
 * Rejestracja mieszka we wtyczce, nie w motywie — §6 wymaga, aby zmiana motywu
 * nie usuwała danych katalogu (docs/ADR-001-architektura.md §1).
 *
 * @package ISKT\Szkolenia\Core
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Rejestruje typy treści katalogu.
 */
function iskt_rejestruj_typy_tresci(): void {
	$uprawnienia = iskt_typy_z_uprawnieniami();

	register_post_type(
		ISKT_CPT_SZKOLENIE,
		array(
			'labels'              => array(
				'name'                  => __( 'Szkolenia', 'iskt-szkolenia-core' ),
				'singular_name'         => __( 'Szkolenie', 'iskt-szkolenia-core' ),
				'add_new'               => __( 'Dodaj szkolenie', 'iskt-szkolenia-core' ),
				'add_new_item'          => __( 'Dodaj nowe szkolenie', 'iskt-szkolenia-core' ),
				'edit_item'             => __( 'Edytuj szkolenie', 'iskt-szkolenia-core' ),
				'new_item'              => __( 'Nowe szkolenie', 'iskt-szkolenia-core' ),
				'view_item'             => __( 'Zobacz szkolenie', 'iskt-szkolenia-core' ),
				'search_items'          => __( 'Szukaj szkoleń', 'iskt-szkolenia-core' ),
				'not_found'             => __( 'Nie znaleziono szkoleń', 'iskt-szkolenia-core' ),
				'not_found_in_trash'    => __( 'Brak szkoleń w koszu', 'iskt-szkolenia-core' ),
				'all_items'             => __( 'Wszystkie szkolenia', 'iskt-szkolenia-core' ),
				'archives'              => __( 'Katalog szkoleń', 'iskt-szkolenia-core' ),
				'featured_image'        => __( 'Grafika szkolenia', 'iskt-szkolenia-core' ),
				'set_featured_image'    => __( 'Ustaw grafikę szkolenia', 'iskt-szkolenia-core' ),
				'remove_featured_image' => __( 'Usuń grafikę szkolenia', 'iskt-szkolenia-core' ),
				'menu_name'             => __( 'Szkolenia', 'iskt-szkolenia-core' ),
			),
			'description'         => __( 'Oferta szkoleniowa prezentowana w katalogu.', 'iskt-szkolenia-core' ),
			'public'              => true,
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-welcome-learn-more',
			'menu_position'       => 20,
			// Katalog to `/szkolenia/`, pojedyncze szkolenie `/szkolenia/{slug}/` — §4.3 wymaga trwałego adresu.
			'has_archive'         => 'szkolenia',
			'rewrite'             => array(
				'slug'       => 'szkolenia',
				'with_front' => false,
			),
			'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes' ),
			'taxonomies'          => array( ISKT_TAX_KATEGORIA, ISKT_TAX_FORMA ),
			'capability_type'     => 'iskt_szkolenie',
			'capabilities'        => iskt_capabilities_dla( ...$uprawnienia[ ISKT_CPT_SZKOLENIE ] ),
			'map_meta_cap'        => true,
			'exclude_from_search' => false,
		)
	);

	register_post_type(
		ISKT_CPT_TRENER,
		array(
			'labels'              => array(
				'name'                  => __( 'Trenerzy', 'iskt-szkolenia-core' ),
				'singular_name'         => __( 'Trener', 'iskt-szkolenia-core' ),
				'add_new'               => __( 'Dodaj trenera', 'iskt-szkolenia-core' ),
				'add_new_item'          => __( 'Dodaj nowego trenera', 'iskt-szkolenia-core' ),
				'edit_item'             => __( 'Edytuj trenera', 'iskt-szkolenia-core' ),
				'new_item'              => __( 'Nowy trener', 'iskt-szkolenia-core' ),
				'view_item'             => __( 'Zobacz profil trenera', 'iskt-szkolenia-core' ),
				'search_items'          => __( 'Szukaj trenerów', 'iskt-szkolenia-core' ),
				'not_found'             => __( 'Nie znaleziono trenerów', 'iskt-szkolenia-core' ),
				'not_found_in_trash'    => __( 'Brak trenerów w koszu', 'iskt-szkolenia-core' ),
				'all_items'             => __( 'Wszyscy trenerzy', 'iskt-szkolenia-core' ),
				'featured_image'        => __( 'Zdjęcie trenera', 'iskt-szkolenia-core' ),
				'set_featured_image'    => __( 'Ustaw zdjęcie trenera', 'iskt-szkolenia-core' ),
				'remove_featured_image' => __( 'Usuń zdjęcie trenera', 'iskt-szkolenia-core' ),
				'menu_name'             => __( 'Trenerzy', 'iskt-szkolenia-core' ),
			),
			'description'         => __( 'Profile trenerów prowadzących szkolenia.', 'iskt-szkolenia-core' ),
			'public'              => true,
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-groups',
			'menu_position'       => 21,
			'has_archive'         => 'trenerzy',
			'rewrite'             => array(
				'slug'       => 'trenerzy',
				'with_front' => false,
			),
			// Biografia w treści wpisu, krótki opis w zajawce — §4.5 wymaga obu.
			'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes' ),
			'capability_type'     => 'iskt_trener',
			'capabilities'        => iskt_capabilities_dla( ...$uprawnienia[ ISKT_CPT_TRENER ] ),
			'map_meta_cap'        => true,
			'exclude_from_search' => false,
		)
	);

	register_post_type(
		ISKT_CPT_TERMIN,
		array(
			'labels'             => array(
				'name'               => __( 'Terminy', 'iskt-szkolenia-core' ),
				'singular_name'      => __( 'Termin', 'iskt-szkolenia-core' ),
				'add_new'            => __( 'Dodaj termin', 'iskt-szkolenia-core' ),
				'add_new_item'       => __( 'Dodaj nowy termin', 'iskt-szkolenia-core' ),
				'edit_item'          => __( 'Edytuj termin', 'iskt-szkolenia-core' ),
				'new_item'           => __( 'Nowy termin', 'iskt-szkolenia-core' ),
				'search_items'       => __( 'Szukaj terminów', 'iskt-szkolenia-core' ),
				'not_found'          => __( 'Nie znaleziono terminów', 'iskt-szkolenia-core' ),
				'not_found_in_trash' => __( 'Brak terminów w koszu', 'iskt-szkolenia-core' ),
				'all_items'          => __( 'Terminy', 'iskt-szkolenia-core' ),
				'menu_name'          => __( 'Terminy', 'iskt-szkolenia-core' ),
			),
			'description'        => __( 'Terminy realizacji szkoleń. Prezentowane na stronie szkolenia, bez własnego adresu.', 'iskt-szkolenia-core' ),

			/*
			 * Termin nie ma własnej strony publicznej — §4.4 opisuje go jako element
			 * strony szkolenia. `public => false` przy `show_ui => true` daje ekran
			 * w panelu bez tworzenia adresu, który trzeba by pozycjonować i obsłużyć.
			 */
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => 'edit.php?post_type=' . ISKT_CPT_SZKOLENIE,
			'show_in_rest'       => false,
			'has_archive'        => false,
			'rewrite'            => false,

			// Tytuł terminu generujemy z daty i szkolenia — redaktor go nie wpisuje. Patrz terminy.php.
			'supports'           => array( 'title' ),
			'capability_type'    => 'iskt_termin',
			'capabilities'       => iskt_capabilities_dla( ...$uprawnienia[ ISKT_CPT_TERMIN ] ),
			'map_meta_cap'       => true,
		)
	);
}
add_action( 'init', 'iskt_rejestruj_typy_tresci' );

/*
 * Taksonomie rejestrujemy PRZED typami treści (priorytet 9 wobec domyślnego 10),
 * bo o kolejności sprawdzania reguł adresów decyduje kolejność ich rejestracji.
 * Bez tego reguła załączników szkolenia — `szkolenia/{cokolwiek}/{cokolwiek}` —
 * przechwytuje `/szkolenia/kategoria/esg-i-zrownowazony-rozwoj/` i oddaje
 * odwiedzającemu stronę „nie znaleziono”. Kafelki obszarów na stronie głównej
 * prowadzą dokładnie pod te adresy (§4.1).
 *
 * Kolejność musi być taka sama przy aktywacji wtyczki (`activation.php`), bo to
 * tam po raz pierwszy przeliczamy reguły.
 */

/**
 * Rejestruje taksonomie katalogu.
 */
function iskt_rejestruj_taksonomie(): void {
	register_taxonomy(
		ISKT_TAX_KATEGORIA,
		array( ISKT_CPT_SZKOLENIE ),
		array(
			'labels'            => array(
				'name'              => __( 'Kategorie szkoleń', 'iskt-szkolenia-core' ),
				'singular_name'     => __( 'Kategoria szkolenia', 'iskt-szkolenia-core' ),
				'add_new_item'      => __( 'Dodaj kategorię', 'iskt-szkolenia-core' ),
				'edit_item'         => __( 'Edytuj kategorię', 'iskt-szkolenia-core' ),
				'search_items'      => __( 'Szukaj kategorii', 'iskt-szkolenia-core' ),
				'all_items'         => __( 'Wszystkie kategorie', 'iskt-szkolenia-core' ),
				'not_found'         => __( 'Nie znaleziono kategorii', 'iskt-szkolenia-core' ),
				'back_to_items'     => __( 'Wróć do kategorii', 'iskt-szkolenia-core' ),
				'menu_name'         => __( 'Kategorie', 'iskt-szkolenia-core' ),
			),

			/*
			 * Hierarchiczna, mimo że na starcie hierarchii nie ma. Powód: interfejs
			 * z listą do zaznaczenia jest dla nietechnicznego redaktora czytelniejszy
			 * niż pole tagów, w którym literówka tworzy nową kategorię. §4.2 wymaga,
			 * aby kategorie były edytowalne — nie żeby były płaskie.
			 */
			'hierarchical'      => true,
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array(
				'slug'       => 'szkolenia/kategoria',
				'with_front' => false,
			),
			'capabilities'      => array(
				'manage_terms' => 'edit_iskt_szkolenia',
				'edit_terms'   => 'edit_iskt_szkolenia',
				'delete_terms' => 'edit_iskt_szkolenia',
				'assign_terms' => 'edit_iskt_szkolenia',
			),
		)
	);

	register_taxonomy(
		ISKT_TAX_FORMA,
		array( ISKT_CPT_SZKOLENIE ),
		array(
			'labels'            => array(
				'name'          => __( 'Formy realizacji', 'iskt-szkolenia-core' ),
				'singular_name' => __( 'Forma realizacji', 'iskt-szkolenia-core' ),
				'add_new_item'  => __( 'Dodaj formę', 'iskt-szkolenia-core' ),
				'edit_item'     => __( 'Edytuj formę', 'iskt-szkolenia-core' ),
				'all_items'     => __( 'Wszystkie formy', 'iskt-szkolenia-core' ),
				'menu_name'     => __( 'Formy realizacji', 'iskt-szkolenia-core' ),
			),

			/*
			 * Forma jako taksonomia, nie pole — §4.2 wymaga filtrowania katalogu po
			 * formie. Taksonomia daje wydajne filtrowanie bez `meta_query`.
			 */
			'hierarchical'      => true,
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array(
				'slug'       => 'szkolenia/forma',
				'with_front' => false,
			),
			'capabilities'      => array(
				'manage_terms' => 'edit_iskt_szkolenia',
				'edit_terms'   => 'edit_iskt_szkolenia',
				'delete_terms' => 'edit_iskt_szkolenia',
				'assign_terms' => 'edit_iskt_szkolenia',
			),
		)
	);
}
add_action( 'init', 'iskt_rejestruj_taksonomie', 9 );

/**
 * Porządkuje listę trenerów pod `/trenerzy/`.
 *
 * Domyślna kolejność archiwum to data publikacji — dla listy ludzi jest to
 * porządek, którego odwiedzający nie zna i nie potrafi przewidzieć. Alfabetycznie
 * da się przeszukać wzrokiem, a atrybut „Kolejność” (page-attributes) pozwala
 * właścicielowi wysunąć kogoś na początek z panelu, bez dotykania kodu.
 *
 * @param WP_Query $zapytanie Zapytanie w trakcie przygotowania.
 */
function iskt_kolejnosc_trenerow( WP_Query $zapytanie ): void {
	if ( is_admin() || ! $zapytanie->is_main_query() || ! $zapytanie->is_post_type_archive( ISKT_CPT_TRENER ) ) {
		return;
	}

	$zapytanie->set(
		'orderby',
		array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		)
	);
}
add_action( 'pre_get_posts', 'iskt_kolejnosc_trenerow' );

/*
 * Trwałość adresu (§4.3) nie wymaga tu żadnego kodu. Po publikacji edytor odsyła
 * istniejący `post_name`, więc zmiana tytułu nie przelicza sluga. Rozważaliśmy filtr
 * `wp_unique_post_slug`, który wymuszałby stary slug — odrzucony, bo odebrałby
 * właścicielowi możliwość świadomej zmiany adresu w polu odnośnika bezpośredniego,
 * a tego zlecenie nie wyklucza. Punkt wchodzi na listę testów QA zamiast do kodu.
 */
