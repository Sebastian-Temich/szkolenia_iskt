<?php
/**
 * Panel edycji pól katalogu — natywne skrzynki metadanych.
 *
 * Bez ACF i bez żadnej innej zależności (decyzja ISKT z 2026-09-09, ADR-002 §3.1).
 * `add_meta_box()` z flagą `__block_editor_compatible_meta_box` renderuje się
 * wewnątrz edytora blokowego — to natywny mechanizm WordPressa, nie obejście.
 *
 * @package ISKT\Szkolenia\Core
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Nazwa pola `nonce` chroniącego zapis.
 */
const ISKT_NONCE_POLA = 'iskt_pola_nonce';

/**
 * Rejestruje skrzynki metadanych dla typów treści katalogu.
 */
function iskt_dodaj_skrzynki(): void {
	$tytuly = array(
		ISKT_CPT_SZKOLENIE => __( 'Dane szkolenia', 'iskt-szkolenia-core' ),
		ISKT_CPT_TRENER    => __( 'Dane trenera', 'iskt-szkolenia-core' ),
		ISKT_CPT_TERMIN    => __( 'Dane terminu', 'iskt-szkolenia-core' ),
	);

	foreach ( $tytuly as $typ_tresci => $tytul ) {
		add_meta_box(
			'iskt-pola-' . $typ_tresci,
			$tytul,
			'iskt_renderuj_skrzynke',
			$typ_tresci,
			'normal',
			'high',
			array(
				'__block_editor_compatible_meta_box' => true,
				'__back_compat_meta_box'             => false,
			)
		);
	}
}
add_action( 'add_meta_boxes', 'iskt_dodaj_skrzynki' );

/**
 * Renderuje skrzynkę metadanych danego typu treści.
 *
 * @param WP_Post $post Edytowany wpis.
 */
function iskt_renderuj_skrzynke( WP_Post $post ): void {
	$definicje = iskt_definicje_pol()[ $post->post_type ] ?? array();

	if ( array() === $definicje ) {
		return;
	}

	wp_nonce_field( 'iskt_zapis_pol_' . $post->ID, ISKT_NONCE_POLA );

	echo '<div class="iskt-pola">';

	foreach ( $definicje as $klucz => $definicja ) {
		iskt_renderuj_pole( $post, $klucz, $definicja );
	}

	echo '</div>';
}

/**
 * Renderuje pojedyncze pole.
 *
 * @param WP_Post              $post      Edytowany wpis.
 * @param string               $klucz     Nazwa pola.
 * @param array<string, mixed> $definicja Definicja pola.
 */
function iskt_renderuj_pole( WP_Post $post, string $klucz, array $definicja ): void {
	$id        = 'iskt-pole-' . sanitize_key( $klucz );
	$wartosc   = iskt_pole( $post->ID, $klucz );
	$kontrolka = (string) ( $definicja['kontrolka'] ?? 'tekst' );
	$opis_id   = $id . '-opis';
	$ma_opis   = isset( $definicja['opis'] ) && '' !== $definicja['opis'];

	echo '<div class="iskt-pole iskt-pole--' . esc_attr( $kontrolka ) . '">';

	// Przełącznik ma etykietę przy polu wyboru, nie nad nim — inaczej czytnik ekranu odczyta ją dwa razy.
	if ( 'przelacznik' !== $kontrolka ) {
		printf(
			'<label class="iskt-pole__etykieta" for="%s">%s</label>',
			esc_attr( $id ),
			esc_html( (string) $definicja['etykieta'] )
		);
	}

	$aria = $ma_opis ? sprintf( ' aria-describedby="%s"', esc_attr( $opis_id ) ) : '';

	switch ( $kontrolka ) {
		case 'textarea':
			printf(
				'<textarea id="%s" name="%s" rows="4" class="large-text"%s>%s</textarea>',
				esc_attr( $id ),
				esc_attr( $klucz ),
				$aria, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- zbudowane z esc_attr powyżej.
				esc_textarea( (string) $wartosc )
			);
			break;

		case 'lista':
			printf(
				'<textarea id="%s" name="%s" rows="5" class="large-text"%s>%s</textarea>',
				esc_attr( $id ),
				esc_attr( $klucz ),
				$aria, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- zbudowane z esc_attr powyżej.
				esc_textarea( implode( "\n", is_array( $wartosc ) ? $wartosc : array() ) )
			);
			break;

		case 'wybor':
			$slownik = is_callable( $definicja['slownik'] ) ? ( $definicja['slownik'] )() : array();

			printf( '<select id="%s" name="%s"%s>', esc_attr( $id ), esc_attr( $klucz ), $aria ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			printf( '<option value="">%s</option>', esc_html__( '— nie wybrano —', 'iskt-szkolenia-core' ) );

			foreach ( $slownik as $wartosc_opcji => $etykieta_opcji ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( (string) $wartosc_opcji ),
					selected( (string) $wartosc, (string) $wartosc_opcji, false ),
					esc_html( (string) $etykieta_opcji )
				);
			}

			echo '</select>';
			break;

		case 'przelacznik':
			printf(
				'<label for="%s"><input type="checkbox" id="%s" name="%s" value="1"%s%s> %s</label>',
				esc_attr( $id ),
				esc_attr( $id ),
				esc_attr( $klucz ),
				checked( (bool) $wartosc, true, false ),
				$aria, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- zbudowane z esc_attr powyżej.
				esc_html( (string) $definicja['etykieta'] )
			);
			break;

		case 'data':
			printf(
				'<input type="date" id="%s" name="%s" value="%s"%s>',
				esc_attr( $id ),
				esc_attr( $klucz ),
				esc_attr( (string) $wartosc ),
				$aria // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- zbudowane z esc_attr powyżej.
			);
			break;

		case 'program':
			iskt_renderuj_program( $klucz, is_array( $wartosc ) ? $wartosc : array() );
			break;

		case 'trenerzy':
			iskt_renderuj_wybor_wpisow( $klucz, ISKT_CPT_TRENER, is_array( $wartosc ) ? array_map( 'intval', $wartosc ) : array(), true );
			break;

		case 'szkolenie':
			iskt_renderuj_wybor_wpisow( $klucz, ISKT_CPT_SZKOLENIE, array( absint( $wartosc ) ), false );
			break;

		default:
			printf(
				'<input type="text" id="%s" name="%s" value="%s" class="regular-text"%s>',
				esc_attr( $id ),
				esc_attr( $klucz ),
				esc_attr( (string) $wartosc ),
				$aria // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- zbudowane z esc_attr powyżej.
			);
	}

	if ( $ma_opis ) {
		printf(
			'<p class="description" id="%s">%s</p>',
			esc_attr( $opis_id ),
			esc_html( (string) $definicja['opis'] )
		);
	}

	echo '</div>';
}

/**
 * Renderuje powtarzalną listę modułów programu.
 *
 * Powtarzanie wierszy obsługuje zwykły JavaScript klonujący wzorzec — bez `npm`
 * i bez kroku budowania (ADR-002 §3.2). Bez JavaScriptu widoczne pozostają
 * istniejące moduły plus jeden pusty wiersz, więc pole nie przestaje działać.
 *
 * @param string                                   $klucz  Nazwa pola.
 * @param array<int, array{tytul: string, opis: string}> $moduly Zapisane moduły.
 */
function iskt_renderuj_program( string $klucz, array $moduly ): void {
	$moduly[] = array(
		'tytul' => '',
		'opis'  => '',
	);

	echo '<div class="iskt-program" data-iskt-program>';

	foreach ( array_values( $moduly ) as $indeks => $modul ) {
		printf(
			'<div class="iskt-program__modul"><input type="text" name="%1$s[%2$d][tytul]" value="%3$s" class="large-text" placeholder="%4$s"><textarea name="%1$s[%2$d][opis]" rows="2" class="large-text" placeholder="%5$s">%6$s</textarea></div>',
			esc_attr( $klucz ),
			(int) $indeks,
			esc_attr( (string) ( $modul['tytul'] ?? '' ) ),
			esc_attr__( 'Tytuł modułu', 'iskt-szkolenia-core' ),
			esc_attr__( 'Opis modułu (opcjonalny)', 'iskt-szkolenia-core' ),
			esc_textarea( (string) ( $modul['opis'] ?? '' ) )
		);
	}

	printf(
		'<button type="button" class="button" data-iskt-dodaj-modul>%s</button>',
		esc_html__( 'Dodaj moduł', 'iskt-szkolenia-core' )
	);

	echo '</div>';
}

/**
 * Renderuje wybór powiązanych wpisów.
 *
 * Lista wielokrotnego wyboru zamiast pola wyszukiwania: liczba trenerów i szkoleń
 * jest z założenia niewielka, a natywna kontrolka działa z klawiatury i czytnikiem
 * ekranu bez żadnego kodu po naszej stronie (§7 wymaga obsługi klawiaturą).
 *
 * @param string $klucz          Nazwa pola.
 * @param string $typ_tresci     Typ treści do wyboru.
 * @param int[]  $wybrane        Zaznaczone identyfikatory.
 * @param bool   $wielokrotny    Czy wybór jest wielokrotny.
 */
function iskt_renderuj_wybor_wpisow( string $klucz, string $typ_tresci, array $wybrane, bool $wielokrotny ): void {
	$wpisy = get_posts(
		array(
			'post_type'      => $typ_tresci,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => 200,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	$id   = 'iskt-pole-' . sanitize_key( $klucz );
	$nazwa = $wielokrotny ? $klucz . '[]' : $klucz;

	if ( array() === $wpisy ) {
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'Brak pozycji do wyboru — dodaj je najpierw w odpowiedniej sekcji panelu.', 'iskt-szkolenia-core' )
		);

		/*
		 * Puste pole ukryte utrzymuje zapis: bez niego formularz nie wysyłałby klucza,
		 * a istniejące powiązanie zostałoby nietknięte mimo świadomej zmiany redaktora.
		 */
		printf( '<input type="hidden" name="%s" value="">', esc_attr( $wielokrotny ? $klucz . '[]' : $klucz ) );

		return;
	}

	printf(
		'<select id="%s" name="%s"%s>',
		esc_attr( $id ),
		esc_attr( $nazwa ),
		$wielokrotny ? ' multiple size="6"' : ''
	);

	if ( ! $wielokrotny ) {
		printf( '<option value="">%s</option>', esc_html__( '— nie wybrano —', 'iskt-szkolenia-core' ) );
	}

	// Etykieta powstaje przy relacji (`relacje.php`), nie tutaj — widok ją tylko wypisuje.
	$etykiety = iskt_etykiety_relacji( $wpisy );

	foreach ( $wpisy as $wpis ) {
		printf(
			'<option value="%d"%s>%s</option>',
			(int) $wpis->ID,
			in_array( (int) $wpis->ID, $wybrane, true ) ? ' selected' : '',
			esc_html( (string) ( $etykiety[ (int) $wpis->ID ] ?? '' ) )
		);
	}

	echo '</select>';

	if ( $wielokrotny ) {
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'Przytrzymaj Ctrl (Cmd na Macu), aby zaznaczyć kilka pozycji.', 'iskt-szkolenia-core' )
		);

		// Gwarantuje wysłanie klucza także przy odznaczeniu wszystkich pozycji.
		printf( '<input type="hidden" name="%s" value="">', esc_attr( $klucz . '[]' ) );
	}
}

/**
 * Zapisuje pola katalogu.
 *
 * @param int     $post_id Identyfikator wpisu.
 * @param WP_Post $post    Wpis.
 */
function iskt_zapisz_pola( int $post_id, WP_Post $post ): void {
	$definicje = iskt_definicje_pol()[ $post->post_type ] ?? array();

	if ( array() === $definicje ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	/*
	 * Brak `nonce` oznacza zapis spoza ekranu edycji — szybka edycja, REST, WP-CLI,
	 * import. Wychodzimy zamiast czyścić pola: potraktowanie braku pól jako „redaktor
	 * je wyczyścił" skasowałoby dane katalogu przy zwykłej szybkiej edycji tytułu.
	 */
	$nonce = isset( $_POST[ ISKT_NONCE_POLA ] ) ? sanitize_text_field( wp_unslash( (string) $_POST[ ISKT_NONCE_POLA ] ) ) : '';

	if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'iskt_zapis_pol_' . $post_id ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( $definicje as $klucz => $definicja ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanityzuje `register_post_meta`.
		$surowa = isset( $_POST[ $klucz ] ) ? wp_unslash( $_POST[ $klucz ] ) : null;

		if ( null === $surowa && 'przelacznik' !== $definicja['kontrolka'] ) {
			continue;
		}

		// Niezaznaczone pole wyboru nie trafia do żądania — brak wartości to świadome „nie”.
		if ( 'przelacznik' === $definicja['kontrolka'] ) {
			$surowa = null !== $surowa;
		}

		if ( is_array( $surowa ) ) {
			// Ukryte pole wysyła pusty ciąg, który po odfiltrowaniu daje pustą tablicę — czyli świadome wyczyszczenie.
			$surowa = array_values( array_filter( $surowa, static fn ( $pozycja ): bool => '' !== $pozycja && array() !== $pozycja ) );
		}

		// `update_post_meta` uruchamia `sanitize_callback` z `register_post_meta`.
		update_post_meta( $post_id, $klucz, $surowa );
	}
}
add_action( 'save_post', 'iskt_zapisz_pola', 10, 2 );

/**
 * Wczytuje style i skrypt panelu na ekranach edycji katalogu.
 *
 * @param string $hook Identyfikator ekranu panelu.
 */
function iskt_wczytaj_zasoby_panelu( string $hook ): void {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$ekran = get_current_screen();

	if ( null === $ekran || ! array_key_exists( $ekran->post_type, iskt_definicje_pol() ) ) {
		return;
	}

	$url  = plugins_url( 'assets/', ISKT_CORE_FILE );
	$sciezka = ISKT_CORE_DIR . '/assets/';

	wp_enqueue_style(
		'iskt-panel',
		$url . 'panel.css',
		array(),
		(string) ( file_exists( $sciezka . 'panel.css' ) ? filemtime( $sciezka . 'panel.css' ) : ISKT_CORE_VERSION )
	);

	wp_enqueue_script(
		'iskt-panel',
		$url . 'panel.js',
		array(),
		(string) ( file_exists( $sciezka . 'panel.js' ) ? filemtime( $sciezka . 'panel.js' ) : ISKT_CORE_VERSION ),
		true
	);
}
add_action( 'admin_enqueue_scripts', 'iskt_wczytaj_zasoby_panelu' );
