# Motyw ISKT Szkolenia — siatka, typografia i komponenty wspólne

Data: 2026-09-09 · ISK-17 zadanie 3 (M1)
Dotyczy: `wp-content/themes/iskt-szkolenia/`

Ten dokument jest **umową nazewniczą**. Zadania 4–8 budują widoki na tych
klasach i ich nie zmieniają. Zmiana nazwy klasy po tym dokumencie to zmiana
kontraktu — wymaga poprawienia dokumentu i wszystkich miejsc użycia.

---

## 1. Zasady

1. **Motyw to wyłącznie prezentacja.** Nie rejestruje typów treści, taksonomii
   ani pól — to należy do wtyczki (§6 zlecenia, ADR-001 §1). Wyłączenie motywu
   nie może zabrać danych katalogu.
2. **Zero wartości wprost.** Każdy kolor, odstęp, promień, cień i stopień pisma
   pochodzi ze zmiennej CSS z `assets/css/tokens/`. Jeśli czegoś brakuje, dokłada
   się token, a nie liczbę w regule.
3. **Bez kroku budowania.** Zwykły CSS i zwykły JavaScript. Bez `npm`, bez Sass,
   bez `node_modules` (ADR-002 §1).
4. **Bez zewnętrznych usług.** Fonty samohostowane, ikony wbudowane w dokument.
   Żadnego CDN.
5. **JavaScript wyłącznie ulepsza.** Każdy widok musi być użyteczny, gdy skrypt
   się nie wykona.

## 2. Pliki

| Plik | Odpowiada za |
|---|---|
| `assets/css/tokens/*.css` | zmienne systemu projektowego — nie edytujemy ich pod pojedynczy widok |
| `assets/css/uklad.css` | kontener, sekcje, siatka, `stack`/`cluster`, klasy pomocnicze, ograniczenie ruchu |
| `assets/css/typografia.css` | skala nagłówków, role tekstu, `.iskt-prose` dla treści z edytora |
| `assets/css/komponenty.css` | przycisk, karta, plakietka, pole formularza, komunikat, paginacja |
| `assets/css/szkielet.css` | nagłówek, nawigacja, stopka, drobne elementy szablonów |
| `assets/css/sekcje.css` | sekcje strony głównej i oprawa bloków rdzenia (zadanie 4) |
| `theme.json` | paleta, skala pisma i odstępy dla edytora — wskazują na te same tokeny (zadanie 4) |
| `patterns/*.php` | wzorce sekcji strony głównej (zadanie 4) — patrz `STRONA-GLOWNA.md` |
| `assets/js/nawigacja.js` | menu mobilne, podmenu, stan nagłówka po przewinięciu |
| `assets/js/odbiorca.js` | przyspieszenie przełącznika odbiorcy; strona działa bez niego (zadanie 4) |
| `inc/ikony.php` | wbudowane ikony SVG (`iskt_icon()`, `iskt_the_icon()`) |
| `inc/nawigacja.php` | zawartość zastępcza menu, `aria-current`, nazwa i hasło serwisu |
| `inc/szablony.php` | nagłówek listy, metadane wpisu, paginacja |

Kolejność wczytywania utrwala `functions.php` przez zależności `wp_enqueue_style` —
każdy arkusz zależy od wszystkich poprzednich, więc kaskada nie zależy od kolejności
w kolejce. Ta sama lista zasila podgląd w edytorze blokowym (`add_editor_style`).

## 3. Nazewnictwo

`iskt-blok__element--wariant`, po angielsku, z prefiksem `iskt-`.

- **prefiks** — motyw dzieli stronę z wtyczką i blokami rdzenia WordPressa;
  prefiks usuwa ryzyko kolizji nazw;
- **angielski** — tokeny, zmienne CSS i klasy WordPressa (`current-menu-item`,
  `screen-reader-text`) też są po angielsku, mieszanie języków w jednym selektorze
  czytałoby się gorzej. Dokumentacja i teksty interfejsu pozostają polskie;
- **stan** zapisujemy klasą `is-*` (`is-open`, `is-scrolled`) — łatwo odróżnić
  stan przełączany skryptem od wariantu wyglądu.

## 4. Układ

### Kontener

```html
<div class="iskt-container">…</div>              <!-- 1200 px -->
<div class="iskt-container iskt-container--narrow">…</div>   <!-- 46rem, tekst -->
<div class="iskt-container iskt-container--wide">…</div>
```

### Sekcja

```html
<section class="iskt-section iskt-section--muted">
  <div class="iskt-container">
    <div class="iskt-section__head iskt-section__head--center">
      <p class="iskt-eyebrow">Katalog</p>
      <h2 class="iskt-title-lg">Nasze szkolenia</h2>
      <p class="iskt-lead">Jedno zdanie wsparcia.</p>
    </div>
    …
  </div>
</section>
```

Warianty tła: `--subtle`, `--muted` (zielona poświata), `--wash` (gradient),
`--dark` (pasmo atramentowe; kolory tekstu i odnośników przełączają się same).
Warianty rytmu: `--tight`, `--flush-top`, `--flush-bottom`.

Strony w systemie projektowym przeplatają tła: biel → zieleń → biel → atrament → biel.

### Siatka

```html
<div class="iskt-grid iskt-grid--3">…</div>
<div class="iskt-grid iskt-grid--auto" style="--iskt-grid-min: 20rem">…</div>
<div class="iskt-grid iskt-grid--sidebar">…</div>   <!-- treść + kolumna boczna -->
```

Odstęp zmienia `--iskt-grid-gap`. `--2/--3/--4` idą od jednej kolumny na telefonie,
przez dwie od 768 px, po docelową liczbę od 1024 px.

Do prostszych układów: `.iskt-stack` (pion, odstęp `--iskt-stack-space`,
skróty `--tight` i `--loose`) oraz `.iskt-cluster` (poziom, zawijanie,
warianty `--center` i `--between`).

### Punkty łamania

| Szerokość | Zapis | Co się zmienia |
|---|---|---|
| od 360 px | baza | jedna kolumna, menu w panelu rozwijanym |
| od 768 px | `min-width: 48em` | dwie kolumny siatek, stopka na dwie kolumny |
| od 1024 px | `min-width: 64em` | nawigacja pozioma, docelowa liczba kolumn |
| 1440 px | — | kontener osiąga 1200 px i przestaje rosnąć |

360 / 768 / 1440 px to szerokości wskazane w §7 zlecenia. 1024 px dołożyliśmy jako
moment przejścia nawigacji — na tablecie w poziomie menu pozioma się mieści.

## 5. Typografia

Nagłówki `h1`–`h6` mają skalę domyślnie. Klasy `.iskt-title-xl|lg|md|sm|xs`
pozwalają **rozdzielić poziom nagłówka od jego wielkości** — w sekcji zawsze
używamy `h2`, a wielkość dobieramy klasą. To wymóg dostępności, nie estetyki.

Role tekstu: `.iskt-eyebrow` (nadokreślnik nad tytułem), `.iskt-lead` (zdanie
wsparcia), `.iskt-muted`, `.iskt-secondary`, `.iskt-mono` (akcent techniczny —
domena, dane), `.iskt-balance`, `.iskt-text-sm`, `.iskt-text-xs`.

`.iskt-prose` obejmuje wszystko, co wychodzi z `the_content()`.

## 6. Komponenty

### Przycisk

```html
<a class="iskt-button iskt-button--primary" href="…">
  Zapisz się
  <svg class="iskt-icon iskt-button__icon iskt-button__icon--forward">…</svg>
</a>
```

Warianty: `--primary`, `--secondary` (obrys), `--ghost`, `--inverse` (na ciemnym
paśmie). Rozmiary: `--sm`, `--lg`, `--block`. Sam `.iskt-button` bez wariantu to
przycisk neutralny — w widokach zawsze podajemy wariant.

Wysokość minimalna 44 px, czyli wygodny cel dotykowy. Stan zablokowany zapisujemy
atrybutem `disabled` lub `aria-disabled`, nigdy samą klasą.

### Karta

```html
<article class="iskt-card iskt-card--interactive">
  <div class="iskt-card__media"><img …></div>
  <span class="iskt-card__icon">…</span>
  <p class="iskt-card__meta"><span>8 godzin</span><span>Online</span></p>
  <h3 class="iskt-card__title"><a class="iskt-card__link" href="…">Tytuł</a></h3>
  <p class="iskt-card__body">Opis.</p>
  <div class="iskt-card__footer">
    <span class="iskt-badge iskt-badge--accent">Dofinansowanie</span>
    <span class="iskt-card__more">Zobacz szczegóły</span>
  </div>
</article>
```

**Reguła klikalnej karty.** Prawdziwym odnośnikiem jest wyłącznie
`.iskt-card__link` w tytule; jego obszar kliknięcia rozciąga się na całą kartę
przez `::after`. Nie dodajemy drugiego odnośnika prowadzącego w to samo miejsce —
klawiatura zatrzymywałaby się dwa razy na tym samym celu. Odnośnik, który
naprawdę prowadzi gdzie indziej (np. kategoria), umieszczamy w `.iskt-card__meta`,
`.iskt-card__footer` albo oznaczamy klasą `.iskt-card__above`.

Warianty: `--interactive`, `--flat`, `--muted`, `--dark`, `--padded`.

### Plakietka

```html
<span class="iskt-badge iskt-badge--success">Zapisy otwarte</span>
```

Warianty: domyślny (zielony jasny), `--accent`, `--solid`, `--outline`,
`--neutral`, `--success`, `--warning`, `--error`, `--info`, `--dark`.

Plakietka jest **etykietą, nie kontrolką** — jeśli ma coś przełączać, użyj
przycisku lub odnośnika (filtry katalogu, zadanie 6).

### Pole formularza

```html
<div class="iskt-field">
  <label class="iskt-field__label" for="imie">
    Imię i nazwisko <span class="iskt-field__required" aria-hidden="true">*</span>
  </label>
  <input class="iskt-field__control" id="imie" name="imie" type="text"
         required aria-describedby="imie-hint">
  <p class="iskt-field__hint" id="imie-hint">Tak, jak ma się pojawić na zaświadczeniu.</p>
</div>
```

Zasady, których zadania 9–10 nie zmieniają:

- etykieta **zawsze widoczna** i powiązana z kontrolką przez `for`/`id`;
- znak zastępczy (placeholder) nigdy nie zastępuje etykiety;
- błąd pola: `aria-invalid="true"` na kontrolce, treść błędu w
  `.iskt-field__error` powiązanym przez `aria-describedby`, wariant
  `.iskt-field--invalid` na opakowaniu;
- pole wyboru i opcji: `.iskt-field--check` (etykieta obok kontrolki).

Dodatkowo: `.iskt-form`, `.iskt-form__actions`, `.iskt-fieldset`
z `.iskt-fieldset__legend`.

### Komunikat i paginacja

`.iskt-notice` z wariantami `--success`, `--warning`, `--error` — komunikaty
formularza i pustych wyników. `.iskt-pagination` opakowuje wynik
`the_posts_pagination()`; wygodniej wywołać `iskt_pagination()`.

### Ikony

```php
iskt_the_icon( 'arrow-right', 'iskt-button__icon' );
```

Dostępne: `menu`, `close`, `chevron-down`, `arrow-right`, `arrow-left`, `search`,
`mail`, `phone`, `map-pin`, `calendar`, `clock`, `graduation`, `leaf`, `check`.

Ikona ma zawsze `aria-hidden="true"` — **znaczenie niesie tekst obok niej**,
także wtedy, gdy jest ukryty klasą `.screen-reader-text`.

## 7. Nagłówek, nawigacja, stopka

Nagłówek jest przyklejony, półprzezroczysty, po przewinięciu dostaje klasę
`.is-scrolled` i cień. Logo bierzemy z Personalizacji (`custom-logo`); do czasu
wgrania własnego pliku wyświetlamy `assets/img/logo-iskt.png`. Znak i nazwa
tworzą **jeden** odnośnik do strony głównej.

Nawigacja pochodzi wyłącznie z menu `primary`. Motyw nie wypisuje własnych
odnośników. Gdy menu nie ma, pokazujemy opublikowane strony, a administratorowi
odnośnik do ekranu menu — nigdy `#`.

**Wezwanie do działania w menu.** Pozycji menu, która ma wyglądać jak główny
przycisk, właściciel nadaje w panelu klasę CSS `iskt-cta` (Wygląd → Menu →
Opcje ekranu → Klasy CSS). Dzięki temu przycisk jest edytowalny z panelu i nie
jest wpisany na stałe w szablon.

**Menu na wąskim ekranie.** Panel jest rozwinięty, dopóki skrypt go nie zwinie:
`nawigacja.js` dokłada klasę `iskt-site-header--js`, odsłania przycisk „Menu”
i przejmuje `aria-expanded`. Bez skryptu nawigacja nadal działa. Escape zamyka
i wraca fokusem na przycisk, kliknięcie poza nagłówkiem zamyka.

Stopka: kolumna marki + obszar widżetów `iskt-footer` („Stopka — obszar treści”),
poniżej rok, nazwa serwisu, menu `footer` i domena pismem maszynowym. Cała treść
pochodzi z panelu.

## 8. Dostępność — co jest już zapewnione

| Wymóg §7 | Realizacja |
|---|---|
| widoczny fokus | `:focus-visible` z 3-pikselową obwódką w `tokens/base.css`; na ciemnym tle obwódka przełącza się na jaśniejszą zieleń |
| obsługa klawiaturą | odnośnik „Przejdź do treści”, przełącznik menu na `<button>` z `aria-expanded`, podmenu na przyciskach, Escape zamyka |
| semantyczne nagłówki | jeden `h1` na widok; wielkość niezależna od poziomu dzięki `.iskt-title-*` |
| etykiety pól | `.iskt-field__label` z `for`/`id`, opis przez `aria-describedby` |
| responsywność | 360 / 768 / 1440 px, cele dotykowe 44 px |
| ograniczenie ruchu | `prefers-reduced-motion` wyłącza przejścia i animacje |
| bieżąca pozycja | `aria-current="page"` w menu |

Pełny przegląd dostępności to zadanie 11 — tutaj zapewniamy fundament, nie
zamykamy tematu.
