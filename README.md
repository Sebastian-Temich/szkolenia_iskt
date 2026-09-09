# szkolenia_iskt

Serwis szkoleniowy **szkolenia.iskt.pl** — ISKT Greennovation.
Zlecenie: ISK-17. Docelowy hosting: SEOHost (wdrożenie poza zakresem tego zlecenia).

> **Status: przed bramką startu.** Zgodnie z §12 zlecenia start realizacji wymaga
> decyzji ISKT. W repozytorium znajduje się analiza materiałów, decyzja
> architektoniczna, plan i estymacja oraz szkielet motywu i wtyczki.
> Implementacja funkcji rozpocznie się po zatwierdzeniu planu.

## Dokumentacja

| Dokument | Zawartość |
|---|---|
| [`docs/PLAN-I-ESTYMACJA.md`](docs/PLAN-I-ESTYMACJA.md) | Podział pracy, estymacja ≈ 36 dni, ryzyka, bramki |
| [`docs/ADR-001-architektura.md`](docs/ADR-001-architektura.md) | Decyzja architektoniczna wymagana przez §6 |
| [`docs/PROTOTYP-INWENTARYZACJA.md`](docs/PROTOTYP-INWENTARYZACJA.md) | Inwentaryzacja załączników i braki prototypu względem zlecenia |
| [`docs/MATERIALY-I-DECYZJE.md`](docs/MATERIALY-I-DECYZJE.md) | Czego potrzebujemy od ISKT i treści do potwierdzenia |

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
Instrukcja uruchomienia środowiska lokalnego zostanie dodana w zadaniu 1 planu.

Wymagania docelowe: PHP 8.2+, MySQL 8.0+ lub MariaDB 10.6+.

## Zasady

- Sekrety nie trafiają do repozytorium ani do paczki przekazania (§8, §11 pkt 11).
- Każdy tekst widoczny dla odwiedzającego jest edytowalny z panelu — bez modyfikowania
  PHP, HTML i JavaScript (§4.7).
- Treści niepotwierdzone przez ISKT pozostają oznaczone jako demonstracyjne albo
  ukryte przed publikacją (§9).
