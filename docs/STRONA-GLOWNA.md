# Strona główna — sekcje, bloki i przełącznik odbiorcy

Data: 2026-09-09 · ISK-17 zadanie 4 (M1)
Dotyczy: `wp-content/themes/iskt-szkolenia/patterns/`, `wp-content/plugins/iskt-szkolenia-core/blocks/`

Realizuje §4.1 zlecenia: odwzorowanie strony głównej z załącznika wraz z możliwością
**edycji obu wersji komunikacji, dodawania i przestawiania sekcji oraz ukrywania
sekcji bez treści** — wszystko z panelu, bez dotykania kodu.

---

## 1. Skąd bierze się treść strony głównej

`front-page.php` nie zawiera ani jednego tekstu redakcyjnego. Wypisuje wyłącznie
`the_content()` strony ustawionej jako strona główna. Cała zawartość to bloki.

Konsekwencja jest taka, jakiej wymaga §4.7: przestawienie sekcji to przeciągnięcie
bloku, usunięcie sekcji to skasowanie bloku, a zmiana tekstu to kliknięcie w tekst.
Żadna z tych czynności nie dotyka PHP.

Gdy strona główna jest pusta, redaktor (i tylko on — sprawdzamy `edit_pages`) widzi
komunikat z odnośnikiem do edytora. Odwiedzający nie widzi nic.

## 2. Podział: wzorce kontra bloki dynamiczne

Podział wynika z ADR-002 §3.3 i ma jedno kryterium: **czy treść sekcji zmienia się
sama, gdy zmienia się katalog.**

| Sekcja | Realizacja | Dlaczego tak |
|---|---|---|
| Powitanie (hero) | wzorzec `iskt-szkolenia/hero` | Tekst redakcyjny. Nie ma powodu, żeby przechodził przez kod |
| Obszary szkoleń | blok `iskt/obszary-szkolen` | Lista kategorii żyje w taksonomii. Zapisana w bloku rozjechałaby się przy pierwszej edycji kategorii |
| Wyróżnione szkolenia | blok `iskt/wyroznione-szkolenia` | Wyróżnienie to przełącznik przy szkoleniu. Sekcja musi czytać katalog przy każdym wyświetleniu |
| Przebieg współpracy | wzorzec `iskt-szkolenia/przebieg` | Tekst redakcyjny, dwa warianty odbiorcy |
| Dofinansowania | wzorzec `iskt-szkolenia/dofinansowania` | Tekst redakcyjny |
| Wskaźniki | wzorzec `iskt-szkolenia/wskazniki` | Tekst redakcyjny — w całości do potwierdzenia (§9) |
| Kontakt | wzorzec `iskt-szkolenia/kontakt` | Tekst redakcyjny. Formularz dokłada zadanie 9 |

Wzorzec `iskt-szkolenia/strona-glowna` składa komplet w jednym kroku. Nie powiela
znaczników — dołącza te same pliki sekcji przez `iskt_wzorzec_sekcji()`.

## 3. Przełącznik „Dla Ciebie” / „Dla firm”

### 3.1. Jak działa

Dwa bloki:

- **`iskt/przelacznik-odbiorcy`** — sam przełącznik, wstawiany raz na stronie;
- **`iskt/tresc-odbiorcy`** — pojemnik na treść jednego wariantu, wstawiany tyle
  razy, ile sekcji ma się różnić. W środku działa cały edytor blokowy.

Wybrany wariant jest **częścią adresu**: `?odbiorca=firmowy`. Serwer renderuje oba
warianty, nieaktywnemu dokłada atrybut `hidden`.

### 3.2. Dlaczego wariant siedzi w adresie, a nie w stanie skryptu

Prototyp trzymał wybór w stanie komponentu React. Skutki, opisane
w `PROTOTYP-INWENTARYZACJA.md` §2.1: odświeżenie strony gubiło wybór, przycisk
„Wstecz” nie działał, a odnośnika do wariantu firmowego nie dało się nikomu wysłać.

Nasz przełącznik to formularz `GET`. Bez JavaScriptu kliknięcie przeładowuje stronę
z parametrem i serwer pokazuje właściwy wariant. Skrypt `assets/js/odbiorca.js`
tę mechanikę wyłącznie skraca — przełącza widoczność na miejscu i podmienia adres
przez `history.replaceState()`. Wyłączenie skryptu nie psuje niczego poza płynnością.

`replaceState`, a nie `pushState`: przełącznik zmienia wariant tekstu, a nie stronę.
Gdyby każde kliknięcie dokładało wpis do historii, „Wstecz” przestałby wracać tam,
skąd odwiedzający przyszedł.

### 3.3. Dostępność

- Przyciski niosą stan w `aria-pressed`, a CSS koloruje je **z tego samego atrybutu** —
  wygląd nie może rozjechać się z tym, co słyszy czytnik ekranu.
- Grupa przycisków ma `role="group"` i etykietę edytowalną z panelu.
- Nieaktywny wariant jest ukryty atrybutem `hidden`, nie klasą CSS. Czytnik ekranu
  pomija treść ukrytą atrybutem, więc odwiedzający nie usłyszy dwóch wersji tego
  samego nagłówka.

### 3.4. Wariant domyślny

Opcja `iskt_odbiorca_domyslny`; brak opcji oznacza wariant indywidualny. Ekran
ustawień dokłada zadanie 10 — do tego czasu wartość zmienia się przez
`update_option()`. Uszkodzona wartość opcji nie wywraca strony: wraca wariant
indywidualny.

## 4. Ukrywanie sekcji bez treści (§4.1)

Żaden przełącznik „pokaż sekcję” nie jest do tego potrzebny — sekcja znika sama:

| Blok | Kiedy zwraca pusty ciąg |
|---|---|
| `iskt/obszary-szkolen` | brak kategorii z opublikowanym szkoleniem |
| `iskt/wyroznione-szkolenia` | żadne szkolenie nie jest wyróżnione |
| `iskt/tresc-odbiorcy` | pojemnik nie ma treści |

„Nie ma treści” nie znaczy „nie ma tekstu”: sekcja złożona wyłącznie ze zdjęcia,
filmu albo separatora zostaje pokazana (`iskt_ma_tresc()`). Ukrycie jej byłoby
zniknięciem sekcji bez żadnego komunikatu — usterką trudną do zrozumienia dla
właściciela.

W edytorze pusta sekcja pokazuje wyjaśnienie zamiast pustego miejsca: podgląd jest
renderowany przez serwer (`ServerSideRender`), więc redaktor widzi dokładnie to,
co zobaczy odwiedzający.

## 5. Cena na karcie szkolenia

`iskt_cena_szkolenia()` składa kwotę z jednostką i sposobem prezentacji podatku
(§4.3). **Nie wylicza ceny po dofinansowaniu.**

To świadome odstępstwo od prototypu, który mnożył cenę przez 0,2 i podpisywał wynik
„z dofinansowaniem”. §4.3 zabrania tego wprost: „Nie obliczać automatycznie ceny po
dofinansowaniu na podstawie założenia, że każdy uczestnik uzyska określony poziom
wsparcia”. Zamiast wyliczonej kwoty karta pokazuje opisową plakietkę „Możliwe
dofinansowanie”, a warunki są osobnym polem tekstowym szkolenia.

Jednostka „do ustalenia indywidualnie” daje „Wycena indywidualna” bez kwoty — cena,
której nie ma, nie udaje, że jest.

## 6. Symbol kategorii

Karty obszarów mają w załączniku po jednej ikonie. Kategorie są edytowalne (§4.2),
więc przypisanie ikony po nazwie albo slugu byłoby kruche.

Rozwiązanie zachowuje podział z ADR-001 §1:

- **wtyczka** przechowuje w polu terminu (`iskt_ikona`) wyłącznie **identyfikator**
  symbolu, np. `ai`, wybierany z listy przy edycji kategorii;
- **motyw** mapuje identyfikator na kształt SVG (`iskt_ikony_symboli()`) przez filtr
  `iskt_symbol_html`.

Zmiana motywu nie unieważnia zapisanych wartości; motyw bez obsługi filtru pokazuje
karty bez symbolu i nadal jest kompletny.

## 7. `theme.json` — co dostaje redaktor

Paleta, skala pisma i odstępy w edytorze wskazują **na te same tokeny**, co arkusze
motywu — jedna wartość, dwa miejsca użycia.

Celowo wyłączone: własne kolory, własne stopnie pisma, własne odstępy, promienie
i obramowania. Powód jest praktyczny: pole wyboru dowolnego koloru w edytorze to
najkrótsza droga do strony, która przestaje wyglądać jak marka, a §11 pkt 1 każe
utrzymać zgodność z załącznikiem. Redaktor wybiera z palety marki i nie może się
pomylić.

Wyłączone są też wzorce z katalogu WordPress.org — pobiera je zewnętrzna usługa,
wyglądają obco i zasypałyby siedem wzorców przygotowanych dla tego serwisu.

## 8. Bloki rdzenia w oprawie motywu

`assets/css/sekcje.css` ubiera bloki rdzenia (przycisk, kolumny, lista) w wygląd
z załącznika. Dzięki temu przycisk dodany przez właściciela w dowolnym miejscu
strony wygląda jak przycisk z szablonu, a nie jak obcy element.

Styl „Na ciemnym tle” jest zarejestrowany jako **styl bloku**
(`register_block_style`), a nie jako klasa do wpisania ręcznie — właściciel wybiera
go z listy i nie musi wiedzieć, jak nazywa się klasa CSS.

## 9. Co sprawdzono

- `php -l` na wszystkich plikach PHP motywu i wtyczki — bez błędów;
- `node --check` na `assets/bloki.js` i `assets/js/odbiorca.js` — bez błędów;
- walidacja JSON wszystkich plików `block.json` i `theme.json`;
- `php tests/run.php` — **79 asercji**, wszystkie przechodzą. Nowe przypadki
  obejmują: sanityzację wariantu odbiorcy, pierwszeństwo adresu nad opcją,
  odrzucenie wstrzyknięcia w adresie, `aria-pressed` na obu przyciskach,
  ukrywanie nieaktywnego wariantu, zachowanie sekcji złożonej ze zdjęcia,
  składanie ceny z jednostką i podatkiem oraz brak kwoty przy wycenie indywidualnej.

Czego **nie** sprawdzono — patrz `ODSTEPSTWA-OD-WZORCA.md` §5. Wyglądu nadal nie
obejrzeliśmy w przeglądarce, bo `wp-env` nie zostało uruchomione (brak działającego
demona Dockera w środowisku wykonawczym).
