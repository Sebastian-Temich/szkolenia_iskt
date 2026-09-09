# szkolenia_iskt

Serwis szkoleniowy **szkolenia.iskt.pl** — ISKT Greennovation.
Zlecenie: ISK-17. Docelowy hosting: SEOHost (wdrożenie poza zakresem tego zlecenia).

> **Status: M1 wykonany i sprawdzony w przeglądarce — czeka na odbiór.**
> ISKT zatwierdziło start 2026-09-09 w zakresie opisanym
> w `docs/ADR-002-rewizja-zakresu-bez-platnych-zaleznosci.md`.
> Wykonane: model danych katalogu (zadanie 2), motyw (zadanie 3), strona główna
> (zadanie 4), strona szkolenia (zadanie 5) oraz środowisko `wp-env` (zadanie 1).
> Ścieżka właściciela „dodaj trenera → szkolenie → dwa terminy → zobacz na stronie”
> przechodzi w panelu od początku do końca: `tests/e2e/odbior-m1.spec.js`, 6/6.
> Co obejrzeć i czego M1 jeszcze nie ma: [`docs/ODBIOR-M1.md`](docs/ODBIOR-M1.md).
> W M2 gotowy jest katalog `/szkolenia/` — wyszukiwanie po nazwie i opisie, filtry
> kategorii i formy działające bez JavaScriptu, paginacja i dwa różne komunikaty
> pustego widoku: [`docs/KATALOG-SZKOLEN.md`](docs/KATALOG-SZKOLEN.md) (zadanie 6).

## Dokumentacja

| Dokument | Zawartość |
|---|---|
| [`docs/PLAN-I-ESTYMACJA.md`](docs/PLAN-I-ESTYMACJA.md) | Podział pracy, estymacja 37,5 dnia (pozostało 24,5), ryzyka, bramki |
| [`docs/ADR-001-architektura.md`](docs/ADR-001-architektura.md) | Decyzja architektoniczna wymagana przez §6 |
| [`docs/PROTOTYP-INWENTARYZACJA.md`](docs/PROTOTYP-INWENTARYZACJA.md) | Inwentaryzacja załączników i braki prototypu względem zlecenia |
| [`docs/MATERIALY-I-DECYZJE.md`](docs/MATERIALY-I-DECYZJE.md) | Czego potrzebujemy od ISKT i treści do potwierdzenia |
| [`docs/MOTYW-KOMPONENTY.md`](docs/MOTYW-KOMPONENTY.md) | Umowa nazewnicza motywu: siatka, typografia, komponenty |
| [`docs/STRONA-GLOWNA.md`](docs/STRONA-GLOWNA.md) | Sekcje strony głównej, bloki dynamiczne i przełącznik odbiorcy |
| [`docs/ODSTEPSTWA-OD-WZORCA.md`](docs/ODSTEPSTWA-OD-WZORCA.md) | Rejestr odstępstw od załącznika wymagany przez §11 pkt 1 |
| [`docs/FORMULARZ-ZGLOSZENIOWY.md`](docs/FORMULARZ-ZGLOSZENIOWY.md) | Formularz zgłoszeniowy: walidacja, wysyłka, granice ochrony antyspamowej, pytanie o retencję |
| [`docs/ODBIOR-M1.md`](docs/ODBIOR-M1.md) | Przejście odbiorowe M1: co kliknąć, czego jeszcze nie ma, treści do potwierdzenia |
| [`docs/KATALOG-SZKOLEN.md`](docs/KATALOG-SZKOLEN.md) | Katalog: wyszukiwanie, filtry, paginacja, puste widoki i decyzja o indeksowaniu (§7) |
| [`docs/ADMINISTRACJA.md`](docs/ADMINISTRACJA.md) | Instrukcja administracji dla właściciela: prowadzenie oferty z panelu, role, czego serwis nie robi (§11 pkt 13) |
| [`docs/EKSPORT-I-MIGRACJA.md`](docs/EKSPORT-I-MIGRACJA.md) | Przeniesienie serwisu na docelowy hosting: paczka eksportu, import, adresy, HTTPS, poczta, indeksowanie i wycofanie wdrożenia (§8) |

## Struktura

```
design/
  tokens/    tokeny designu z dostarczonego design systemu (paleta, typografia, odstępy)
  assets/    logo ISKT
  source/    materiały wejściowe bez zmian — wzorzec, nie kod produkcyjny
docs/        dokumentacja projektowa
tools/       skrypty przekazania: eksport paczki, import, wycofanie, test odtworzenia
wp-content/
  themes/iskt-szkolenia/            motyw — wyłącznie prezentacja
  plugins/iskt-szkolenia-core/      wtyczka — model danych, formularz, SEO
```

Rozdział motywu i wtyczki jest wymogiem: zmiana motywu nie może usuwać danych
katalogu (§6). Uzasadnienie w ADR-001 §1.

## Materiały wejściowe

`design/source/` zawiera dostarczone załączniki w postaci nienaruszonej. Są **wzorcem
wyglądu i zachowania**, nie kodem do osadzenia — §6 zakazuje umieszczania prototypu
jako iframe lub nieedytowalnego bloku HTML.

Paczka ZIP została zinwentaryzowana jako materiał nieufny: zawartość wypakowano
i opisano, **żadnego skryptu z niej nie uruchomiono** (§3).

## Środowisko

Instalacja WordPressa nie należy do repozytorium — trzymamy wyłącznie motyw i wtyczkę.
Do lokalnej weryfikacji używamy `wp-env`, który tworzy tymczasową instancję
WordPressa z podpiętym motywem i wtyczką z tego repozytorium.

Wymagania lokalne:

- Docker Desktop uruchomiony przed startem środowiska.
- Node.js z dostępem do `npx`.

Start środowiska:

```bash
npx @wordpress/env start
```

Panel administracyjny:

- adres: `http://localhost:8888/wp-admin`
- login: `admin`
- hasło: `password`

Po starcie należy aktywować motyw `iskt-szkolenia` i wtyczkę
`iskt-szkolenia-core`. Motyw sam zakłada wtedy stronę główną, ustawia ją jako
startową i wyłącza indeksowanie — dokładnie raz na instalację.

Instalacja pracuje po polsku; ustawia się to raz, bo dotyczy dat i separatora
tysięcy na całym serwisie:

```bash
npx @wordpress/env run cli wp language core install pl_PL --activate
npx @wordpress/env run cli wp option update date_format 'j F Y'
npx @wordpress/env run cli wp option update time_format 'H:i'
```

Przejście odbiorowe M1 i test end-to-end: [`docs/ODBIOR-M1.md`](docs/ODBIOR-M1.md).

Wymagania docelowe: PHP 8.2+, MySQL 8.0+ lub MariaDB 10.6+.

## Zasady

- Sekrety nie trafiają do repozytorium ani do paczki przekazania (§8, §11 pkt 11).
- Każdy tekst widoczny dla odwiedzającego jest edytowalny z panelu — bez modyfikowania
  PHP, HTML i JavaScript (§4.7).
- Treści niepotwierdzone przez ISKT pozostają oznaczone jako demonstracyjne albo
  ukryte przed publikacją (§9).
