# Formularz zgłoszeniowy

Zadanie 9 (M2) zlecenia ISK-17, §5. Dokument opisuje, **jak formularz działa**,
**czego ochrona antyspamowa nie obejmuje** i **co pozostaje do rozstrzygnięcia
przez ISKT**. Jest adresowany do QA, przeglądu bezpieczeństwa i do osoby, która
będzie wdrażać serwis na docelowym hostingu.

## 1. Gdzie formularz mieszka

Formularz jest **blokiem wtyczki** `iskt/formularz-zgloszeniowy`, a nie szablonem
motywu. Ta sama zasada co przy modelu danych (§6): zmiana motywu nie może zabrać
właścicielowi możliwości przyjmowania zgłoszeń.

Wtyczka zakłada przy aktywacji stronę **`/zgloszenie/`** z tym blokiem i zapamiętuje
jej identyfikator w opcji `iskt_strona_zgloszenia`. Dzieje się to **dokładnie raz na
instalację** (znacznik `iskt_strona_zgloszenia_zalozona`) — usunięcie tej strony przez
właściciela jest jego decyzją i nie zostaje cofnięte przy następnym wejściu do panelu.
Instalacje sprzed tego zadania mają wtyczkę już aktywną, więc strona powstaje u nich
przy pierwszym wejściu do panelu (`admin_init`).

Blok znajduje się także w sekcji kontaktowej strony głównej (wzorzec
`patterns/kontakt.php`, z wyłączonym własnym nagłówkiem, bo sekcja ma swój).
Blok wolno wstawić w dowolnym miejscu — jest oznaczony jako `multiple: false`,
żeby dwa formularze nie trafiły na jedną stronę z tymi samymi `id` pól.

| Element | Plik |
|---|---|
| Walidacja, antyspam, wysyłka, adresy | `wp-content/plugins/iskt-szkolenia-core/includes/formularz.php` |
| Znaczniki formularza | `wp-content/plugins/iskt-szkolenia-core/includes/bloki/formularz.php` |
| Definicja bloku | `wp-content/plugins/iskt-szkolenia-core/blocks/formularz-zgloszeniowy/block.json` |
| Napisy | `includes/teksty.php`, grupa `formularz` |
| Oprawa graficzna | motyw: `assets/css/komponenty.css` (pola), `assets/css/sekcje.css` (układ) |

## 2. Pola i ścieżka odwiedzającego

Pola z §5: rodzaj odbiorcy (osoba / firma), imię i nazwisko, e-mail, telefon
(opcjonalny), nazwa firmy (opcjonalna), szkolenie lub obszar zainteresowania,
wybrany termin (jeśli dotyczy), wiadomość.

**Wymagane są trzy pola: imię i nazwisko, e-mail, wiadomość.** Reszta jest opcjonalna.
Zgłoszenie bez wskazania oferty jest w pełni dopuszczalne — pierwsza pozycja listy
szkoleń to „Zapytanie ogólne”, zaznaczona na starcie (§5 wprost tego wymaga).

**Zgody marketingowej nie ma i nie będzie** — §5 zabrania uzależniania wysłania
zwykłego zapytania od takiej zgody.

Szkolenie i obszar zainteresowania to **jedna lista**, nie dwie kontrolki: dwa osobne
pola zmuszałyby odwiedzającego do rozstrzygania, którego użyć. Lista ma dwie grupy —
szkolenia z katalogu i obszary (kategorie). Wartości mają postać `s-<id szkolenia>`
i `k-<id kategorii>`; cokolwiek innego przychodzi w żądaniu, jest traktowane jak
zapytanie ogólne.

Pole terminu **pokazuje się tylko wtedy**, gdy wybrane szkolenie ma nadchodzące
terminy. Bez JavaScriptu lista terminów nie przeładowuje się po zmianie szkolenia —
i nie musi: kontekst przychodzi z adresu ze strony szkolenia, a zapytanie ogólne
terminu nie potrzebuje.

### Wejście ze strony szkolenia

`template-parts/szkolenie/panel.php` buduje adres funkcją `iskt_adres_zgloszenia()`:

- przycisk **„Zapytaj o to szkolenie”** → `/zgloszenie/?iskt_zgl_szkolenie=<id>`;
- odnośnik **„Zapytaj o ten termin”** przy każdym terminie z otwartymi zapisami →
  `…&iskt_zgl_termin=<id>`.

Terminy z zamkniętymi zapisami nie dostają odnośnika — odesłanie do formularza
sugerowałoby dostępność, której nie ma (§4.4).

Parametry nazywają się `iskt_zgl_szkolenie` i `iskt_zgl_termin`, a **nie**
`iskt_szkolenie`. Powód jest konkretny: `iskt_szkolenie` to nazwa publicznego typu
treści, którą WordPress rejestruje jako zmienną zapytania — adres
`/zgloszenie/?iskt_szkolenie=131` kończy się stroną „nie znaleziono”, bo WordPress
szuka wtedy szkolenia o nazwie „131”. Sprawdzone w `wp-env`, nie wywnioskowane.

Gdy strony `/zgloszenie/` nie ma, `iskt_adres_zgloszenia()` wraca do sekcji
kontaktowej strony głównej. Zaślepki `#` nie ma na żadnej ścieżce (§11 pkt 10).

## 3. Walidacja jest serwerowa

`required` i `type="email"` w znacznikach są udogodnieniem dla odwiedzającego, nie
zabezpieczeniem — żądanie POST da się złożyć bez przeglądarki. Rozstrzyga
`iskt_waliduj_zgloszenie()`, sprawdzana testem `php tests/run.php`.

- **Same spacje nie są wypełnieniem.** Przechodzą przez `required` przeglądarki;
  serwer je odrzuca.
- **Adres pominięty i adres z literówką to dwa różne komunikaty** — „uzupełnij”
  i „popraw”. Jeden wspólny komunikat kazałby szukać po omacku.
- **Dane wpisane przez odwiedzającego wracają do pól przy każdym błędzie**, łącznie
  z niepoprawnym adresem e-mail. To wymóg odbioru, nie wygoda: przy ośmiu polach
  powtórne wypełnianie jest najczęstszym powodem porzucenia formularza. Dlatego
  adres przechodzi najpierw przez `sanitize_text_field()`, a dopiero potem przez
  `sanitize_email()` — inaczej literówka wracałaby jako puste pole.
- Błąd pola: `aria-invalid="true"` na kontrolce, treść w `.iskt-field__error`
  powiązanym przez `aria-describedby`, podsumowanie nad formularzem w `role="alert"`.

## 4. Wysyłka

Odbiorcą jest `iskt_tekst( 'kontakt_email' )` — domyślnie `szkolenia@iskt.pl`.
**Adresu nie ma w kodzie**: §4.7 wymaga jednego źródła danych kontaktowych,
a właściciel ma móc przekierować zgłoszenia bez pomocy programisty
(Szkolenia → Teksty serwisu → Dane kontaktowe).

- **Nadawcy (`From`) nie ustawiamy.** Domyślny nadawca WordPressa należy do domeny
  serwisu, więc przechodzi kontrolę SPF i DMARC tej domeny. Podstawienie adresu
  zgłaszającego wyglądałoby wygodnie w skrzynce, a w praktyce zatrzymywałoby
  wiadomość na SPF/DMARC **jego** domeny.
- **Adres zgłaszającego trafia do `Reply-To`**, więc odpowiedź z klienta pocztowego
  idzie tam, gdzie powinna. Imię traci znaki złamania wiersza, cudzysłowy i nawiasy
  kątowe — bez tego dałoby się nimi dopisać kolejne nagłówki (wstrzyknięcie do
  nagłówków poczty). Sprawdzone testem.
- Treść jest **zwykłym tekstem**. Pochodzi od obcej osoby; tekst bez znaczników nie
  ma jak niczego wykonać ani rozjechać widoku w programie pocztowym odbiorcy.
- Wiadomość niesie kontekst: rodzaj odbiorcy, szkolenie, termin, adres strony,
  z której wysłano zapytanie. **Etykiety pochodzą z tego samego rejestru, co
  etykiety pól** — zmiana napisu w panelu zmienia go w obu miejscach.
- **Kontekst opisuje wyłącznie to, co odwiedzający mógł wybrać na stronie.**
  Identyfikatory szkolenia i terminu przychodzą z adresu oraz z pól formularza,
  więc da się je podmienić. `iskt_kontekst_zgloszenia()` nazywa w wiadomości tylko
  szkolenie **opublikowane** (wycofane zniknęło z oferty — §11 pkt 5) oraz termin
  opublikowany i **przypisany właśnie do tego szkolenia**. Podmieniona wartość nie
  daje pustej wiadomości: kontekst po prostu znika, a zgłoszenie idzie dalej jako
  zapytanie ogólne. Bez tej reguły ISKT odpisywałoby na termin, którego nikt nie
  proponował. Sprawdzone testem jednostkowym.
- **Potwierdzenie pojawia się dopiero po przyjęciu wiadomości.** `wp_mail()`
  zwracające `false` daje komunikat `formularz_blad_wysylki`, nigdy podziękowania.
  Prototyp z załącznika pokazywał podziękowanie bez wysyłki — to zachowanie nie
  zostało powtórzone i jest osobnym przypadkiem testowym.
- Po udanej wysyłce następuje przekierowanie (POST → przekierowanie → GET), więc
  odświeżenie strony z podziękowaniem nie wysyła zgłoszenia drugi raz. Znacznik
  potwierdzenia w adresie jest jednorazowy i zapisany przy wysyłce — samo dopisanie
  `?iskt_wyslano=1` do adresu **nie** wywołuje podziękowania.

### Co musi zrobić wdrożenie

1. Sprawdzić, że hosting dostarcza pocztę z `wordpress@<domena>` albo ustawić własną
   skrzynkę filtrem `iskt_naglowki_zgloszenia` (albo wtyczką SMTP hostingu).
2. Ustawić SPF/DKIM/DMARC dla nadawcy w domenie `iskt.pl` (DNS — poza zakresem §13).
3. Potwierdzić, że `szkolenia@iskt.pl` odbiera pocztę i że zgłoszenia nie wpadają
   do spamu.

## 5. Ochrona antyspamowa — co zatrzymuje, a czego nie

ADR-002 wyklucza Turnstile, reCAPTCHĘ i inne usługi zewnętrzne. Ochrona jest
zbudowana z tego, co daje WordPress. **To celowo nie jest zabezpieczenie klasy
CAPTCHA** i nie należy go tak przedstawiać właścicielowi.

| Warstwa | Co zatrzymuje | Czego NIE zatrzymuje |
|---|---|---|
| `nonce` (`iskt_zgloszenie`) | żądania składane bez pobrania formularza — najprostsze skrypty masowe, wysyłkę z obcej strony (CSRF) | automat, który najpierw pobiera stronę i odczytuje `nonce` |
| Pułapka (`iskt_adres_www`) | automaty wypełniające każde pole, jakie znajdą w dokumencie | automat czytający CSS lub napisany pod ten konkretny formularz |
| Minimalny czas wypełniania (3 s, znacznik podpisany `wp_hash()`) | wysyłkę natychmiast po wczytaniu strony; podrobienie starszej daty (podpis) | automat, który po prostu odczeka cztery sekundy |
| Odstęp między zgłoszeniami z jednego IP (60 s) | seryjną wysyłkę z jednego łącza | rozproszoną wysyłkę z wielu adresów; nie chroni też przed pierwszym zgłoszeniem |

Znane, świadomie przyjęte ograniczenia:

- **Limit liczy tylko zgłoszenia przyjęte.** Zgłoszenie odrzucone przez walidację nie
  uruchamia licznika — inaczej literówka w adresie e-mail blokowałaby poprawkę na
  minutę. Skutek: automat, który wysyła same niepoprawne dane, nie jest limitowany
  (ale też niczego nie dostarcza).
- **Adres IP bierzemy wyłącznie z `REMOTE_ADDR`.** Nagłówka `X-Forwarded-For` nie
  czytamy, bo ustawia go klient — automat podmieniałby go przy każdym żądaniu i limit
  przestałby cokolwiek znaczyć. **Za odwrotnym proxy albo CDN wszyscy odwiedzający
  mają wtedy ten sam adres**, więc limit robi się wspólny; przy wdrożeniu na taką
  infrastrukturę należy przeliczyć wartość filtrem `iskt_odstep_zgloszen`.
- **Adres IP nie jest przechowywany.** Do odmierzenia odstępu wystarczy rozróżnienie
  „ten sam czy inny”, więc w bazie ląduje skrót z solą instalacji, w przejściowym
  wpisie o czasie życia równym odstępowi.
- **`nonce` żyje 12–24 godziny.** Formularz otwarty na dłużej (albo podany
  z pamięci podręcznej strony) zostanie odrzucony. Ten przypadek dostaje **własny
  komunikat** o wygaśnięciu, a nie komunikat o spamie — i zachowuje wpisane dane.
- **Pułapka jest ukrywana stylem wpisanym wprost w znacznik**, a nie klasą z arkusza
  motywu. To jedyne miejsce we wtyczce, gdzie tak robimy: przy innym motywie klasa
  mogłaby nie mieć reguły i ukryte pole stałoby się widoczne dla wszystkich, którzy
  je wtedy grzecznie wypełnią.
- Etykieta pułapki jest jedynym napisem formularza spoza rejestru tekstów. Nie widzi
  jej ani odwiedzający, ani czytnik ekranu (`aria-hidden`), więc §4.7 jej nie dotyczy.

Wartości progów są filtrowalne: `iskt_minimalny_czas_formularza`, `iskt_odstep_zgloszen`.

## 6. Napisy w panelu

Wszystkie w Szkolenia → **Teksty serwisu**, grupa „Formularz zgłoszeniowy”.
Żadnej etykiety ani komunikatu nie ma na sztywno w kodzie (§4.7, zadanie 10).

`formularz_tytul`, `formularz_wstep`, `formularz_typ`, `formularz_typ_osoba`,
`formularz_typ_firma`, `formularz_imie`, `formularz_email`, `formularz_telefon`,
`formularz_firma`, `formularz_szkolenie`, `formularz_szkolenie_ogolne`,
`formularz_grupa_szkolenia`, `formularz_grupa_obszary`, `formularz_termin`,
`formularz_termin_dowolny`, `formularz_wiadomosc`, `formularz_wymagane_opis`,
`formularz_przycisk`, `formularz_zastrzezenie`, `formularz_blad_ogolny`,
`formularz_blad_wymagane`, `formularz_blad_email`, `formularz_blad_wysylki`,
`formularz_blad_nonce`, `formularz_blad_antyspam`, `formularz_blad_limit`,
`formularz_sukces`.

Poza grupą: `kontakt_email` (adres odbiorcy), `stopka_prywatnosc_adres`
i `stopka_prywatnosc_tekst` (odnośnik pod formularzem), `szkolenie_termin_cta`
(odnośnik przy terminie na stronie szkolenia).

Dwa napisy są wymagane treścią zlecenia i **nie należy ich usuwać**:

- `formularz_zastrzezenie` — „zgłoszenie nie jest potwierdzeniem rezerwacji miejsca”
  (§5). Puste pole przywraca treść domyślną, więc skasować się go nie da.
- odnośnik do informacji o prywatności pokazuje się **tylko wtedy**, gdy ustawiono
  `stopka_prywatnosc_adres`. Dopóki ISKT nie dostarczy polityki prywatności
  (pozycja C11 w `MATERIALY-I-DECYZJE.md`), odnośnika nie ma — odnośnik prowadzący
  donikąd jest gorszy niż jego brak.

## 7. Przechowywanie zgłoszeń — DO DECYZJI ISKT

Typ treści `iskt_zgloszenie` jest zadeklarowany jako stała, ale **nie ma modelu
i nic się do bazy nie zapisuje**. §5 zostawia retencję „do uzgodnienia”, a §10
oznacza dane osobowe jako pozycję ryzyka — to nie jest decyzja wykonawcy.
Do czasu odpowiedzi działa ścieżka e-mailowa, która zapisu nie wymaga.

| | Wariant A — tylko e-mail (stan obecny) | Wariant B — zapis w bazie z retencją |
|---|---|---|
| Zakres danych w serwisie | żadnych danych osobowych w bazie WordPressa | imię, e-mail, telefon, firma, treść — w bazie |
| Ryzyko przy włamaniu na serwis | zgłoszenia nie do wykradzenia — nie ma ich | baza zgłoszeń jako łup |
| Utrata zgłoszenia | zgłoszenie ginie, jeśli poczta zawiedzie po stronie odbiorcy | kopia zostaje w panelu |
| Obowiązki RODO | administratorem danych jest skrzynka pocztowa, jak przy zwykłym mailu | rejestr czynności, retencja, obsługa żądań usunięcia, eksport |
| Praca do wykonania | zero | model, ekran w panelu, uprawnienia, automatyczne usuwanie po N dniach, eksport |

**Rekomendacja wykonawcy: wariant A.** Powód nie jest oszczędnościowy: zgłoszenie
i tak trafia do skrzynki, więc zapis w bazie tworzy **drugi** zbiór danych osobowych
do zabezpieczenia i do usuwania na żądanie, nie zmniejszając pierwszego. Wariant B
ma sens dopiero wtedy, gdy ISKT chce obsługiwać zgłoszenia w panelu WordPressa
zamiast w poczcie — i wtedy trzeba go domówić razem z okresem retencji.

Pytanie zgłoszone na ISK-17. Do czasu odpowiedzi obowiązuje wariant A.

## 8. Weryfikacja

```bash
php tests/run.php                       # walidacja, treść wiadomości, Reply-To, antyspam

npx @wordpress/env start
npx @wordpress/env run cli wp eval-file wp-content/iskt-tests/e2e/dane-formularza.php
npx @playwright/test test tests/e2e/formularz.spec.js
```

Środowisko `wp-env` nie ma agenta pocztowego, więc `wp_mail()` zwracałoby tam `false`
przy każdej próbie — ścieżki powodzenia nie dałoby się w ogóle sprawdzić. Dlatego
`tests/env/mu-plugins/iskt-poczta-testowa.php` (podpięty przez `mappings`
w `.wp-env.json`) przechwytuje wysyłkę, zapisuje nagłówki i treść do
`.wp-env/uploads/iskt-poczta.jsonl` i pozwala testowi wymusić nieudaną wysyłkę
opcją `iskt_test_poczta_awaria`. **Ten plik należy do środowiska testowego, nie do
wtyczki — do paczki przekazania nie trafia.**

Przechwycona wiadomość z przebiegu odbiorowego:
`qa-artifacts/isk-25/przechwycona-poczta.jsonl`.

### Czego ten test NIE dowodzi (§11 pkt 14)

**Dostarczenia zgłoszenia na prawdziwą skrzynkę `szkolenia@iskt.pl`.** Nie mamy
dostępu do poczty ISKT (§8), więc sprawdzone jest wyłącznie to, że wiadomość
z poprawnym odbiorcą, tematem, nagłówkami i treścią zostaje **przekazana** do
mechanizmu wysyłkowego WordPressa. Dostawa, SPF/DKIM/DMARC i filtry antyspamowe
odbiorcy pozostają do sprawdzenia po migracji — pozycja 1 listy w
`docs/MATERIALY-I-DECYZJE.md` §4.
