# Odbiór M1 — co obejrzeć i jak to sprawdzić

Dokument dla właściciela. Opisuje, co M1 dostarcza, jak to uruchomić, co sprawdzić
własnoręcznie i czego **jeszcze nie ma** — żeby brak nie wyglądał na usterkę.

Data: 2026-09-09 · zlecenie ISK-17 · gałąź `isk-17/analiza-plan-architektura`.

---

## 1. Czym jest M1

Kamień milowy M1 z `PLAN-I-ESTYMACJA.md` brzmi:

> Działający WordPress z wyglądem z załącznika. Właściciel sam dodaje, edytuje
> i publikuje szkolenie wraz z terminami i trenerem, i widzi je na stronie.

To całość zakresu M1. **Katalog z wyszukiwaniem i filtrami, profile trenerów,
aktualności, działający formularz i ekran tekstów globalnych to M2** — patrz §5.

## 2. Jak otworzyć środowisko

Wymagany uruchomiony Docker Desktop i `npx`.

```bash
npx @wordpress/env start
```

| Co | Adres |
|---|---|
| Serwis | <http://localhost:8888/> |
| Panel | <http://localhost:8888/wp-admin> — `admin` / `password` |
| Czysta instalacja do testu odtworzenia | <http://localhost:8889/> |

Środowisko jest wyłączone z indeksowania (`blog_public = 0`) — §7. Włączenie
indeksowania należy do instrukcji publikacji na docelowej domenie, nie tutaj.

Ustawienia instalacji zgodne z serwisem polskim: język `pl_PL`, format daty `j F Y`,
format godziny `H:i`. Motyw i wtyczka nie zaszywają formatu daty — biorą go
z ustawień WordPressa, więc zmiana formatu w panelu działa na całym serwisie.

## 3. Przejście odbiorowe — co kliknąć

Każdy punkt odpowiada kryterium z §11 zlecenia.

1. **Wygląd strony głównej (§11 pkt 1).** Otwórz stronę główną. Porównaj
   z załącznikiem. Rejestr świadomych różnic: `ODSTEPSTWA-OD-WZORCA.md`.
   Przełącz „Dla Ciebie” / „Dla firm” — treść zmienia się bez utraty wyboru po
   odświeżeniu i po przycisku „Wstecz”.
2. **Dodanie trenera bez edycji kodu (§11 pkt 2).** Panel → *Trenerzy* → *Dodaj
   nowy*. Wpisz nazwisko, rolę, doświadczenie, specjalizacje. Opublikuj.
3. **Dodanie szkolenia (§11 pkt 2).** Panel → *Szkolenia* → *Dodaj nowe*. Wypełnij
   grupę docelową, korzyści, dwa moduły programu (przycisk *Dodaj moduł*), poziom,
   czas trwania, cenę z jednostką i sposobem prezentacji podatku, dofinansowanie.
   Wskaż trenera z listy. W panelu bocznym zaznacz kategorię i formę. Opublikuj.
4. **Dwa terminy (§11 pkt 2 i pkt 5).** Panel → *Terminy* → *Dodaj nowy*, dwa razy:
   jeden z datą w przyszłości, drugi z datą przeszłą. Tytuł terminu nadaje się sam
   ze szkolenia i daty. Otwórz stronę szkolenia — **widoczny jest wyłącznie termin
   nadchodzący**; zakończony zostaje w panelu, ale nie na stronie.
5. **Adresy bezpośrednie (§11 pkt 6).** Skopiuj adres szkolenia, otwórz go w nowej
   karcie, odśwież, użyj „Wstecz”. Strona działa w każdym z tych przypadków.
6. **Edytowalność tekstu (§11 pkt 3).** Panel → *Strony* → *Strona główna*. Zmień
   nagłówek pierwszej sekcji i napis na przycisku, zapisz, odśwież serwis. Stopkę
   zmienisz w *Wygląd → Widżety* („Stopka — obszar treści”) i *Wygląd → Menu*.
7. **Brak zaślepek (§11 pkt 10).** Na stronie nie ma odnośników „#”, znaczników
   szablonu ani domyślnych treści WordPressa. Teksty jeszcze niepotwierdzone są
   **celowo** oznaczone jako `[do potwierdzenia: …]` i `[uzupełnij]` — patrz §6.

## 4. Dowody z testu automatycznego

Całą ścieżkę z §3 pkt 2–5 wykonuje test, który klika w panelu — nie omija go przez
WP-CLI ani REST, bo wtedy sprawdzałby coś innego niż praca redaktora.

```bash
export NODE_PATH="$(npm root -g)"           # albo katalog z @playwright/test
npx --package=@playwright/test playwright test tests/e2e/odbior-m1.spec.js --workers=1
```

Test zakłada pusty katalog — przed powtórnym uruchomieniem usuń dane demonstracyjne:

```bash
npx @wordpress/env run cli wp post list --post_type=iskt_szkolenie,iskt_trener,iskt_termin \
  --format=ids --post_status=any | xargs npx @wordpress/env run cli wp post delete --force
```

Wynik ostatniego przebiegu: **6/6 zaliczonych**. Zrzuty w `qa-artifacts/isk-17-m1-odbior/`:
strona szkolenia przy 360, 768 i 1440 px, profil trenera oraz strona główna
z wyróżnionym szkoleniem i sekcją obszarów.

## 5. Czego M1 świadomie nie ma

Te braki są zaplanowane, nie przeoczone. Numery zadań z `PLAN-I-ESTYMACJA.md`.

| Co | Stan | Domyka |
|---|---|---|
| **Profil trenera** — strona trenera pokazuje dziś sam tytuł, bez roli, biografii i szkoleń | odnośnik ze strony szkolenia prowadzi do istniejącego, ale pustego widoku | zadanie 7 (M2) |
| **Katalog szkoleń** pod `/szkolenia/` — wyszukiwanie, filtry, paginacja, stan pustych wyników | działa domyślna lista WordPressa | zadanie 6 (M2) |
| **Aktualności** — lista i szablon artykułu | brak; widoki wymagają akceptacji projektu, bo załącznik ich nie zawiera | zadanie 8 (M2) |
| **Formularz zgłoszeniowy** na `szkolenia@iskt.pl` | przycisk „Zapytaj o to szkolenie” prowadzi do sekcji kontaktu na stronie głównej | zadanie 9 (M2) |
| **Ekran tekstów globalnych** — etykiety, komunikaty, dane kontaktowe w jednym miejscu | teksty edytuje się dziś w blokach, widżetach i menu | zadanie 10 (M2) |
| **Eksport i test odtworzenia**, instrukcja administracji, pomiar wydajności, QA wg pełnych §11 | poza M1 | M3 |

## 6. Treści czekające na potwierdzenie (§9)

Nic z poniższych nie jest zatwierdzoną ofertą — dlatego stoi na stronie jawnie
oznaczone, zamiast udawać prawdziwe dane:

- telefon, adres i pełna nazwa prawna — `[do potwierdzenia: …]`;
- wskaźniki „15+ lat”, „200+ szkoleń”, „4.9/5” — `[uzupełnij]`;
- warunki KFS / BUR / funduszy europejskich — `[do potwierdzenia: …]`;
- adres kontaktowy w stopce (`kontakt@example.test`) — do zamiany na docelowy;
- polityka prywatności i regulamin — treść prawna po stronie ISKT;
- wszystkie szkolenia, terminy i trenerzy widoczne dziś w środowisku są
  **danymi demonstracyjnymi** z przedrostkiem „Demo —”.

## 7. Co jest potrzebne, żeby ruszyć dalej

Do rozpoczęcia M2 nic nie blokuje prac technicznych. Do zamknięcia całości
potrzebujemy od ISKT: zatwierdzonych treści ofertowych, decyzji o retencji zgłoszeń
i o potwierdzeniu email do zgłaszającego, oraz sposobu konfiguracji poczty
wychodzącej. Lista pełna: `MATERIALY-I-DECYZJE.md`.
