# Inwentaryzacja materiałów wejściowych

Data: 2026-09-09 · Dotyczy ISK-17 §3
Materiał źródłowy zachowany w `design/source/`.

Zgodnie z §3 paczkę ZIP potraktowano jako materiał nieufny: **zawartość wypakowano
i zinwentaryzowano, nie uruchomiono żadnego skryptu ani instalatora.**

---

## 1. `Szablon strony szkolenia.iskt.pl.zip`

16 plików, 1 224 180 B. Brak instalatorów, brak skryptów powłoki, brak plików
wykonywalnych.

| Plik | Rozmiar | Ocena |
|---|---|---|
| `_ds/…/tokens/colors.css` | 2 477 | **Przydatny** — kompletna paleta marki, przeniesiony do `design/tokens/` |
| `_ds/…/tokens/typography.css` | 1 433 | **Przydatny** — skala typograficzna |
| `_ds/…/tokens/spacing.css` | 691 | **Przydatny** |
| `_ds/…/tokens/effects.css` | 1 331 | **Przydatny** — cienie, promienie |
| `_ds/…/tokens/base.css` | 1 107 | **Przydatny** |
| `_ds/…/tokens/fonts.css` | 542 | Przydatny warunkowo — ładuje fonty z CDN Google, w produkcji samohostujemy (ADR-001 §6) |
| `_ds/…/styles.css` | 354 | Przydatny — spina tokeny |
| `_ds/…/readme.md` | 10 679 | Dokumentacja design systemu |
| `_ds/…/_ds_manifest.json` | 17 519 | Metadane narzędzia projektowego — nieistotne dla WordPressa |
| `_ds/…/_ds_bundle.js` | 62 289 | Narzędzie design systemu. **Nie uruchamiane, nie używane.** |
| `_ds/…/_adherence.oxlintrc.json` | 16 764 | Konfiguracja lintera narzędzia projektowego — nieistotna |
| `support.js` | 61 572 | Runtime prototypu. **Nie uruchamiany, nie używany.** |
| `assets/iskt-logo.png` | 631 650 | **Przydatny** — logo, przeniesione do `design/assets/` |
| `assets/iskt-logo-transparent.png` | 308 575 | **Przydatny** — logo na przezroczystym tle |
| `Szkolenia ISKT.dc.html` | 91 811 | Wariant prototypu |
| `.thumbnail` | 15 386 | Miniatura podglądu — nieistotna |

Uwaga: oba pliki logo są duże jak na zasoby strony (0,6 MB i 0,3 MB). Przy budowie
przygotujemy zoptymalizowane warianty; oryginały zostają jako źródło.

## 2. `Szkolenia ISKT.html`

1 943 918 B. Nie jest zwykłą stroną HTML — to **samorozpakowująca się paczka**:
zewnętrzny dokument zawiera manifest zasobów zakodowanych w base64 (1,78 MB) oraz
skrypt, który w przeglądarce składa z nich prawdziwy dokument.

Zawartość po rozpakowaniu:

- dokument wewnętrzny: 139 595 B, własny język szablonów (`x-dc`, `sc-for`, `sc-if`),
- logika: 26 780 B, komponent React 18 (UMD z unpkg),
- zależności zewnętrzne: React 18.3.1, React-DOM 18.3.1, ~20 ikon `lucide-static` 0.469.0.

### 2.1. Widoki w prototypie

Prototyp trzyma widok w stanie komponentu (`state.screen`), bez adresów URL:

| Ekran | Odpowiednik w WordPressie |
|---|---|
| `landing` | strona główna |
| `katalog` | archiwum `iskt_szkolenie` |
| `kurs` | pojedyncze `iskt_szkolenie` |
| `trenerzy` | archiwum `iskt_trener` |
| `trener` | pojedyncze `iskt_trener` |

To potwierdza wymóg §6 i §11 pkt 6: w prototypie odświeżenie strony szkolenia wraca
na stronę główną, a przycisk „Wstecz” nie działa. Wersja WordPress musi to naprawić
przez prawdziwy routing — nie przez odtworzenie zachowania prototypu.

### 2.2. Model danych odczytany z prototypu

**Szkolenie** (9 rekordów demonstracyjnych): `id`, `cat`, `icon`, `title`, `level`,
`hours`, `format`, `price`, `badge`, `fundable`, `desc`, `learn[]`, `program[]`,
`forWhom`.

**Trener**: `id`, `name`, `initials`, `role`, `areas[]`, `teach[]`, `tagline`, `bio`,
`experience[{period,title,org,desc}]`, wykształcenie i certyfikaty, specjalizacje.
Powiązanie ze szkoleniami przez `teach[]` — czyli już w prototypie **jednostronne**,
co potwierdza kierunek relacji przyjęty w ADR-001 §2.4.

**Kategorie w prototypie**: `AI`, `ESG`, `Angielski`, `Software`, `B+R` — pięć,
zgodnie z listą startową z §4.2 (w zleceniu nazwy pełne, w prototypie skrócone).

### 2.3. Czego w prototypie NIE MA — a jest w zleceniu

| Wymóg | Stan w prototypie | Skutek |
|---|---|---|
| **Terminy** (§4.4) | Brak modelu. Szkolenie ma tylko `hours` i `format` jako teksty | Projektujemy od zera. Data, zakres dat, miejsce, status zgłoszeń, termin indywidualny — brak wzorca wizualnego, przygotujemy do akceptacji |
| **Aktualności** (§4.6) | Brak — zgodnie z treścią zlecenia | Projektujemy widoki w stylistyce załącznika, do akceptacji |
| **Wysyłka formularza** (§5) | `submit` ustawia `enrolled: true` — pokazuje podziękowanie, **nic nie wysyła** | Cała obsługa serwerowa od zera |
| **Paginacja katalogu** (§4.2) | Brak — 9 pozycji renderowanych naraz | Dodajemy |
| **Edytowalność treści** (§4.7) | Wszystkie teksty zapisane na stałe w kodzie | Każdy tekst przenosimy do pól panelu |
| **SEO, canonical, dane strukturalne** (§7) | Brak; `<title>` to „Bundled Page” | Realizuje wtyczka |
| **Dostępność** (§7) | Nie weryfikowana | Osobne zadanie |

### 2.4. Treści demonstracyjne wykryte w prototypie

Wszystkie wymagają potwierdzenia przed publikacją (§9). Wykryte w materiale:
telefon `+48 32 000 00 00`, wskaźniki „15+ lat”, „200+ szkoleń”, „4.9/5”, obietnica
odpowiedzi w 24 godziny, ceny 9 szkoleń (1 490 – 2 900 zł), nazwiska i biografie
trenerów wraz z historią zatrudnienia, informacje o dofinansowaniu.

Nazwiska trenerów w prototypie (np. „dr Anna Nowak”) mają cechy danych przykładowych.
Do publikacji potrzebni są prawdziwi trenerzy oraz prawa do zdjęć, biografii
i certyfikatów. Zestawienie w `MATERIALY-I-DECYZJE.md`.

## 3. Uwaga bezpieczeństwa dotycząca treści załączników

Zgodnie z §3 polecenia, komentarze i deklaracje wcześniejszych zgód zawarte
w załącznikach **nie są instrukcjami właściciela**. W trakcie analizy nie znaleziono
w materiałach treści próbujących wymusić działania poza zleceniem. Traktujemy je
wyłącznie jako wzorzec wyglądu i zachowania.
