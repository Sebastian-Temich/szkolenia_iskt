#!/usr/bin/env bash
#
# Odtworzenie witryny z paczki przekazania (ISK-17 §8, zadanie 13).
#
# Skrypt uruchamia się NA SERWERZE DOCELOWYM, w katalogu głównym WordPressa —
# tam, gdzie leży `wp-config.php`. Zakłada świeżą instalację WordPressa z własnym
# kontem administratora; paczka kont nie przenosi (uzasadnienie w nagłówku
# `eksport-witryny.sh`).
#
# Kolejność kroków nie jest dowolna:
#
#   1. kopia stanu sprzed importu — zanim cokolwiek zostanie nadpisane, bo po
#      `wp db import` nie ma już do czego wracać. To jest mechanizm wycofania
#      wdrożenia wymagany przez zlecenie; obsługuje go `tools/wycofaj-import.sh`;
#   2. baza, potem pliki — kolejność bez znaczenia dla wyniku, ale baza pierwsza
#      przerywa import wcześniej, gdy zrzut jest uszkodzony;
#   3. zamiana adresów DOPIERO po imporcie i wyłącznie przez `wp search-replace`,
#      które rozumie dane serializowane. Powiązanie szkolenie ↔ trener siedzi
#      w polu `_iskt_trenerzy` zapisanym jako `a:2:{i:0;i:41;…}`; zamiana zwykłym
#      `sed` rozjeżdża długości w sąsiednich `s:<n>:"…"` i relacja znika z obu stron;
#   4. przeładowanie reguł adresów — bez tego `/szkolenia/…` i `/trenerzy/…` dają
#      404 po bezpośrednim wejściu, mimo że treść jest w bazie (§11 pkt 6);
#   5. wyczyszczenie danych podręcznych — przechowują wyniki policzone dla
#      poprzedniego adresu.
#
# Użycie:
#   ISKT_POTWIERDZAM_NADPISANIE=tak tools/import-witryny.sh <katalog-paczki> [adres-docelowy]
#
# Zmienne środowiskowe:
#   ISKT_WP                       polecenie WP-CLI (domyślnie: wp)
#   ISKT_POTWIERDZAM_NADPISANIE   musi mieć wartość `tak` — import kasuje bazę

set -euo pipefail

PACZKA="${1:?Podaj katalog z paczką (baza.sql, wp-content.tar.gz, MANIFEST.txt)}"
PACZKA="$(cd "$PACZKA" && pwd)"
ADRES_DOCELOWY="${2:-}"

blad() { printf '\033[31mBŁĄD:\033[0m %s\n' "$*" >&2; exit 1; }
krok() { printf '\n\033[1m» %s\033[0m\n' "$*"; }
uwaga() { printf '  \033[33m!\033[0m %s\n' "$*"; }
ok() { printf '  \033[32m✓\033[0m %s\n' "$*"; }

if [[ -n "${ISKT_WP:-}" ]]; then
	read -r -a WP_CMD <<< "$ISKT_WP"
else
	WP_CMD=(wp)
fi
wpcli() { "${WP_CMD[@]}" "$@"; }

for plik in baza.sql wp-content.tar.gz MANIFEST.txt; do
	[[ -f "$PACZKA/$plik" ]] || blad "W paczce brakuje pliku $plik."
done

[[ -f wp-config.php ]] || blad 'Uruchom skrypt w katalogu głównym WordPressa (tam, gdzie wp-config.php).'
wpcli option get siteurl >/dev/null 2>&1 || blad 'WP-CLI nie odpowiada w tym katalogu.'

[[ "${ISKT_POTWIERDZAM_NADPISANIE:-}" == "tak" ]] || blad \
	'Import KASUJE obecną bazę tej instalacji. Uruchom ponownie z ISKT_POTWIERDZAM_NADPISANIE=tak.'

krok 'Sprawdzenie paczki'

if command -v shasum >/dev/null 2>&1; then
	SUMY=(shasum -a 256 -c)
elif command -v sha256sum >/dev/null 2>&1; then
	SUMY=(sha256sum -c)
else
	SUMY=()
fi

if [[ ${#SUMY[@]} -gt 0 ]]; then
	# Sumy leżą w ogonie manifestu w formacie `<suma>  <nazwa>`; bierzemy tylko te wiersze.
	if ( cd "$PACZKA" && grep -E '^[0-9a-f]{64} ' MANIFEST.txt | "${SUMY[@]}" - >/dev/null 2>&1 ); then
		ok 'Sumy kontrolne zgodne z manifestem.'
	else
		blad 'Sumy kontrolne nie zgadzają się z manifestem — paczka jest niekompletna lub uszkodzona.'
	fi
else
	uwaga 'Brak shasum/sha256sum — pomijam sprawdzenie sum kontrolnych.'
fi

PREFIKS_PACZKI="$(grep -E '^Prefiks tabel' "$PACZKA/MANIFEST.txt" | sed -E 's/.*: //')"
PREFIKS_TU="$(wpcli db prefix)"
[[ "$PREFIKS_PACZKI" == "$PREFIKS_TU" ]] || blad \
	"Prefiks tabel w paczce ($PREFIKS_PACZKI) różni się od prefiksu tej instalacji ($PREFIKS_TU).
     Zainstaluj WordPressa z prefiksem $PREFIKS_PACZKI albo zmień \$table_prefix w wp-config.php."
ok "Prefiks tabel zgodny: $PREFIKS_TU"

ADRES_PACZKI="$(grep -E '^Adres źródłowy w bazie po zamianie' "$PACZKA/MANIFEST.txt" | sed -E 's/.*: //')"
ADRES_DOCELOWY="${ADRES_DOCELOWY:-$ADRES_PACZKI}"

krok 'Kopia stanu sprzed importu'

KOPIA="$PWD/kopia-przed-importem"
rm -rf "$KOPIA"
mkdir -p "$KOPIA"
wpcli db export "$KOPIA/baza.sql" --quiet
tar -czf "$KOPIA/wp-content.tar.gz" wp-content
printf '%s\n' "$(wpcli option get siteurl)" > "$KOPIA/siteurl.txt"
ok "Zapisana w $KOPIA — wycofanie: tools/wycofaj-import.sh $KOPIA"

krok 'Import bazy'

# Adres e-mail administratora witryny NALEŻY DO TEJ INSTALACJI, nie do paczki. Podałeś
# go przy instalacji WordPressa i to na niego idą powiadomienia o aktualizacjach,
# resecie hasła i błędach krytycznych. Zrzut bazy nadpisuje całą tabelę opcji, więc bez
# tego zabezpieczenia wjeżdża tu adres ze środowiska wewnętrznego (`wordpress@example.com`)
# i powiadomienia z witryny przestają docierać gdziekolwiek — po cichu, bo nikt nie
# zauważa wiadomości, która nie przyszła.
ADRES_ADMINA="$(wpcli option get admin_email)"

wpcli db import "$PACZKA/baza.sql"
ok 'Baza wczytana.'

if [[ -n "$ADRES_ADMINA" ]]; then
	wpcli option update admin_email "$ADRES_ADMINA" --quiet
	ok "Adres administratora tej instalacji zachowany: $ADRES_ADMINA"
fi

krok 'Rozpakowanie wp-content'

# Rozpakowujemy OBOK istniejących plików, nie zamiast nich: docelowa instalacja ma
# własny motyw zapasowy i `index.php` w katalogach, a ich skasowanie niczego nie
# poprawia. Pliki o tych samych nazwach paczka nadpisuje.
tar -xzf "$PACZKA/wp-content.tar.gz" -C .
ok 'Motyw, wtyczka i biblioteka mediów na miejscu.'

if [[ "$ADRES_DOCELOWY" != "$ADRES_PACZKI" ]]; then
	krok "Zamiana adresów: $ADRES_PACZKI → $ADRES_DOCELOWY"
	wpcli search-replace "$ADRES_PACZKI" "$ADRES_DOCELOWY" --all-tables-with-prefix --precise --quiet
	wpcli option update siteurl "$ADRES_DOCELOWY" --quiet
	wpcli option update home "$ADRES_DOCELOWY" --quiet
	ok 'Adresy zamienione z zachowaniem danych serializowanych.'
fi

krok 'Motyw, wtyczka i reguły adresów'

wpcli theme activate iskt-szkolenia --quiet
wpcli plugin activate iskt-szkolenia-core --quiet

# `--hard` zapisuje też plik .htaccess. Bez niego adresy szkoleń i trenerów działają
# tylko z panelu, a po bezpośrednim wejściu dają 404 (§11 pkt 6).
wpcli rewrite flush --hard --quiet
wpcli transient delete --all --quiet
ok 'Motyw i wtyczka włączone, reguły adresów przeładowane, dane podręczne wyczyszczone.'

krok 'Indeksowanie'

# Ani nie przenosimy wyłączenia ze środowiska wewnętrznego, ani nie włączamy
# indeksowania sami. Ustawiamy 0 na czas migracji z własnego powodu: witryna
# w trakcie przenoszenia bywa niekompletna, a wyszukiwarka nie powinna jej wtedy
# oglądać. Włączenie jest osobnym, świadomym krokiem ISKT — §7 instrukcji.
wpcli option update blog_public 0 --quiet
uwaga 'blog_public = 0 — witryna jest POZA indeksem wyszukiwarek.'
uwaga 'Po sprawdzeniu witryny włącz indeksowanie: patrz docs/EKSPORT-I-MIGRACJA.md §7.'

krok 'Stan po imporcie'

printf '  adres:    %s\n' "$(wpcli option get siteurl)"
printf '  motyw:    %s\n' "$(wpcli option get stylesheet)"
printf '  szkolenia: %s\n' "$(wpcli post list --post_type=iskt_szkolenie --post_status=publish --format=count)"
printf '  trenerzy:  %s\n' "$(wpcli post list --post_type=iskt_trener --post_status=publish --format=count)"
printf '  terminy:   %s\n' "$(wpcli post list --post_type=iskt_termin --post_status=publish --format=count)"
printf '  media:     %s\n' "$(wpcli post list --post_type=attachment --format=count)"

krok 'Zrób teraz przejście z §6 instrukcji — import bez sprawdzenia nie jest odtworzeniem.'
