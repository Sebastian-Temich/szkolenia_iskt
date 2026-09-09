<?php
/**
 * Teksty globalne serwisu — jedno źródło każdego napisu spoza treści wpisów.
 *
 * §4.7 wymaga, żeby KAŻDY tekst redakcyjny widoczny dla odwiedzającego dał się
 * zmienić z panelu, i żeby powtarzające się dane miały jedno źródło. Treści
 * wpisów i pola katalogu spełniają to same z siebie — problemem są napisy zaszyte
 * w szablonach: nagłówki sekcji, etykiety filtrów, komunikaty pustych wyników,
 * etykiety i błędy formularza, dane kontaktowe.
 *
 * Rejestr mieszka we wtyczce, a nie w motywie, z tego samego powodu co model
 * danych (§6): zmiana motywu nie może usuwać danych. Motyw wywołuje `iskt_tekst()`
 * i nie przechowuje niczego własnego.
 *
 * Zasada zapisu, celowo prosta dla właściciela: **puste pole = tekst domyślny**.
 * Dzięki temu nie da się przypadkiem wykasować zdania wymaganego przez zlecenie
 * (np. zastrzeżenia z §5), a jednocześnie nie trzeba pamiętać oryginalnej treści,
 * żeby wrócić do stanu wyjściowego. Napisy, które właściciel ma prawo w ogóle nie
 * pokazywać — telefon, adres, godziny — mają pustą wartość domyślną i szablony
 * pomijają je, gdy są puste.
 *
 * @package ISKT\Szkolenia\Core
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Opcja z nadpisanymi tekstami.
 *
 * Jedna opcja zamiast kilkudziesięciu: teksty czytane są na każdej odsłonie,
 * a autoładowana tablica to jedno zapytanie zamiast wielu.
 */
const ISKT_OPCJA_TEKSTY = 'iskt_teksty';

/**
 * Zwraca rejestr tekstów globalnych pogrupowany tak, jak wygląda ekran w panelu.
 *
 * Struktura grupy: `etykieta`, `opis` oraz `pola` — mapa klucz → definicja
 * (`etykieta`, `domyslna`, `typ`, opcjonalnie `opis`).
 *
 * Typy pól: `linia` (jeden wiersz), `obszar` (kilka wierszy), `email`, `telefon`,
 * `adres_www`. Typ decyduje o sanityzacji przy zapisie i o kontrolce w panelu.
 *
 * @return array<string, array{etykieta: string, opis: string, pola: array<string, array{etykieta: string, domyslna: string, typ: string, opis?: string}>}>
 */
function iskt_rejestr_tekstow(): array {
	$rejestr = array(
		'kontakt'     => array(
			'etykieta' => __( 'Dane kontaktowe', 'iskt-szkolenia-core' ),
			'opis'     => __( 'Jedno źródło danych kontaktowych. Te same wartości trafiają do stopki, sekcji kontaktu i wiadomości z formularza. Pola pozostawione puste nie pokazują się nigdzie.', 'iskt-szkolenia-core' ),
			'pola'     => array(
				'kontakt_email'   => array(
					'etykieta' => __( 'Adres e-mail', 'iskt-szkolenia-core' ),
					'domyslna' => 'szkolenia@iskt.pl',
					'typ'      => 'email',
					'opis'     => __( 'Adres, na który trafiają zgłoszenia z formularza.', 'iskt-szkolenia-core' ),
				),
				'kontakt_telefon' => array(
					'etykieta' => __( 'Telefon', 'iskt-szkolenia-core' ),
					'domyslna' => '',
					'typ'      => 'telefon',
					'opis'     => __( 'Puste do czasu potwierdzenia numeru (§9 zlecenia). Numer z prototypu +48 32 000 00 00 jest przykładowy i celowo nie został wpisany.', 'iskt-szkolenia-core' ),
				),
				'kontakt_adres'   => array(
					'etykieta' => __( 'Adres', 'iskt-szkolenia-core' ),
					'domyslna' => '',
					'typ'      => 'obszar',
				),
				'kontakt_godziny' => array(
					'etykieta' => __( 'Godziny kontaktu', 'iskt-szkolenia-core' ),
					'domyslna' => '',
					'typ'      => 'linia',
				),
			),
		),
		'naglowek'    => array(
			'etykieta' => __( 'Nagłówek i stopka', 'iskt-szkolenia-core' ),
			'opis'     => __( 'Odnośniki nawigacji ustawia się w Wygląd → Menu, a treść stopki w Wygląd → Widżety. Tutaj są napisy, których menu nie obejmuje.', 'iskt-szkolenia-core' ),
			'pola'     => array(
				'naglowek_menu'            => array(
					'etykieta' => __( 'Przycisk menu na telefonie', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Menu', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'naglowek_nawigacja'       => array(
					'etykieta' => __( 'Opis nawigacji dla czytników ekranu', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Nawigacja główna', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
					'opis'     => __( 'Nie jest widoczny na ekranie; czyta go program udźwiękawiający.', 'iskt-szkolenia-core' ),
				),
				'stopka_dopisek'           => array(
					'etykieta' => __( 'Dopisek pod stopką', 'iskt-szkolenia-core' ),
					'domyslna' => '',
					'typ'      => 'obszar',
					'opis'     => __( 'Wyświetla się obok noty o prawach autorskich. Sama nota składa się z roku i nazwy serwisu, więc nie wymaga wpisywania.', 'iskt-szkolenia-core' ),
				),
				'stopka_prywatnosc_tekst'  => array(
					'etykieta' => __( 'Nazwa odnośnika do informacji o prywatności', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Informacja o prywatności', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'stopka_prywatnosc_adres'  => array(
					'etykieta' => __( 'Adres informacji o prywatności', 'iskt-szkolenia-core' ),
					'domyslna' => '',
					'typ'      => 'adres_www',
					'opis'     => __( 'Dopóki jest pusty, formularz i stopka nie pokazują odnośnika zamiast prowadzić do nieistniejącej strony.', 'iskt-szkolenia-core' ),
				),
			),
		),
		'katalog'     => array(
			'etykieta' => __( 'Katalog szkoleń', 'iskt-szkolenia-core' ),
			'opis'     => __( 'Napisy listy szkoleń: wyszukiwarka, filtry i komunikaty, gdy nic nie zostało znalezione.', 'iskt-szkolenia-core' ),
			'pola'     => array(
				'katalog_tytul'              => array(
					'etykieta' => __( 'Tytuł katalogu', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Katalog szkoleń', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'katalog_wstep'              => array(
					'etykieta' => __( 'Tekst pod tytułem katalogu', 'iskt-szkolenia-core' ),
					'domyslna' => '',
					'typ'      => 'obszar',
				),
				'katalog_szukaj_etykieta'    => array(
					'etykieta' => __( 'Etykieta pola wyszukiwania', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Szukaj szkolenia', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'katalog_szukaj_podpowiedz'  => array(
					'etykieta' => __( 'Podpowiedź w polu wyszukiwania', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'np. ESG, AI, angielski', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'katalog_szukaj_przycisk'    => array(
					'etykieta' => __( 'Przycisk wyszukiwania', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Szukaj', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'katalog_filtr_kategoria'    => array(
					'etykieta' => __( 'Etykieta filtra kategorii', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Kategoria', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'katalog_filtr_forma'        => array(
					'etykieta' => __( 'Etykieta filtra formy', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Forma realizacji', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'katalog_filtr_wszystkie'    => array(
					'etykieta' => __( 'Pozycja „bez ograniczenia” na liście filtra', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Wszystkie', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'katalog_filtr_zastosuj'     => array(
					'etykieta' => __( 'Przycisk zastosowania filtrów', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Pokaż wyniki', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'katalog_filtr_wyczysc'      => array(
					'etykieta' => __( 'Przycisk czyszczenia filtrów', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Wyczyść filtry', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'katalog_brak_wynikow_tytul' => array(
					'etykieta' => __( 'Brak wyników — nagłówek', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Nie znaleźliśmy szkoleń dla tych kryteriów', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'katalog_brak_wynikow_opis'  => array(
					'etykieta' => __( 'Brak wyników — wyjaśnienie', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Zmień frazę albo wyczyść filtry, aby zobaczyć pełną ofertę.', 'iskt-szkolenia-core' ),
					'typ'      => 'obszar',
				),
				'katalog_brak_oferty'        => array(
					'etykieta' => __( 'Pusty katalog — komunikat', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Katalog jest w przygotowaniu. Napisz do nas — dobierzemy szkolenie do Twoich potrzeb.', 'iskt-szkolenia-core' ),
					'typ'      => 'obszar',
					'opis'     => __( 'Pokazuje się, gdy nie ma jeszcze żadnego opublikowanego szkolenia — inaczej niż komunikat braku wyników filtrowania.', 'iskt-szkolenia-core' ),
				),
			),
		),
		'szkolenie'   => array(
			'etykieta' => __( 'Strona szkolenia', 'iskt-szkolenia-core' ),
			'opis'     => __( 'Nagłówki sekcji i stałe napisy na stronie pojedynczego szkolenia. Opis, program i cena pochodzą z pól konkretnego szkolenia.', 'iskt-szkolenia-core' ),
			'pola'     => array(
				'szkolenie_sciezka_katalog'         => array(
					'etykieta' => __( 'Ścieżka nawigacji — nazwa katalogu', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Katalog szkoleń', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'szkolenie_etykieta_czas'           => array(
					'etykieta' => __( 'Etykieta „czas trwania”', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Czas trwania', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'szkolenie_etykieta_forma'          => array(
					'etykieta' => __( 'Etykieta „forma”', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Forma', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'szkolenie_etykieta_poziom'         => array(
					'etykieta' => __( 'Etykieta „poziom”', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Poziom', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'szkolenie_odznaka_wyroznione'      => array(
					'etykieta' => __( 'Odznaka szkolenia wyróżnionego', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Wyróżnione', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'szkolenie_odznaka_dofinansowanie'  => array(
					'etykieta' => __( 'Odznaka możliwego dofinansowania', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Możliwe dofinansowanie', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'szkolenie_naglowek_opis'           => array(
					'etykieta' => __( 'Opis sekcji z treścią dla czytników ekranu', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Opis szkolenia', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'szkolenie_naglowek_korzysci'       => array(
					'etykieta' => __( 'Nagłówek sekcji korzyści', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Czego się nauczysz', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'szkolenie_naglowek_program'        => array(
					'etykieta' => __( 'Nagłówek sekcji programu', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Program szkolenia', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'szkolenie_naglowek_grupa'          => array(
					'etykieta' => __( 'Nagłówek sekcji grupy docelowej', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Dla kogo jest to szkolenie', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'szkolenie_naglowek_cena'           => array(
					'etykieta' => __( 'Podpis nad ceną', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Cena szkolenia', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'szkolenie_naglowek_terminy'        => array(
					'etykieta' => __( 'Nagłówek listy terminów', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Najbliższe terminy', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'szkolenie_brak_terminow'           => array(
					'etykieta' => __( 'Komunikat, gdy nie ma nadchodzących terminów', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Termin ustalamy indywidualnie. Napisz do nas — dobierzemy dogodną datę.', 'iskt-szkolenia-core' ),
					'typ'      => 'obszar',
				),
				'szkolenie_naglowek_trenerzy'       => array(
					'etykieta' => __( 'Nagłówek listy prowadzących', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Prowadzący', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'szkolenie_cta'                     => array(
					'etykieta' => __( 'Przycisk zapytania o szkolenie', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Zapytaj o to szkolenie', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'szkolenie_zastrzezenie'            => array(
					'etykieta' => __( 'Zastrzeżenie przy przycisku', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Wysłanie zapytania nie rezerwuje miejsca na szkoleniu. Odpowiemy z potwierdzeniem dostępności.', 'iskt-szkolenia-core' ),
					'typ'      => 'obszar',
					'opis'     => __( '§5 zlecenia wymaga, aby zgłoszenie nie było przedstawiane jako rezerwacja miejsca. Zmieniając ten tekst, prosimy zachować tę informację.', 'iskt-szkolenia-core' ),
				),
				'szkolenie_naglowek_dofinansowanie' => array(
					'etykieta' => __( 'Nagłówek sekcji dofinansowania', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Dofinansowanie', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'szkolenie_dofinansowanie_opis'     => array(
					'etykieta' => __( 'Domyślny opis dofinansowania', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'To szkolenie może zostać objęte dofinansowaniem. Napisz do nas — sprawdzimy dostępne źródła i warunki.', 'iskt-szkolenia-core' ),
					'typ'      => 'obszar',
					'opis'     => __( 'Używany, gdy przy szkoleniu zaznaczono dofinansowanie, ale nie opisano warunków. Warunki wpisane przy szkoleniu mają pierwszeństwo.', 'iskt-szkolenia-core' ),
				),
			),
		),
		'trenerzy'    => array(
			'etykieta' => __( 'Trenerzy', 'iskt-szkolenia-core' ),
			'opis'     => __( 'Napisy listy trenerów i profilu trenera.', 'iskt-szkolenia-core' ),
			'pola'     => array(
				'trenerzy_tytul'                 => array(
					'etykieta' => __( 'Tytuł listy trenerów', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Trenerzy', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'trenerzy_wstep'                 => array(
					'etykieta' => __( 'Tekst pod tytułem listy trenerów', 'iskt-szkolenia-core' ),
					'domyslna' => '',
					'typ'      => 'obszar',
				),
				'trenerzy_brak'                  => array(
					'etykieta' => __( 'Komunikat pustej listy trenerów', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Lista trenerów jest w przygotowaniu.', 'iskt-szkolenia-core' ),
					'typ'      => 'obszar',
				),
				'trener_naglowek_doswiadczenie'  => array(
					'etykieta' => __( 'Nagłówek sekcji doświadczenia', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Doświadczenie', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'trener_naglowek_wyksztalcenie'  => array(
					'etykieta' => __( 'Nagłówek sekcji wykształcenia i certyfikatów', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Wykształcenie i certyfikaty', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'trener_naglowek_specjalizacje'  => array(
					'etykieta' => __( 'Nagłówek sekcji specjalizacji', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Specjalizacje', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'trener_naglowek_szkolenia'      => array(
					'etykieta' => __( 'Nagłówek listy szkoleń trenera', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Szkolenia prowadzone przez trenera', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'trener_brak_szkolen'            => array(
					'etykieta' => __( 'Komunikat, gdy trener nie ma powiązanych szkoleń', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Ten trener nie ma jeszcze przypisanych szkoleń w katalogu.', 'iskt-szkolenia-core' ),
					'typ'      => 'obszar',
				),
			),
		),
		'aktualnosci' => array(
			'etykieta' => __( 'Aktualności', 'iskt-szkolenia-core' ),
			'opis'     => __( 'Napisy listy wpisów i strony artykułu.', 'iskt-szkolenia-core' ),
			'pola'     => array(
				'aktualnosci_tytul'       => array(
					'etykieta' => __( 'Tytuł listy aktualności', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Aktualności', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'aktualnosci_wstep'       => array(
					'etykieta' => __( 'Tekst pod tytułem listy aktualności', 'iskt-szkolenia-core' ),
					'domyslna' => '',
					'typ'      => 'obszar',
				),
				'aktualnosci_czytaj'      => array(
					'etykieta' => __( 'Odnośnik do pełnego wpisu', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Czytaj dalej', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'aktualnosci_brak'        => array(
					'etykieta' => __( 'Komunikat pustej listy aktualności', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Nie ma tu jeszcze żadnych treści.', 'iskt-szkolenia-core' ),
					'typ'      => 'obszar',
				),
				'aktualnosci_kategorie'   => array(
					'etykieta' => __( 'Nazwa filtra kategorii nad listą', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Kategorie', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
					'opis'     => __( 'Czytnik ekranu odczytuje ten napis przed listą kategorii. Filtr pokazuje się dopiero przy dwóch niepustych kategoriach.', 'iskt-szkolenia-core' ),
				),
				'aktualnosci_kategorie_wszystkie' => array(
					'etykieta' => __( 'Pozycja „wszystkie” w filtrze kategorii', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Wszystkie', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'aktualnosci_zaktualizowano' => array(
					'etykieta' => __( 'Informacja o dacie aktualizacji wpisu', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Zaktualizowano %s', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
					'opis'     => __( '„%s” zastępujemy datą. Bez tego znaku data doklei się na końcu zdania. Informacja pojawia się tylko wtedy, gdy wpis zmieniono w innym dniu niż opublikowano.', 'iskt-szkolenia-core' ),
				),
				'aktualnosci_poprzedni'   => array(
					'etykieta' => __( 'Etykieta odnośnika do poprzedniego wpisu', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Poprzedni wpis', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'aktualnosci_nastepny'    => array(
					'etykieta' => __( 'Etykieta odnośnika do następnego wpisu', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Następny wpis', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'aktualnosci_polecane_tytul' => array(
					'etykieta' => __( 'Tytuł sekcji ze szkoleniami pod artykułem', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Zobacz nasze szkolenia', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
					'opis'     => __( 'Sekcja pokazuje szkolenia oznaczone jako wyróżnione. Gdy żadne nie jest wyróżnione, sekcja w ogóle się nie pojawia.', 'iskt-szkolenia-core' ),
				),
				'aktualnosci_polecane_katalog' => array(
					'etykieta' => __( 'Odnośnik do katalogu pod artykułem', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Cały katalog', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
					'opis'     => __( 'Puste pole ukrywa przycisk.', 'iskt-szkolenia-core' ),
				),
			),
		),
		'formularz'   => array(
			'etykieta' => __( 'Formularz zgłoszeniowy', 'iskt-szkolenia-core' ),
			'opis'     => __( 'Etykiety pól, komunikaty błędów i potwierdzenie wysyłki. Adres odbiorcy ustawia się w grupie „Dane kontaktowe”.', 'iskt-szkolenia-core' ),
			'pola'     => array(
				'formularz_tytul'         => array(
					'etykieta' => __( 'Tytuł formularza', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Zapytaj o szkolenie', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'formularz_wstep'         => array(
					'etykieta' => __( 'Tekst pod tytułem formularza', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Odpowiemy na zapytanie i dobierzemy szkolenie do Twoich potrzeb.', 'iskt-szkolenia-core' ),
					'typ'      => 'obszar',
				),
				'formularz_typ'           => array(
					'etykieta' => __( 'Etykieta wyboru rodzaju odbiorcy', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Piszę jako', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'formularz_typ_osoba'     => array(
					'etykieta' => __( 'Rodzaj odbiorcy — osoba', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Osoba indywidualna', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'formularz_typ_firma'     => array(
					'etykieta' => __( 'Rodzaj odbiorcy — firma', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Firma', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'formularz_imie'          => array(
					'etykieta' => __( 'Etykieta pola „imię i nazwisko”', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Imię i nazwisko', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'formularz_email'         => array(
					'etykieta' => __( 'Etykieta pola „e-mail”', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Adres e-mail', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'formularz_telefon'       => array(
					'etykieta' => __( 'Etykieta pola „telefon”', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Telefon (opcjonalnie)', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'formularz_firma'         => array(
					'etykieta' => __( 'Etykieta pola „nazwa firmy”', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Nazwa firmy (opcjonalnie)', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'formularz_szkolenie'     => array(
					'etykieta' => __( 'Etykieta pola wyboru szkolenia', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Szkolenie lub obszar zainteresowania', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'formularz_termin'        => array(
					'etykieta' => __( 'Etykieta pola wyboru terminu', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Wybrany termin', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'formularz_wiadomosc'     => array(
					'etykieta' => __( 'Etykieta pola wiadomości', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Wiadomość', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'formularz_przycisk'      => array(
					'etykieta' => __( 'Przycisk wysyłki', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Wyślij zapytanie', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'formularz_zastrzezenie'  => array(
					'etykieta' => __( 'Zastrzeżenie pod formularzem', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Zapytanie nie jest potwierdzeniem rezerwacji miejsca. Skontaktujemy się, aby potwierdzić dostępność.', 'iskt-szkolenia-core' ),
					'typ'      => 'obszar',
					'opis'     => __( '§5 zlecenia wymaga tej informacji przy formularzu.', 'iskt-szkolenia-core' ),
				),
				'formularz_blad_ogolny'   => array(
					'etykieta' => __( 'Błąd — podsumowanie nad formularzem', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Nie udało się wysłać zapytania. Popraw zaznaczone pola i spróbuj ponownie.', 'iskt-szkolenia-core' ),
					'typ'      => 'obszar',
				),
				'formularz_blad_wymagane' => array(
					'etykieta' => __( 'Błąd — pole wymagane', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'To pole jest wymagane.', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'formularz_blad_email'    => array(
					'etykieta' => __( 'Błąd — niepoprawny adres e-mail', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Podaj adres e-mail w formacie nazwa@domena.pl.', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'formularz_blad_wysylki'  => array(
					'etykieta' => __( 'Błąd — wiadomość nie została dostarczona', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Nie udało się dostarczyć wiadomości. Prosimy o kontakt e-mailowy — adres znajduje się w stopce.', 'iskt-szkolenia-core' ),
					'typ'      => 'obszar',
					'opis'     => __( '§5 zlecenia: potwierdzenie pokazujemy dopiero po przyjęciu zgłoszenia przez mechanizm wysyłkowy. Ten komunikat pojawia się, gdy wysyłka zawiodła.', 'iskt-szkolenia-core' ),
				),
				'formularz_sukces'        => array(
					'etykieta' => __( 'Potwierdzenie wysłania', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Dziękujemy — zapytanie do nas dotarło. Odezwiemy się na podany adres e-mail.', 'iskt-szkolenia-core' ),
					'typ'      => 'obszar',
				),
			),
		),
		'wyszukiwanie' => array(
			'etykieta' => __( 'Wyszukiwanie i strona błędu', 'iskt-szkolenia-core' ),
			'opis'     => __( 'Napisy wyszukiwarki serwisu i strony „nie znaleziono”.', 'iskt-szkolenia-core' ),
			'pola'     => array(
				'wyszukiwanie_etykieta'   => array(
					'etykieta' => __( 'Etykieta wyszukiwarki serwisu', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Szukaj w serwisie', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'wyszukiwanie_podpowiedz' => array(
					'etykieta' => __( 'Podpowiedź w wyszukiwarce serwisu', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'np. ESG, AI, angielski', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'wyszukiwanie_przycisk'   => array(
					'etykieta' => __( 'Przycisk wyszukiwarki serwisu', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Szukaj', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'blad404_tytul'           => array(
					'etykieta' => __( 'Strona nie znaleziona — nagłówek', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Nie znaleźliśmy tej strony', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'blad404_opis'            => array(
					'etykieta' => __( 'Strona nie znaleziona — wyjaśnienie', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Adres mógł się zmienić albo zawiera literówkę. Poniżej znajdziesz wyszukiwarkę i drogę powrotną.', 'iskt-szkolenia-core' ),
					'typ'      => 'obszar',
				),
				'blad404_przycisk'        => array(
					'etykieta' => __( 'Strona nie znaleziona — przycisk powrotu', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Wróć na stronę główną', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
				'blad404_sekcje'          => array(
					'etykieta' => __( 'Strona nie znaleziona — nagłówek listy odnośników', 'iskt-szkolenia-core' ),
					'domyslna' => __( 'Sekcje serwisu', 'iskt-szkolenia-core' ),
					'typ'      => 'linia',
				),
			),
		),
	);

	/**
	 * Pozwala rozszerzyć rejestr tekstów o własne grupy i pola.
	 *
	 * Motyw może dołożyć napisy, których wtyczka nie zna, i nadal korzystać
	 * z tego samego ekranu w panelu oraz z `iskt_tekst()`.
	 *
	 * @param array<string, array<string, mixed>> $rejestr Rejestr grup i pól.
	 */
	$rozszerzony = apply_filters( 'iskt_rejestr_tekstow', $rejestr );

	return is_array( $rozszerzony ) ? $rozszerzony : $rejestr;
}

/**
 * Zwraca płaską mapę klucz → definicja pola.
 *
 * @return array<string, array{etykieta: string, domyslna: string, typ: string, opis?: string}>
 */
function iskt_definicje_tekstow(): array {
	$plaska = array();

	foreach ( iskt_rejestr_tekstow() as $grupa ) {
		foreach ( ( $grupa['pola'] ?? array() ) as $klucz => $definicja ) {
			$plaska[ (string) $klucz ] = $definicja;
		}
	}

	return $plaska;
}

/**
 * Sanityzuje pojedynczą wartość zgodnie z typem pola.
 *
 * Nieznany typ traktujemy jak `linia` — bezpieczniej zawęzić niż przepuścić.
 *
 * @param mixed  $wartosc Wartość z formularza.
 * @param string $typ     Typ pola z rejestru.
 */
function iskt_sanitize_tekst( $wartosc, string $typ ): string {
	if ( is_array( $wartosc ) || is_object( $wartosc ) || null === $wartosc ) {
		return '';
	}

	$surowa = trim( (string) $wartosc );

	if ( '' === $surowa ) {
		return '';
	}

	switch ( $typ ) {
		case 'obszar':
			return trim( sanitize_textarea_field( $surowa ) );

		case 'email':
			return sanitize_email( $surowa );

		case 'adres_www':
			return esc_url_raw( $surowa );

		case 'telefon':
			/*
			 * Numer telefonu to nie dowolny tekst. Zostawiamy cyfry, plus, spacje
			 * i myślniki — dzięki temu „+48 32 000 00 00” przechodzi, a wklejony
			 * przez pomyłkę adres czy znacznik nie trafia na stronę.
			 */
			$numer = preg_replace( '/[^0-9+()\-\s]/u', '', $surowa );

			return trim( is_string( $numer ) ? $numer : '' );

		default:
			return trim( sanitize_text_field( $surowa ) );
	}
}

/**
 * Sanityzuje całą opcję z tekstami.
 *
 * Zapisujemy wyłącznie klucze obecne w rejestrze i wyłącznie wartości różne od
 * domyślnych. Powód praktyczny: gdy właściciel nie zmieniał danego napisu, po
 * aktualizacji serwisu zobaczy poprawioną wersję domyślną zamiast zamrożonej
 * kopii sprzed aktualizacji. Wartość równa domyślnej i tak daje ten sam wynik.
 *
 * @param mixed $wartosc Tablica z formularza.
 *
 * @return array<string, string>
 */
function iskt_sanitize_teksty( $wartosc ): array {
	if ( ! is_array( $wartosc ) ) {
		return array();
	}

	$czyste = array();

	foreach ( iskt_definicje_tekstow() as $klucz => $definicja ) {
		if ( ! array_key_exists( $klucz, $wartosc ) ) {
			continue;
		}

		$typ    = (string) ( $definicja['typ'] ?? 'linia' );
		$czysta = iskt_sanitize_tekst( $wartosc[ $klucz ], $typ );

		if ( '' === $czysta || $czysta === (string) ( $definicja['domyslna'] ?? '' ) ) {
			continue;
		}

		$czyste[ $klucz ] = $czysta;
	}

	return $czyste;
}

/**
 * Zwraca zapisane nadpisania tekstów.
 *
 * @return array<string, string>
 */
function iskt_zapisane_teksty(): array {
	$zapisane = get_option( ISKT_OPCJA_TEKSTY, array() );

	return is_array( $zapisane ) ? $zapisane : array();
}

/**
 * Zwraca tekst globalny — wartość właściciela albo domyślną.
 *
 * Klucz spoza rejestru zwraca pusty ciąg zamiast wywoływać błąd: literówka w
 * szablonie ma zabraknąć jednego napisu, a nie wywrócić stronę.
 *
 * @param string $klucz Klucz z rejestru.
 */
function iskt_tekst( string $klucz ): string {
	$definicje = iskt_definicje_tekstow();

	if ( ! isset( $definicje[ $klucz ] ) ) {
		return '';
	}

	$zapisane = iskt_zapisane_teksty();

	if ( isset( $zapisane[ $klucz ] ) && '' !== trim( (string) $zapisane[ $klucz ] ) ) {
		return (string) $zapisane[ $klucz ];
	}

	return (string) ( $definicje[ $klucz ]['domyslna'] ?? '' );
}

/**
 * Rejestruje opcję tekstów.
 *
 * `register_setting()` daje sanityzację przy każdym zapisie — także przez REST
 * i WP-CLI, nie tylko przez ekran w panelu.
 */
function iskt_rejestruj_teksty(): void {
	register_setting(
		'iskt_teksty',
		ISKT_OPCJA_TEKSTY,
		array(
			'type'              => 'object',
			'description'       => __( 'Teksty globalne serwisu ISKT.', 'iskt-szkolenia-core' ),
			'sanitize_callback' => 'iskt_sanitize_teksty',
			'default'           => array(),
			'show_in_rest'      => false,
		)
	);
}
add_action( 'init', 'iskt_rejestruj_teksty' );
