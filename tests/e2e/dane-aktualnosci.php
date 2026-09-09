<?php
/**
 * Dane demonstracyjne aktualności dla `aktualnosci.spec.js`.
 *
 * Uruchamiane przez `wp eval-file`, więc BEZ `declare( strict_types )` — eval-file
 * wykonuje treść przez `eval()`, a deklaracja musi być pierwszą instrukcją pliku.
 *
 * Skrypt jest idempotentny: powtórne uruchomienie nie mnoży wpisów ani kategorii.
 * Test może więc wystartować na środowisku, na którym już był, i nie zobaczy
 * dziesięciu kopii tego samego artykułu.
 *
 * @package ISKT\Szkolenia\Tests
 */

defined( 'WP_CLI' ) || exit;

/** Kategoria wpisu → opis pokazywany na archiwum kategorii. */
$iskt_kategorie = array(
	'Demo — Wydarzenia ISKT' => 'Relacje ze szkoleń, konferencji i spotkań branżowych.',
	'Demo — Dofinansowania'  => 'Co się zmienia w naborach — bez obiecywania kwot wsparcia.',
);

$iskt_termy = array();

foreach ( $iskt_kategorie as $iskt_nazwa => $iskt_opis ) {
	$iskt_istnieje = get_term_by( 'name', $iskt_nazwa, 'category' );

	if ( $iskt_istnieje instanceof WP_Term ) {
		$iskt_termy[ $iskt_nazwa ] = (int) $iskt_istnieje->term_id;
		continue;
	}

	$iskt_wynik = wp_insert_term( $iskt_nazwa, 'category', array( 'description' => $iskt_opis ) );

	$iskt_termy[ $iskt_nazwa ] = is_array( $iskt_wynik ) ? (int) $iskt_wynik['term_id'] : 0;
}

/*
 * Dwa wpisy w dwóch kategoriach to minimum, na którym widać wszystko, co zadanie 8
 * obiecuje: filtr kategorii pojawia się dopiero przy drugiej kategorii, a odnośniki
 * „poprzedni/następny” wymagają sąsiada.
 */
$iskt_wpisy = array(
	array(
		'tytul'     => 'Demo — Podsumowanie warsztatów z narzędzi generatywnych',
		'kategoria' => 'Demo — Wydarzenia ISKT',
		'zajawka'   => 'Dwa dni, dwanaście osób i jeden wniosek: narzędzie bez procesu niczego nie przyspiesza.',
		'tresc'     => "<!-- wp:paragraph --><p>Warsztat zamknęliśmy ćwiczeniem, w którym uczestnicy porównywali własny proces sprzed i po.</p><!-- /wp:paragraph -->\n<!-- wp:heading --><h2>Co zabraliśmy ze sobą</h2><!-- /wp:heading -->\n<!-- wp:list --><ul><li>Proces przed narzędziem.</li><li>Miara przed obietnicą.</li></ul><!-- /wp:list -->",
		'data'      => '2026-08-20 09:00:00',
	),
	array(
		'tytul'     => 'Demo — Zmiany w naborach na dofinansowania szkoleń',
		'kategoria' => 'Demo — Dofinansowania',
		'zajawka'   => 'Terminy naborów przesunęły się o miesiąc. Zbieramy, co to znaczy dla planów szkoleniowych.',
		'tresc'     => '<!-- wp:paragraph --><p>Nabory ruszyły w innym rytmie niż rok temu. Poniżej stan na dziś, bez wskazywania procentu wsparcia.</p><!-- /wp:paragraph -->',
		'data'      => '2026-09-02 11:30:00',
	),
);

foreach ( $iskt_wpisy as $iskt_wpis ) {
	$iskt_znalezione = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'any',
			'title'          => $iskt_wpis['tytul'],
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);

	if ( array() !== $iskt_znalezione ) {
		WP_CLI::log( 'jest: ' . $iskt_wpis['tytul'] );
		continue;
	}

	$iskt_id = wp_insert_post(
		array(
			'post_type'     => 'post',
			'post_status'   => 'publish',
			'post_title'    => $iskt_wpis['tytul'],
			'post_excerpt'  => $iskt_wpis['zajawka'],
			'post_content'  => $iskt_wpis['tresc'],
			'post_date'     => $iskt_wpis['data'],
			'post_category' => array( $iskt_termy[ $iskt_wpis['kategoria'] ] ),
		)
	);

	WP_CLI::log( 'dodane: ' . $iskt_wpis['tytul'] . ' -> ' . get_permalink( $iskt_id ) );
}

/*
 * Sekcja domykająca artykuł pokazuje szkolenia WYRÓŻNIONE. Bez ani jednego
 * wyróżnionego szkolenia nie pojawia się w ogóle — i test sprawdzałby wtedy
 * nieobecność, biorąc ją za poprawne zachowanie.
 */
$iskt_wyroznione = get_posts(
	array(
		'post_type'      => 'iskt_szkolenie',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'meta_key'       => '_iskt_wyroznione',
		'meta_value'     => '1',
		'fields'         => 'ids',
	)
);

if ( array() === $iskt_wyroznione ) {
	$iskt_pierwsze = get_posts(
		array(
			'post_type'      => 'iskt_szkolenie',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);

	if ( array() !== $iskt_pierwsze ) {
		update_post_meta( (int) $iskt_pierwsze[0], '_iskt_wyroznione', '1' );
		WP_CLI::log( 'wyróżnione szkolenie: ' . get_the_title( (int) $iskt_pierwsze[0] ) );
	}
}

WP_CLI::log( 'lista: ' . get_permalink( (int) get_option( 'page_for_posts' ) ) );
