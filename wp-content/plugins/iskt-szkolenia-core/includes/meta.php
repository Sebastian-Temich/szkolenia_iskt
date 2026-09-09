<?php
/**
 * Pola katalogu — rejestracja, słowniki wartości i sanityzacja.
 *
 * Realizujemy je natywnym `register_post_meta()`, bez ACF i bez żadnej innej
 * zależności — decyzja ISKT z 2026-09-09, patrz docs/ADR-002-*.md §3.1.
 *
 * Rejestracja pól to warstwa bezpieczeństwa, nie tylko dokumentacja: `sanitize_callback`
 * i `auth_callback` obowiązują niezależnie od tego, którą drogą dane trafiają do bazy
 * (formularz edycji, REST, import, WP-CLI).
 *
 * @package ISKT\Szkolenia\Core
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Dozwolone poziomy szkolenia.
 *
 * @return array<string, string>
 */
function iskt_slownik_poziomow(): array {
	return array(
		'podstawowy'    => __( 'Podstawowy', 'iskt-szkolenia-core' ),
		'sredniozaawan' => __( 'Średniozaawansowany', 'iskt-szkolenia-core' ),
		'zaawansowany'  => __( 'Zaawansowany', 'iskt-szkolenia-core' ),
		'mieszany'      => __( 'Wszystkie poziomy', 'iskt-szkolenia-core' ),
	);
}

/**
 * Dozwolone sposoby prezentowania podatku przy cenie.
 *
 * §4.3 wymaga „jednoznacznego opisu jednostki i sposobu prezentowania podatku”.
 * Wartość jest wybierana z listy, a nie wpisywana — dowolny tekst w tym miejscu
 * to prosta droga do ceny, która wprowadza klienta w błąd.
 *
 * @return array<string, string>
 */
function iskt_slownik_podatku(): array {
	return array(
		'netto'     => __( 'netto (+ VAT)', 'iskt-szkolenia-core' ),
		'brutto'    => __( 'brutto (z VAT)', 'iskt-szkolenia-core' ),
		'zwolnione' => __( 'zwolnione z VAT', 'iskt-szkolenia-core' ),
	);
}

/**
 * Dozwolone jednostki ceny.
 *
 * @return array<string, string>
 */
function iskt_slownik_jednostek_ceny(): array {
	return array(
		'osoba'       => __( 'za osobę', 'iskt-szkolenia-core' ),
		'grupa'       => __( 'za grupę', 'iskt-szkolenia-core' ),
		'osobodzien'  => __( 'za osobodzień', 'iskt-szkolenia-core' ),
		'do_ustalenia' => __( 'do ustalenia indywidualnie', 'iskt-szkolenia-core' ),
	);
}

/**
 * Dozwolone tryby realizacji terminu.
 *
 * @return array<string, string>
 */
function iskt_slownik_trybow(): array {
	return array(
		'online'       => __( 'Online', 'iskt-szkolenia-core' ),
		'stacjonarnie' => __( 'Stacjonarnie', 'iskt-szkolenia-core' ),
		'hybrydowo'    => __( 'Hybrydowo', 'iskt-szkolenia-core' ),
	);
}

/**
 * Dozwolone statusy zgłoszeń na termin.
 *
 * @return array<string, string>
 */
function iskt_slownik_statusow_zgloszen(): array {
	return array(
		'otwarte'         => __( 'Zapisy otwarte', 'iskt-szkolenia-core' ),
		'lista_rezerwowa' => __( 'Lista rezerwowa', 'iskt-szkolenia-core' ),
		'zamkniete'       => __( 'Zapisy zamknięte', 'iskt-szkolenia-core' ),
	);
}

/**
 * Zwraca funkcję sprawdzającą, czy wartość należy do słownika.
 *
 * Wartość spoza słownika zamieniamy na pustą, a nie na domyślną — cicha podmiana
 * na „pierwszą z listy” ukryłaby błąd importu i mogłaby opublikować nieprawdziwą
 * informację o cenie lub dostępności.
 *
 * @param callable $slownik Funkcja zwracająca tablicę dozwolonych wartości.
 *
 * @return callable
 */
function iskt_sanitize_ze_slownika( callable $slownik ): callable {
	return static function ( $wartosc ) use ( $slownik ): string {
		$wartosc = is_scalar( $wartosc ) ? (string) $wartosc : '';
		$dozwolone = $slownik();

		return array_key_exists( $wartosc, $dozwolone ) ? $wartosc : '';
	};
}

/**
 * Sanityzuje datę do formatu `RRRR-MM-DD`.
 *
 * Odrzucamy daty nieistniejące (np. 2026-02-30), a nie tylko źle sformatowane —
 * `strtotime` przyjąłby je i przesunął na marzec, co pokazałoby klientowi
 * termin, którego nikt nie ustalił.
 *
 * @param mixed $wartosc Wartość z formularza.
 *
 * @return string Data w formacie `Y-m-d` albo pusty ciąg.
 */
function iskt_sanitize_data( $wartosc ): string {
	$wartosc = is_scalar( $wartosc ) ? trim( (string) $wartosc ) : '';

	if ( '' === $wartosc ) {
		return '';
	}

	$data = DateTimeImmutable::createFromFormat( '!Y-m-d', $wartosc );

	if ( false === $data || $data->format( 'Y-m-d' ) !== $wartosc ) {
		return '';
	}

	return $wartosc;
}

/**
 * Sanityzuje listę pozycji wpisywanych po jednej w wierszu.
 *
 * @param mixed $wartosc Tekst wielowierszowy albo tablica.
 *
 * @return string[]
 */
function iskt_sanitize_lista( $wartosc ): array {
	if ( is_string( $wartosc ) ) {
		$wartosc = preg_split( '/\R/u', $wartosc ) ?: array();
	}

	if ( ! is_array( $wartosc ) ) {
		return array();
	}

	$pozycje = array();

	foreach ( $wartosc as $pozycja ) {
		if ( ! is_scalar( $pozycja ) ) {
			continue;
		}

		$czysta = sanitize_text_field( (string) $pozycja );

		if ( '' !== $czysta ) {
			$pozycje[] = $czysta;
		}
	}

	return $pozycje;
}

/**
 * Sanityzuje program szkolenia — listę modułów `{ tytul, opis }`.
 *
 * Moduł bez tytułu odrzucamy: pusty nagłówek modułu nie ma czego opisywać,
 * a wyświetlony wyglądałby jak błąd strony.
 *
 * @param mixed $wartosc Tablica modułów.
 *
 * @return array<int, array{tytul: string, opis: string}>
 */
function iskt_sanitize_program( $wartosc ): array {
	if ( ! is_array( $wartosc ) ) {
		return array();
	}

	$moduly = array();

	foreach ( $wartosc as $modul ) {
		if ( ! is_array( $modul ) ) {
			continue;
		}

		$tytul = isset( $modul['tytul'] ) && is_scalar( $modul['tytul'] )
			? sanitize_text_field( (string) $modul['tytul'] )
			: '';

		if ( '' === $tytul ) {
			continue;
		}

		$opis = isset( $modul['opis'] ) && is_scalar( $modul['opis'] )
			? sanitize_textarea_field( (string) $modul['opis'] )
			: '';

		$moduly[] = array(
			'tytul' => $tytul,
			'opis'  => $opis,
		);
	}

	return $moduly;
}

/**
 * Sanityzuje listę identyfikatorów powiązanych trenerów.
 *
 * Sprawdzamy nie tylko, czy to liczby, ale też czy wskazują istniejących trenerów.
 * Bez tego usunięcie trenera zostawiłoby na szkoleniu odwołanie donikąd, a §4.5
 * wymaga spójnej prezentacji powiązań w obu widokach.
 *
 * @param mixed $wartosc Tablica identyfikatorów.
 *
 * @return int[]
 */
function iskt_sanitize_trenerzy( $wartosc ): array {
	if ( ! is_array( $wartosc ) ) {
		return array();
	}

	$identyfikatory = array();

	foreach ( $wartosc as $id ) {
		$id = absint( $id );

		if ( 0 === $id || isset( $identyfikatory[ $id ] ) ) {
			continue;
		}

		if ( ISKT_CPT_TRENER !== get_post_type( $id ) ) {
			continue;
		}

		$identyfikatory[ $id ] = true;
	}

	return array_map( 'intval', array_keys( $identyfikatory ) );
}

/**
 * Sanityzuje cenę do zapisu jako tekst.
 *
 * Cenę trzymamy jako tekst, nie liczbę zmiennoprzecinkową — grosze w `float`
 * to znany sposób na wyświetlenie klientowi 1199,99 zamiast 1200.
 *
 * @param mixed $wartosc Wartość z formularza.
 *
 * @return string
 */
function iskt_sanitize_cena( $wartosc ): string {
	$wartosc = is_scalar( $wartosc ) ? trim( (string) $wartosc ) : '';

	if ( '' === $wartosc ) {
		return '';
	}

	$wartosc = str_replace( array( ' ', "\u{00A0}", ',' ), array( '', '', '.' ), $wartosc );

	if ( ! is_numeric( $wartosc ) || (float) $wartosc < 0 ) {
		return '';
	}

	return number_format( (float) $wartosc, 2, '.', '' );
}

/**
 * Definicje pól katalogu.
 *
 * Jedno miejsce opisu pól: `register_post_meta()` czyta stąd sanityzację, a panel
 * edycji (includes/admin/pola.php) etykiety i typ kontrolki. Rozdzielenie tych list
 * skończyłoby się polem, które da się wpisać, ale nie da się zapisać.
 *
 * @return array<string, array<string, array<string, mixed>>>
 */
function iskt_definicje_pol(): array {
	return array(
		ISKT_CPT_SZKOLENIE => array(
			'_iskt_grupa_docelowa'      => array(
				'etykieta' => __( 'Grupa docelowa', 'iskt-szkolenia-core' ),
				'opis'     => __( 'Dla kogo jest to szkolenie.', 'iskt-szkolenia-core' ),
				'kontrolka' => 'textarea',
				'type'      => 'string',
				'sanitize'  => 'sanitize_textarea_field',
			),
			'_iskt_korzysci'            => array(
				'etykieta'  => __( 'Korzyści i efekty uczenia', 'iskt-szkolenia-core' ),
				'opis'      => __( 'Jedna korzyść w wierszu.', 'iskt-szkolenia-core' ),
				'kontrolka' => 'lista',
				'type'      => 'array',
				'single'    => true,
				'sanitize'  => 'iskt_sanitize_lista',
			),
			'_iskt_program'             => array(
				'etykieta'  => __( 'Program', 'iskt-szkolenia-core' ),
				'opis'      => __( 'Moduły szkolenia — dowolna liczba.', 'iskt-szkolenia-core' ),
				'kontrolka' => 'program',
				'type'      => 'array',
				'single'    => true,
				'sanitize'  => 'iskt_sanitize_program',
			),
			'_iskt_poziom'              => array(
				'etykieta'  => __( 'Poziom', 'iskt-szkolenia-core' ),
				'kontrolka' => 'wybor',
				'slownik'   => 'iskt_slownik_poziomow',
				'type'      => 'string',
				'sanitize'  => null,
			),
			'_iskt_czas_trwania'        => array(
				'etykieta'  => __( 'Czas trwania', 'iskt-szkolenia-core' ),
				'opis'      => __( 'Np. „2 dni (16 godzin)”.', 'iskt-szkolenia-core' ),
				'kontrolka' => 'tekst',
				'type'      => 'string',
				'sanitize'  => 'sanitize_text_field',
			),
			ISKT_META_TRENERZY          => array(
				'etykieta'  => __( 'Trenerzy', 'iskt-szkolenia-core' ),
				'opis'      => __( 'Powiązanie zapisujemy tylko tutaj — profil trenera pobiera swoje szkolenia sam.', 'iskt-szkolenia-core' ),
				'kontrolka' => 'trenerzy',
				'type'      => 'array',
				'single'    => true,
				'sanitize'  => 'iskt_sanitize_trenerzy',
			),
			'_iskt_cena'                => array(
				'etykieta'  => __( 'Cena', 'iskt-szkolenia-core' ),
				'opis'      => __( 'Sama liczba, bez waluty. Puste pole ukrywa cenę.', 'iskt-szkolenia-core' ),
				'kontrolka' => 'tekst',
				'type'      => 'string',
				'sanitize'  => 'iskt_sanitize_cena',
			),
			'_iskt_cena_jednostka'      => array(
				'etykieta'  => __( 'Jednostka ceny', 'iskt-szkolenia-core' ),
				'kontrolka' => 'wybor',
				'slownik'   => 'iskt_slownik_jednostek_ceny',
				'type'      => 'string',
				'sanitize'  => null,
			),
			'_iskt_cena_podatek'        => array(
				'etykieta'  => __( 'Prezentacja podatku', 'iskt-szkolenia-core' ),
				'kontrolka' => 'wybor',
				'slownik'   => 'iskt_slownik_podatku',
				'type'      => 'string',
				'sanitize'  => null,
			),
			'_iskt_dofinansowanie'      => array(
				'etykieta'  => __( 'Szkolenie objęte dofinansowaniem', 'iskt-szkolenia-core' ),
				'kontrolka' => 'przelacznik',
				'type'      => 'boolean',
				'sanitize'  => 'rest_sanitize_boolean',
			),
			'_iskt_dofinansowanie_opis' => array(
				'etykieta'  => __( 'Warunki dofinansowania', 'iskt-szkolenia-core' ),
				'opis'      => __( 'Nie wyliczamy ceny po dofinansowaniu — §4.3 tego zabrania.', 'iskt-szkolenia-core' ),
				'kontrolka' => 'textarea',
				'type'      => 'string',
				'sanitize'  => 'sanitize_textarea_field',
			),
			'_iskt_wyroznione'          => array(
				'etykieta'  => __( 'Wyróżnij szkolenie', 'iskt-szkolenia-core' ),
				'opis'      => __( 'Wyróżnione szkolenia trafiają na stronę główną.', 'iskt-szkolenia-core' ),
				'kontrolka' => 'przelacznik',
				'type'      => 'boolean',
				'sanitize'  => 'rest_sanitize_boolean',
			),
		),
		ISKT_CPT_TRENER    => array(
			'_iskt_rola'          => array(
				'etykieta'  => __( 'Rola', 'iskt-szkolenia-core' ),
				'opis'      => __( 'Np. „Trener AI i automatyzacji”.', 'iskt-szkolenia-core' ),
				'kontrolka' => 'tekst',
				'type'      => 'string',
				'sanitize'  => 'sanitize_text_field',
			),
			'_iskt_doswiadczenie' => array(
				'etykieta'  => __( 'Doświadczenie', 'iskt-szkolenia-core' ),
				'opis'      => __( 'Jedna pozycja w wierszu.', 'iskt-szkolenia-core' ),
				'kontrolka' => 'lista',
				'type'      => 'array',
				'single'    => true,
				'sanitize'  => 'iskt_sanitize_lista',
			),
			'_iskt_wyksztalcenie' => array(
				'etykieta'  => __( 'Wykształcenie i certyfikaty', 'iskt-szkolenia-core' ),
				'opis'      => __( 'Jedna pozycja w wierszu.', 'iskt-szkolenia-core' ),
				'kontrolka' => 'lista',
				'type'      => 'array',
				'single'    => true,
				'sanitize'  => 'iskt_sanitize_lista',
			),
			'_iskt_specjalizacje' => array(
				'etykieta'  => __( 'Specjalizacje', 'iskt-szkolenia-core' ),
				'opis'      => __( 'Jedna pozycja w wierszu.', 'iskt-szkolenia-core' ),
				'kontrolka' => 'lista',
				'type'      => 'array',
				'single'    => true,
				'sanitize'  => 'iskt_sanitize_lista',
			),
		),
		ISKT_CPT_TERMIN    => array(
			ISKT_META_TERMIN_SZKOLENIE => array(
				'etykieta'  => __( 'Szkolenie', 'iskt-szkolenia-core' ),
				'kontrolka' => 'szkolenie',
				'type'      => 'integer',
				'sanitize'  => 'absint',
			),
			'_iskt_termin_indywidualny' => array(
				'etykieta'  => __( 'Termin ustalany indywidualnie', 'iskt-szkolenia-core' ),
				'opis'      => __( 'Zaznacz przy szkoleniu zamkniętym dla firmy — daty nie są wtedy wymagane.', 'iskt-szkolenia-core' ),
				'kontrolka' => 'przelacznik',
				'type'      => 'boolean',
				'sanitize'  => 'rest_sanitize_boolean',
			),
			'_iskt_data_start'          => array(
				'etykieta'  => __( 'Data rozpoczęcia', 'iskt-szkolenia-core' ),
				'kontrolka' => 'data',
				'type'      => 'string',
				'sanitize'  => 'iskt_sanitize_data',
			),
			'_iskt_data_koniec'         => array(
				'etykieta'  => __( 'Data zakończenia', 'iskt-szkolenia-core' ),
				'opis'      => __( 'Zostaw puste przy terminie jednodniowym.', 'iskt-szkolenia-core' ),
				'kontrolka' => 'data',
				'type'      => 'string',
				'sanitize'  => 'iskt_sanitize_data',
			),
			'_iskt_tryb'                => array(
				'etykieta'  => __( 'Tryb', 'iskt-szkolenia-core' ),
				'kontrolka' => 'wybor',
				'slownik'   => 'iskt_slownik_trybow',
				'type'      => 'string',
				'sanitize'  => null,
			),
			'_iskt_lokalizacja'         => array(
				'etykieta'  => __( 'Miejsce', 'iskt-szkolenia-core' ),
				'opis'      => __( 'Adres lub miasto. Przy trybie online zostaw puste.', 'iskt-szkolenia-core' ),
				'kontrolka' => 'tekst',
				'type'      => 'string',
				'sanitize'  => 'sanitize_text_field',
			),
			'_iskt_status_zgloszen'     => array(
				'etykieta'  => __( 'Status zgłoszeń', 'iskt-szkolenia-core' ),
				'kontrolka' => 'wybor',
				'slownik'   => 'iskt_slownik_statusow_zgloszen',
				'type'      => 'string',
				'sanitize'  => null,
			),
		),
	);
}

/**
 * Zwraca domyślną wartość pola zgodną z typem `register_post_meta()`.
 *
 * @param string $typ Typ pola WordPress REST schema.
 *
 * @return mixed
 */
function iskt_domyslna_wartosc_pola( string $typ ) {
	switch ( $typ ) {
		case 'array':
			return array();
		case 'boolean':
			return false;
		case 'integer':
			return 0;
		case 'string':
		default:
			return '';
	}
}

/**
 * Rejestruje pola katalogu.
 */
function iskt_rejestruj_pola(): void {
	foreach ( iskt_definicje_pol() as $typ_tresci => $pola ) {
		foreach ( $pola as $klucz => $definicja ) {
			$sanitize = $definicja['sanitize'];

			if ( null === $sanitize && isset( $definicja['slownik'] ) ) {
				$sanitize = iskt_sanitize_ze_slownika( $definicja['slownik'] );
			}

			register_post_meta(
				$typ_tresci,
				$klucz,
				array(
					'type'              => $definicja['type'],
					'description'       => $definicja['etykieta'],
					'single'            => true,
					'default'           => iskt_domyslna_wartosc_pola( $definicja['type'] ),
					'sanitize_callback' => $sanitize,
					'auth_callback'     => 'iskt_moze_edytowac_pole',

					/*
					 * Pola nie trafiają do REST. Zapis prowadzi wyłącznie przez skrzynki
					 * metadanych i `save_post`, więc udostępnianie ich przez API dawałoby
					 * drugą, nieużywaną drogę zapisu — czyli drugą powierzchnię do
					 * zabezpieczenia bez żadnej korzyści. Do rozważenia, gdy pojawi się
					 * realny konsument API.
					 */
					'show_in_rest'      => false,
				)
			);
		}
	}
}
add_action( 'init', 'iskt_rejestruj_pola' );

/**
 * Odczytuje pole katalogu z wartością domyślną zgodną z typem.
 *
 * @param int    $post_id Identyfikator wpisu.
 * @param string $klucz   Nazwa pola.
 *
 * @return mixed
 */
function iskt_pole( int $post_id, string $klucz ) {
	return get_post_meta( $post_id, $klucz, true );
}
