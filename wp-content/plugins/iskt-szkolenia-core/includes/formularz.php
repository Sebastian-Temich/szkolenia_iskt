<?php
/**
 * Formularz zgłoszeniowy — walidacja serwerowa, ochrona antyspamowa i wysyłka.
 *
 * §5 zlecenia opisuje jedną ścieżkę: odwiedzający wypełnia formularz, a zapytanie
 * trafia na adres kontaktowy ISKT. Wszystko, co ta ścieżka robi z danymi, dzieje się
 * tutaj — motyw wyłącznie wyświetla wynik.
 *
 * Trzy rozstrzygnięcia, które warto znać przed czytaniem kodu:
 *
 * 1. **Walidacja jest serwerowa.** Atrybuty `required` i `type="email"` w znacznikach
 *    są udogodnieniem dla odwiedzającego, nie zabezpieczeniem — żądanie POST można
 *    złożyć bez przeglądarki. Każde pole przechodzi więc pełną kontrolę tutaj,
 *    niezależnie od tego, co zrobiła (albo czego nie zrobiła) przeglądarka.
 *
 * 2. **Sukces dopiero po przyjęciu wiadomości.** `wp_mail()` zwracające `false`
 *    kończy się komunikatem błędu, nigdy podziękowaniem. Prototyp z załącznika
 *    pokazywał podziękowanie bez wysyłki; to zachowanie nie zostało powtórzone.
 *    Potwierdzenie jest dodatkowo związane z jednorazowym znacznikiem zapisanym
 *    przy wysyłce, więc ręcznie wpisany adres z `?iskt_wyslano=` nie udaje sukcesu.
 *
 * 3. **Nadawcą jest serwis, nie zgłaszający.** Adres z formularza trafia do
 *    `Reply-To`. Podstawienie go jako `From` wyglądałoby wygodnie w skrzynce,
 *    a w praktyce zatrzymywałoby wiadomość na SPF i DMARC domeny zgłaszającego.
 *
 * Zakres ochrony antyspamowej i jej znane granice opisuje
 * `docs/FORMULARZ-ZGLOSZENIOWY.md` — QA i przegląd bezpieczeństwa mają wiedzieć,
 * co ta ochrona zatrzymuje, a czego nie.
 *
 * @package ISKT\Szkolenia\Core
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Akcja `nonce` formularza.
 */
const ISKT_AKCJA_ZGLOSZENIE = 'iskt_zgloszenie';

/**
 * Nazwa pola-pułapki (honeypot).
 *
 * Wygląda na zwykłe pole, którego formularz mógłby potrzebować, więc automat
 * wypełniający wszystko, co znajdzie, wpisze do niego wartość. Człowiek go nie
 * zobaczy i nie dosięgnie klawiszem Tab.
 */
const ISKT_POLE_PULAPKA = 'iskt_adres_www';

/**
 * Pole ze znacznikiem czasu otwarcia formularza i jego podpisem.
 */
const ISKT_POLE_OTWARTO = 'iskt_otwarto';
const ISKT_POLE_PODPIS  = 'iskt_podpis';

/**
 * Parametry adresu przenoszące kontekst ze strony szkolenia.
 *
 * Nazwy celowo NIE brzmią `iskt_szkolenie` ani `iskt_trener`: WordPress rejestruje
 * publiczne typy treści jako zmienne zapytania, więc taki parametr w adresie strony
 * kazałby mu szukać szkolenia o nazwie „131” i skończyłby się stroną „nie znaleziono”
 * zamiast formularzem.
 */
const ISKT_PARAM_SZKOLENIE = 'iskt_zgl_szkolenie';
const ISKT_PARAM_TERMIN    = 'iskt_zgl_termin';

/**
 * Parametr adresu z jednorazowym znacznikiem potwierdzenia.
 */
const ISKT_PARAM_WYSLANO = 'iskt_wyslano';

/**
 * Opcje strony ze zgłoszeniem.
 */
const ISKT_OPCJA_STRONA_ZGLOSZENIA = 'iskt_strona_zgloszenia';
const ISKT_OPCJA_ZGLOSZENIE_ZALOZONE = 'iskt_strona_zgloszenia_zalozona';

/**
 * Adres strony ze zgłoszeniem — slug nadawany przy zakładaniu.
 */
const ISKT_SLUG_ZGLOSZENIA = 'zgloszenie';

/**
 * Minimalny czas wypełniania formularza w sekundach.
 *
 * Człowiek czytający etykiety i wpisujący dane nie zamknie tego formularza
 * w sekundę; automat wysyłający gotowy zestaw pól — tak.
 */
function iskt_minimalny_czas_formularza(): int {
	return max( 0, (int) apply_filters( 'iskt_minimalny_czas_formularza', 3 ) );
}

/**
 * Minimalny odstęp między przyjętymi zgłoszeniami z jednego adresu IP (w sekundach).
 */
function iskt_odstep_zgloszen(): int {
	return max( 0, (int) apply_filters( 'iskt_odstep_zgloszen', 60 ) );
}

/**
 * Zwraca listę dopuszczalnych rodzajów odbiorcy.
 *
 * @return array<int, string>
 */
function iskt_rodzaje_zglaszajacego(): array {
	return array( 'osoba', 'firma' );
}

/**
 * Zwraca etykietę rodzaju odbiorcy z rejestru tekstów.
 *
 * @param string $rodzaj Rodzaj odbiorcy.
 */
function iskt_etykieta_rodzaju( string $rodzaj ): string {
	return 'firma' === $rodzaj
		? iskt_tekst( 'formularz_typ_firma' )
		: iskt_tekst( 'formularz_typ_osoba' );
}

/**
 * Sprawdza i porządkuje dane przesłane formularzem.
 *
 * Funkcja nie dotyka bazy i nie wysyła niczego — dzięki temu jej zachowanie da się
 * sprawdzić testem jednostkowym (`php tests/run.php`), a nie dopiero klikaniem.
 *
 * Zwraca komplet danych ZAWSZE, także przy błędach: szablon odtwarza z nich pola,
 * żeby odwiedzający nie wpisywał wszystkiego od nowa. Przy ośmiu polach powtórne
 * wypełnianie jest najczęstszym powodem porzucenia formularza.
 *
 * @param array<string, mixed> $wejscie Surowe dane żądania (po `wp_unslash()`).
 *
 * @return array{dane: array<string, string>, bledy: array<string, string>}
 */
function iskt_waliduj_zgloszenie( array $wejscie ): array {
	$tekst = static fn ( $klucz ): string => isset( $wejscie[ $klucz ] ) && is_scalar( $wejscie[ $klucz ] )
		? trim( (string) $wejscie[ $klucz ] )
		: '';

	$rodzaj = $tekst( 'iskt_typ' );

	/*
	 * Adres e-mail przechodzi przez `sanitize_text_field()`, a nie od razu przez
	 * `sanitize_email()`. Powód jest jeden: `sanitize_email()` z literówki robi pusty
	 * ciąg, a pusty ciąg wrócony do formularza kasowałby to, co odwiedzający wpisał —
	 * czyli dokładnie ten błąd, którego zadanie zabrania. Poprawność sprawdzamy niżej.
	 */
	$email_wpisany = sanitize_text_field( $tekst( 'iskt_email' ) );
	$email_czysty  = sanitize_email( $email_wpisany );

	$dane = array(
		/*
		 * Rodzaj odbiorcy spoza listy wraca do wartości domyślnej, a nie do pustej:
		 * to pole ma zawsze zaznaczoną opcję, więc brak wyboru nie jest stanem,
		 * o którego poprawę można kogokolwiek poprosić.
		 */
		'typ'       => in_array( $rodzaj, iskt_rodzaje_zglaszajacego(), true ) ? $rodzaj : 'osoba',
		'imie'      => sanitize_text_field( $tekst( 'iskt_imie' ) ),
		'email'     => $email_wpisany,
		'telefon'   => iskt_sanitize_tekst( $tekst( 'iskt_telefon' ), 'telefon' ),
		'firma'     => sanitize_text_field( $tekst( 'iskt_firma' ) ),
		'temat'     => iskt_sanitize_temat( $tekst( 'iskt_temat' ) ),
		'termin'    => iskt_sanitize_identyfikator( $tekst( 'iskt_termin' ) ),
		'wiadomosc' => trim( sanitize_textarea_field( $tekst( 'iskt_wiadomosc' ) ) ),
	);

	$bledy    = array();
	$wymagane = iskt_tekst( 'formularz_blad_wymagane' );

	foreach ( array( 'imie', 'wiadomosc' ) as $pole ) {
		if ( '' === $dane[ $pole ] ) {
			$bledy[ $pole ] = $wymagane;
		}
	}

	/*
	 * Adres pominięty i adres z literówką to dwa różne błędy. Pierwszy mówi „uzupełnij”,
	 * drugi „popraw” — komunikat, który tego nie rozróżnia, każe szukać po omacku.
	 */
	if ( '' === $email_wpisany ) {
		$bledy['email'] = $wymagane;
	} elseif ( '' === $email_czysty || ! is_email( $email_czysty ) ) {
		$bledy['email'] = iskt_tekst( 'formularz_blad_email' );
	} else {
		$dane['email'] = $email_czysty;
	}

	return array(
		'dane'  => $dane,
		'bledy' => $bledy,
	);
}

/**
 * Sprawdza wartość pola „szkolenie lub obszar zainteresowania”.
 *
 * Dopuszczalne postacie: `s-<id szkolenia>`, `k-<id kategorii>` albo pustka
 * (zapytanie ogólne). Cokolwiek innego traktujemy jak zapytanie ogólne —
 * podmieniona wartość nie ma prawa wskazać przypadkowego wpisu.
 *
 * @param string $wartosc Wartość z formularza.
 */
function iskt_sanitize_temat( string $wartosc ): string {
	if ( 1 !== preg_match( '/^([sk])-([1-9][0-9]*)$/', trim( $wartosc ), $czesci ) ) {
		return '';
	}

	return $czesci[1] . '-' . $czesci[2];
}

/**
 * Sprowadza identyfikator do dodatniej liczby zapisanej tekstem albo do pustki.
 *
 * @param string $wartosc Wartość z formularza.
 */
function iskt_sanitize_identyfikator( string $wartosc ): string {
	$id = absint( $wartosc );

	return $id > 0 ? (string) $id : '';
}

/**
 * Rozpoznaje powód odrzucenia zgłoszenia przez ochronę antyspamową.
 *
 * Rozdzielone od samej obsługi żądania, bo to jedyny fragment ochrony, który da się
 * sprawdzić testem bez działającego WordPressa — a jednocześnie ten, w którym
 * pomyłka kosztuje najwięcej: zbyt ostra reguła odrzuca zgłoszenia od ludzi.
 *
 * Kolejność sprawdzeń jest częścią zachowania. Najpierw `nonce`, bo wygasły
 * formularz to najczęstszy przypadek u człowieka i zasługuje na własny komunikat.
 * Potem limit czasu, potem pułapka i czas wypełniania.
 *
 * @param array{nonce_ok: bool, limit_aktywny: bool, pulapka: string, otwarto: int, podpis_ok: bool, teraz: int, minimalny_czas: int} $stan Stan żądania.
 *
 * @return string Pusty ciąg, gdy zgłoszenie przechodzi; inaczej `nonce`, `limit` albo `antyspam`.
 */
function iskt_ocena_antyspamowa( array $stan ): string {
	if ( true !== ( $stan['nonce_ok'] ?? false ) ) {
		return 'nonce';
	}

	if ( true === ( $stan['limit_aktywny'] ?? false ) ) {
		return 'limit';
	}

	if ( '' !== trim( (string) ( $stan['pulapka'] ?? '' ) ) ) {
		return 'antyspam';
	}

	/*
	 * Znacznik czasu bez poprawnego podpisu albo z przyszłości oznacza, że ktoś
	 * złożył żądanie sam, zamiast wysłać formularz otrzymany z serwera.
	 */
	if ( true !== ( $stan['podpis_ok'] ?? false ) ) {
		return 'antyspam';
	}

	$otwarto = (int) ( $stan['otwarto'] ?? 0 );
	$teraz   = (int) ( $stan['teraz'] ?? 0 );

	if ( $otwarto <= 0 || $otwarto > $teraz ) {
		return 'antyspam';
	}

	if ( ( $teraz - $otwarto ) < (int) ( $stan['minimalny_czas'] ?? 0 ) ) {
		return 'antyspam';
	}

	return '';
}

/**
 * Zwraca komunikat przypisany powodowi odrzucenia.
 *
 * @param string $powod Powód z `iskt_ocena_antyspamowa()`.
 */
function iskt_komunikat_odrzucenia( string $powod ): string {
	switch ( $powod ) {
		case 'nonce':
			return iskt_tekst( 'formularz_blad_nonce' );

		case 'limit':
			return iskt_tekst( 'formularz_blad_limit' );

		default:
			return iskt_tekst( 'formularz_blad_antyspam' );
	}
}

/**
 * Składa nagłówek `Reply-To` z danych zgłaszającego.
 *
 * Imię trafia do nagłówka wiadomości, więc musi stracić znaki złamania wiersza —
 * inaczej dałoby się nim dopisać kolejne nagłówki (wstrzyknięcie do nagłówków
 * poczty). Cudzysłowy i nawiasy kątowe usuwamy z tego samego powodu.
 *
 * @param string $imie  Imię i nazwisko zgłaszającego.
 * @param string $email Adres zgłaszającego.
 *
 * @return string Pusty ciąg, gdy adres jest niepoprawny.
 */
function iskt_naglowek_reply_to( string $imie, string $email ): string {
	if ( ! is_email( $email ) ) {
		return '';
	}

	$nazwa = preg_replace( '/[\r\n\t"<>,;:]+/u', ' ', $imie );
	$nazwa = trim( is_string( $nazwa ) ? $nazwa : '' );

	if ( '' === $nazwa ) {
		return 'Reply-To: ' . $email;
	}

	return sprintf( 'Reply-To: "%s" <%s>', $nazwa, $email );
}

/**
 * Składa temat wiadomości.
 *
 * Temat niesie kontekst już na liście wiadomości: po samym „Zapytanie ze strony”
 * nie dałoby się odróżnić dwudziestu zgłoszeń bez otwierania każdego.
 *
 * @param array<string, string> $dane Dane zgłoszenia wraz z opisem kontekstu.
 */
function iskt_temat_zgloszenia( array $dane ): string {
	$kontekst = trim( (string) ( $dane['szkolenie'] ?? '' ) );

	if ( '' === $kontekst ) {
		$kontekst = iskt_tekst( 'formularz_szkolenie_ogolne' );
	}

	$temat = sprintf(
		/* translators: %s: nazwa szkolenia albo opis zapytania ogólnego. */
		__( 'Zapytanie ze strony: %s', 'iskt-szkolenia-core' ),
		$kontekst
	);

	return sanitize_text_field( $temat );
}

/**
 * Składa treść wiadomości wysyłanej do ISKT.
 *
 * Wiadomość jest zwykłym tekstem. Powód jest praktyczny: treść pochodzi od obcej
 * osoby, a tekst bez znaczników nie ma jak wykonać niczego w programie pocztowym
 * odbiorcy ani rozjechać jego widoku.
 *
 * Etykiety pochodzą z tego samego rejestru, co etykiety pól formularza — zmiana
 * napisu w panelu zmienia go w obu miejscach naraz (§4.7).
 *
 * @param array<string, string> $dane Dane zgłoszenia wraz z opisem kontekstu.
 */
function iskt_tresc_zgloszenia( array $dane ): string {
	$pobierz = static fn ( string $klucz ): string => trim( (string) ( $dane[ $klucz ] ?? '' ) );

	/*
	 * Lista par, a nie mapa etykieta → wartość. Etykiety pochodzą z panelu, więc dwie
	 * mogą być identyczne — w mapie druga zjadłaby pierwszą i wiadomość po cichu
	 * zgubiłaby dane zgłaszającego.
	 */
	$wiersze = array(
		array( iskt_tekst( 'formularz_typ' ), iskt_etykieta_rodzaju( $pobierz( 'typ' ) ) ),
		array( iskt_tekst( 'formularz_imie' ), $pobierz( 'imie' ) ),
		array( iskt_tekst( 'formularz_email' ), $pobierz( 'email' ) ),
		array( iskt_tekst( 'formularz_telefon' ), $pobierz( 'telefon' ) ),
		array( iskt_tekst( 'formularz_firma' ), $pobierz( 'firma' ) ),
		array( iskt_tekst( 'formularz_szkolenie' ), $pobierz( 'szkolenie' ) ),
		array( iskt_tekst( 'formularz_termin' ), $pobierz( 'termin_etykieta' ) ),
	);

	$tresc = '';

	foreach ( $wiersze as list( $etykieta, $wartosc ) ) {
		// Pole pominięte przez zgłaszającego nie zostawia w wiadomości pustej linii.
		if ( '' === $wartosc || '' === trim( (string) $etykieta ) ) {
			continue;
		}

		$tresc .= $etykieta . ': ' . $wartosc . "\n";
	}

	$tresc .= "\n" . iskt_tekst( 'formularz_wiadomosc' ) . ":\n";
	$tresc .= $pobierz( 'wiadomosc' ) . "\n";

	$adres = $pobierz( 'adres' );

	if ( '' !== $adres ) {
		$tresc .= "\n-- \n";
		$tresc .= sprintf(
			/* translators: %s: adres strony, z której wysłano zapytanie. */
			__( 'Zapytanie wysłane ze strony: %s', 'iskt-szkolenia-core' ),
			$adres
		) . "\n";
	}

	return $tresc;
}

/**
 * Uzupełnia dane zgłoszenia o opis kontekstu pobrany z katalogu.
 *
 * Nazwa szkolenia i etykieta terminu wchodzą do wiadomości jako tekst, a nie jako
 * identyfikatory: odbiorca zgłoszenia czyta wiadomość w skrzynce, nie w bazie.
 *
 * Identyfikatory pochodzą z adresu i z pól formularza, więc mogą być podmienione.
 * Opisujemy wyłącznie to, co odwiedzający naprawdę mógł wybrać: szkolenie
 * opublikowane (wycofane zniknęło z oferty — §11 pkt 5) oraz termin opublikowany
 * i przypisany właśnie do tego szkolenia. Inaczej wiadomość nazywałaby termin,
 * który z wybranym szkoleniem nie ma nic wspólnego, a ISKT odpisywałoby na
 * ustalenia, których nikt nie proponował.
 *
 * @param array<string, string> $dane Dane po walidacji.
 *
 * @return array<string, string>
 */
function iskt_kontekst_zgloszenia( array $dane ): array {
	$dane['szkolenie']       = '';
	$dane['termin_etykieta'] = '';

	$temat        = (string) ( $dane['temat'] ?? '' );
	$szkolenie_id = 0;

	if ( str_starts_with( $temat, 's-' ) ) {
		$szkolenie = get_post( (int) substr( $temat, 2 ) );

		if ( $szkolenie instanceof WP_Post
			&& ISKT_CPT_SZKOLENIE === $szkolenie->post_type
			&& 'publish' === $szkolenie->post_status
		) {
			$dane['szkolenie'] = $szkolenie->post_title;
			$szkolenie_id      = (int) $szkolenie->ID;
		}
	} elseif ( str_starts_with( $temat, 'k-' ) ) {
		$kategoria = get_term( (int) substr( $temat, 2 ), ISKT_TAX_KATEGORIA );

		if ( $kategoria instanceof WP_Term ) {
			$dane['szkolenie'] = $kategoria->name;
		}
	}

	$termin_id = (int) ( $dane['termin'] ?? 0 );

	/*
	 * Bez rozpoznanego szkolenia nie ma czego potwierdzać — pole terminu pokazuje się
	 * dopiero po jego wybraniu, więc sam termin nie jest stanem, który da się kliknąć.
	 */
	if ( $termin_id > 0 && $szkolenie_id > 0 ) {
		$termin = get_post( $termin_id );

		if ( $termin instanceof WP_Post
			&& ISKT_CPT_TERMIN === $termin->post_type
			&& 'publish' === $termin->post_status
			&& absint( iskt_pole( $termin_id, ISKT_META_TERMIN_SZKOLENIE ) ) === $szkolenie_id
		) {
			$dane['termin_etykieta'] = iskt_etykieta_terminu( $termin_id );
		}
	}

	return $dane;
}

/**
 * Wysyła zgłoszenie na adres kontaktowy serwisu.
 *
 * Adres odbiorcy pochodzi z rejestru tekstów (`kontakt_email`), a nie z kodu —
 * §4.7 wymaga jednego źródła danych kontaktowych, a właściciel ma móc przekierować
 * zgłoszenia bez pomocy programisty.
 *
 * Nadawcy nie ustawiamy. Domyślny nadawca WordPressa należy do domeny serwisu,
 * więc przechodzi kontrolę SPF i DMARC tej domeny; podstawienie adresu zgłaszającego
 * kosztowałoby dostarczalność. Wdrożenie może zmienić nadawcę filtrem
 * `iskt_naglowki_zgloszenia`, jeśli hosting wymaga innej skrzynki.
 *
 * @param array<string, string> $dane Dane zgłoszenia z kontekstem.
 */
function iskt_wyslij_zgloszenie( array $dane ): bool {
	$odbiorca = iskt_tekst( 'kontakt_email' );

	if ( '' === $odbiorca || ! is_email( $odbiorca ) ) {
		return false;
	}

	$naglowki = array( 'Content-Type: text/plain; charset=UTF-8' );
	$reply_to = iskt_naglowek_reply_to( (string) ( $dane['imie'] ?? '' ), (string) ( $dane['email'] ?? '' ) );

	if ( '' !== $reply_to ) {
		$naglowki[] = $reply_to;
	}

	/**
	 * Pozwala wdrożeniu dołożyć albo zmienić nagłówki wiadomości ze zgłoszeniem.
	 *
	 * @param array<int, string>    $naglowki Nagłówki wiadomości.
	 * @param array<string, string> $dane     Dane zgłoszenia.
	 */
	$naglowki = (array) apply_filters( 'iskt_naglowki_zgloszenia', $naglowki, $dane );

	return (bool) wp_mail(
		$odbiorca,
		iskt_temat_zgloszenia( $dane ),
		iskt_tresc_zgloszenia( $dane ),
		$naglowki
	);
}

/**
 * Zwraca klucz przejściowy limitu wysyłek dla adresu IP żądania.
 *
 * Adres zapisujemy wyłącznie jako skrót z solą instalacji. Do odmierzenia odstępu
 * między zgłoszeniami wystarczy rozróżnienie „ten sam czy inny” — samego adresu,
 * czyli danej osobowej, nie ma powodu przechowywać.
 *
 * Świadomie NIE czytamy `X-Forwarded-For`: nagłówek ustawia klient, więc automat
 * podmieniałby go przy każdym żądaniu i limit przestałby cokolwiek znaczyć.
 */
function iskt_klucz_limitu_zgloszen(): string {
	$adres = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';

	return 'iskt_zgl_' . md5( $adres . wp_salt( 'nonce' ) );
}

/**
 * Podpisuje znacznik czasu otwarcia formularza.
 *
 * Bez podpisu automat wpisałby dowolnie starą datę i minimalny czas wypełniania
 * przestałby działać.
 *
 * @param int $znacznik Znacznik czasu.
 */
function iskt_podpis_czasu( int $znacznik ): string {
	return wp_hash( ISKT_AKCJA_ZGLOSZENIE . '|' . $znacznik );
}

/**
 * Zwraca wynik przetwarzania zgłoszenia dla bieżącego żądania.
 *
 * @return array{stan: string, bledy: array<string, string>, dane: array<string, string>, komunikat: string}
 */
function iskt_wynik_zgloszenia(): array {
	$pusty = array(
		'stan'      => '',
		'bledy'     => array(),
		'dane'      => array(),
		'komunikat' => '',
	);

	$wynik = $GLOBALS['iskt_wynik_zgloszenia'] ?? null;

	return is_array( $wynik ) ? array_merge( $pusty, $wynik ) : $pusty;
}

/**
 * Przyjmuje i obsługuje wysłany formularz.
 *
 * Uruchamiane na `template_redirect`, czyli zanim cokolwiek trafi do przeglądarki —
 * dzięki temu po udanej wysyłce możemy przekierować (wzorzec POST → przekierowanie
 * → GET). Odświeżenie strony z potwierdzeniem nie wysyła wtedy zgłoszenia po raz drugi.
 *
 * Przy błędzie przekierowania nie ma: dane zostają w żądaniu i wracają do pól.
 */
function iskt_przetworz_zgloszenie(): void {
	if ( 'POST' !== strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) {
		return;
	}

	if ( ! isset( $_POST[ ISKT_AKCJA_ZGLOSZENIE ] ) ) {
		return;
	}

	$wejscie = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- `nonce` sprawdza `iskt_ocena_antyspamowa()` poniżej.
	$wejscie = is_array( $wejscie ) ? $wejscie : array();

	$otwarto = isset( $wejscie[ ISKT_POLE_OTWARTO ] ) ? absint( $wejscie[ ISKT_POLE_OTWARTO ] ) : 0;
	$podpis  = isset( $wejscie[ ISKT_POLE_PODPIS ] ) ? (string) $wejscie[ ISKT_POLE_PODPIS ] : '';

	$powod = iskt_ocena_antyspamowa(
		array(
			'nonce_ok'       => isset( $wejscie['_wpnonce'] )
				&& (bool) wp_verify_nonce( (string) $wejscie['_wpnonce'], ISKT_AKCJA_ZGLOSZENIE ),
			'limit_aktywny'  => false !== get_transient( iskt_klucz_limitu_zgloszen() ),
			'pulapka'        => isset( $wejscie[ ISKT_POLE_PULAPKA ] ) ? (string) $wejscie[ ISKT_POLE_PULAPKA ] : '',
			'otwarto'        => $otwarto,
			'podpis_ok'      => '' !== $podpis && hash_equals( iskt_podpis_czasu( $otwarto ), $podpis ),
			'teraz'          => time(),
			'minimalny_czas' => iskt_minimalny_czas_formularza(),
		)
	);

	$sprawdzone = iskt_waliduj_zgloszenie( $wejscie );

	if ( '' !== $powod ) {
		/*
		 * Dane zostają także tutaj. Wygasły `nonce` i limit czasu dotykają ludzi,
		 * nie tylko automatów — a człowiek, który właśnie stracił wpisany tekst,
		 * drugi raz go nie wpisze.
		 */
		$GLOBALS['iskt_wynik_zgloszenia'] = array(
			'stan'      => 'blad',
			'bledy'     => array(),
			'dane'      => $sprawdzone['dane'],
			'komunikat' => iskt_komunikat_odrzucenia( $powod ),
		);

		return;
	}

	if ( array() !== $sprawdzone['bledy'] ) {
		$GLOBALS['iskt_wynik_zgloszenia'] = array(
			'stan'      => 'blad',
			'bledy'     => $sprawdzone['bledy'],
			'dane'      => $sprawdzone['dane'],
			'komunikat' => iskt_tekst( 'formularz_blad_ogolny' ),
		);

		return;
	}

	$dane          = iskt_kontekst_zgloszenia( $sprawdzone['dane'] );
	$dane['adres'] = sanitize_text_field( iskt_adres_bezwzgledny( iskt_adres_biezacy() ) );

	if ( ! iskt_wyslij_zgloszenie( $dane ) ) {
		$GLOBALS['iskt_wynik_zgloszenia'] = array(
			'stan'      => 'blad',
			'bledy'     => array(),
			'dane'      => $sprawdzone['dane'],
			'komunikat' => iskt_tekst( 'formularz_blad_wysylki' ),
		);

		return;
	}

	$odstep = iskt_odstep_zgloszen();

	/*
	 * Zero oznacza „bez limitu”, a nie „limit bez końca”. `set_transient()` z zerowym
	 * czasem życia zapisuje wartość na zawsze — czyli po pierwszym zgłoszeniu
	 * zablokowałoby wszystkie następne z tego adresu.
	 */
	if ( $odstep > 0 ) {
		set_transient( iskt_klucz_limitu_zgloszen(), time(), $odstep );
	}

	/*
	 * Potwierdzenie wiążemy z jednorazowym znacznikiem zapisanym w chwili wysyłki.
	 * Bez tego wystarczyłoby dopisać `?iskt_wyslano=1` do adresu, żeby strona
	 * podziękowała za zgłoszenie, którego nikt nie wysłał.
	 *
	 * Znacznik zapisujemy małymi literami, bo przy odczycie przechodzi przez
	 * `sanitize_key()` — wielka litera nie miałaby prawa się dopasować.
	 */
	$znacznik = strtolower( wp_generate_password( 24, false, false ) );
	set_transient( 'iskt_zgl_ok_' . $znacznik, 1, 15 * MINUTE_IN_SECONDS );

	wp_safe_redirect(
		add_query_arg( ISKT_PARAM_WYSLANO, $znacznik, remove_query_arg( ISKT_PARAM_WYSLANO ) )
	);
	exit;
}
add_action( 'template_redirect', 'iskt_przetworz_zgloszenie', 5 );

/**
 * Sprawdza, czy bieżące żądanie niesie ważne potwierdzenie wysyłki.
 */
function iskt_potwierdzenie_wyslania(): bool {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- odczyt znacznika z adresu, bez zmiany stanu.
	$znacznik = isset( $_GET[ ISKT_PARAM_WYSLANO ] ) ? sanitize_key( (string) wp_unslash( $_GET[ ISKT_PARAM_WYSLANO ] ) ) : '';

	if ( '' === $znacznik ) {
		return false;
	}

	return false !== get_transient( 'iskt_zgl_ok_' . $znacznik );
}

/**
 * Zwraca adres bieżącej strony bez znacznika potwierdzenia.
 *
 * Adres jest względny i taki ma być: trafia do atrybutu `action` formularza,
 * a wysłanie danych pod adres złożony z nagłówka `Host` byłoby zaproszeniem do
 * podmiany celu żądania.
 */
function iskt_adres_biezacy(): string {
	$adres = remove_query_arg( ISKT_PARAM_WYSLANO );

	return is_string( $adres ) && '' !== $adres ? $adres : (string) home_url( '/' );
}

/**
 * Uzupełnia adres względny o schemat, host i port serwisu.
 *
 * Do wiadomości e-mail adres względny nie nadaje się — w skrzynce nie ma czego
 * dopełnić. Część adresowa pochodzi z ustawień serwisu, a nie z nagłówków żądania:
 * `Host` ustawia klient, więc dałoby się nim wpisać do wiadomości obcą domenę.
 *
 * @param string $sciezka Adres względny wraz z zapytaniem.
 */
function iskt_adres_bezwzgledny( string $sciezka ): string {
	if ( str_starts_with( $sciezka, 'http://' ) || str_starts_with( $sciezka, 'https://' ) ) {
		return $sciezka;
	}

	$dom = (string) home_url( '/' );

	$schemat = (string) wp_parse_url( $dom, PHP_URL_SCHEME );
	$host    = (string) wp_parse_url( $dom, PHP_URL_HOST );
	$port    = wp_parse_url( $dom, PHP_URL_PORT );

	if ( '' === $host ) {
		return $dom;
	}

	$poczatek = $schemat . '://' . $host . ( $port ? ':' . (int) $port : '' );

	return $poczatek . '/' . ltrim( $sciezka, '/' );
}

/**
 * Zwraca stronę ze zgłoszeniem, o ile istnieje i jest opublikowana.
 */
function iskt_strona_zgloszenia(): ?WP_Post {
	$id = absint( get_option( ISKT_OPCJA_STRONA_ZGLOSZENIA, 0 ) );

	$strona = $id > 0 ? get_post( $id ) : get_page_by_path( ISKT_SLUG_ZGLOSZENIA, OBJECT, 'page' );

	if ( ! $strona instanceof WP_Post || 'publish' !== $strona->post_status ) {
		return null;
	}

	return $strona;
}

/**
 * Buduje adres formularza z kontekstem szkolenia i terminu.
 *
 * Gdy strona ze zgłoszeniem nie istnieje — bo właściciel ją usunął albo instalacja
 * jest starsza niż to zadanie — wracamy do sekcji kontaktowej strony głównej.
 * To nadal jest działający odnośnik; zaślepki „#” zabrania §11 pkt 10.
 *
 * @param int $szkolenie_id Identyfikator szkolenia albo 0.
 * @param int $termin_id    Identyfikator terminu albo 0.
 */
function iskt_adres_zgloszenia( int $szkolenie_id = 0, int $termin_id = 0 ): string {
	$parametry = array();

	if ( $szkolenie_id > 0 ) {
		$parametry[ ISKT_PARAM_SZKOLENIE ] = $szkolenie_id;
	}

	if ( $termin_id > 0 ) {
		$parametry[ ISKT_PARAM_TERMIN ] = $termin_id;
	}

	$strona = iskt_strona_zgloszenia();
	$adres  = $strona instanceof WP_Post ? (string) get_permalink( $strona ) : '';
	$kotwica = '';

	if ( '' === $adres ) {
		$adres   = (string) home_url( '/' );
		$kotwica = '#kontakt';
	}

	if ( array() !== $parametry ) {
		$adres = (string) add_query_arg( $parametry, $adres );
	}

	return $adres . $kotwica;
}

/**
 * Zakłada stronę ze zgłoszeniem — dokładnie raz na instalację.
 *
 * Ta sama zasada, co przy stronie głównej motywu: instalacja dokłada brakującą
 * konfigurację, ale nigdy nie cofa decyzji właściciela. Jeśli ktoś tę stronę usunie,
 * nie wraca ona przy następnym wejściu do panelu — wraca tylko odnośnik zapasowy.
 */
function iskt_zapewnij_strone_zgloszenia(): void {
	if ( (bool) get_option( ISKT_OPCJA_ZGLOSZENIE_ZALOZONE, false ) ) {
		return;
	}

	update_option( ISKT_OPCJA_ZGLOSZENIE_ZALOZONE, true, false );

	$istniejaca = get_page_by_path( ISKT_SLUG_ZGLOSZENIA, OBJECT, 'page' );

	if ( $istniejaca instanceof WP_Post ) {
		update_option( ISKT_OPCJA_STRONA_ZGLOSZENIA, (int) $istniejaca->ID, false );

		return;
	}

	$id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_name'    => ISKT_SLUG_ZGLOSZENIA,
			'post_title'   => __( 'Zgłoszenie na szkolenie', 'iskt-szkolenia-core' ),
			'post_status'  => 'publish',
			'post_content' => '<!-- wp:iskt/formularz-zgloszeniowy /-->',
		),
		true
	);

	if ( ! is_wp_error( $id ) ) {
		update_option( ISKT_OPCJA_STRONA_ZGLOSZENIA, (int) $id, false );
	}
}

/*
 * Strona powstaje przy aktywacji wtyczki (patrz `iskt_on_activate()`), ale instalacje
 * sprzed tego zadania mają wtyczkę już aktywną i żadnej aktywacji nie wykonają.
 * Dlatego domykamy to jeszcze przy pierwszym wejściu do panelu — jeden odczyt
 * autoładowanej opcji, bez zapytania do bazy w typowym żądaniu.
 */
add_action( 'admin_init', 'iskt_zapewnij_strone_zgloszenia' );
