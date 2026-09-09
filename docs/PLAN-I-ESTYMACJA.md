# Plan realizacji i estymacja — szkolenia.iskt.pl (ISK-17)

Status: **realizacja rozpoczęta** — decyzja ISKT z 2026-09-09 dała zgodę na start
w zakresie opisanym w `ADR-002-rewizja-zakresu-bez-platnych-zaleznosci.md`.
Wersja 2 planu. Zastępuje wersję 1 (estymacja 36 dni), która nie została przyjęta.

Data: 2026-09-09 · Koordynator Techniczny / Intake Lead

Dokumenty powiązane: `ADR-001-architektura.md`, `ADR-002-rewizja-zakresu-bez-platnych-zaleznosci.md`,
`PROTOTYP-INWENTARYZACJA.md`, `MATERIALY-I-DECYZJE.md`.

---

## 1. Zakres po decyzji ISKT

Wiążące ograniczenia, na których opiera się ten plan:

- **zero płatnych zależności** — bez ACF Pro, bez płatnych wtyczek, licencji, subskrypcji
  i zewnętrznych usług płatnych;
- **mechanizmy natywne** — edytor blokowy, własne typy treści, taksonomie i pola WordPressa;
- dedykowany motyw + własna wtyczka ISKT pozostają;
- **bez wtyczek SEO, analityki, reklam i rozbudowanych danych strukturalnych** —
  zostają podstawy: działające adresy, semantyczne nagłówki, responsywność,
  brak indeksowania środowiska wewnętrznego;
- eksport i instrukcja odtworzenia **bez płatnego narzędzia migracyjnego**;
- bez zakupów i bez wdrożenia na docelowy hosting.

## 2. Uczciwa uwaga o wpływie decyzji na czas

Rezygnacja z ACF Pro **nie skraca pracy — przenosi ją do nas.** ACF dostarczał gotowy
interfejs pól powtarzalnych; teraz budujemy go sami. To ok. **+2 dni**, które w dużej
części zostały już wykonane (zadanie 2 poniżej).

Skraca pracę natomiast rezygnacja z warstwy SEO i danych strukturalnych: **−1,5 dnia**,
oraz rezygnacja z Turnstile i drugiej ścieżki eksportu: **−0,5 dnia**.

Suma tych zmian daje wynik bliski poprzedniemu. Nie „dopasowujemy” estymacji w dół —
przedstawiamy ją taką, jaka wychodzi z zakresu, żeby harmonogram nie rozjechał się
w trakcie realizacji.

## 3. Kamienie milowe

Podział wynika wprost z decyzji ISKT: „Pierwszy kamień milowy: działający WordPress
z wyglądem z załącznika oraz możliwością samodzielnego dodania i edycji szkolenia”.

| Kamień | Co właściciel dostaje do rąk | Dni | Stan |
|---|---|---|---|
| **M1** | Działający WordPress z wyglądem z załącznika. Właściciel sam dodaje, edytuje i publikuje szkolenie wraz z terminami i trenerem, i widzi je na stronie | 14,0 | **odebrany 2026-09-09** |
| **M2** | Pełny serwis: katalog z wyszukiwaniem i filtrami, trenerzy, aktualności, działający formularz na szkolenia@iskt.pl, pełna edytowalność tekstów | 11,0 | w realizacji od 2026-09-09 |
| **M3** | Gotowość do odbioru: dostępność, responsywność, wydajność, eksport i sprawdzone odtworzenie, dokumentacja, przegląd bezpieczeństwa, QA wg §11 | 9,5 | przed nami |

## 4. Zadania

### M1 — działający WordPress z wyglądem i edytowalnym szkoleniem

| # | Zadanie | Kompetencja | Dni | Stan |
|---|---|---|---|---|
| 1 | Środowisko `wp-env`, szkielet motywu i wtyczki, tokeny designu, lint PHP, testy sanityzacji | WP/frontend | 1,5 | **wykonane 2026-09-09** — środowisko uruchomione, wygląd sprawdzony w przeglądarce |
| 2 | Model danych: typy treści, taksonomie, pola natywne, uprawnienia, relacje, kolumny panelu | backend WP | 3,5 | **wykonane 2026-09-09** |
| 3 | Motyw: siatka, typografia, nagłówek, stopka, menu, komponenty wspólne | WP/frontend | 2,5 | **wykonane 2026-09-09** — `MOTYW-KOMPONENTY.md`, `ODSTEPSTWA-OD-WZORCA.md`; wygląd sprawdzony w przeglądarce |
| 4 | Strona główna: wzorce bloków + bloki dynamiczne, przełącznik „Dla Ciebie”/„Dla firm”, ukrywanie pustych sekcji | WP/frontend | 4,0 | **wykonane 2026-09-09** — `STRONA-GLOWNA.md`; wygląd sprawdzony w przeglądarce |
| 5 | Strona szkolenia: szablon, program, korzyści, cena z jednostką i podatkiem, dofinansowanie, terminy, trenerzy | WP/frontend | 2,5 | **wykonane 2026-09-09** — `ODSTEPSTWA-OD-WZORCA.md` §8; wygląd niesprawdzony w przeglądarce (brak Dockera) |
| | **Razem M1** | | **14,0** | |

### M2 — pełny serwis

| # | Zadanie | Kompetencja | Dni | Stan |
|---|---|---|---|---|
| 6 | Katalog: wyszukiwanie po nazwie i opisie, filtry kategoria + forma, paginacja, stan braku wyników, czyszczenie filtrów | WP/frontend | 2,5 | w realizacji od 2026-09-09 |
| 7 | Trenerzy: lista i profil, spójna prezentacja powiązań w obu widokach | WP/frontend | 2,0 | w realizacji od 2026-09-09 |
| 8 | Aktualności: lista i artykuł — **projekt widoków do akceptacji ISKT**, brak wzorca w załączniku | WP/frontend | 1,5 | **wykonane 2026-09-09** — `PROJEKT-AKTUALNOSCI.md`; bramka 2 zamknięta, widoki sprawdzone w przeglądarce |
| 9 | Formularz: walidacja serwerowa, własny antyspam, wysyłka, kontekst szkolenia i terminu, zachowanie danych przy błędzie | backend WP | 3,0 | w realizacji od 2026-09-09 |
| 10 | Pełna edytowalność treści: ekran tekstów globalnych, menu, stopka, etykiety, komunikaty pustych wyników | backend WP | 2,0 | **wykonane 2026-09-09** — patrz §5a |
| | **Razem M2** | | **11,0** | |

### M3 — gotowość do odbioru

| # | Zadanie | Kompetencja | Dni |
|---|---|---|---|
| 11 | Dostępność i responsywność 360/768/1440, klawiatura, fokus, kontrast + pomiar wydajności | QA + frontend | 2,0 |
| 12 | Treści demonstracyjne z jawnym oznaczeniem i mechanizmem usunięcia jednym działaniem | backend WP | 1,0 |
| 13 | Eksport (`wp db export` + `wp-content` + ustawienia), test odtworzenia w czystym WordPressie, instrukcja migracji i wycofania | backend + dok. | 2,0 |
| 14 | Instrukcja administracji dla właściciela | dokumentacja | 1,5 |
| 15 | Przegląd bezpieczeństwa: uprawnienia, formularz, media, brak sekretów w paczce | security | 1,0 |
| 16 | QA końcowe: przejście 14 kryteriów akceptacji §11 | QA | 2,0 |
| | **Razem M3** | | **9,5** |

### Podsumowanie

| Pozycja | Dni |
|---|---|
| M1 + M2 + M3 | 34,5 |
| Koordynacja i przeglądy | 3,0 |
| **Razem** | **37,5** |
| Wykonane 2026-09-09 (zadania 1, 2, 3, 4, 5 — cały M1) | −14,0 |
| **Pozostało** | **23,5** |

### Kalendarz

| Obsada | M1 | Całość |
|---|---|---|
| 1 wykonawca | ok. 2,5 tygodnia | ok. 7 tygodni |
| 2 wykonawców | ok. 1,5 tygodnia | ok. 4,5 tygodnia |

Zadania 3–8 zrównoleglają się, bo model danych (zadanie 2) jest już zamknięty.
Zadania 13–16 wymagają gotowej całości i nie skracają się przez dodanie osób.

## 5. Co zostało zrobione 2026-09-09

Zadanie 2 — model danych — jest wykonane i zweryfikowane:

- typy treści `iskt_szkolenie`, `iskt_trener`, `iskt_termin` z własnymi uprawnieniami
  (`map_meta_cap`), nadawanymi rolom administratora i redaktora przy aktywacji;
- taksonomie `iskt_kategoria` i `iskt_forma`, hierarchiczne, z zasianiem pięciu kategorii
  startowych z §4.2 **dokładnie raz** — ponowna aktywacja nie odtwarza kategorii,
  które właściciel usunął;
- komplet pól z §4.3, §4.4 i §4.5 przez `register_post_meta()` z własną sanityzacją
  i kontrolą uprawnień;
- panel edycji na natywnych skrzynkach metadanych renderowanych **wewnątrz edytora
  blokowego**, z powtarzalnym programem szkolenia w zwykłym JavaScripcie (bez `npm`);
- relacja szkolenie–trener w jednym miejscu + zapytanie zwrotne na profilu trenera
  i sprzątanie po usunięciu trenera;
- rozróżnienie terminów nadchodzących i zakończonych po stronie bazy, obsługa terminu
  ustalanego indywidualnie;
- kolumny list w panelu: szkolenie, data realizacji z oznaczeniem zakończonego, tryb, status zgłoszeń.

**Weryfikacja:** `php -l` na wszystkich plikach oraz 86 asercji w `tests/run.php`
(sanityzacja dat, cen, list, programu, słowników, relacji trenerów, rozróżnienie
terminów zakończonych). Testy uruchamiają prawdziwy kod wtyczki, nie jego kopię.

Zadanie 3 — motyw — dostarcza siatkę, typografię, nagłówek, stopkę, menu i komponenty
wspólne; umowa nazewnicza w `MOTYW-KOMPONENTY.md`.

Zadanie 4 — strona główna — dostarcza:

- `theme.json` z paletą, skalą pisma i odstępami wskazującymi na te same tokeny,
  co arkusze motywu; własne kolory i stopnie pisma celowo wyłączone;
- **siedem wzorców sekcji** w `patterns/` (powitanie, obszary, wyróżnione, przebieg,
  dofinansowania, wskaźniki, kontakt) plus wzorzec składający całą stronę;
- **cztery bloki dynamiczne** we wtyczce: przełącznik odbiorcy, pojemnik treści
  wariantu, obszary szkoleń i wyróżnione szkolenia — każdy zwraca pusty ciąg,
  gdy nie ma czego pokazać (§4.1);
- przełącznik „Dla Ciebie”/„Dla firm” **działający bez JavaScriptu**, z wariantem
  zapisanym w adresie, więc odświeżenie i przycisk „Wstecz” zachowują wybór;
- symbol kategorii jako pole terminu we wtyczce z mapowaniem na kształt w motywie;
- `front-page.php` bez ani jednego tekstu redakcyjnego — cała treść pochodzi z bloków.

Rozwiązania i uzasadnienia: `STRONA-GLOWNA.md`. Odstępstwa od załącznika,
w tym rezygnacja z wyliczanej ceny po dofinansowaniu: `ODSTEPSTWA-OD-WZORCA.md` §6.

Zadanie 5 — strona szkolenia — dostarcza `single-iskt_szkolenie.php` z trzema
częściami szablonu: nagłówkiem (ścieżka powrotu, plakietki, tytuł, zajawka, fakty),
treścią (opis, korzyści, program jako lista uporządkowana, grupa docelowa)
i panelem bocznym (cena, terminy, trenerzy, dofinansowanie).

Każda sekcja pojawia się wyłącznie wtedy, gdy ma treść. Terminy zakończone są
odsiewane po stronie bazy (§4.4), a powiązani trenerzy pochodzą z relacji zapisanej
na szkoleniu, więc oba widoki pokazują to samo (§4.5). Odstępstwa: `ODSTEPSTWA-OD-WZORCA.md` §8.

**Kamień milowy M1 jest kompletny i sprawdzony w przeglądarce.** Środowisko `wp-env`
działa, a ścieżka właściciela z definicji M1 — dodanie trenera, szkolenia z programem
i ceną, dwóch terminów oraz obejrzenie efektu na stronie — przechodzi w panelu
od początku do końca (`tests/e2e/odbior-m1.spec.js`, 6/6 zaliczonych). Test klika
w panelu, a nie zapisuje danych przez WP-CLI: inaczej ominąłby skrzynki metadanych
i `save_post`, czyli dokładnie tę część, którą ma potwierdzać.

Zakres przekazany do odbioru i lista rzeczy, których M1 świadomie nie zawiera:
`ODBIOR-M1.md`.

## 5a. Co zostało zrobione po odbiorze M1 (2026-09-09)

Zadanie 10 — pełna edytowalność treści — jest wykonane.

Rejestr tekstów globalnych mieszka we wtyczce (`includes/teksty.php`), a ekran
„Szkolenia → Teksty serwisu” buduje się z tego rejestru. Dodanie napisu do rejestru
automatycznie dokłada pole w panelu, więc nie da się dopisać tekstu, którego
właściciel nie może zmienić. Objęte grupy: dane kontaktowe, nagłówek i stopka,
katalog, strona szkolenia, trenerzy, aktualności, formularz, wyszukiwanie i strona
błędu — łącznie kilkadziesiąt napisów.

Dwie decyzje warte odnotowania przy odbiorze:

- **puste pole = treść domyślna.** Właściciel nie musi pamiętać oryginału, żeby
  cofnąć zmianę, i nie skasuje przypadkiem zdania wymaganego przez §5 zlecenia.
  Napisy, których wolno w ogóle nie pokazywać — telefon, adres, godziny — mają
  pustą wartość domyślną i szablony je pomijają;
- **zapisujemy tylko wartości różne od domyślnych**, więc poprawka domyślnej treści
  w kolejnej wersji dotrze do właściciela, który tego pola nie ruszał.

Formularz ekranu idzie przez `admin-post.php`, nie przez `options.php`: ten drugi
wymaga uprawnienia `manage_options`, którego redaktor nie ma, a §6 daje dostęp
administracji **i redakcji**.

Przy okazji naprawiona usterka z M1: strona szkolenia bez nadchodzących terminów
pokazywała puste miejsce, które czytało się jak wycofana oferta. Teraz mówi wprost,
że termin ustalamy indywidualnie (§4.4) — i ten komunikat też jest edytowalny.

**Weryfikacja:** `php tests/run.php` — 120 asercji (było 97). Playwright
`tests/e2e/teksty-globalne.spec.js` — 4/4, ścieżka przez formularz panelu: zmiana
napisu widoczna na stronie, wyczyszczenie pola wraca do treści domyślnej, znacznik
`<script>` nie przechodzi przez zapis. Regresja `odbior-m1.spec.js` — 6/6.

**Znalezione przy okazji, zgłoszone osobno:** `odbior-m1.spec.js` przechodzi tylko
na czystych danych. Drugi przebieg tworzy szkolenie o identycznym tytule, a termin
trafia do niewłaściwej kopii — główny test odbiorowy M1 daje wtedy fałszywy błąd.
Trafiło do QA razem z pytaniem, czy właściciel nie ma tego samego problemu przy
wyborze szkolenia dla terminu.

## 6. Czego estymacja NIE zawiera

- wdrożenia na SEOHost — poza zakresem (§13) i wprost niezatwierdzone;
- redakcji treści ofertowych, opisów szkoleń i biografii — dostarcza ISKT;
- polityki prywatności i regulaminu — treść prawna po stronie ISKT;
- sesji zdjęciowej trenerów ani zakupu zdjęć;
- warstwy SEO, danych strukturalnych, metadanych udostępniania i analityki —
  wyłączone decyzją ISKT, do wyceny osobno, jeśli wrócą do zakresu;
- wielojęzyczności, sklepu, płatności i LMS — poza zakresem (§13).

## 7. Ryzyka

| Ryzyko | Wpływ | Postępowanie |
|---|---|---|
| ~~Brak działającego Dockera w środowisku wykonawczym~~ | — | **Zamknięte 2026-09-09**: Docker działa, `wp-env` uruchomione, wygląd i ścieżka redaktora sprawdzone w przeglądarce |
| Brak zatwierdzonych treści ofertowych | Blokuje publikację, nie budowę | Budujemy na oznaczonych danych demonstracyjnych; §14 wprost na to pozwala |
| Brak dostępu do poczty szkolenia@iskt.pl | Brak testu dostarczenia end-to-end | Test wysyłki w środowisku wewnętrznym; test docelowy dokumentowany osobno po migracji (§8) |
| Terminy i aktualności bez wzorca wizualnego | Możliwa iteracja projektowa | Propozycja do akceptacji przed implementacją — bramka przed zadaniem 8 |
| Rozbieżność wyglądu względem prototypu | Kryterium akceptacji §11 pkt 1 | Jawna lista odstępstw z uzasadnieniem: `ODSTEPSTWA-OD-WZORCA.md`, założona w zadaniu 3 i uzupełniana w każdym kolejnym |
| Rezygnacja z SEO i danych strukturalnych | Szkolenia nie pojawią się w Google jako kursy z terminami; udostępniane odnośniki bez grafiki i opisu | Skutek świadomej decyzji ISKT (ADR-002 §5). Model danych przygotowany tak, by dodać tę warstwę później bez migracji |
| Brak wzorca kroju firmowego | Typografia oparta na substytucie (Inter) | Design system sam oznacza Inter jako substytut. Jeśli ISKT ma licencjonowany krój — prosimy o pliki |

## 8. Bramki (§12)

1. ~~Zatwierdzenie planu i startu realizacji~~ — **zamknięte 2026-09-09**, zgoda w zmienionym zakresie
2. ~~Akceptacja projektu widoków terminów i aktualności~~ — **zamknięte 2026-09-09**, dokument `PROJEKT-AKTUALNOSCI.md` przyjęty bez uwag; zadanie 8 wykonane w zaproponowanym wariancie
3. ~~Odbiór M1 przez ISKT~~ — **zamknięte 2026-09-09**
4. Przegląd bezpieczeństwa — zadanie 15
5. Przegląd obsługi danych osobowych i treści informacyjnych — przed publikacją
6. QA i test odtworzenia — zadania 13 i 16
7. Prezentacja i odbiór końcowy przez ISKT

Termin i budżet pozostają nieustalone — §1 zlecenia zostawiał je „do ustalenia po
estymacji”, a odpowiedź ISKT ich nie podała. Nie blokuje to realizacji; wraca jako
pytanie przy odbiorze M1.

## 9. Kolejny krok

Zadania 6, 7 i 9 idą równolegle jako zadania podrzędne — katalog, trenerzy, formularz.
Zadanie 10 jest zrobione i jest ich wspólnym fundamentem: każde z nich czyta napisy
z rejestru tekstów zamiast wpisywać je do szablonu. Kolejność nie była kosmetyczna —
gdyby trzy gałęzie ruszyły przed rejestrem, powstałyby trzy różne sposoby na te same
etykiety i komunikaty, a §4.7 wymaga jednego źródła.

Zadanie 8 jest wykonane: bramka 2 została zamknięta akceptacją bez uwag, więc widoki
powstały w wariancie proponowanym w dokumencie — z sekcją „Zobacz nasze szkolenia”
pod artykułem, bez podpisu autora i z filtrem kategorii nad listą.

Po zamknięciu zadań 6, 7, 8 i 10 z M2 zostaje wyłącznie zadanie 9 (formularz).
