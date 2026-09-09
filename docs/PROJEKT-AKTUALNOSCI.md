# Projekt widoków aktualności — do akceptacji ISKT

ISK-17, zadanie 8 (M2). Bramka 2 z §8 planu: „Akceptacja projektu widoków terminów
i aktualności — przed zadaniem 8”.

Data: 2026-09-09 · Koordynator Techniczny / Intake Lead

---

## 1. Dlaczego ten dokument w ogóle powstał

§4.6 zlecenia wymaga aktualności — listy wpisów i szablonu artykułu — i sam
stwierdza, że **„widoki aktualności nie występują w załączniku; wykonawca
przygotuje je do akceptacji”**. Prototyp nie zawiera ani jednego ekranu wpisu.

Dla każdego innego widoku mamy wzorzec do odwzorowania i odstępstwa opisujemy
w `ODSTEPSTWA-OD-WZORCA.md`. Tutaj nie ma od czego odstępować — projektujemy.
Stąd bramka: chcemy zgody na kierunek, zanim powstanie kod, bo przerobienie
gotowego szablonu kosztuje więcej niż zmiana zdania w tym dokumencie.

## 2. Zasada nadrzędna: aktualności nie są drugim serwisem

Wpisy dostają **te same komponenty**, co reszta serwisu — kartę, plakietkę,
nagłówek strony, paginację z `MOTYW-KOMPONENTY.md`. Nie tworzymy nowego języka
wizualnego dla trzeciego typu treści.

Skutek praktyczny dla właściciela: aktualności wyglądają jak katalog szkoleń,
więc nie trzeba się uczyć drugiego układu, a każda przyszła zmiana tokenów
(kolor, typografia, odstępy) obejmuje wpisy automatycznie.

Skutek dla kosztu: zadanie 8 to 1,5 dnia właśnie dlatego, że składamy je
z istniejących części.

## 3. Lista aktualności — `/aktualnosci/`

**Układ:** nagłówek strony (`iskt-page-header`) z tytułem i opcjonalnym wstępem,
poniżej siatka kart — 3 kolumny na 1440 px, 2 na 768 px, 1 na 360 px. Ta sama
siatka co w katalogu szkoleń.

**Karta wpisu** zawiera, w tej kolejności:

| Element | Skąd pochodzi | Gdy brakuje |
|---|---|---|
| Zdjęcie wyróżniające | obrazek wyróżniający wpisu | karta bez pasma zdjęcia, nie pusta ramka |
| Data publikacji | data wpisu, format z ustawień WordPressa | zawsze jest |
| Kategoria | kategoria wpisu | pomijana |
| Tytuł | tytuł wpisu, odnośnik na całej karcie | zawsze jest |
| Zajawka | zajawka wpisu albo skrócona treść | pomijana |
| „Czytaj dalej” | tekst globalny `aktualnosci_czytaj` | zawsze jest |

**Filtrowanie po kategorii** — tak, ale prostsze niż w katalogu: lista kategorii
nad siatką, bez wyszukiwarki i bez filtra formy. Uzasadnienie: §4.6 wymienia
kategorię wśród pól wpisu, ale nie żąda wyszukiwania; wyszukiwarka serwisu
i tak obejmuje wpisy.

**Paginacja** — komponent z `MOTYW-KOMPONENTY.md` §6, ten sam co w katalogu.

**Pusta lista** — komunikat z tekstu globalnego `aktualnosci_brak`, nie pusty
ekran. Właściciel może go zmienić z panelu.

## 4. Strona artykułu

**Układ jednokolumnowy**, szerokość czytelna (ok. 68 znaków w wierszu — token
`iskt-measure` już to robi). Bez panelu bocznego: wpis nie ma danych, które
uzasadniałyby drugą kolumnę, a jedna kolumna czyta się lepiej na telefonie
i nie wymaga osobnego układu dla 768 px.

Kolejność elementów:

1. ścieżka nawigacji: Aktualności → kategoria;
2. kategoria jako plakietka;
3. tytuł (`h1`);
4. data publikacji; data aktualizacji **tylko wtedy**, gdy jest późniejsza niż
   publikacja — inaczej wygląda jak błąd;
5. zdjęcie wyróżniające, jeśli jest;
6. treść wpisu z edytora blokowego — pełna typografia motywu, więc właściciel
   może wstawić nagłówki, listy, cytat, galerię i osadzone wideo;
7. odnośniki do poprzedniego i następnego wpisu;
8. sekcja domykająca — patrz niżej.

**Sekcja domykająca artykuł.** Proponujemy pod treścią wpisu blok „Zobacz nasze
szkolenia” z trzema wyróżnionymi szkoleniami. Powód jest biznesowy, nie
estetyczny: §2 zlecenia mówi, że serwis ma pozyskiwać zgłoszenia, a artykuł jest
najczęstszym wejściem z wyszukiwarki. Czytelnik, który skończył czytać, nie ma
dziś dokąd pójść.

To jedyny element tego projektu, który wykracza poza literalne brzmienie §4.6 —
dlatego wskazujemy go osobno i przyjmiemy decyzję odmowną bez dyskusji.

## 5. Czego świadomie NIE robimy

| Element | Powód |
|---|---|
| Autor wpisu pod tytułem | §4.6 nie wymienia autora wśród pól. Wszystkie wpisy pisze ISKT — podpis „admin” byłby szumem. Dodamy, jeśli ISKT chce publikować pod nazwiskami |
| Komentarze | Poza zakresem, generują moderację i dane osobowe (§10) |
| Czas czytania („5 min”) | Wymaga założeń o tempie czytania, których nikt nie potwierdził |
| Przyciski udostępniania | ADR-002 wyklucza zewnętrzne skrypty; metadane udostępniania są poza zakresem po decyzji ISKT |
| Powiązanie wpisu ze szkoleniem | Wymagałoby nowego pola i pracy w panelu. §4.6 tego nie żąda. Do wyceny osobno, jeśli okaże się potrzebne |
| Osobny typ treści na aktualności | Natywne wpisy WordPressa dają wszystko z §4.6. Własny typ oznaczałby więcej kodu i mniej znajomy panel |

## 6. Widoki terminów — stan faktyczny, nie propozycja

Bramka 2 obejmuje też terminy. Terminy **są już zrobione i działają** — powstały
w M1 (zadanie 5) i przeszły odbiór M1. Nie ma tu nic do zaprojektowania; opisujemy
stan, żeby akceptacja obejmowała to, co właściciel faktycznie zobaczy:

- lista terminów w panelu bocznym strony szkolenia: data albo zakres dat, miejsce
  albo forma online, plakietka ze statusem zgłoszeń;
- terminy zakończone są odsiewane po stronie bazy — §4.4 wymaga, żeby zakończony
  termin nie był pokazywany jako nadchodzący;
- termin ustalany indywidualnie ma własną obsługę i nie udaje daty;
- **nowe od 2026-09-09:** szkolenie bez nadchodzących terminów pokazuje komunikat
  „Termin ustalamy indywidualnie. Napisz do nas — dobierzemy dogodną datę.”
  zamiast pustego miejsca. Wcześniej brak terminów czytał się jak wycofana oferta.
  Treść jest edytowalna (Szkolenia → Teksty serwisu).

Terminy nie mają osobnej strony ani listy zbiorczej. §4.4 opisuje termin jako
element szkolenia, a nie samodzielną ofertę — strona terminu byłaby adresem,
który dubluje stronę szkolenia i konkuruje z nią w wyszukiwarce.

## 7. Edytowalność

Każdy napis z widoków aktualności jest już w rejestrze tekstów globalnych
(zadanie 10, zrobione): `aktualnosci_tytul`, `aktualnosci_wstep`,
`aktualnosci_czytaj`, `aktualnosci_brak`. Właściciel zmienia je w panelu
w Szkolenia → Teksty serwisu, bez dotykania kodu.

## 8. O co konkretnie prosimy

Zgoda na kierunek z §3–§5 wystarczy, żeby zadanie 8 ruszyło. Trzy punkty,
w których decyzja ISKT zmienia zakres pracy:

1. **Sekcja „Zobacz nasze szkolenia” pod artykułem** — zostaje czy odpada?
2. **Autor wpisu** — publikujemy jako ISKT bez podpisu, czy pod nazwiskami osób?
3. **Filtrowanie po kategorii na liście** — potrzebne od razu, czy wystarczy
   prosta lista chronologiczna?

Brak odpowiedzi na te trzy punkty nie blokuje pozostałych zadań M2 (katalog,
trenerzy, formularz) — idą równolegle.
