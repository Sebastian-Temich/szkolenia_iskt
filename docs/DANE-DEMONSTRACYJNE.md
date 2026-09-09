# Dane demonstracyjne — oznaczenie i usunięcie

Zadanie 12 planu ISK-17 (M3). Data: 2026-09-09 · Architekt Rozwiązania.

Dokumenty powiązane: `PLAN-I-ESTYMACJA.md` §4 (M3), `ADR-001-architektura.md`,
`MATERIALY-I-DECYZJE.md`.

---

## 1. Problem

§9 zlecenia mówi wprost: materiały z prototypu **nie są zatwierdzoną ofertą ISKT**,
a treści niepotwierdzone mają zostać oznaczone jako demonstracyjne albo ukryte przed
publikacją. §8 wymaga, żeby właściciel mógł dane demonstracyjne usunąć.

Do tego zadania oba wymagania były spełnione tylko w połowie. Prefiks „Demo —”
w tytule mówił *człowiekowi*, że wpis jest pokazowy. Nie mówił tego *programowi* —
a to program ma je usunąć jednym działaniem. Skutki widać było w środowisku roboczym:
95 wpisów i 2 kategorie, część z przerwanych przebiegów testów, bez żadnego spisu.
Właściciel po migracji na SEOHost musiałby je rozpoznawać po tytule, wpis po wpisie,
zgadując, który jest prawdziwy.

## 2. Wybrany mechanizm: pole `_iskt_demo`

Każdy wpis i każde hasło taksonomii założone na pokaz nosi pole o kluczu
`_iskt_demo` i wartości `1` — jako `post_meta` albo `term_meta`.

```php
iskt_oznacz_demo( $post_id );        // wpis
iskt_oznacz_demo_termin( $term_id ); // kategoria, forma realizacji, tag
```

Prefiks „Demo —” w tytule zostaje, bo §9 wymaga oznaczenia widocznego dla człowieka.
Ale **decyduje pole**: zmiana tytułu nie wyprowadza wpisu ze spisu, a wpis właściciela
nazwany przypadkiem „Demo — coś tam” nie zostanie skasowany.

Kod: `wp-content/plugins/iskt-szkolenia-core/includes/demo.php`.

### 2.1 Dlaczego pole, a nie taksonomia

Taksonomia „rodzaj treści: demonstracyjna” dałaby za darmo kolumnę i filtr w panelu.
Odrzucona z dwóch powodów:

- **danymi demonstracyjnymi bywa sama kategoria.** Zasiew aktualności zakłada
  kategorie „Demo — Wydarzenia ISKT” i „Demo — Dofinansowania”. Taksonomii nie da się
  założyć na haśle innej taksonomii, więc te dwie pozycje wypadłyby ze spisu
  i zostałyby po sprzątaniu jako puste kategorie w menu;
- **taksonomia to widoczna kontrolka w edytorze.** Redaktor, który przez pomyłkę
  zaznaczy „demonstracyjne” na prawdziwej ofercie, przygotowuje sobie jej skasowanie.
  Pole z podkreśleniem na początku nazwy jest chronione — nie pojawia się
  w skrzynce „Pola własne” i nie da się go ustawić przypadkiem.

### 2.2 Dlaczego pole, a nie opcja ze spisem identyfikatorów

Opcja `iskt_demo_ids = [12, 34, 56]` jest kusząco prosta i przegrywa na trzech polach:

- **nie przeżywa eksportu i importu.** Zadanie 13 buduje paczkę przekazania,
  a test odtworzenia ma ją wczytać w czystym WordPressie. Import WXR nadaje wpisom
  **nowe identyfikatory**; spis wskazywałby wtedy albo w pustkę, albo — gorzej —
  na wpisy właściciela, które akurat dostały te numery. `postmeta` jest częścią
  eksportu i wędruje razem z wpisem;
- **rozjeżdża się przy usunięciu wpisu inną drogą.** Wpis skasowany ręcznie zostawia
  w opcji martwy numer i spis przestaje odpowiadać stanowi bazy;
- **jest jednym rekordem dla całej instalacji.** Dwa równoległe zasiewy potrafią
  nadpisać sobie listę; przy polu na wpisie taka kolizja nie ma jak powstać.

### 2.3 Dlaczego wartość jest ciągiem `'1'`, a nie wartością logiczną

`update_post_meta( $id, $klucz, false )` zapisuje w bazie **pusty ciąg**. Zapytanie
`meta_value = '1'` przestałoby wtedy odróżniać „to nie są dane demonstracyjne”
od „oznaczenie było i je wyłączono”. Pole sterujące kasowaniem treści musi mieć
dokładnie dwa stany, więc sanityzacja sprowadza do nich każdą wartość — w tym `'0'`,
które **nie jest** oznaczeniem (`iskt_sanitize_demo()`).

## 3. Co dostaje właściciel

### 3.1 Rozpoznanie bez otwierania wpisu

Listy w panelu pokazują przy tytule plakietkę **„Dane demonstracyjne”** — tym samym
natywnym mechanizmem (`display_post_states`), którym WordPress pokazuje „Szkic”
czy „Strona główna”. Nie ma nowego oznaczenia do nauczenia się, a plakietka pojawia
się na każdej liście, także w wynikach wyszukiwania panelu.

Listy haseł taksonomii nie mają odpowiednika tego mechanizmu, więc dostają kolumnę
**„Demo”**.

### 3.2 Usunięcie jednym działaniem

**Szkolenia → Dane demonstracyjne.** Ekran wymienia imiennie każdą pozycję, która
zniknie, w rozbiciu na typy treści i taksonomie. Jedno zgłoszenie formularza —
potwierdzenie i przycisk — kasuje wszystko naraz.

Spis jest imienny, nie zbiorczy. „13 szkoleń” nie pozwala właścicielowi sprawdzić,
czy przypadkiem nie dopisał czegoś swojego do wpisu demonstracyjnego; lista tytułów
z odnośnikami do edycji pozwala.

Trzy decyzje warte odnotowania przy odbiorze:

- **kasujemy z pominięciem kosza.** Kosz zostawiłby te same wpisy w bazie,
  a paczka przekazania z zadania 13 powstaje z eksportu bazy — „usunięte” pojechałoby
  do właściciela razem z resztą. Ekran mówi to wprost przed kliknięciem;
- **każdy wpis przechodzi osobne sprawdzenie `delete_post`.** Uprawnienie do ekranu
  nie znaczy uprawnienia do każdego wpisu; pozycje bez uprawnienia są pomijane
  i policzone w podsumowaniu, a nie kasowane po cichu;
- **oznaczenie jest sprawdzane ponownie tuż przed skasowaniem.** Między zbudowaniem
  spisu a wykonaniem operacji ktoś mógł je zdjąć. Koszt sprawdzenia jest żaden,
  a ceną pomyłki byłby skasowany wpis właściciela.

Dostęp: uprawnienie `delete_iskt_szkolenia` (administrator i redaktor dostają je przy
aktywacji wtyczki). Bierzemy uprawnienie **kasowania**, a nie edycji — próg ma
odpowiadać skutkowi.

### 3.3 Czego sprzątanie NIE rusza

- wpisów bez oznaczenia — czyli wszystkiego, co założył właściciel;
- **rejestru tekstów globalnych** (`ISKT_OPCJA_TEKSTY`). Sprzątanie nie dotyka opcji
  w ogóle;
- znacznika `[do potwierdzenia: …]` w tekstach. To **osobny mechanizm** z §9
  i zadania 10: dotyczy napisów, nie wpisów, i nie ma nic wspólnego z kasowaniem
  treści. Celowo nie łączymy tych dwóch dróg;
- kategorii startowych z §4.2 (`ESG i zrównoważony rozwój` i pozostałe) — to słownik
  właściciela, nie dane pokazowe, więc zasiewy ich nie oznaczają.

**Skutek uboczny, o którym ekran uprzedza:** termin właściciela przypisany do
szkolenia demonstracyjnego po sprzątaniu traci powiązanie. Lista terminów w panelu
oznacza go wtedy na czerwono („Brak powiązania — termin się nie wyświetli”), więc
stan jest widoczny, a nie cichy.

## 4. Skąd biorą się oznaczenia

Zasiewy oznaczają swoje dane same, w chwili zapisu:

| Źródło | Co zakłada | Oznaczenie |
|---|---|---|
| `tests/e2e/dane-katalogu.php` | 12 szkoleń katalogu | `iskt_oznacz_demo()` przy każdym zapisie |
| `tests/e2e/dane-aktualnosci.php` | 2 wpisy + 2 kategorie | wpisy i **kategorie**, także przy powtórnym uruchomieniu |
| `tests/e2e/dane-formularza.php` | szkolenie + termin | w `iskt_test_zapewnij_wpis()`, czyli w jedynym miejscu zapisu |
| `odbior-m1.spec.js`, `trenerzy.spec.js` | trenerzy i szkolenia **klikane w panelu** | `oznacz-demo.php` w `afterAll` |

Każdy skrypt zasiewu przerywa z błędem, jeśli wtyczka jest nieaktywna — dane bez
oznaczenia nie mają jak wpaść do bazy „przy okazji”.

Testy przechodzące ścieżkę **właściciela** (`odbior-m1`, `trenerzy`) zakładają wpisy
klikaniem w panelu, i tak ma zostać: tylko to dowodzi, że właściciel poradzi sobie
sam. Skutkiem ubocznym jest to, że taki wpis powstaje dokładnie tak jak prawdziwy
i żaden skrypt zasiewu go nie widzi. Domyka to `tests/e2e/oznacz-demo.php`,
uruchamiany po przebiegu:

```sh
npx @wordpress/env run cli wp eval-file wp-content/iskt-tests/e2e/oznacz-demo.php
```

Ten skrypt jest **jedynym** miejscem w całym rozwiązaniu, w którym o przynależności
do danych demonstracyjnych rozstrzyga tytuł. Wyszukiwanie i usuwanie idą wyłącznie
po polu. Ten sam skrypt posłużył do doznaczenia danych, które powstały w środowisku
roboczym, zanim pole w ogóle istniało — 95 wpisów i 2 kategorie.

## 5. Zakres spisu

| Przeszukiwane | Wartości |
|---|---|
| Typy treści | `iskt_szkolenie`, `iskt_trener`, `iskt_termin`, `iskt_zgloszenie`, `post`, `page`, `attachment` |
| Taksonomie | `iskt_kategoria`, `iskt_forma`, `category`, `post_tag` |
| Statusy | `publish`, `future`, `draft`, `pending`, `private`, **`trash`**, `inherit` |

`attachment` jest na liście, bo do wpisu demonstracyjnego bywa podpięte zdjęcie,
a plik bez właściciela zostawałby na dysku po sprzątaniu.

`trash` jest na liście, bo `'any'` w WordPressie **pomija kosz**, a wpis w koszu dalej
leży w bazie i pojechałby w eksporcie z zadania 13.

Obie listy da się rozszerzyć filtrami `iskt_typy_demo` i `iskt_taksonomie_demo`.

## 6. Dowód

**`php tests/run.php` — 261 asercji, w tym 30 nowych.** Nowa grupa sprawdza to, na czym
opiera się obietnica z §8:

- sanityzacja oznaczenia ma dokładnie dwa stany; `'0'`, `'nie'` i `'demo'` **nie są**
  oznaczeniem — inaczej wpis z polem ustawionym na zero zostałby skasowany;
- `iskt_wpisy_do_usuniecia()` z listy czterech wpisów zwraca wyłącznie dwa oznaczone;
- hasła taksonomii mają własną ścieżkę rozpoznania (kategorie aktualności);
- kosz jest objęty spisem;
- spis obejmuje wszystkie typy treści katalogu — asercja pilnuje, żeby dołożenie
  typu bez dopisania go do listy nie dało danych niewidocznych dla ekranu;
- oznaczenie nie koliduje z żadnym polem katalogu ani z rejestrem tekstów.

**Przejście w przeglądarce — `tests/e2e/dane-demonstracyjne.spec.js`, 6/6.**
Ścieżka jest dokładnie ta, którą przejdzie właściciel po migracji:

1. właściciel zakłada **własne** szkolenie w panelu;
2. lista szkoleń pokazuje plakietkę przy wpisach demonstracyjnych i **nie pokazuje**
   jej przy jego wpisie;
3. ekran spisu wymienia dane demonstracyjne i **nie zawiera** wpisu właściciela;
4. jedno zgłoszenie formularza kasuje 18 pozycji; powtórne wejście na ekran mówi,
   że nie ma już czego usuwać;
5. po sprzątaniu stoją nietknięte: szkolenie właściciela, kategorie startowe
   i ekran tekstów serwisu; kategoria „Demo — Wydarzenia ISKT” zniknęła;
6. strona główna, katalog i lista trenerów zwracają 200 bez błędu krytycznego,
   strona główna dalej jest stroną główną (przełącznik odbiorcy), a katalog pokazuje
   szkolenie właściciela zamiast pustki.

Test jest destrukcyjny z założenia, więc **sam zasiewa dane przed przebiegiem
i odtwarza je po nim** — środowisko robocze dzielą inne zadania ISK-17 i nie może
zostać po nas puste.

Zrzuty: `qa-artifacts/isk-17-m3-demo/`.

## 7. Co to odblokowuje

Zadanie 13 (eksport i test odtworzenia) buduje paczkę przekazania na czystych danych:
jedno wejście na ekran, jedno kliknięcie, eksport. Bez tego zadania paczka albo
zawierałaby dane pokazowe, albo powstawałaby po ręcznym przeglądaniu bazy wpis
po wpisie.
