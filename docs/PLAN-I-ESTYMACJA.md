# Plan realizacji i estymacja — szkolenia.iskt.pl (ISK-17)

Status: **oczekuje na decyzję ISKT** — §12 „Start realizacji … wymaga decyzji ISKT jako człowieka”
Data: 2026-09-09 · Koordynator Techniczny / Intake Lead

Dokumenty powiązane: `ADR-001-architektura.md`, `PROTOTYP-INWENTARYZACJA.md`,
`MATERIALY-I-DECYZJE.md`.

---

## 1. Co zostało już zrobione (przed bramką startu)

Prace niezależne od decyzji, wykonane bez ponoszenia kosztów:

- naprawiono środowisko pracy: repozytorium `szkolenia_iskt` działa w katalogu
  roboczym zlecenia (poprzedni przebieg nie startował z powodu braku repozytorium),
- zinwentaryzowano oba załączniki bez uruchamiania skryptów (§3),
- rozpakowano prototyp i odczytano z niego model danych, listę widoków oraz braki
  względem zlecenia — `PROTOTYP-INWENTARYZACJA.md`,
- przeniesiono do repozytorium tokeny designu (paleta, typografia, odstępy, efekty)
  i pliki logo,
- przygotowano decyzję architektoniczną wymaganą przez §6 — `ADR-001-architektura.md`.

## 2. Kierunek

WordPress + dedykowany motyw `iskt-szkolenia` (wyłącznie prezentacja) + wtyczka
`iskt-szkolenia-core` (model danych, formularz, SEO). Szczegóły i uzasadnienia
w ADR-001. Kluczowe rozstrzygnięcia:

- terminy jako osobny typ treści, nie pole powtarzalne — wymóg archiwizacji i statusu,
- relacja szkolenie–trener zapisana tylko na szkoleniu — jedno źródło prawdy,
- aktualności na natywnym wpisie WordPress, bez nowego typu treści,
- brak wtyczki SEO — własny `Course`/`Event`/`Person` w danych strukturalnych,
- rekomendacja ACF Pro (49 USD/rok) z bezkosztową alternatywą Carbon Fields,
- fonty samohostowane, nie z CDN Google (RODO).

## 3. Podział pracy

Kolejność uwzględnia zależności: model danych przed widokami, widoki przed
dostępnością i wydajnością, wszystko przed testem odtworzenia.

| # | Zadanie | Kompetencja | Dni |
|---|---|---|---|
| 1 | Fundament: środowisko `wp-env`, szkielet motywu i wtyczki, tokeny w CSS, lint PHP | WP/frontend | 2,0 |
| 2 | Model danych: typy treści, taksonomie, pola, relacja, uprawnienia, zasianie kategorii | backend WP | 3,0 |
| 3 | Strona główna: sekcje przestawialne, przełącznik „Dla Ciebie”/„Dla firm”, ukrywanie pustych sekcji | WP/frontend | 4,0 |
| 4 | Katalog: wyszukiwanie, filtry kategoria + forma, paginacja, stan braku wyników, czyszczenie filtrów | WP/frontend | 2,5 |
| 5 | Strona szkolenia: pełny szablon, program, korzyści, cena z jednostką i podatkiem, dofinansowanie, terminy | WP/frontend | 2,0 |
| 6 | Trenerzy: lista i profil, spójna prezentacja powiązań w obu widokach | WP/frontend | 2,0 |
| 7 | Aktualności: lista i artykuł — **nowy projekt, do akceptacji ISKT** | WP/frontend | 1,5 |
| 8 | Formularz: walidacja serwerowa, antyspam, wysyłka, kontekst szkolenia i terminu, zachowanie danych przy błędzie | backend WP | 2,5 |
| 9 | Pełna edytowalność treści: ekran tekstów globalnych, menu, stopka, etykiety, komunikaty | backend WP | 2,0 |
| 10 | SEO: pola tytułu i opisu, canonical, metadane udostępniania, JSON-LD, mapa witryny, `noindex` filtrów | backend WP | 1,5 |
| 11 | Dostępność i responsywność 360/768/1440, klawiatura, fokus, kontrast + pomiar wydajności | QA + frontend | 2,0 |
| 12 | Treści demonstracyjne z jawnym oznaczeniem i mechanizmem usunięcia | backend WP | 1,0 |
| 13 | Eksport, test odtworzenia w czystym WordPressie, instrukcja migracji i wycofania | backend + dok. | 2,0 |
| 14 | Instrukcja administracji dla właściciela | dokumentacja | 1,5 |
| 15 | Przegląd bezpieczeństwa: uprawnienia, formularz, media, zależności | security | 1,0 |
| 16 | QA końcowe: przejście 14 kryteriów akceptacji §11 | QA | 2,0 |
| | **Razem** | | **32,5** |

Do tego koordynacja i przeglądy: ok. 3,5 dnia. **Łącznie ≈ 36 dni roboczych.**

### 3.1. Kalendarz

| Obsada | Czas trwania |
|---|---|
| 1 wykonawca | ok. 7,5 tygodnia |
| 2 wykonawców (frontend + backend równolegle od zadania 3) | ok. 4,5 tygodnia |

Zadania 3–7 dają się zrównoleglić po zamknięciu zadania 2. Zadania 13–16 wymagają
gotowej całości i nie skracają się przez dodanie osób.

### 3.2. Wpływ decyzji o edytorze pól

Estymacja powyżej zakłada ACF Pro. Wybór Carbon Fields: **+2,5 dnia** (zadania 2, 3, 9),
łącznie ≈ 38,5 dnia. Model danych i zakres pozostają bez zmian.

## 4. Czego estymacja NIE zawiera

- wdrożenia na SEOHost — poza zakresem (§13),
- redakcji treści ofertowych, opisów szkoleń i biografii — dostarcza ISKT,
- polityki prywatności i regulaminu — treść prawna po stronie ISKT,
- sesji zdjęciowej trenerów ani zakupu zdjęć,
- wielojęzyczności, sklepu, płatności, LMS — poza zakresem (§13),
- kosztu licencji ACF Pro, jeśli ISKT wybierze tę opcję.

## 5. Ryzyka

| Ryzyko | Wpływ | Postępowanie |
|---|---|---|
| Brak zatwierdzonych treści ofertowych | Blokuje publikację, nie budowę | Budujemy na oznaczonych danych demonstracyjnych; §14 wprost na to pozwala |
| Brak dostępu do poczty szkolenia@iskt.pl | Brak testu dostarczenia end-to-end | Test wysyłki w środowisku wewnętrznym; test docelowy dokumentowany osobno po migracji (§8) |
| Odmowa zakupu ACF Pro | +2,5 dnia | Carbon Fields, bez zmiany zakresu |
| Terminy i aktualności bez wzorca wizualnego | Możliwa iteracja projektowa | Przedstawiamy propozycję do akceptacji przed implementacją |
| Rozbieżność wyglądu względem prototypu | Kryterium akceptacji §11 pkt 1 | Prowadzimy jawną listę odstępstw z uzasadnieniem |
| Limity narzędzia migracyjnego | Utrudniony import przez właściciela | Ścieżka podstawowa bez limitów (`wp db export`), Duplicator tylko pomocniczo |

## 6. Kolejność bramek (§12)

1. **Zatwierdzenie tego planu, estymacji i decyzji o licencji** ← tu jesteśmy
2. Akceptacja projektu widoków terminów i aktualności (brak wzorca w załączniku)
3. Przegląd bezpieczeństwa
4. Przegląd obsługi danych osobowych i treści informacyjnych
5. QA i test odtworzenia
6. Prezentacja i odbiór przez ISKT

## 7. Wniosek do ISKT

Prosimy o decyzję w trzech punktach:

1. **Start realizacji** zgodnie z tym planem i estymacją ≈ 36 dni roboczych.
2. **Edytor pól**: ACF Pro (49 USD/rok, rekomendowany) czy Carbon Fields (0 zł, +2,5 dnia).
3. **Termin i budżet** — §1 zlecenia pozostawia je do ustalenia po estymacji.

Po zatwierdzeniu startu zakładamy zadania podrzędne według §3 i rozpoczynamy od
zadań 1–2, które nie zależą od żadnej z treści oczekujących na potwierdzenie.
