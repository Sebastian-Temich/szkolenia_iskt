# ADR-001 — Architektura serwisu szkolenia.iskt.pl

Status: **propozycja — oczekuje na decyzję ISKT** (bramka §12: start realizacji)
Data: 2026-09-09
Autor: Koordynator Techniczny / Intake Lead
Dotyczy: ISK-17 §6 „Wymagana krótka decyzja architektoniczna”

---

## 1. Podział odpowiedzialności: motyw vs wtyczka

| Warstwa | Nazwa | Odpowiada za |
|---|---|---|
| Wtyczka | `iskt-szkolenia-core` | Model danych: typy treści, taksonomie, pola, relacje, obsługa formularza, wysyłka email, dane strukturalne, ustawienia |
| Motyw | `iskt-szkolenia` | Wyłącznie prezentacja: szablony, tokeny designu, CSS, komponenty, wzorce sekcji |

Uzasadnienie: wymóg §6 „Zmiana motywu nie może usuwać danych katalogu”. Rejestracja
CPT/taksonomii/pól w motywie oznaczałaby, że przełączenie motywu ukrywa katalog. Cały
model danych trafia więc do wtyczki, która działa niezależnie od motywu.

Konsekwencja: dezaktywacja wtyczki ukrywa widoki katalogu, ale **nie usuwa danych**
(rekordy zostają w `wp_posts` / `wp_postmeta`). Odinstalowanie nie czyści danych bez
jawnego potwierdzenia w ustawieniach.

## 2. Model danych

### 2.1. Typy treści (CPT)

| CPT | Etykieta w panelu | Publiczny | URL |
|---|---|---|---|
| `iskt_szkolenie` | Szkolenia | tak | `/szkolenia/{slug}/` |
| `iskt_trener` | Trenerzy | tak | `/trenerzy/{slug}/` |
| `iskt_termin` | Terminy | nie (zarządzany z panelu) | brak własnego URL |
| `iskt_zgloszenie` | Zgłoszenia | nie (prywatny) | brak — patrz §6 |
| `post` (natywny) | Aktualności | tak | `/aktualnosci/{slug}/` |

**Aktualności na natywnym `post`** — a nie na nowym CPT. Powód: §4.6 wymaga tytułu,
treści, zdjęcia, kategorii, daty i statusu publikacji, czyli dokładnie tego, co
natywny wpis już ma. Nowy CPT dodałby pracę i utrudnił migrację bez żadnej korzyści.
Motyw dostarcza szablony listy i artykułu w stylistyce załącznika.

### 2.2. Taksonomie

| Taksonomia | Dotyczy | Rola |
|---|---|---|
| `iskt_kategoria` | `iskt_szkolenie` | Kategorie oferty — **edytowalne przez właściciela** (§4.2) |
| `iskt_forma` | `iskt_szkolenie` | Forma realizacji: online / stacjonarnie / hybrydowo |

Kategorie startowe zasiane jednorazowo przy aktywacji (idempotentnie, bez nadpisywania
późniejszych zmian właściciela): AI i narzędzia generatywne, ESG i zrównoważony rozwój,
język angielski, rozwój oprogramowania, projekty B+R.

Forma jako **taksonomia, nie pole** — ponieważ §4.2 wymaga filtrowania katalogu po
formie. Taksonomia daje wydajne filtrowanie i archiwa bez `meta_query`.

### 2.3. Terminy — osobny CPT, nie pole powtarzalne

Rozważane: pole powtarzalne (repeater) na szkoleniu. **Odrzucone.** §4.4 wymaga, aby
termin miał własny status dostępności zgłoszeń i podlegał archiwizacji, a zakończony
termin nie był prezentowany jako nadchodzący. To wymaga sortowania i filtrowania po
dacie oraz niezależnego statusu — czego repeater w `postmeta` nie zapewnia bez
kosztownych zapytań. Osobny CPT daje natywny status publikacji (`publish`/`draft`),
archiwizację oraz sortowanie po dacie.

Pola terminu: `data_start`, `data_koniec`, `tryb` (online / stacjonarnie),
`lokalizacja`, `status_zgloszen` (otwarte / lista rezerwowa / zamknięte),
`termin_indywidualny` (flaga — §4.4 „termin ustalany indywidualnie”),
`szkolenie_id` (powiązanie).

Archiwizacja terminu nie dotyka strony szkolenia — to osobne rekordy.

### 2.4. Relacja szkolenie ↔ trener — jedno źródło prawdy

Powiązanie przechowywane **wyłącznie** na szkoleniu, w polu `iskt_trenerzy`
(lista ID trenerów). Strona trenera nie ma własnej kopii — listę swoich szkoleń
pobiera zapytaniem po tym polu.

Uzasadnienie: §4.5 „Powiązania trener–szkolenie mają być zarządzane w jednym miejscu
i spójnie prezentowane na obu stronach”. Dwustronny zapis (kopia na trenerze) tworzy
ryzyko rozjechania się danych. Jedno źródło + zapytanie zwrotne gwarantuje spójność
z definicji, bez logiki synchronizacji.

Na ekranie trenera dodajemy pole tylko do odczytu pokazujące powiązane szkolenia,
z odnośnikiem „zarządzaj na stronie szkolenia”, aby redaktor nie szukał tej opcji.

## 3. Sposób edycji pól i treści

### 3.1. Rekomendacja: ACF Pro — wymaga decyzji zakupowej ISKT

§4.7 stawia bardzo szeroki wymóg: **każdy** tekst widoczny dla odwiedzającego jest
edytowalny z panelu, a właściciel ma dodawać i przestawiać sekcje strony głównej.
To wymaga pól powtarzalnych i elastycznej zawartości.

| Opcja | Koszt | Ocena |
|---|---|---|
| **A. ACF Pro** (rekomendowana) | 49 USD/rok/1 stanowisko lub licencja bezterminowa | Dojrzały, najlepszy UX dla nietechnicznego właściciela. `Flexible Content` realizuje przestawianie sekcji, `Repeater` — moduły programu, doświadczenie trenera, terminy. |
| B. Carbon Fields | 0 zł (MIT, przez Composer) | Bez kosztu licencji i bez komunikatów o odnowieniu. Słabszy UX panelu, uboższa dokumentacja. Szacunkowo **+2–3 dni** pracy na dopracowanie panelu. |
| C. Własne pola na `register_meta` + Gutenberg | 0 zł | Pełna kontrola, brak zależności — ale najdroższy w budowie i najsłabszy w utrzymaniu. Odrzucone. |

**Rekomendacja: A (ACF Pro).** Adresatem panelu jest osoba nietechniczna, a wymóg
§4.7 jest nietypowo szeroki — jakość panelu jest tu funkcją produktu, nie wygodą
programisty. Jeśli ISKT nie zaakceptuje zakupu, realizujemy B bez zmiany modelu danych
i bez zmiany zakresu; rośnie tylko estymacja.

Nie kupujemy licencji samodzielnie — §12 wymaga decyzji ISKT dla płatnych zakupów.

### 3.2. Teksty globalne — jedno źródło

Powtarzające się treści (dane kontaktowe, teksty stopki, etykiety i komunikaty
formularza, komunikaty pustych wyników, teksty dofinansowania, oba warianty odbiorcy)
trafiają do jednego ekranu ustawień wtyczki, a nie do szablonów. Realizuje to §4.7
„Powtarzające się dane mają mieć jedno źródło”.

Menu i stopka: natywne menu WordPress + obszary widgetów/wzorce, bez linków `#`.

### 3.3. Sekcje strony głównej

Strona główna zbudowana z listy sekcji (`Flexible Content`), gdzie każdy typ sekcji
odpowiada sekcji z załącznika: hero z przełącznikiem odbiorcy, obszary szkoleń,
wyróżnione szkolenia, przebieg współpracy, dofinansowania, kontakt z formularzem.
Właściciel dodaje, przestawia i usuwa sekcje; sekcja bez treści nie renderuje się.

Przełącznik „Dla Ciebie” / „Dla firm” — oba warianty tekstów i przycisków są polami
sekcji, przełączanie po stronie przeglądarki bez przeładowania, z zachowaniem
dostępności (przyciski z `aria-pressed`). Wariant domyślny konfigurowalny.

## 4. Formularz zgłoszeniowy

- Wysyłka `POST` na `admin-post.php` (nie AJAX-only) — działa bez JS, walidacja
  wyłącznie po stronie serwera jest źródłem prawdy (§5).
- Błędy: dane wpisane przez użytkownika trafiają do krótkotrwałego transientu
  powiązanego z jednorazowym tokenem, przekierowanie wraca na formularz z błędami
  przy polach. Realizuje §5 „zachowanie wpisanych danych przy błędzie”.
- Sukces **dopiero** gdy mechanizm wysyłkowy przyjmie wiadomość (`wp_mail` zwróci
  sukces). Brak przyjęcia = komunikat błędu, nie podziękowanie.
- Antyspam bez kosztu: honeypot, pułapka czasowa, nonce, limit zgłoszeń na IP.
  Opcjonalnie Cloudflare Turnstile (darmowy) — do decyzji ISKT.
- Nagłówki: `From` = autoryzowany adres w domenie iskt.pl, `Reply-To` = email
  zgłaszającego. Odbiorca: szkolenia@iskt.pl (konfigurowalny).
- Treść wiadomości zawiera kontekst: szkolenie, termin, rodzaj odbiorcy.
- Komunikat wyraźnie stwierdza, że zgłoszenie **nie jest** potwierdzeniem rezerwacji.
- Wejście ze strony szkolenia wypełnia nazwę szkolenia i wybrany termin; dozwolone
  zapytanie ogólne bez wskazania oferty.
- Brak zgody marketingowej jako warunku wysłania (§5). Odnośnik do polityki
  prywatności — treść do zatwierdzenia przez ISKT.

Przechowywanie zgłoszeń w bazie (`iskt_zgloszenie`) — **domyślnie wyłączone**,
włączane przełącznikiem. §5 pozostawia retencję do uzgodnienia, więc domyślnie nie
gromadzimy danych osobowych ponad to, co konieczne do wysłania emaila.

## 5. SEO i dane strukturalne — bez wtyczki SEO

Rezygnujemy z Yoast/Rank Math/SEOPress. Powody: żadna z nich nie generuje poprawnie
schematu `Course` powiązanego z `Event` dla terminów, a §7 wymaga danych strukturalnych
zgodnych z opublikowaną treścią. Wtyczka `iskt-szkolenia-core` dostarcza:

- edytowalne pola tytułu i opisu SEO na każdym typie treści (§7 „unikalne, edytowalne”),
- `canonical` liczone z adresu docelowego, bez odwołań do środowiska wewnętrznego,
- metadane udostępniania (Open Graph / Twitter),
- JSON-LD: `Course` dla szkolenia, `Event` dla terminów otwartych, `Person` dla trenera,
  `Organization` globalnie — generowane z rzeczywistej treści, nie z szablonu,
- rozszerzenie natywnej mapy witryny WordPress o `iskt_szkolenie` i `iskt_trener`,
- `noindex` dla wyników wyszukiwania i widoków filtrowanych (§7 „ustalona obsługa
  indeksowania filtrów”), strony szkoleń i trenerów indeksowane,
- środowisko wewnętrzne całkowicie wyłączone z indeksowania; włączenie indeksowania
  jest **punktem instrukcji publikacji**, nie czynnością wykonywaną tutaj (§7).

## 6. Zależności

| Zależność | Licencja | Koszt | Uwaga |
|---|---|---|---|
| WordPress | GPLv2+ | 0 | PHP 8.2+, MySQL 8.0+ / MariaDB 10.6+ |
| ACF Pro **albo** Carbon Fields | komercyjna / MIT | 49 USD/rok **albo** 0 | decyzja ISKT — §3.1 |
| Inter, JetBrains Mono | OFL 1.1 | 0 | **samohostowane**, nie z CDN Google — patrz niżej |
| Cloudflare Turnstile | darmowy | 0 | opcjonalny, do decyzji |

Fonty: załączony `fonts.css` ładuje Inter i JetBrains Mono z CDN Google Fonts.
W wersji produkcyjnej hostujemy pliki lokalnie — CDN Google Fonts wysyła adres IP
odwiedzającego do zewnętrznego podmiotu, co przy polskim odbiorcy jest zbędnym
ryzykiem RODO. Oba kroje mają licencję OFL, samohostowanie jest dozwolone i bez kosztu.
Design system sam oznacza Inter jako **substytut** — brak dostarczonych plików kroju
firmowego. Jeśli ISKT ma licencjonowany krój firmowy, prosimy o pliki; w przeciwnym
razie Inter zostaje jako świadomy wybór.

Brak zależności na React — prototyp jest wzorcem wyglądu, nie kodem produkcyjnym
(patrz `PROTOTYP-INWENTARYZACJA.md`). §6 zakazuje osadzania prototypu jako iframe
lub nieedytowalnego bloku HTML.

## 7. Środowisko i migracja

**Środowisko wewnętrzne:** `@wordpress/env` (Docker, darmowy) — odtwarzalna instalacja
opisana w repozytorium, identyczna u każdego wykonawcy. Podgląd i panel udostępniamy
właścicielowi do odbioru.

**Eksport — dwie ścieżki, świadomie:**

1. Podstawowa: `wp db export` + archiwum `wp-content` + zapisane ustawienia.
   Bez limitu rozmiaru, bez opłat, bez zależności od wtyczki migracyjnej.
2. Pomocnicza: Duplicator (wersja darmowa) — wygodniejsza dla osoby nietechnicznej.
   Ograniczenie: darmowa wersja nie obsługuje bardzo dużych witryn i nie ma wsparcia.

Nie rekomendujemy All-in-One WP Migration jako ścieżki podstawowej — darmowa wersja
ma limit rozmiaru importu, a jego zniesienie jest płatne. Przy ścieżce (1) problem
nie występuje.

**Test odtworzenia** (§11 pkt 12) wykonujemy w czystym WordPressie: sprawdzamy treści,
relacje szkolenie–trener, terminy, media, ustawienia i działanie formularza.

**Instrukcja przekazania** obejmuje: import, zmianę adresów (search-replace w bazie),
HTTPS, konfigurację poczty, włączenie indeksowania oraz **wycofanie wdrożenia**.
Sekrety środowiska wewnętrznego nie trafiają do paczki przekazania (§8).

Bezpośrednie wdrożenie na SEOHost jest poza zakresem (§13). Brak dostępu do SEOHost
nie blokuje budowy (§8).

## 8. Adresy URL i trwałość

Struktura `/szkolenia/{slug}/` i `/trenerzy/{slug}/` oparta na natywnym routingu
WordPress, nie na stanie po stronie przeglądarki. Skutek: widoki działają po
bezpośrednim wejściu, odświeżeniu i przycisku „Wstecz” (§6, §11 pkt 6) — czego
prototyp React, trzymający ekran w stanie komponentu, nie zapewnia.

Zmiana tytułu nie zmienia slug po publikacji — adres pozostaje trwały (§4.3).

## 9. Uprawnienia

- Logowanie tylko dla administracji i redakcji (§6); brak rejestracji publicznej,
  brak kont uczestników (§13).
- Zarządzanie katalogiem oparte na własnych uprawnieniach CPT, przypisanych do roli
  administratora i redaktora — nie na `manage_options` dla wszystkiego.
- Media wyłącznie przez bibliotekę WordPress, dla uprawnionych użytkowników;
  publiczny formularz **nie** przyjmuje załączników (§10).

## 10. Decyzje odłożone

Wymagają wejścia od ISKT — nie blokują prac technicznych po zatwierdzeniu startu:

1. ACF Pro (zakup) vs Carbon Fields (bez kosztu) — §3.1.
2. Retencja zgłoszeń w bazie i ewentualne potwierdzenie email do zgłaszającego.
3. Sposób konfiguracji poczty (SMTP autoryzowanego nadawcy).
4. Analityka — §6 pozostawia do decyzji właściciela, nie blokuje budowy.
5. Turnstile jako dodatkowa ochrona antyspamowa.

Zestawienie w `MATERIALY-I-DECYZJE.md`.
