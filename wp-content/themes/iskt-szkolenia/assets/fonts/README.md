# Fonty motywu — samohostowane

Ten katalog jest celowo pusty w repozytorium: nie dokładamy plików kroju, dopóki
nie jest przesądzone, którego kroju używamy (decyzja D10 w `docs/MATERIALY-I-DECYZJE.md`).

## Dlaczego nie CDN

`design/tokens/fonts.css` z dostarczonego systemu projektowego ładował Inter
i JetBrains Mono z CDN Google Fonts. W motywie tego **nie robimy**: CDN Google
przekazuje adres IP każdego odwiedzającego podmiotowi spoza EOG, co przy polskim
odbiorcy jest zbędnym ryzykiem RODO. Uzasadnienie: `docs/ADR-001-architektura.md` §6.

## Stan obecny

Do czasu dostarczenia plików obowiązuje awaryjny stos systemowy zdefiniowany
w `assets/css/tokens/typography.css` (`system-ui`, `-apple-system`, `Segoe UI`,
`Roboto`). Układ, rytm i wielkości są poprawne — zmienia się wyłącznie krój.

## Jak dołożyć krój

1. Wgraj tutaj pliki `.woff2` (najlepiej warianty zmienne — jeden plik na rodzinę).
2. Odkomentuj deklaracje `@font-face` w `assets/css/tokens/fonts.css`
   i popraw nazwy plików.
3. Zostaw `font-display: swap` — tekst ma być czytelny, zanim krój się wczyta.

Inter i JetBrains Mono mają licencję OFL 1.1, więc samohostowanie jest dozwolone
i bezpłatne. Jeśli ISKT ma licencjonowany krój firmowy, podmieniamy go w tym
samym miejscu.
