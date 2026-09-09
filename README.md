# szkolenia_iskt

Serwis szkoleniowy **szkolenia.iskt.pl** — ISKT Greennovation.
Zlecenie: ISK-17. Docelowy hosting: SEOHost (wdrożenie poza zakresem tego zlecenia).

> **Status: realizacja M1.** ISKT zatwierdziło start 2026-09-09 w zakresie
> opisanym w `docs/ADR-002-rewizja-zakresu-bez-platnych-zaleznosci.md`.
> Wykonane: model danych katalogu (zadanie 2), motyw — siatka, nagłówek, stopka,
> menu i komponenty (zadanie 3), strona główna — wzorce sekcji, bloki dynamiczne
> i przełącznik odbiorcy (zadanie 4). Kolejne: strona szkolenia (zadanie 5).
>
> Wyglądu nie obejrzano jeszcze w przeglądarce — `wp-env` nie zostało uruchomione
> z powodu braku działającego demona Dockera w środowisku wykonawczym.

## Dokumentacja

| Dokument | Zawartość |
|---|---|
| [`docs/PLAN-I-ESTYMACJA.md`](docs/PLAN-I-ESTYMACJA.md) | Podział pracy, estymacja 37,5 dnia (pozostało 27,0), ryzyka, bramki |
| [`docs/ADR-001-architektura.md`](docs/ADR-001-architektura.md) | Decyzja architektoniczna wymagana przez §6 |
| [`docs/PROTOTYP-INWENTARYZACJA.md`](docs/PROTOTYP-INWENTARYZACJA.md) | Inwentaryzacja załączników i braki prototypu względem zlecenia |
| [`docs/MATERIALY-I-DECYZJE.md`](docs/MATERIALY-I-DECYZJE.md) | Czego potrzebujemy od ISKT i treści do potwierdzenia |
| [`docs/MOTYW-KOMPONENTY.md`](docs/MOTYW-KOMPONENTY.md) | Umowa nazewnicza motywu: siatka, typografia, komponenty |
| [`docs/STRONA-GLOWNA.md`](docs/STRONA-GLOWNA.md) | Sekcje strony głównej, bloki dynamiczne i przełącznik odbiorcy |
| [`docs/ODSTEPSTWA-OD-WZORCA.md`](docs/ODSTEPSTWA-OD-WZORCA.md) | Rejestr odstępstw od załącznika wymagany przez §11 pkt 1 |

## Struktura

```
design/
  tokens/    tokeny designu z dostarczonego design systemu (paleta, typografia, odstępy)
  assets/    logo ISKT
  source/    materiały wejściowe bez zmian — wzorzec, nie kod produkcyjny
docs/        dokumentacja projektowa
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
`iskt-szkolenia-core`, a następnie wykonać testy wizualne opisane w `ISK-20`.

Wymagania docelowe: PHP 8.2+, MySQL 8.0+ lub MariaDB 10.6+.

## Zasady

- Sekrety nie trafiają do repozytorium ani do paczki przekazania (§8, §11 pkt 11).
- Każdy tekst widoczny dla odwiedzającego jest edytowalny z panelu — bez modyfikowania
  PHP, HTML i JavaScript (§4.7).
- Treści niepotwierdzone przez ISKT pozostają oznaczone jako demonstracyjne albo
  ukryte przed publikacją (§9).
