# Katalog szkoleń — wyszukiwanie, filtry, paginacja

Zadanie 6 planu (M2). Realizuje §4.2 zlecenia: pełna lista oferty z wyszukiwaniem
po nazwie i opisie, filtrowaniem po kategorii i formie realizacji, wyróżnianiem
wybranych szkoleń oraz paginacją, dzięki której katalog rośnie bez przebudowy widoku.

| Gdzie | Co odpowiada |
|---|---|
| `wp-content/plugins/iskt-szkolenia-core/includes/katalog.php` | **co** katalog pokazuje: parametry adresu, zapytanie, listy filtrów, reguła indeksowania |
| `wp-content/themes/iskt-szkolenia/archive-iskt_szkolenie.php` | **jak** katalog wygląda: nagłówek, siatka kart, komunikaty pustych widoków |
| `wp-content/themes/iskt-szkolenia/template-parts/katalog/filtry.php` | pasek wyszukiwania i filtrów |
| `wp-content/themes/iskt-szkolenia/taxonomy-iskt_kategoria.php`, `taxonomy-iskt_forma.php` | te same widoki pod adresem kategorii i formy |

Podział motyw / wtyczka jest ten sam, co przy modelu danych (ADR-001 §1): zmiana
motywu nie może zabrać katalogowi filtrów.

## 1. Adresy

| Adres | Widok |
|---|---|
| `/szkolenia/` | pełny katalog |
| `/szkolenia/page/2/` | kolejne strony |
| `/szkolenia/?szukaj=esg&kategoria=…&forma=…` | katalog zawężony |
| `/szkolenia/kategoria/{slug}/` | katalog zawężony do kategorii, z widocznym filtrem |
| `/szkolenia/forma/{slug}/` | katalog zawężony do formy |
| `/szkolenia/{slug}/` | strona szkolenia (zadanie 5) |

Stan filtrów siedzi w adresie, bo §6 wymaga, aby „Wstecz” działało, a wynik dał się
odesłać linkiem. To także jedyny sposób, w jaki filtry mogą działać **bez
JavaScriptu** — pasek filtrów to zwykły formularz GET. Lekcja z ISK-21 jest
świeża: mechanizm zależny od skryptu pada pierwszy.

## 2. Dlaczego fraza jedzie w `szukaj`, a nie w `s`

Parametr `s` włącza w WordPressie tryb wyszukiwania w całym serwisie: widok
przestaje być archiwum szkoleń, dostaje szablon wyników wyszukiwania i miesza
szkolenia z wpisami i stronami. Katalog ma zostać katalogiem, więc ma własny
parametr. Wyszukiwarka całego serwisu (`searchform.php`) działa niezależnie i dalej
używa `s`.

Fraza trafia do zapytania głównego jako `s`, więc przeszukuje **tytuł, zajawkę
i treść** szkolenia — czyli nazwę i opis wymagane przez §4.2.

## 3. Filtry zmieniają zapytanie główne

Wyszukiwanie i filtry nakładamy w `pre_get_posts`, zamiast budować drugie zapytanie
obok głównego. Dzięki temu paginacja, adresy `/szkolenia/page/2/`, adres kanoniczny
i obsługa nieistniejącej strony pochodzą z WordPressa — nie odtwarzamy ich własnym
kodem, który trzeba by utrzymywać.

Skutki uboczne, które chcieliśmy:

- **wycofane szkolenie znika samo.** Zapytanie archiwum czyta wyłącznie wpisy
  opublikowane, więc przeniesienie szkolenia do szkiców zabiera je z katalogu,
  z wyszukiwania i — przez `hide_empty` — z list filtrów (§4.2);
- **kategoria bez oferty znika z filtra.** Pozycja prowadząca zawsze do pustej listy
  jest gorsza niż jej brak;
- **paginacja zachowuje filtry.** `paginate_links()` dokleja bieżące parametry adresu
  sama, więc druga strona wyników filtrowanych nadal jest filtrowana.

## 4. Kolejność i wyróżnianie

Kolejność: **wyróżnione → kolejność ustawiona przez właściciela (pole „Kolejność”)
→ najnowsze**.

Wyróżnienie to pole `_iskt_wyroznione` przy szkoleniu, a nie druga lista utrzymywana
obok katalogu. Szkolenie, którego nikt nigdy nie wyróżnił, **nie ma tego pola
w bazie** — dlatego zapytanie pyta o „pole istnieje LUB nie istnieje”, zamiast
sortować po `meta_key`. Zwykłe `meta_key` wycięłoby z katalogu wszystko, czego nikt
nie wyróżnił, a widok wyglądałby przy tym na kompletny. Test `tests/run.php` pilnuje
dokładnie tego przypadku.

W katalogu wyróżnione szkolenie dostaje plakietkę „Wyróżnione” i wyraźniejszą
obwódkę karty. Napis niesie informację sam, więc wyróżnienie działa także bez
rozróżniania kolorów. W sekcji wyróżnionych na stronie głównej plakietki nie ma —
tam każda karta jest wyróżniona, więc odznaka na każdej z nich nic by nie mówiła.

## 5. §7 — indeksowanie widoków filtrowanych (decyzja)

**Decyzja: widoki z parametrami filtrów dostają `noindex`. Katalog bez parametrów,
archiwa kategorii i formy oraz kolejne strony paginacji indeksują się normalnie.**

Uzasadnienie:

- fraza i dwa filtry dają praktycznie nieskończoną liczbę adresów pokazujących te
  same szkolenia w innej kolejności. Indeksowanie ich rozprasza sygnały między
  duplikaty i marnuje budżet indeksowania na strony, które nikomu nie odpowiadają
  na pytanie lepiej niż pełny katalog;
- **archiwa kategorii i formy zostają w indeksie** — mają trwałe adresy, własną
  nazwę i opis edytowalny w panelu, więc są prawdziwymi stronami tematycznymi,
  a nie kombinacją parametrów;
- **paginacja zostaje w indeksie** — druga strona katalogu zawiera inne szkolenia,
  więc nie jest duplikatem. Wycięcie jej odcięłoby robotom drogę do szkoleń
  spoza pierwszej strony;
- dokładamy wyłącznie `noindex`, bez `nofollow`. Podążanie za odnośnikami jest
  zachowaniem domyślnym, więc roboty dojdą z widoku filtrowanego do stron szkoleń.
  Nie nadpisujemy też ustawienia „Proś wyszukiwarki o nieindeksowanie” z panelu —
  gdy właściciel je włączy, zostaje w mocy.

Zakres nie obejmuje wtyczki SEO (ADR-002), więc decyzję realizuje jeden filtr
rdzenia `wp_robots` w `includes/katalog.php`. Gdyby ISKT zdecydowało się później na
wtyczkę SEO, ten filtr jest jedynym miejscem do uzgodnienia.

## 6. Poprawka adresów kategorii przy okazji

Adresy `/szkolenia/kategoria/{slug}/` i `/szkolenia/forma/{slug}/` **zwracały stronę
„nie znaleziono”**, mimo poprawnej rejestracji taksonomii. Powód: WordPress dokłada
typowi treści regułę adresu dla załączników szkolenia — `szkolenia/{cokolwiek}/{cokolwiek}` —
a reguły sprawdzane są w kolejności rejestracji, więc reguła załącznika przechwytywała
adres kategorii. Kafelki obszarów szkoleń na stronie głównej (§4.1) prowadzą właśnie
tam, czyli każdy odwiedzający, który w nie kliknął, trafiał na stronę błędu.

Naprawa: taksonomie rejestrujemy przed typami treści (`init`, priorytet 9), tak samo
przy aktywacji wtyczki. Kolejność jest znacząca — komentarz w `model.php` i w
`activation.php` mówi o tym wprost, żeby nikt jej nie „posprzątał”.

**Po aktualizacji wtyczki na środowisku, na którym już działała, trzeba raz przeliczyć
reguły adresów** — wystarczy wejść w panelu w Ustawienia → Bezpośrednie odnośniki
i zapisać, albo wykonać `wp rewrite flush`. Aktywacja wtyczki robi to sama.

## 7. Dwa różne puste widoki

To dwie różne sytuacje dla odwiedzającego i mają dwa różne komunikaty:

| Sytuacja | Komunikat | Co proponuje |
|---|---|---|
| Filtry bez trafień | `katalog_brak_wynikow_tytul` + `katalog_brak_wynikow_opis` | zmianę frazy albo wyczyszczenie filtrów |
| Katalog pusty — brak jakiegokolwiek opublikowanego szkolenia | `katalog_brak_oferty` | kontakt; nie proponuje czyszczenia filtrów, bo nie ma czego czyścić |

Dodatkowe zapytanie sprawdzające, czy katalog ma w ogóle jakieś szkolenie, wykonuje
się wyłącznie wtedy, gdy widok nie ma wyników.

## 8. Napisy

Żaden napis katalogu nie jest wpisany w szablon. Wszystkie pochodzą z rejestru
tekstów wtyczki (grupa „Katalog szkoleń”, `includes/teksty.php`) i właściciel zmienia
je w panelu: Szkolenia → Teksty serwisu. Puste pole oznacza powrót do treści
domyślnej. Przy okazji na kartę katalogu trafiła odznaka dofinansowania czytana
z tego samego rejestru — wcześniej była jedynym napisem karty zaszytym w kodzie.

Dwa przyciski w pasku filtrów („Szukaj” i „Pokaż wyniki”) to nie pomyłka: to dwie
różne intencje odwiedzającego. Formularz jest jeden, więc każdy z przycisków wysyła
komplet stanu — fraza i filtry zawsze zawężają razem.

## 9. Czego katalog świadomie nie ma

- **filtrów wielokrotnych** (kilka kategorii naraz) — zlecenie ich nie wymaga,
  a lista wyboru wielokrotnego jest dla nietechnicznego odwiedzającego trudniejsza
  niż dwie zwykłe listy. Model danych jest gotowy: `tax_query` przyjmie listę slugów
  bez zmiany struktury;
- **sortowania po cenie** — część oferty ma wycenę indywidualną, więc sortowanie
  ustawiałoby te szkolenia w kolejności, która niczego nie znaczy;
- **podpowiedzi w trakcie pisania** — wymagałaby JavaScriptu jako jedynej drogi do
  wyniku, czego §7 i ISK-21 zabraniają w praktyce.

## 10. Jak to sprawdzić

| Co | Czym |
|---|---|
| Sanityzacja parametrów adresu, kształt zapytania, wyróżnianie bez wycinania reszty | `php tests/run.php` |
| Ścieżka odwiedzającego: fraza po opisie, filtry, brak wyników, czyszczenie, druga strona, brak JS, wycofanie szkolenia, pusty katalog, `noindex` | `tests/e2e/katalog.spec.js` |
| Wygląd 360 / 768 / 1440 | `qa-artifacts/isk-17-katalog/` |

Dane demonstracyjne katalogu zakłada `tests/e2e/dane-katalogu.php` (idempotentny,
uruchamiany przez WP-CLI z testu). Ścieżkę właściciela — dodanie szkolenia przez
panel — sprawdza `tests/e2e/odbior-m1.spec.js` i to ona pozostaje dowodem, że katalog
da się wypełnić bez programisty.
