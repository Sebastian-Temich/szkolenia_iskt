# Eksport i migracja serwisu szkolenia.iskt.pl

Dokument dla osoby technicznej po stronie ISKT, która przeniesie serwis na SEOHost
**bez naszego udziału** — zlecenie §8 i §13 mówią wprost, że dostępu do docelowego
hostingu nie mamy i mieć nie będziemy.

Każdy krok poniżej został przejściem sprawdzony na czystym WordPressie, a nie tylko
opisany. Zapis przebiegu i zrzuty odtworzonej witryny: `qa-artifacts/isk-17-m3-eksport/`.

**Czego ten dokument nie obejmuje.** Nie uczy obsługi panelu — dodawania szkoleń,
terminów, trenerów i zmiany tekstów. To jest treść instrukcji administracji
(zadanie 14). Tutaj jest wyłącznie przeniesienie serwisu z jednego serwera na drugi.

---

## 1. Co dostajesz

Paczka to trzy pliki w jednym katalogu:

| Plik | Zawartość |
|---|---|
| `baza.sql` | zrzut bazy z adresami **już zamienionymi** na `https://szkolenia.iskt.pl` |
| `wp-content.tar.gz` | motyw, wtyczka, biblioteka mediów i pliki tłumaczeń pl_PL |
| `MANIFEST.txt` | spis zawartości, ustawienia źródła, sumy kontrolne i lista tego, czego w paczce świadomie nie ma |

Zacznij od `MANIFEST.txt`. Jest w nim liczba szkoleń, trenerów, terminów, wpisów
i plików — po imporcie porównasz ją z tym, co widzisz.

### Czego w paczce nie ma i dlaczego

- **`wp-config.php`** — niesie hasło do bazy i klucze soli. Docelowa instalacja ma własne.
- **Kont użytkowników** (tabele `wp_users`, `wp_usermeta`). W środowisku wewnętrznym
  administrator ma hasło znane każdemu, kto widział repozytorium; przeniesienie takiego
  konta oddawałoby Ci witrynę z gotową dziurą. Zamiast tego zachowujesz konto założone
  przy własnej instalacji WordPressa — ma identyfikator 1, tak samo jak konto
  w środowisku wewnętrznym, więc **autorstwo wpisów zostaje na Twoim koncie**.
- **Przechwytywania poczty z testów** (`mu-plugins`). W środowisku wewnętrznym nie ma
  serwera pocztowego, więc wiadomości z formularza szły do pliku zamiast na skrzynkę.
  Na docelowej domenie ten sam mechanizm **połknąłby prawdziwe zgłoszenia**.
- **Motywów `twenty*` i wtyczki `hello.php`** — dostarcza je sama instalacja WordPressa.
- **Plików spoza biblioteki mediów.** `wp-content/uploads` bywa katalogiem roboczym;
  do paczki wchodzą wyłącznie katalogi `RRRR/MM`, w których WordPress trzyma bibliotekę.
  Co odpadło, wypisane jest w `MANIFEST.txt` — nic nie znika po cichu.

### Dlaczego bez narzędzia migracyjnego

Paczka powstaje z `wp db export` i archiwum katalogu — bez wtyczki migracyjnej.
Decyzja zapadła w `ADR-002` (rezygnacja z płatnych zależności) i ma pokrycie
w praktyce:

- **All-in-One WP Migration** — darmowa wersja ma limit rozmiaru importu; zniesienie
  limitu jest płatne. Przy naszej ścieżce limit nie istnieje.
- **Duplicator (wersja darmowa)** — wygodniejszy dla osoby nietechnicznej, ale nie
  obsługuje bardzo dużych witryn i nie ma wsparcia. Jeśli wolisz go użyć, nic nie stoi
  na przeszkodzie; paczka opisana tutaj jest niezależna od takiego wyboru.

---

## 2. Czego wymaga serwer

| Wymaganie | Wartość | Dlaczego |
|---|---|---|
| PHP | **8.2 lub nowsze** | kod używa typów i składni z 8.x; na 8.1 wtyczka nie wystartuje |
| Baza | MySQL 8.0+ albo MariaDB 10.6+ | |
| WordPress | **6.6.2** (ta wersja, nie starsza) | zrzut bazy pochodzi z niej |
| Prefiks tabel | **`wp_`** | zrzut niesie nazwy tabel; przy innym prefiksie import się nie sklei |
| Rozszerzenia PHP | `mysqli`, `gd` albo `imagick`, `zip`, `mbstring` | `gd`/`imagick` odpowiada za miniatury zdjęć trenerów |
| Limit uploadu | co najmniej **32 MB**, zalecane 64 MB | dotyczy wgrywania zdjęć z panelu, nie samego importu |
| `max_execution_time` | co najmniej 120 s | import bazy przez `wp db import` |
| Pamięć PHP | 256 MB | |
| WP-CLI | zalecane | bez niego import trwa dłużej i wymaga phpMyAdmina — patrz §5.2 |

Limit uploadu i czas wykonania sprawdzisz po instalacji w **Narzędzia → Stan witryny
→ Info → Serwer**.

---

## 3. Zanim zaczniesz

1. **Zainstaluj czystego WordPressa 6.6.2** na docelowej domenie, z prefiksem tabel
   `wp_`. Konto administratora zakładasz swoje — hasła z naszego środowiska nie
   przenosimy (§1).
2. **Nie instaluj motywu ani wtyczek** — przyjdą z paczką.
3. Sprawdź sumy kontrolne paczki (są na końcu `MANIFEST.txt`):

   ```bash
   cd <katalog-paczki>
   grep -E '^[0-9a-f]{64} ' MANIFEST.txt | sha256sum -c
   ```

4. Jeśli witryna pod tą domeną już działa — zrób własną kopię zapasową. Skrypt importu
   robi swoją (§8), ale nie zna kopii, których nie zrobił.

---

## 4. Import — droga zalecana

Skopiuj na serwer katalog `tools/` z repozytorium i katalog z paczką, wejdź do katalogu
głównego WordPressa (tam, gdzie leży `wp-config.php`) i uruchom:

```bash
ISKT_POTWIERDZAM_NADPISANIE=tak tools/import-witryny.sh <katalog-paczki>
```

Zmienna `ISKT_POTWIERDZAM_NADPISANIE` nie jest ozdobnikiem: import **kasuje obecną
bazę** tej instalacji i bez niej skrypt odmówi startu.

Skrypt po kolei:

1. odkłada kopię stanu sprzed importu do `kopia-przed-importem/` (§8 — wycofanie);
2. wczytuje `baza.sql`;
3. rozpakowuje `wp-content.tar.gz`;
4. włącza motyw `iskt-szkolenia` i wtyczkę `iskt-szkolenia-core`;
5. przeładowuje reguły adresów (`wp rewrite flush --hard`);
6. czyści dane podręczne;
7. ustawia `blog_public = 0` — witryna jest **poza indeksem** do czasu Twojej decyzji (§7);
8. wypisuje liczby do porównania z `MANIFEST.txt`.

### Import na inną domenę niż docelowa

Chcesz najpierw postawić kopię testową, np. pod `https://test.iskt.pl`? Podaj adres
jako drugi argument — skrypt sam zamieni adresy w bazie:

```bash
ISKT_POTWIERDZAM_NADPISANIE=tak tools/import-witryny.sh <katalog-paczki> https://test.iskt.pl
```

---

## 5. Import — droga ręczna

### 5.1. Z WP-CLI

```bash
cd /ścieżka/do/wordpressa

# 1. Kopia stanu sprzed importu — PIERWSZY krok, nie ostatni.
mkdir -p kopia-przed-importem
wp db export kopia-przed-importem/baza.sql
tar -czf kopia-przed-importem/wp-content.tar.gz wp-content

# 2. Baza.
wp db import <katalog-paczki>/baza.sql

# 3. Pliki.
tar -xzf <katalog-paczki>/wp-content.tar.gz -C .

# 4. Adresy — TYLKO jeśli domena jest inna niż w MANIFEST.txt.
wp search-replace 'https://szkolenia.iskt.pl' 'https://twoja.domena' \
    --all-tables-with-prefix --precise
wp option update siteurl 'https://twoja.domena'
wp option update home    'https://twoja.domena'

# 5. Motyw, wtyczka, adresy stron.
wp theme activate iskt-szkolenia
wp plugin activate iskt-szkolenia-core
wp rewrite flush --hard
wp transient delete --all

# 6. Indeksowanie — patrz §7.
wp option update blog_public 0
```

> **Adresów w bazie nie zamieniaj edytorem tekstu ani poleceniem `sed` na pliku SQL.**
> Powiązanie szkolenia z trenerem jest zapisane jako dane serializowane
> (`a:2:{i:0;i:41;i:1;i:42;}`), w których PHP pamięta **długość każdego ciągu**.
> Zamiana adresu na krótszy lub dłuższy rozjeżdża te długości i powiązanie przestaje
> się odnajdywać — z obu stron naraz, po cichu, bez żadnego błędu.
> `wp search-replace --precise` rozpakowuje takie dane, podmienia i pakuje z powrotem.
> To samo dotyczy narzędzi typu „Search & Replace” w phpMyAdmin.

### 5.2. Bez WP-CLI (phpMyAdmin i FTP)

1. W phpMyAdmin **usuń wszystkie tabele** świeżej instalacji, potem
   **Import → wybierz `baza.sql` → Wykonaj**.
2. Przez FTP wgraj zawartość `wp-content.tar.gz` do `wp-content`, nadpisując pliki.
3. **Jeśli domena jest inna niż `szkolenia.iskt.pl`** — nie zamieniaj adresów
   w phpMyAdmin. Zainstaluj wtyczkę **Better Search Replace** (darmowa), zaznacz
   *„Uruchom jako test”*, potem właściwe uruchomienie z zaznaczonym
   **„Obsługa danych serializowanych”**. Po zamianie wtyczkę usuń.
4. Zaloguj się do panelu (Twoje konto z instalacji), wejdź w **Wygląd → Motywy**
   i włącz **iskt-szkolenia**, potem **Wtyczki** i włącz **iskt-szkolenia-core**.
5. **Ustawienia → Bezpośrednie odnośniki → Zapisz zmiany.** Ten krok wygląda na pusty
   — nic nie zmieniasz — ale to on przepisuje reguły adresów. Bez niego strony szkoleń
   i trenerów dają 404 po wejściu z zewnątrz.
6. **Ustawienia → Czytanie** — upewnij się, że *„Proś wyszukiwarki o nieindeksowanie”*
   jest **zaznaczone** do czasu sprawdzenia witryny (§7).

---

## 6. Sprawdzenie po imporcie

Import bez sprawdzenia nie jest odtworzeniem. To jest lista, którą przeszliśmy sami
i którą warto przejść jeszcze raz na docelowym serwerze — zajmuje kilka minut.

Sprawdzenia można też uruchomić automatycznie, jeśli masz Dockera po swojej stronie:
`tools/test-odtworzenia.sh <katalog-paczki>`.

| # | Co otworzyć | Co ma być widać |
|---|---|---|
| 1 | strona główna | sekcje z przełącznikiem odbiorcy, nie lista wpisów |
| 2 | `/szkolenia/` | katalog z kaflami, tytuł katalogu, filtry kategorii i formy |
| 3 | `/szkolenia/?szukaj=termowizja` | jedno szkolenie — fraza jest **tylko w opisie**, więc trafienie dowodzi, że przeszukiwany jest opis, a nie sam tytuł |
| 4 | `/szkolenia/?szukaj=` + wymyślona fraza | komunikat pustego wyniku, nie pusta strona |
| 5 | `/szkolenia/page/2/` | druga strona katalogu — paginacja |
| 6 | strona dowolnego szkolenia | **nazwiska trenerów** i lista terminów |
| 7 | profil tego trenera (`/trenerzy/...`) | **to samo szkolenie na jego liście** — powiązanie musi być widoczne z obu stron |
| 8 | strona szkolenia z terminem przeszłym | termin zakończony **nie stoi** wśród nadchodzących |
| 9 | daty terminów | po polsku („30 września 2026”), nie „30 September 2026” — jeśli po angielsku, patrz §10 |
| 10 | zdjęcie trenera | zdjęcie, nie pusta ramka |
| 11 | **odśwież stronę szkolenia (F5) i wejdź w nią z pustej karty** | ta sama strona, nie 404 — to sprawdza reguły adresów |
| 12 | `/zgloszenie/` | formularz z listą szkoleń do wyboru |
| 13 | stopka i nagłówek | teksty ustawione w panelu, nie domyślne |
| 14 | **Wygląd → Widżety** i **Szkolenia → Teksty globalne** | dane kontaktowe — patrz ostrzeżenie niżej |

> **Adresy zastępcze.** `MANIFEST.txt` ma sekcję *„DO PRZEJRZENIA PRZED PUBLIKACJĄ”*
> z listą adresów z domen zastrzeżonych do przykładów (`example.test`, `example.com`).
> Powstały w środowisku wewnętrznym jako treść zastępcza. Nie są usterką techniczną
> i nie zablokują niczego — ale **nie mogą zostać na publicznej witrynie jako dane
> kontaktowe ISKT**. Mechanizm usuwania danych demonstracyjnych (§11) obejmuje wpisy
> i media, ale nie widżety i nie teksty globalne; te zmieniasz ręcznie z panelu.

Liczby porównaj z `MANIFEST.txt` — sekcja *ZAWARTOŚĆ*.

---

## 7. Indeksowanie przez wyszukiwarki

Środowisko wewnętrzne jest wyłączone z indeksowania (§7 zlecenia). **Ten stan nie
przechodzi na Twoją domenę jako spadek** — import ustawia `blog_public = 0` z własnego
powodu: witryna w trakcie przenoszenia bywa niekompletna i wyszukiwarka nie powinna
jej wtedy oglądać.

Włączenie indeksowania jest **osobną, świadomą decyzją ISKT** i naszą czynnością nie
jest. Zrób to dopiero wtedy, gdy przejdziesz listę z §6 i uznasz witrynę za gotową:

**Ustawienia → Czytanie → odznacz „Proś wyszukiwarki o nieindeksowanie tej witryny”
→ Zapisz zmiany.**

Z wiersza poleceń:

```bash
wp option update blog_public 1
```

Sprawdź, że zadziałało — w źródle strony głównej **nie może** być `noindex`:

```bash
curl -s https://szkolenia.iskt.pl/ | grep -c 'noindex'   # ma zwrócić 0
```

Osobno i niezależnie: widoki filtrowane i wyniki wyszukiwania w katalogu mają własną
regułę `noindex` i tak zostaje. Nie jest to pomyłka — uzasadnienie w
`docs/KATALOG-SZKOLEN.md` §5. Ustawienia z panelu nie nadpisujemy.

---

## 8. Wycofanie wdrożenia

`tools/import-witryny.sh` **zanim cokolwiek nadpisze** odkłada stan sprzed importu do
`kopia-przed-importem/` w katalogu głównym WordPressa. Powrót:

```bash
ISKT_POTWIERDZAM_NADPISANIE=tak tools/wycofaj-import.sh kopia-przed-importem
```

Ręcznie to trzy polecenia:

```bash
wp db reset --yes
wp db import kopia-przed-importem/baza.sql
rm -rf wp-content && tar -xzf kopia-przed-importem/wp-content.tar.gz -C .
wp rewrite flush --hard
```

`wp-content` kasujemy w całości zamiast rozpakowywać na wierzch: po imporcie leżą w nim
pliki, których w kopii nie ma, a wycofanie ma dać stan sprzed importu, nie sumę obu.

**Kopię przenieś w bezpieczne miejsce, gdy witryna zacznie działać** — `kopia-przed-importem/`
leży w katalogu WordPressa i przy kolejnym imporcie zostanie nadpisana.

Sprawdzone: po wycofaniu instalacja wraca do motywu `twentytwentyfour`, bez wtyczki,
bez katalogu szkoleń, z Twoim kontem administratora nietkniętym.

---

## 9. HTTPS i poczta

### HTTPS

Certyfikat wystaw **przed** importem albo zaraz po nim, a adresy w bazie ustaw od razu
na `https://` — zmiana `http` → `https` później to kolejne `wp search-replace`
i kolejne miejsce na pomyłkę.

```bash
wp option get siteurl    # ma zaczynać się od https://
wp option get home
```

Wymuszenie HTTPS ustaw po stronie hostingu (panel SEOHost) albo w `.htaccess`.
W `wp-config.php` przy hostingu z proxy bywa potrzebne:

```php
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
    $_SERVER['HTTPS'] = 'on';
}
```

Po włączeniu HTTPS przejdź jeszcze raz punkty 1–3 z §6 — mieszana treść (obrazek po
`http://` na stronie po `https://`) objawia się brakiem zdjęć, nie komunikatem.

### Poczta

Formularz zgłoszeniowy wysyła wiadomość przez `wp_mail()` na adres z **Szkolenia →
Teksty globalne → Dane kontaktowe → Adres e-mail** (domyślnie `szkolenia@iskt.pl`).

Domyślna wysyłka PHP z hostingu współdzielonego trafia do spamu — nadawcą jest wtedy
adres serwera, a nie domena iskt.pl. Ustaw **SMTP autoryzowanego nadawcy w domenie
iskt.pl**: wtyczka typu WP Mail SMTP (darmowa) albo stałe w `wp-config.php`, zależnie
od tego, co daje SEOHost.

Do sprawdzenia po stronie DNS: **SPF, DKIM i DMARC** dla nadawcy. Bez nich poprawnie
wysłana wiadomość i tak bywa odrzucana. Tego nie dało się sprawdzić przed migracją —
patrz §11.

**Nie testuj wysyłki na `szkolenia@iskt.pl` z naszej strony** — dostępu do tej skrzynki
nie mamy. Pierwszy prawdziwy test dostarczenia wykonaj sam, po migracji.

---

## 10. Kiedy coś nie działa

| Objaw | Przyczyna | Co zrobić |
|---|---|---|
| Strony szkoleń i trenerów dają **404**, panel działa | reguły adresów nie zostały przepisane | **Ustawienia → Bezpośrednie odnośniki → Zapisz zmiany** albo `wp rewrite flush --hard` |
| Profil trenera **nie pokazuje żadnych szkoleń**, choć na szkoleniu widać trenera | dane serializowane rozjechały się przy zamianie adresów zwykłym `sed` | wycofaj import (§8) i powtórz zamianę przez `wp search-replace --precise` |
| Daty po angielsku („30 September 2026”) | brak plików tłumaczeń | pliki są w paczce w `wp-content/languages`; sprawdź, czy się rozpakowały, albo `wp language core install pl_PL --activate` |
| **Puste ramki zamiast zdjęć** | katalog `uploads` nie doszedł albo ma złe uprawnienia | sprawdź `wp-content/uploads` i prawa zapisu dla użytkownika serwera WWW |
| Zdjęcia są, ale **miniatury nie** | brak `gd`/`imagick` w PHP | włącz rozszerzenie, potem `wp media regenerate` |
| **Biała strona** po włączeniu wtyczki | PHP starsze niż 8.2 | podnieś wersję PHP w panelu hostingu |
| Panel prosi o **aktualizację bazy** | wersja WordPressa inna niż 6.6.2 | zgódź się; potem przejdź §6 |
| Zgłoszenia z formularza **nie docierają** | brak SMTP albo SPF/DKIM | §9 |

---

## 11. Sprawy, które nie należą do migracji

- **Dane demonstracyjne.** Katalog przyjeżdża z treściami oznaczonymi „Demo —”.
  Usuwa je jednym działaniem ekran **Szkolenia → Dane demonstracyjne** (zadanie 12).
  Migracja ma ten mechanizm **przenieść w stanie działającym**, a nie użyć — o tym,
  kiedy skasować dane, decyduje ISKT. Widżetów i tekstów globalnych ten mechanizm
  nie obejmuje; te poprawiasz ręcznie (§6, punkt 14).
- **Obsługa panelu** — dodawanie szkoleń, terminów, trenerów, zmiana tekstów.
  Instrukcja administracji, zadanie 14.
- **Testy, których nie dało się wykonać przed migracją** — pełna lista w
  `docs/MATERIALY-I-DECYZJE.md` §4. Najkrócej: dostarczenie wiadomości na prawdziwą
  skrzynkę, poprawność SPF/DKIM/DMARC, zachowanie na infrastrukturze SEOHost
  i pomiar wydajności na niej.

---

## 12. Zrobienie paczki od nowa

Paczka nie jest jednorazowa — tym samym skryptem zrobisz kopię działającej witryny
albo nową paczkę po zmianach.

Ze środowiska wewnętrznego (u nas, `wp-env`):

```bash
tools/eksport-witryny.sh export https://szkolenia.iskt.pl
```

Z dowolnej instalacji WordPressa (np. z SEOHost, przez SSH):

```bash
ISKT_WP=wp ISKT_SHELL= tools/eksport-witryny.sh /ścieżka/na/paczkę https://docelowa.domena
```

Skrypt na koniec **sam przegląda to, co zbudował** i kończy się błędem, gdy znajdzie
adres środowiska wewnętrznego, klucz soli, dane dostępowe do bazy albo tabelę z kontami.
Wymóg §8 jest w ten sposób sprawdzany, a nie deklarowany.

Skrypt **przerwie pracę**, gdy w źródle jest więcej niż jedno konto użytkownika — paczka
kont nie przenosi, a to zabezpieczenie pilnuje, żeby uproszczenie nie zgubiło autorów
po cichu. Wtedy albo usuń zbędne konta, albo przenieś je świadomie osobnym eksportem
tabel `wp_users` i `wp_usermeta` — **ze zmianą haseł po imporcie**.
