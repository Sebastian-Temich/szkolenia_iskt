# ADR-002 — Rewizja architektury: wyłącznie natywny WordPress, bez płatnych zależności

Status: **przyjęta** — zastępuje wskazane punkty `ADR-001-architektura.md`
Data: 2026-09-09
Autor: Koordynator Techniczny / Intake Lead
Podstawa: decyzja ISKT z 2026-09-09 (odpowiedź na prośbę o zatwierdzenie planu, bramka §12)

---

## 1. Co zdecydowało ISKT

Zgoda na start realizacji została udzielona, ale w zmienionym zakresie. Wiążące punkty:

1. Serwis w 100% oparty na WordPressie. **Bez płatnych wtyczek, licencji, subskrypcji
   i zewnętrznych płatnych usług.** ACF Pro odpada. Preferowane mechanizmy natywne;
   darmowe dodatki dopuszczalne, o ile nie wymagają płatnego planu do uzgodnionych funkcji.
2. Edytor blokowy, własne typy treści, taksonomie i pola WordPressa. Dedykowany motyw
   i własna wtyczka ISKT pozostają zgodne z wymaganiem. Bez osobnej aplikacji i bez
   zewnętrznego panelu.
3. Zakres funkcjonalny bez zmian: strona główna, katalog z wyszukiwaniem i filtrami,
   szkolenia i terminy, trenerzy, aktualności, formularz na szkolenia@iskt.pl. Wszystkie
   teksty, zdjęcia, przyciski, menu i stopka edytowalne z panelu.
4. **Bez wtyczek SEO, analityki, reklam i rozbudowanych danych strukturalnych.** SEO
   konfiguruje ISKT później. Zostają podstawy: działające adresy, czytelne nagłówki,
   responsywność, brak indeksowania środowiska wewnętrznego.
5. Organizacja instaluje WordPressa u siebie, buduje witrynę, udostępnia podgląd i panel,
   przygotowuje pełny eksport i sprawdzoną instrukcję odtworzenia na SEOHost —
   **bez płatnego narzędzia migracyjnego**.
6. Estymacja 36 dni **nie jest zatwierdzona**. Pierwszy kamień milowy: działający
   WordPress z wyglądem z załącznika oraz możliwością samodzielnego dodania i edycji szkolenia.

Zakupy i wdrożenie na docelowy hosting pozostają niezatwierdzone.

## 2. Co się zmienia względem ADR-001

| Obszar | ADR-001 | ADR-002 (obowiązuje) |
|---|---|---|
| Edytor pól | ACF Pro (rekomendacja) lub Carbon Fields | **Natywne `register_post_meta` + `add_meta_box`**, bez żadnej zależności |
| Sekcje strony głównej | ACF `Flexible Content` | **Edytor blokowy**: wzorce bloków + własne bloki dynamiczne |
| SEO | Własne pola, canonical, OG, JSON-LD `Course`/`Event`/`Person`, mapa witryny | **Odłożone** (pkt 4 decyzji). Zostają: poprawne adresy, semantyczne nagłówki, `noindex` środowiska wewnętrznego |
| Dane strukturalne | JSON-LD generowany przez wtyczkę | **Poza bieżącym zakresem** |
| Analityka | do decyzji właściciela | **Poza bieżącym zakresem** |
| Turnstile (antyspam) | opcjonalny, do decyzji | **Odrzucony** — usługa zewnętrzna. Antyspam wyłącznie własny |
| Duplicator jako ścieżka pomocnicza | dopuszczony | **Usunięty** — pkt 5 wprost wyklucza płatne narzędzie migracyjne; Duplicator w wersji darmowej ma limity i ścieżkę płatną. Zostaje wyłącznie `wp db export` |
| Harmonogram | 36 dni zatwierdzone do akceptacji | **Odrzucony**; nowa estymacja w `PLAN-I-ESTYMACJA.md` z kamieniem milowym M1 |

Bez zmian pozostają: podział motyw/wtyczka, model danych (§2 ADR-001), terminy jako osobny
typ treści, relacja szkolenie–trener zapisana wyłącznie na szkoleniu, aktualności na natywnym
wpisie, samohostowane fonty, `wp-env` jako środowisko wewnętrzne, obsługa formularza przez
`admin-post.php`.

## 3. Jak realizujemy pola bez ACF

### 3.1. Pola strukturalne — `register_post_meta` + `add_meta_box`

Każde pole jest rejestrowane przez `register_post_meta()` z `sanitize_callback`
i `auth_callback`. Rejestracja to warstwa bezpieczeństwa: sanityzacja i kontrola uprawnień
obowiązują niezależnie od tego, którą drogą dane trafiają do bazy.

Panel edycji dostarcza `add_meta_box()` z flagą `__block_editor_compatible_meta_box`.
Skrzynki metadanych renderują się **wewnątrz edytora blokowego** — to natywny mechanizm
WordPressa, nie obejście. Zapis idzie przez `save_post`, więc działa też przy zapisie
z edytora blokowego.

Powód wyboru zamiast Carbon Fields: Carbon Fields jest darmowy, ale to zależność instalowana
Composerem, która musi trafić do paczki przekazania i podlegać aktualizacjom u właściciela.
Pkt 1 i 2 decyzji wskazują mechanizmy natywne. Koszt: nieco więcej kodu po naszej stronie,
zero zależności po stronie właściciela.

### 3.2. Listy powtarzalne bez zależności

- **Listy proste** (korzyści, specjalizacje, wykształcenie, certyfikaty) — jedno pole
  tekstowe wielowierszowe, jedna pozycja w wierszu. Nietechniczny redaktor rozumie to
  natychmiast, a my nie budujemy interfejsu powtarzalnego tam, gdzie nie jest potrzebny.
- **Program szkolenia** (moduł = tytuł + opis) — powtarzalny interfejs w zwykłym
  JavaScripcie, klonujący wiersz wzorcowy. Bez `npm`, bez kroku budowania.

### 3.3. Sekcje strony głównej — edytor blokowy

Dwie kategorie sekcji, obie w natywnym edytorze:

- **Sekcje redakcyjne** (hero, przebieg współpracy, dofinansowania, kontakt) — **wzorce
  bloków** (`register_block_pattern`) zbudowane z bloków rdzenia. Właściciel wstawia
  gotową sekcję, edytuje każdy tekst na miejscu, przestawia przeciągnięciem, usuwa jak
  każdy blok. Zero kodu do utrzymania, pełna edytowalność.
- **Sekcje z danymi** (wyróżnione szkolenia, lista trenerów, katalog, formularz,
  przełącznik „Dla Ciebie”/„Dla firm”) — **własne bloki dynamiczne** rejestrowane
  w PHP (`register_block_type` + `render_callback`), z plikiem `block.json`.

Skrypt edytora dla bloków dynamicznych piszemy w zwykłym JavaScripcie
(`wp.element.createElement`), **bez JSX i bez kroku budowania**. Uzasadnienie: `@wordpress/scripts`
jest darmowy, ale wprowadza `node_modules` i build do repozytorium oraz do procesu przekazania.
Bloki dynamiczne mają proste panele ustawień — JSX nie daje tu przewagi wartej tego kosztu.

Sekcja bez treści nie renderuje się — `render_callback` zwraca pusty ciąg, gdy nie ma czego
pokazać (§4.1 „ukrywanie sekcji bez treści”).

### 3.4. Teksty globalne — ekran ustawień wtyczki

Bez zmian względem ADR-001 §3.2: dane kontaktowe, teksty stopki, etykiety i komunikaty
formularza, komunikaty pustych wyników, oba warianty odbiorcy — jeden ekran ustawień
oparty na Settings API. Menu i stopka na natywnych menu WordPressa.

## 4. Skutki dla wymagań zlecenia

| Wymóg | Status po rewizji |
|---|---|
| §4.3 „dane SEO” jako pole szkolenia | **Odłożone** decyzją pkt 4. Wymaga jawnego potwierdzenia przy odbiorze — patrz `MATERIALY-I-DECYZJE.md` |
| §7 dane strukturalne, mapa witryny, metadane udostępniania | **Poza bieżącym zakresem** (pkt 4). Natywna mapa witryny WordPressa działa domyślnie |
| §7 obsługa indeksowania filtrów | Realizujemy minimalnie: `noindex` na wynikach wyszukiwania i widokach filtrowanych — to jedna reguła, nie „rozbudowane dane strukturalne” |
| §7 pomiar wydajności | Zachowany — nie wymaga wtyczek ani usług |
| §5 ochrona przed spamem | Wyłącznie własna: honeypot, pułapka czasowa, `nonce`, limit na IP. Bez usługi zewnętrznej |
| §8 eksport | Wyłącznie `wp db export` + archiwum `wp-content` + zapisane ustawienia |

Punkty odłożone nie znikają — trafiają na jawną listę „niewykonane / oczekujące”
wymaganą przez §11 pkt 14.

## 5. Ryzyko przyjęte świadomie

Rezygnacja z danych strukturalnych i metadanych udostępniania oznacza, że po publikacji
szkolenia nie pojawią się w wynikach Google jako kursy z terminami, a odnośniki udostępniane
w mediach społecznościowych nie będą miały grafiki i opisu. To bezpośredni skutek pkt 4
decyzji i świadomy wybór właściciela, nie przeoczenie wykonawcy. Model danych zostaje
przygotowany tak, aby dodanie tej warstwy później było zadaniem na 1,5 dnia, bez migracji.
