# ISK-29 — dostępność, responsywność i wydajność

Data pomiaru: 2026-09-09. Środowisko: lokalne `wp-env`, strona robocza na
`http://localhost:8888`, Chromium headless.

## Zakres i dane

Przejście objęło osiem publicznych widoków z danymi demonstracyjnymi: stronę
główną, katalog, stronę szkolenia, listę i profil trenera, listę i artykuł
aktualności oraz formularz zgłoszeniowy. Każdy widok sprawdzono przy 360, 768 i
1440 px. Nie badano panelu administracyjnego ani integracji docelowej poczty —
nie należą do kryteriów tego zadania.

## Wynik końcowy

- 24/24 kombinacje widok/szerokość: HTTP bez błędu, dokładnie jeden znacznik
  `h1`, brak przeskoków poziomów nagłówków, brak poziomego przewijania i brak pól
  bez dostępnej nazwy.
- Klawiatura: przechodzą menu mobilne (Enter i `aria-expanded`), przełącznik
  odbiorcy, wyszukiwarka i oba filtry katalogu, karta szkolenia oraz wszystkie
  widoczne kontrolki formularza. Kontrolki mają widoczny obrys fokusu.
- Lighthouse Accessibility: katalog 100/100, formularz 100/100; audyt kontrastu
  przechodzi w obu widokach.
- Testy PHP: 261 przeszło, 0 nie przeszło.

W toku audytu usunięto trzy odstępstwa od kryteriów: dwa `h1` strony głównej,
przeskok z `h1` do `h3` na listach katalogu i aktualności oraz kontrast 3,8:1
tekstu pomocniczego na białym tle. Tekst pomocniczy korzysta teraz z
`neutral-600`, a Lighthouse nie zgłasza naruszeń kontrastu.

## Wydajność (Lighthouse 13.4.1, desktop)

| Widok | Wynik | FCP | LCP | TBT | CLS | Speed Index |
|---|---:|---:|---:|---:|---:|---:|
| Strona główna — pomiar kontrolny | 100 | 0,3 s | 0,4 s | 0 ms | 0 | 0,3 s |
| Katalog | 100 | 0,3 s | 0,4 s | 0 ms | 0 | 0,3 s |
| Strona szkolenia | 100 | 0,3 s | 0,4 s | 0 ms | 0 | 0,3 s |

Pierwszy pomiar strony głównej uzyskał 93 punkty i TBT 210 ms przez pojedyncze,
nieprzypisane zadanie głównego wątku (412 ms). Powtórzenie na niezmienionym kodzie
dało 100 punktów i TBT 0 ms, więc wynik zapisano jako szum lokalnego pomiaru, a
nie problem aplikacji. Nieistotne wskazówki pierwszego przebiegu: ok. 2 KiB CSS
do minifikacji, ok. 15 KiB nieużywanego CSS, polityka cache dla ok. 91 KiB zasobów
i ok. 45 KiB potencjalnej optymalizacji obrazu. Są typowe dla roboczego `wp-env`;
nie wpływają na metryki kontrolnego przebiegu i nie uzasadniają ryzykownej
przebudowy wspólnych arkuszy przed wdrożeniem.

## Dowody

- `responsywnosc.json` — surowe wyniki 24 kombinacji.
- `audyt.spec.js` — odtwarzalny audyt przeglądarkowy.
- `home-*`, `katalog-*`, `szkolenie-*`, `trenerzy-*`, `trener-*`,
  `aktualnosci-*`, `artykul-*`, `zgloszenie-*` — zrzuty 360/768/1440.
- `klawiatura-formularz-360.png` — stan formularza w przejściu klawiaturą.
- `lighthouse-*.json` i `lighthouse-a11y-*.json` — surowe raporty Lighthouse.

Nowych odstępstw od wzorca wizualnego nie stwierdzono. Zmiany opisane wyżej są
naprawami kryteriów §7, nie świadomymi odstępstwami od wzorca.
