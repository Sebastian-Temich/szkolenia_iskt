# Odstępstwa od wzorca wyglądu — rejestr

Data założenia: 2026-09-09 · ISK-17, prowadzony od zadania 3 (M1)
Podstawa: §11 pkt 1 zlecenia — zgodność z załącznikiem albo **opisane odstępstwo**.

Wzorzec: `design/source/Szkolenia ISKT.html` (prototyp React) oraz
`design/DESIGN-SYSTEM-readme.md`. Zgodnie z §3 zlecenia prototypu nie uruchamiamy;
pracujemy na inwentaryzacji `docs/PROTOTYP-INWENTARYZACJA.md`.

Rejestr jest **uzupełniany w każdym kolejnym zadaniu**. Stan po zadaniu 4
(strona główna). Sekcje 1–5 pochodzą z zadania 3, sekcja 6 dokłada zadanie 4.

---

## 1. Odstępstwa wynikające z decyzji ISKT z 2026-09-09

Te punkty nie są wyborem wykonawcy — wynikają wprost z zatwierdzonego zakresu
(`ADR-002-rewizja-zakresu-bez-platnych-zaleznosci.md`).

| # | Wzorzec | Co robimy | Dlaczego |
|---|---|---|---|
| 1.1 | Ikony **Lucide ładowane z CDN unpkg**, podmieniane skryptem po wczytaniu strony | Wybrane ikony **wbudowane w dokument** jako SVG (`inc/ikony.php`), zestaw 14 kształtów | Zakaz zewnętrznych usług i zależności. Dodatkowo: zero żądań sieciowych i brak migotania ikon przy wczytywaniu. Kształty odwzorowują Lucide (licencja ISC — wolno osadzać) |
| 1.2 | Fonty **Inter i JetBrains Mono z CDN Google Fonts** | Fonty samohostowane; **do czasu dostarczenia plików obowiązuje stos systemowy** | CDN Google przekazuje adres IP odwiedzającego poza EOG — zbędne ryzyko RODO przy polskim odbiorcy. Skutek: krój pisma różni się dziś od wzorca, proporcje i rytm nie. Patrz `assets/fonts/README.md` |
| 1.3 | Prototyp jako aplikacja React (React 18 z unpkg, własny język szablonów) | Zwykły HTML generowany przez PHP, zwykły CSS, zwykły JavaScript | Brak kroku budowania (`npm`, `node_modules`) był warunkiem decyzji. Zachowujemy wygląd, nie technologię |
| 1.4 | — | Brak danych strukturalnych, metadanych udostępniania i analityki | Wyłączone decyzją ISKT pkt 4. Szerzej: ADR-002 §5 |

## 2. Odstępstwa wynikające z wymagań zlecenia

| # | Wzorzec | Co robimy | Dlaczego |
|---|---|---|---|
| 2.1 | Widok trzymany w stanie komponentu (`state.screen`), **bez adresów URL**; odświeżenie wraca na stronę główną, „Wstecz” nie działa | Prawdziwe adresy WordPressa dla każdego widoku | §11 pkt 6 wprost tego wymaga. Naprawiamy zachowanie prototypu, nie odtwarzamy go |
| 2.2 | Wszystkie teksty, pozycje menu i stopka **wpisane na stałe w kod** | Menu `primary` i `footer` z panelu, treść stopki na widżetach, logo z Personalizacji | §4.7: właściciel edytuje wszystko sam. Motyw nie wypisuje ani jednego odnośnika z palca — stąd zero linków „#” (§11 pkt 10) |
| 2.3 | Przycisk „Skontaktuj się” wpisany w nagłówek prototypu | Przyciskiem staje się **pozycja menu z klasą CSS `iskt-cta`** | Wygląd zachowany, adres pozostaje edytowalny. Wpisanie przycisku na stałe dałoby albo martwy link, albo adres nie do zmiany z panelu |
| 2.4 | Na liście kart klikalny jest cały kafelek, a w środku dodatkowy odnośnik „Zobacz” | **Jeden** odnośnik (tytuł), którego obszar kliknięcia rozciąga się na kartę; „Czytaj dalej” jest tekstem | Wygląd i zachowanie myszy bez zmian. Dwa odnośniki w to samo miejsce to dwa przystanki tabulatora i dwa ogłoszenia czytnika ekranu — §7 wymaga obsługi klawiaturą |

## 3. Odstępstwa techniczne — decyzja wykonawcy

| # | Wzorzec | Co robimy | Dlaczego |
|---|---|---|---|
| 3.1 | Nawigacja **przezroczysta nad sekcją powitalną**, dopiero po przewinięciu biała z rozmyciem | Nagłówek **od razu** półprzezroczysty biały z rozmyciem; po przewinięciu dokłada cień (`.is-scrolled`) | Przezroczysty nagłówek działa tylko nad ciemną sekcją powitalną. Serwis ma też katalog, stronę szkolenia, wyniki wyszukiwania i 404 — na jasnym tle biały tekst nagłówka byłby nieczytelny. Jeden przewidywalny nagłówek zamiast wyjątku na jednym widoku |
| 3.2 | Prototyp nie miał wyszukiwarki w nagłówku | Wyszukiwarka w stopce, na 404 i na widoku wyników; katalog dostanie własną (zadanie 6) | Nie dokładamy kontrolki, której wzorzec nie przewiduje. Wyszukiwanie po katalogu jest wymogiem §4.2 i zostanie rozwiązane w katalogu, nie w nagłówku |
| 3.3 | Podmenu w prototypie nie występuje | Podmenu do 2 poziomów: na pulpicie rozwijane najechaniem i fokusem, na wąskim ekranie przyciskiem dokładanym przez skrypt | Menu pochodzi z panelu, więc właściciel może utworzyć pozycje zagnieżdżone. Musimy je obsłużyć, zanim ktoś je utworzy |
| 3.4 | Logo `iskt-logo-transparent.png`, 640×529 px, 308 KB | Kopia 193×160 px, 46 KB w `assets/img/logo-iskt.png`; oryginały zostają w `design/assets/` jako źródło | 308 KB na znak w nagłówku to zbędny transfer na każdej stronie. Dalsza optymalizacja (WebP, kompresja stratna PNG) — zadanie 11, brak narzędzi w tym środowisku |
| 3.5 | Miękkie, rozmyte poświaty zieleni za sekcją powitalną i ciemnymi pasmami | **Nadal nie zrobione** — patrz §7 | Wymagają obejrzenia w przeglądarce, żeby dobrać siłę rozmycia. Bez uruchomionego `wp-env` byłoby to zgadywanie |
| 3.6 | — | Paleta i skala pisma dla edytora — **zrobione w zadaniu 4** (`theme.json`, wskazuje na te same tokeny). Własne kolory, stopnie pisma i odstępy celowo wyłączone | Dowolny kolor w edytorze to najkrótsza droga do strony, która przestaje wyglądać jak marka, a §11 pkt 1 każe utrzymać zgodność z załącznikiem |

## 4. Czego w tym zadaniu świadomie nie ruszaliśmy

- **Sekcje strony głównej** (powitanie, kompetencje, wskaźniki, ciemne pasmo,
  kontakt) — zadanie 4. Zadanie 3 dostarcza pod nie siatkę, sekcje i komponenty.
- **Widok szkolenia i trenera** — zadania 5 i 7. Do tego czasu `index.php`
  obsługuje pojedyncze treści w poprawnej, czytelnej formie.
- **Terminy i aktualności** — brak wzorca w załączniku, projekt idzie do akceptacji
  ISKT przed zadaniem 8 (bramka 2 w `PLAN-I-ESTYMACJA.md`).
- **Treści demonstracyjne z prototypu** (telefon, wskaźniki, ceny, nazwiska
  trenerów) — nie wprowadzamy ich do motywu. Wymagają potwierdzenia (§9),
  zestawienie w `MATERIALY-I-DECYZJE.md`.

## 5. Czego nie zweryfikowano

**Wyglądu nie obejrzeliśmy w przeglądarce.** W środowisku wykonawczym nie działa
demon Dockera, więc `wp-env` nie zostało uruchomione — to ta sama przeszkoda,
która zostawiła zadanie 1 jako częściowe.

Co zostało sprawdzone:

- `php -l` na wszystkich plikach PHP motywu i wtyczki — bez błędów;
- `node --check` na `assets/js/nawigacja.js` — bez błędów;
- zestawienie zmiennych CSS: **każda** zmienna użyta w arkuszach motywu jest
  zdefiniowana w tokenach (poza czterema parametrami układu z wartościami
  domyślnymi: `--iskt-grid-gap`, `--iskt-grid-min`, `--iskt-stack-space`,
  `--iskt-cluster-gap`);
- zestawienie klas: każda klasa użyta w szablonach ma regułę w arkuszach
  (poza identyfikatorami i atrybutami danych, które stylów nie potrzebują);
- `php tests/run.php` — 40 asercji wtyczki nadal przechodzi, motyw ich nie dotyka
  (po zadaniu 4 zestaw liczy 79 asercji — patrz `STRONA-GLOWNA.md` §9).

Czego **nie** sprawdzono i co trzeba obejrzeć po uruchomieniu `wp-env`:

1. rzeczywisty wygląd przy 360, 768 i 1440 px;
2. zachowanie menu mobilnego i podmenu na urządzeniu dotykowym;
3. kontrast w realnym renderowaniu (wartości dobrane z palety, ale niemierzone);
4. `backdrop-filter` w przeglądarkach bez wsparcia — nagłówek pozostaje wtedy
   półprzezroczysty bez rozmycia, co jest akceptowalne, ale niesprawdzone;
5. wygląd stopki bez widżetów i bez ustawionego menu.

Uruchomienie `wp-env` i zrzuty ekranu z trzech szerokości domykają zadanie 1
i są warunkiem odbioru M1 (bramka 3).

---

# Zadanie 4 — strona główna

## 6. Odstępstwa sekcji strony głównej

Szczegóły rozwiązań: `STRONA-GLOWNA.md`.

| # | Wzorzec | Co robimy | Dlaczego |
|---|---|---|---|
| 6.1 | Cena „z dofinansowaniem” **wyliczana skryptem** jako 20% ceny pełnej (`price × 0.2`), obok przekreślona cena bez wsparcia | Pokazujemy **wyłącznie cenę wpisaną przez właściciela**, z jednostką i sposobem prezentacji podatku. Zamiast wyliczonej kwoty — opisowa plakietka „Możliwe dofinansowanie” | §4.3 zabrania tego wprost: nie wolno wyliczać ceny po dofinansowaniu z założenia, że każdy uczestnik dostanie określony poziom wsparcia. Prototyp robi dokładnie to, czego zlecenie zakazuje |
| 6.2 | Przełącznik odbiorcy trzymany w stanie komponentu React; odświeżenie gubi wybór, „Wstecz” nie działa, adresu wariantu nie da się wysłać | Wariant w adresie (`?odbiorca=firmowy`), formularz `GET`, skrypt tylko przyspiesza przełączanie | §11 pkt 6 wymaga działających adresów i przycisku „Wstecz”. Naprawiamy zachowanie prototypu, nie odtwarzamy go |
| 6.3 | Dwa warianty sekcji powitalnej (`heroVariant` A albo B) przełączane parametrem narzędzia projektowego | Zbudowany **wariant A** (wyśrodkowany, dwie karty ścieżek pod spodem) | Wariant B to alternatywa projektowa, nie druga sekcja serwisu. Utrzymywanie dwóch układów tej samej sekcji podwaja pracę i mnoży miejsca do zepsucia. Wariant B pozostaje możliwy do dołożenia jako drugi wzorzec, jeśli ISKT go wybierze |
| 6.4 | Wskaźniki „15+ lat”, „200+ szkoleń”, „4.9/5”, „do 80% dofinansowania” | Układ odtworzony, **liczby zastąpione znacznikiem `[uzupełnij]`**; wzorzec ma w tytule „(treść do potwierdzenia)” | §9 wymienia wszystkie cztery jako treść do potwierdzenia. Opublikowana średnia ocen bez źródła to problem prawny, nie ozdoba. Wykonawca nie ma podstaw, żeby te liczby twierdzić |
| 6.5 | Telefon `+48 32 000 00 00`, adres „Żory, woj. śląskie”, deklaracja odpowiedzi w 24 godziny, „dofinansowanie do 80%” | Zastąpione widocznym `[do potwierdzenia: …]` | §9. Tekst, którego nikt nie zatwierdził, nie może wyglądać jak gotowy do publikacji. Adres `szkolenia@iskt.pl` zostaje — pochodzi wprost ze zlecenia (§5) |
| 6.6 | Każde szkolenie ma własną ikonę wpisaną w dane (`icon: 'cpu'`) | Symbol przypisany **kategorii**, nie szkoleniu; wybierany z listy przy edycji kategorii | Kategorie są edytowalne (§4.2). Ikona per szkolenie oznaczałaby dziewięć pól do uzupełnienia zamiast pięciu i nie dawała nic więcej: w załączniku wszystkie szkolenia z jednej kategorii mają ten sam symbol |
| 6.7 | Karty w prototypie są przyciskami (`<button onClick>`) | Karty są artykułami z odnośnikiem na tytule, rozciągniętym na całą kartę | Zgodnie z ustaleniem 2.4 z zadania 3. Przycisk, który przenosi na inny adres, to odnośnik — i tak musi być ogłoszony czytnikowi ekranu |
| 6.8 | Sekcja kontaktu zawiera działający wizualnie formularz (który niczego nie wysyła) | Sekcja z danymi kontaktowymi i **działającym odnośnikiem `mailto:`** | Formularz z walidacją serwerową to zadanie 9. Do tego czasu sekcja prowadzi do czegoś, co działa — §11 pkt 10 nie dopuszcza roboczych odnośników „#” |

## 7. Czego w zadaniu 4 świadomie nie ruszaliśmy

- **Poświaty w tle sekcji powitalnej** (pkt 3.5 wyżej) — nadal nie zrobione.
  Wymagają obejrzenia w przeglądarce, żeby dobrać ich siłę; bez uruchomionego
  `wp-env` byłoby to zgadywanie.
- **Formularz zgłoszeniowy** — zadanie 9.
- **Katalog z wyszukiwaniem i filtrami** — zadanie 6. Karta szkolenia
  (`iskt_karta_szkolenia()`) jest już wspólna dla obu widoków.
- **Ekran tekstów globalnych** — zadanie 10. Wariant domyślny odbiorcy czeka
  w opcji `iskt_odbiorca_domyslny`, gotowy do podpięcia pod Settings API.
