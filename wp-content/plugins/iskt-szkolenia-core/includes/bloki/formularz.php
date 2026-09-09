<?php
/**
 * Blok formularza zgłoszeniowego.
 *
 * Formularz jest blokiem, a nie szablonem motywu, z tego samego powodu co model
 * danych (§6): właściciel ma go postawić tam, gdzie chce — na stronie zgłoszenia,
 * w sekcji kontaktowej, na stronie kampanii — a zmiana motywu nie może go zabrać.
 *
 * Cały znacznik powstaje tutaj i wszystkie napisy pochodzą z rejestru tekstów
 * (`iskt_rejestr_tekstow()`, grupa `formularz`). W tym pliku nie ma ani jednej
 * etykiety ani komunikatu wpisanego na sztywno — §4.7 i zadanie 10.
 *
 * Formularz działa bez JavaScriptu: to zwykłe żądanie POST na ten sam adres.
 * Skryptu nie ma tu wcale, więc nie ma też ścieżki, którą jego brak mógłby zepsuć.
 *
 * @package ISKT\Szkolenia\Core
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Zwraca przedrostek identyfikatorów dla kolejnego formularza na stronie.
 *
 * Dwa formularze na jednej stronie nie mogą mieć pól o tych samych `id` — powiązanie
 * etykiety z kontrolką przestałoby być jednoznaczne, a to wprost wymóg §7.
 */
function iskt_przedrostek_formularza(): string {
	static $licznik = 0;

	++$licznik;

	return 1 === $licznik ? 'iskt-zgl' : 'iskt-zgl-' . $licznik;
}

/**
 * Buduje pojedyncze pole formularza.
 *
 * Układ znaczników odpowiada umowie z `docs/MOTYW-KOMPONENTY.md`: etykieta zawsze
 * widoczna i powiązana przez `for`/`id`, błąd w `.iskt-field__error` wskazanym
 * przez `aria-describedby`, `aria-invalid` na kontrolce.
 *
 * @param array{id: string, nazwa: string, etykieta: string, typ?: string, wartosc?: string, wymagane?: bool, blad?: string, autouzupelnianie?: string, kontrolka?: string} $pole Opis pola.
 */
function iskt_pole_formularza( array $pole ): string {
	$id       = (string) $pole['id'];
	$nazwa    = (string) $pole['nazwa'];
	$etykieta = (string) $pole['etykieta'];
	$typ      = (string) ( $pole['typ'] ?? 'text' );
	$wartosc  = (string) ( $pole['wartosc'] ?? '' );
	$wymagane = (bool) ( $pole['wymagane'] ?? false );
	$blad     = (string) ( $pole['blad'] ?? '' );

	$opisy = array();

	if ( '' !== $blad ) {
		$opisy[] = $id . '-blad';
	}

	$atrybuty = sprintf( ' id="%1$s" name="%2$s" class="iskt-field__control"', esc_attr( $id ), esc_attr( $nazwa ) );

	if ( $wymagane ) {
		$atrybuty .= ' required';
	}

	if ( '' !== $blad ) {
		$atrybuty .= ' aria-invalid="true"';
	}

	if ( array() !== $opisy ) {
		$atrybuty .= sprintf( ' aria-describedby="%s"', esc_attr( implode( ' ', $opisy ) ) );
	}

	if ( isset( $pole['autouzupelnianie'] ) && '' !== (string) $pole['autouzupelnianie'] ) {
		$atrybuty .= sprintf( ' autocomplete="%s"', esc_attr( (string) $pole['autouzupelnianie'] ) );
	}

	if ( 'textarea' === $typ ) {
		$kontrolka = sprintf( '<textarea%1$s rows="6">%2$s</textarea>', $atrybuty, esc_textarea( $wartosc ) );
	} elseif ( 'select' === $typ ) {
		$kontrolka = sprintf( '<select%1$s>%2$s</select>', $atrybuty, (string) ( $pole['kontrolka'] ?? '' ) );
	} else {
		$kontrolka = sprintf(
			'<input type="%1$s"%2$s value="%3$s">',
			esc_attr( $typ ),
			$atrybuty,
			esc_attr( $wartosc )
		);
	}

	$klasy = 'iskt-field' . ( '' !== $blad ? ' iskt-field--invalid' : '' );

	$html  = sprintf( '<div class="%s">', esc_attr( $klasy ) );
	$html .= sprintf( '<label class="iskt-field__label" for="%1$s">%2$s', esc_attr( $id ), esc_html( $etykieta ) );

	if ( $wymagane ) {
		$html .= '<span class="iskt-field__required" aria-hidden="true">*</span>';
	}

	$html .= '</label>';
	$html .= $kontrolka;

	if ( '' !== $blad ) {
		$html .= sprintf(
			'<p class="iskt-field__error" id="%1$s-blad">%2$s</p>',
			esc_attr( $id ),
			esc_html( $blad )
		);
	}

	return $html . '</div>';
}

/**
 * Buduje listę wyboru szkolenia lub obszaru zainteresowania.
 *
 * Jedna kontrolka zamiast dwóch: §5 wymaga wskazania szkolenia ALBO obszaru, a dwa
 * osobne pola zmuszałyby odwiedzającego do rozstrzygania, którego użyć. Pierwsza
 * pozycja to zapytanie ogólne — §5 wymaga, aby dało się napisać bez wskazania oferty.
 *
 * @param string $wybrane Wartość zaznaczona (`s-<id>`, `k-<id>` albo pustka).
 */
function iskt_opcje_tematu( string $wybrane ): string {
	$html = sprintf(
		'<option value=""%1$s>%2$s</option>',
		'' === $wybrane ? ' selected' : '',
		esc_html( iskt_tekst( 'formularz_szkolenie_ogolne' ) )
	);

	$szkolenia = get_posts(
		array(
			'post_type'              => ISKT_CPT_SZKOLENIE,
			'post_status'            => 'publish',
			'posts_per_page'         => 200,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	if ( array() !== $szkolenia ) {
		$opcje = '';

		foreach ( $szkolenia as $szkolenie ) {
			$wartosc = 's-' . (int) $szkolenie->ID;

			$opcje .= sprintf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $wartosc ),
				$wartosc === $wybrane ? ' selected' : '',
				esc_html( $szkolenie->post_title )
			);
		}

		$html .= sprintf(
			'<optgroup label="%1$s">%2$s</optgroup>',
			esc_attr( iskt_tekst( 'formularz_grupa_szkolenia' ) ),
			$opcje
		);
	}

	$kategorie = get_terms(
		array(
			'taxonomy' => ISKT_TAX_KATEGORIA,
			/*
			 * Kategorie pokazujemy także puste. Obszar bez opublikowanego szkolenia to
			 * nadal obszar, o który wolno zapytać — a takie zapytanie jest dla ISKT
			 * informacją o popycie, nie pomyłką odwiedzającego.
			 */
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( ! is_wp_error( $kategorie ) && array() !== $kategorie ) {
		$opcje = '';

		foreach ( $kategorie as $kategoria ) {
			$wartosc = 'k-' . (int) $kategoria->term_id;

			$opcje .= sprintf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $wartosc ),
				$wartosc === $wybrane ? ' selected' : '',
				esc_html( $kategoria->name )
			);
		}

		$html .= sprintf(
			'<optgroup label="%1$s">%2$s</optgroup>',
			esc_attr( iskt_tekst( 'formularz_grupa_obszary' ) ),
			$opcje
		);
	}

	return $html;
}

/**
 * Buduje listę terminów wybranego szkolenia.
 *
 * @param int    $szkolenie_id Identyfikator szkolenia.
 * @param string $wybrany      Zaznaczony identyfikator terminu.
 *
 * @return string Pusty ciąg, gdy szkolenie nie ma nadchodzących terminów.
 */
function iskt_opcje_terminu( int $szkolenie_id, string $wybrany ): string {
	if ( $szkolenie_id <= 0 ) {
		return '';
	}

	$terminy = iskt_terminy_szkolenia( $szkolenie_id );

	if ( array() === $terminy ) {
		return '';
	}

	$html = sprintf(
		'<option value=""%1$s>%2$s</option>',
		'' === $wybrany ? ' selected' : '',
		esc_html( iskt_tekst( 'formularz_termin_dowolny' ) )
	);

	foreach ( $terminy as $termin ) {
		$id = (string) (int) $termin->ID;

		$html .= sprintf(
			'<option value="%1$s"%2$s>%3$s</option>',
			esc_attr( $id ),
			$id === $wybrany ? ' selected' : '',
			esc_html( iskt_etykieta_terminu( (int) $termin->ID ) )
		);
	}

	return $html;
}

/**
 * Buduje odnośnik do informacji o prywatności.
 *
 * Gdy adres nie jest ustawiony, nie pokazujemy niczego. Odnośnik prowadzący donikąd
 * jest gorszy niż jego brak — obiecuje informację, której nie ma (§11 pkt 10).
 */
function iskt_odnosnik_prywatnosci(): string {
	$adres = iskt_tekst( 'stopka_prywatnosc_adres' );

	if ( '' === $adres ) {
		return '';
	}

	$etykieta = iskt_tekst( 'stopka_prywatnosc_tekst' );

	return sprintf(
		'<p class="iskt-text-xs iskt-muted"><a href="%1$s">%2$s</a></p>',
		esc_url( $adres ),
		esc_html( '' !== $etykieta ? $etykieta : $adres )
	);
}

/**
 * Wypisuje formularz zgłoszeniowy.
 *
 * @param array<string, mixed> $atrybuty Atrybuty bloku.
 */
function iskt_render_formularz_zgloszeniowy( array $atrybuty = array() ): string {
	$przedrostek  = iskt_przedrostek_formularza();
	$pokaz_naglowek = false !== ( $atrybuty['pokazNaglowek'] ?? true );

	$naglowek = '';

	if ( $pokaz_naglowek ) {
		$tytul = iskt_tekst( 'formularz_tytul' );
		$wstep = iskt_tekst( 'formularz_wstep' );

		if ( '' !== $tytul ) {
			$naglowek .= '<h2 class="iskt-title-md">' . esc_html( $tytul ) . '</h2>';
		}

		if ( '' !== $wstep ) {
			$naglowek .= '<p class="iskt-lead">' . esc_html( $wstep ) . '</p>';
		}
	}

	/*
	 * Potwierdzenie zamiast formularza. Zostawienie pod nim pustych pól czytałoby się
	 * jak zaproszenie do wysłania tego samego zapytania drugi raz.
	 */
	if ( iskt_potwierdzenie_wyslania() ) {
		return sprintf(
			'<div %1$s>%2$s<div class="iskt-notice iskt-notice--success" role="status"><p>%3$s</p></div></div>',
			iskt_atrybuty_bloku( array( 'class' => 'iskt-zgloszenie' ) ),
			$naglowek,
			esc_html( iskt_tekst( 'formularz_sukces' ) )
		);
	}

	$wynik = iskt_wynik_zgloszenia();
	$bledy = $wynik['bledy'];
	$dane  = $wynik['dane'];

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- kontekst z adresu, bez zmiany stanu.
	$z_adresu_szkolenie = isset( $_GET[ ISKT_PARAM_SZKOLENIE ] ) ? absint( wp_unslash( $_GET[ ISKT_PARAM_SZKOLENIE ] ) ) : 0;
	$z_adresu_termin    = isset( $_GET[ ISKT_PARAM_TERMIN ] ) ? absint( wp_unslash( $_GET[ ISKT_PARAM_TERMIN ] ) ) : 0;
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$wartosc = static fn ( string $klucz, string $zapasowa = '' ): string => isset( $dane[ $klucz ] )
		? (string) $dane[ $klucz ]
		: $zapasowa;

	/*
	 * Kontekst ze strony szkolenia. Adres wypełnia formularz tylko wtedy, gdy nie ma
	 * danych z odrzuconej wysyłki — inaczej poprawianie błędu cofałoby wybór, którego
	 * odwiedzający właśnie dokonał ręcznie.
	 */
	$temat = $wartosc( 'temat', $z_adresu_szkolenie > 0 ? 's-' . $z_adresu_szkolenie : '' );
	$termin = $wartosc( 'termin', $z_adresu_termin > 0 ? (string) $z_adresu_termin : '' );

	$szkolenie_id = str_starts_with( $temat, 's-' ) ? (int) substr( $temat, 2 ) : 0;
	$opcje_terminu = iskt_opcje_terminu( $szkolenie_id, $termin );

	$pola  = '';
	$pola .= iskt_pole_wyboru_rodzaju( $przedrostek, $wartosc( 'typ', 'osoba' ) );

	$pola .= iskt_pole_formularza(
		array(
			'id'               => $przedrostek . '-imie',
			'nazwa'            => 'iskt_imie',
			'etykieta'         => iskt_tekst( 'formularz_imie' ),
			'wartosc'          => $wartosc( 'imie' ),
			'wymagane'         => true,
			'blad'             => (string) ( $bledy['imie'] ?? '' ),
			'autouzupelnianie' => 'name',
		)
	);

	$pola .= iskt_pole_formularza(
		array(
			'id'               => $przedrostek . '-email',
			'nazwa'            => 'iskt_email',
			'etykieta'         => iskt_tekst( 'formularz_email' ),
			'typ'              => 'email',
			'wartosc'          => $wartosc( 'email' ),
			'wymagane'         => true,
			'blad'             => (string) ( $bledy['email'] ?? '' ),
			'autouzupelnianie' => 'email',
		)
	);

	$pola .= iskt_pole_formularza(
		array(
			'id'               => $przedrostek . '-telefon',
			'nazwa'            => 'iskt_telefon',
			'etykieta'         => iskt_tekst( 'formularz_telefon' ),
			'typ'              => 'tel',
			'wartosc'          => $wartosc( 'telefon' ),
			'autouzupelnianie' => 'tel',
		)
	);

	$pola .= iskt_pole_formularza(
		array(
			'id'               => $przedrostek . '-firma',
			'nazwa'            => 'iskt_firma',
			'etykieta'         => iskt_tekst( 'formularz_firma' ),
			'wartosc'          => $wartosc( 'firma' ),
			'autouzupelnianie' => 'organization',
		)
	);

	$pola .= iskt_pole_formularza(
		array(
			'id'        => $przedrostek . '-temat',
			'nazwa'     => 'iskt_temat',
			'etykieta'  => iskt_tekst( 'formularz_szkolenie' ),
			'typ'       => 'select',
			'kontrolka' => iskt_opcje_tematu( $temat ),
		)
	);

	if ( '' !== $opcje_terminu ) {
		$pola .= iskt_pole_formularza(
			array(
				'id'        => $przedrostek . '-termin',
				'nazwa'     => 'iskt_termin',
				'etykieta'  => iskt_tekst( 'formularz_termin' ),
				'typ'       => 'select',
				'kontrolka' => $opcje_terminu,
			)
		);
	}

	$pola .= iskt_pole_formularza(
		array(
			'id'       => $przedrostek . '-wiadomosc',
			'nazwa'    => 'iskt_wiadomosc',
			'etykieta' => iskt_tekst( 'formularz_wiadomosc' ),
			'typ'      => 'textarea',
			'wartosc'  => $wartosc( 'wiadomosc' ),
			'wymagane' => true,
			'blad'     => (string) ( $bledy['wiadomosc'] ?? '' ),
		)
	);

	$podsumowanie = '';

	if ( 'blad' === $wynik['stan'] && '' !== $wynik['komunikat'] ) {
		$podsumowanie = sprintf(
			'<div class="iskt-notice iskt-notice--error" role="alert"><p>%s</p></div>',
			esc_html( $wynik['komunikat'] )
		);
	}

	$wymagane_opis = iskt_tekst( 'formularz_wymagane_opis' );
	$zastrzezenie  = iskt_tekst( 'formularz_zastrzezenie' );

	$formularz  = sprintf(
		'<form class="iskt-form" method="post" action="%s">',
		esc_url( iskt_adres_biezacy() )
	);
	$formularz .= wp_nonce_field( ISKT_AKCJA_ZGLOSZENIE, '_wpnonce', true, false );
	$formularz .= sprintf( '<input type="hidden" name="%s" value="1">', esc_attr( ISKT_AKCJA_ZGLOSZENIE ) );
	$formularz .= iskt_pola_antyspamowe();

	if ( '' !== $wymagane_opis ) {
		$formularz .= '<p class="iskt-field__hint">' . esc_html( $wymagane_opis ) . '</p>';
	}

	$formularz .= $pola;
	$formularz .= '<div class="iskt-form__actions">';
	$formularz .= sprintf(
		'<button type="submit" class="iskt-button iskt-button--primary">%s</button>',
		esc_html( iskt_tekst( 'formularz_przycisk' ) )
	);
	$formularz .= '</div>';

	if ( '' !== $zastrzezenie ) {
		$formularz .= '<p class="iskt-text-xs iskt-muted">' . esc_html( $zastrzezenie ) . '</p>';
	}

	$formularz .= iskt_odnosnik_prywatnosci();
	$formularz .= '</form>';

	return sprintf(
		'<div %1$s>%2$s%3$s%4$s</div>',
		iskt_atrybuty_bloku( array( 'class' => 'iskt-zgloszenie' ) ),
		$naglowek,
		$podsumowanie,
		$formularz
	);
}

/**
 * Buduje wybór rodzaju odbiorcy.
 *
 * Przyciski opcji, nie lista rozwijana: dwie możliwości widoczne naraz czytają się
 * szybciej niż lista, którą trzeba rozwinąć, żeby dowiedzieć się, co w niej jest.
 *
 * @param string $przedrostek Przedrostek identyfikatorów.
 * @param string $wybrany     Zaznaczony rodzaj.
 */
function iskt_pole_wyboru_rodzaju( string $przedrostek, string $wybrany ): string {
	$etykiety = array(
		'osoba' => iskt_tekst( 'formularz_typ_osoba' ),
		'firma' => iskt_tekst( 'formularz_typ_firma' ),
	);

	$opcje = '';

	foreach ( $etykiety as $rodzaj => $etykieta ) {
		$id = $przedrostek . '-typ-' . $rodzaj;

		$opcje .= '<div class="iskt-field iskt-field--check">';
		$opcje .= sprintf(
			'<input type="radio" id="%1$s" name="iskt_typ" value="%2$s"%3$s>',
			esc_attr( $id ),
			esc_attr( $rodzaj ),
			$rodzaj === $wybrany ? ' checked' : ''
		);
		$opcje .= sprintf(
			'<label class="iskt-field__label" for="%1$s">%2$s</label>',
			esc_attr( $id ),
			esc_html( $etykieta )
		);
		$opcje .= '</div>';
	}

	return sprintf(
		'<fieldset class="iskt-fieldset"><legend class="iskt-fieldset__legend">%1$s</legend>%2$s</fieldset>',
		esc_html( iskt_tekst( 'formularz_typ' ) ),
		$opcje
	);
}

/**
 * Wypisuje pola ochrony antyspamowej.
 *
 * Pułapka ukrywana jest stylem wpisanym wprost w znacznik, a nie klasą z arkusza
 * motywu. To jedyne miejsce we wtyczce, gdzie styl nie należy do motywu — świadomie:
 * przy innym motywie klasa `iskt-*` mogłaby nie mieć reguły i pole stałoby się
 * widoczne dla wszystkich, którzy je wtedy grzecznie wypełnią.
 */
function iskt_pola_antyspamowe(): string {
	$otwarto = time();

	$html  = '<div class="iskt-hp" aria-hidden="true" style="position:absolute;width:1px;height:1px;margin:-1px;padding:0;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap;border:0;">';
	$html .= sprintf(
		'<label for="%1$s">%2$s</label><input type="text" id="%1$s" name="%1$s" value="" tabindex="-1" autocomplete="off">',
		esc_attr( ISKT_POLE_PULAPKA ),
		esc_html__( 'Zostaw to pole puste', 'iskt-szkolenia-core' )
	);
	$html .= '</div>';

	$html .= sprintf(
		'<input type="hidden" name="%1$s" value="%2$s"><input type="hidden" name="%3$s" value="%4$s">',
		esc_attr( ISKT_POLE_OTWARTO ),
		esc_attr( (string) $otwarto ),
		esc_attr( ISKT_POLE_PODPIS ),
		esc_attr( iskt_podpis_czasu( $otwarto ) )
	);

	return $html;
}
