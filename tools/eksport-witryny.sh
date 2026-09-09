#!/usr/bin/env bash
#
# Eksport całej witryny do paczki przekazania (ISK-17 §8, zadanie 13).
#
# Paczka ma pozwolić osobie po stronie ISKT odtworzyć serwis na SEOHost bez naszego
# udziału. Skrypt buduje ją z trzech części i sam sprawdza, czy nie wyniosła
# z sobą niczego ze środowiska wewnętrznego:
#
#   baza.sql          zrzut bazy z adresami JUŻ zamienionymi na domenę docelową
#   wp-content.tar.gz motyw, wtyczka i biblioteka mediów
#   MANIFEST.txt      co jest w paczce, z czego powstała i czego świadomie nie ma
#
# Dwie decyzje wymagają uzasadnienia, bo nie są oczywiste:
#
# 1. **Zamiana adresów dzieje się przy eksporcie, nie przy imporcie.** Wymóg §8
#    mówi, że w paczce nie ma niczego ze środowiska wewnętrznego — a adres
#    `http://localhost:8888` jest właśnie tym. Gdyby zamiana była krokiem
#    instrukcji, wymóg zależałby od tego, czy ktoś ten krok wykona. Robimy ją tutaj,
#    przez `wp search-replace --precise`, które rozumie dane serializowane:
#    powiązanie szkolenie ↔ trener siedzi w polu `_iskt_trenerzy` zapisanym jako
#    `a:2:{i:0;i:41;i:1;i:42;}`, a zamiana adresu zwykłym `sed` na pliku SQL
#    rozjeżdża długości w sąsiednich `s:<n>:"..."` i relacja przestaje się
#    odnajdywać z obu stron. Import na inną domenę (np. testową) jest nadal
#    możliwy — instrukcja opisuje ponowne `wp search-replace` już na miejscu.
#
# 2. **Konta użytkowników NIE wchodzą do paczki.** W środowisku wewnętrznym
#    administrator ma znane hasło `wp-env`. Przeniesienie tabel `wp_users`
#    i `wp_usermeta` oznaczałoby oddanie ISKT witryny z kontem, którego hasło zna
#    każdy, kto widział to repozytorium. Zamiast tego docelowy WordPress zachowuje
#    konto założone przy własnej instalacji — ma identyfikator 1, tak samo jak
#    administrator środowiska wewnętrznego, więc autorstwo wpisów zostaje.
#    Skrypt PRZERYWA pracę, gdy w źródle jest więcej niż jedno konto, żeby to
#    uproszczenie nie mogło po cichu zgubić autorów.
#
# Ustawienia `blog_public` skrypt nie przenosi jako decyzji — zapisuje jego wartość
# źródłową w manifeście, a stan po imporcie ustala `import-witryny.sh`. Powód
# w `docs/EKSPORT-I-MIGRACJA.md` §7.
#
# Użycie:
#   tools/eksport-witryny.sh [katalog-docelowy] [adres-docelowy]
#
# Zmienne środowiskowe:
#   ISKT_WP              polecenie WP-CLI (domyślnie: wykryty kontener wp-env)
#   ISKT_SHELL           przedrostek poleceń powłoki tam, gdzie stoi WordPress
#                        (domyślnie: ten sam kontener wp-env; na serwerze: puste)
#   ISKT_ADRES_ZRODLOWY  adres środowiska wewnętrznego (domyślnie: z bazy)

set -euo pipefail

KATALOG_PROJEKTU="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CEL="${1:-$KATALOG_PROJEKTU/export}"
ADRES_DOCELOWY="${2:-https://szkolenia.iskt.pl}"

blad() { printf '\033[31mBŁĄD:\033[0m %s\n' "$*" >&2; exit 1; }
krok() { printf '\n\033[1m» %s\033[0m\n' "$*"; }
uwaga() { printf '  \033[33m!\033[0m %s\n' "$*"; }
ok() { printf '  \033[32m✓\033[0m %s\n' "$*"; }

# --- WP-CLI ------------------------------------------------------------------
# Domyślnie sięgamy wprost do kontenera `wp-env` przez `docker exec`, a nie przez
# `npx @wordpress/env run cli`: wrapper dokłada do standardowego wyjścia własne
# wiersze („ℹ Starting…”), które wylądowałyby w środku zrzutu SQL.

if [[ -n "${ISKT_WP:-}" ]]; then
	read -r -a WP_CMD <<< "$ISKT_WP"
	read -r -a SHELL_CMD <<< "${ISKT_SHELL:-}"
else
	KONTENER="$(docker ps --format '{{.Names}}' | grep -E '^wp-env-.*-cli-1$' | head -1 || true)"
	[[ -n "$KONTENER" ]] || blad 'Nie znaleziono kontenera wp-env. Uruchom `npx @wordpress/env start` albo ustaw ISKT_WP.'
	WP_CMD=(docker exec -u www-data -w /var/www/html "$KONTENER" wp)
	SHELL_CMD=(docker exec -u www-data -w /var/www/html "$KONTENER")
fi

wpcli() { "${WP_CMD[@]}" "$@"; }

wpcli option get siteurl >/dev/null 2>&1 || blad 'WP-CLI nie odpowiada — sprawdź ISKT_WP.'

ADRES_ZRODLOWY="${ISKT_ADRES_ZRODLOWY:-$(wpcli option get siteurl)}"
PREFIKS="$(wpcli db prefix)"
WERSJA_WP="$(wpcli core version)"
KATALOG_WP="$(wpcli eval 'echo untrailingslashit( ABSPATH );')"

krok "Eksport z $ADRES_ZRODLOWY do $ADRES_DOCELOWY"

[[ "$PREFIKS" == "wp_" ]] || uwaga "Prefiks tabel w źródle to '$PREFIKS'. Docelowa instalacja musi mieć taki sam."

# --- Warunek konieczny: jedno konto ------------------------------------------

LICZBA_KONT="$(wpcli user list --format=count)"
if [[ "$LICZBA_KONT" != "1" ]]; then
	blad "W źródle jest $LICZBA_KONT kont, a paczka nie przenosi tabeli użytkowników (patrz nagłówek skryptu).
     Przenieś autorów świadomie albo usuń zbędne konta przed eksportem."
fi
ok "W źródle jedno konto — autorstwo wpisów przetrwa na koncie nr 1 instalacji docelowej."

# --- Paczka ------------------------------------------------------------------

rm -rf "$CEL"
mkdir -p "$CEL"

krok 'Zrzut bazy z zamianą adresów'

# --skip-columns pominięte świadomie: kolumna `guid` niesie adres środowiska
# wewnętrznego, a §8 zabrania wynoszenia go w paczce. Zwyczajowe ostrzeżenie
# „nie zmieniaj guid” dotyczy witryn, które już były publiczne i mają czytelników
# kanału RSS — ta nigdy publiczna nie była.
wpcli search-replace "$ADRES_ZRODLOWY" "$ADRES_DOCELOWY" \
	--all-tables-with-prefix \
	--precise \
	--skip-tables="${PREFIKS}users,${PREFIKS}usermeta" \
	--export 2>/dev/null > "$CEL/baza.sql"

[[ -s "$CEL/baza.sql" ]] || blad 'Zrzut bazy jest pusty.'
ok "baza.sql — $(wc -c < "$CEL/baza.sql" | tr -d ' ') B"

krok 'Archiwum wp-content'

SCENA="$(mktemp -d)"
trap 'rm -rf "$SCENA"' EXIT

# Archiwum bierzemy z DZIAŁAJĄCEJ instalacji, a nie składamy z katalogów repozytorium.
# Różnica jest istotna przy tłumaczeniach: `wp-content/languages` powstaje przy
# instalacji języka i w repozytorium go nie ma, a bez niego odtworzona witryna
# wypisuje daty terminów po angielsku („30 September 2026”) mimo `WPLANG = pl_PL`.
# Wyszło to dopiero na teście odtworzenia — i jest dokładnie tym, czego test ma szukać.
# Tłumaczenia wędrują w paczce zamiast być krokiem instrukcji, bo krok da się pominąć,
# a poza tym `wp language core install` wymaga połączenia z wordpress.org, którego
# serwer docelowy nie musi mieć.
# Wykluczenia idą PRZED nazwą katalogu: GNU tar traktuje je pozycyjnie i po nazwie
# nie mają już żadnego skutku (ostrzega o tym, ale kończy się to pełnym archiwum).
"${SHELL_CMD[@]}" tar -cf - -C "$KATALOG_WP" \
	--exclude=wp-content/mu-plugins \
	--exclude=wp-content/iskt-tests \
	--exclude=wp-content/upgrade \
	--exclude=wp-content/cache \
	--exclude=wp-content/debug.log \
	'--exclude=wp-content/themes/twenty*' \
	--exclude=wp-content/plugins/hello.php \
	wp-content \
	| tar -xf - -C "$SCENA"

[[ -d "$SCENA/wp-content/themes/iskt-szkolenia" ]] || blad 'W archiwum nie ma motywu iskt-szkolenia.'
[[ -d "$SCENA/wp-content/plugins/iskt-szkolenia-core" ]] || blad 'W archiwum nie ma wtyczki iskt-szkolenia-core.'

# Biblioteka mediów: WordPress trzyma pliki w katalogach `RRRR/MM`. Cokolwiek innego
# leży w `uploads`, nie jest plikiem z biblioteki — u nas są to skrypty zasiewające
# dane testowe i pliki źródłowe zrzutów. Nie usuwamy ich po nazwach, tylko po tej
# regule, a to co odpadło wypisujemy do manifestu: paczka ma nie gubić nic po cichu.
POMINIETE_UPLOADS=()
if [[ -d "$SCENA/wp-content/uploads" ]]; then
	for wpis in "$SCENA/wp-content/uploads"/*; do
		[[ -e "$wpis" ]] || continue
		nazwa="$(basename "$wpis")"
		if [[ -d "$wpis" && "$nazwa" =~ ^[0-9]{4}$ ]]; then
			continue
		fi
		POMINIETE_UPLOADS+=("$nazwa")
		rm -rf "$wpis"
	done
fi

# Pliki wykonywalne w `uploads` nie mają prawa istnieć na serwerze produkcyjnym —
# katalog jest zapisywalny przez WordPressa, więc każdy PHP w nim to gotowa droga
# wykonania kodu. Reguła katalogów `RRRR` już je odsiała; to jest sprawdzenie, że
# faktycznie tak się stało, a nie druga próba tego samego.
if find "$SCENA/wp-content/uploads" -name '*.php' -print -quit | grep -q .; then
	blad 'W archiwum mediów znalazł się plik PHP.'
fi

# `--no-xattrs`, gdy tar to potrafi: bez tego archiwum zbudowane na macOS niesie
# atrybuty `com.apple.provenance`, na których tar w Linuksie wypisuje ostrzeżenia
# przy każdym pliku. Ostrzeżenia są nieszkodliwe, ale gubią w szumie prawdziwe błędy.
BEZ_XATTR=()
if tar --no-xattrs -cf /dev/null -T /dev/null 2>/dev/null; then
	BEZ_XATTR=(--no-xattrs)
fi

COPYFILE_DISABLE=1 tar "${BEZ_XATTR[@]+"${BEZ_XATTR[@]}"}" -czf "$CEL/wp-content.tar.gz" -C "$SCENA" wp-content
ok "wp-content.tar.gz — $(wc -c < "$CEL/wp-content.tar.gz" | tr -d ' ') B"

for pominiety in "${POMINIETE_UPLOADS[@]+"${POMINIETE_UPLOADS[@]}"}"; do
	uwaga "poza paczką (nie jest plikiem biblioteki mediów): uploads/$pominiety"
done

# --- Manifest ----------------------------------------------------------------

krok 'Manifest'

{
	printf 'PACZKA PRZEKAZANIA — szkolenia.iskt.pl (ISK-17 §8, zadanie 13)\n'
	printf 'Instrukcja odtworzenia: docs/EKSPORT-I-MIGRACJA.md\n\n'
	printf 'Adres źródłowy w bazie po zamianie: %s\n' "$ADRES_DOCELOWY"
	printf 'WordPress w źródle: %s\n' "$WERSJA_WP"
	printf 'Prefiks tabel (docelowa instalacja musi mieć taki sam): %s\n' "$PREFIKS"
	printf 'PHP wymagane: 8.2 lub nowsze\n'
	printf 'MySQL 8.0 lub MariaDB 10.6 albo nowsze\n\n'

	printf 'ZAWARTOŚĆ\n'
	printf '  szkolenia: %s\n' "$(wpcli post list --post_type=iskt_szkolenie --post_status=publish --format=count)"
	printf '  trenerzy:  %s\n' "$(wpcli post list --post_type=iskt_trener --post_status=publish --format=count)"
	printf '  terminy:   %s\n' "$(wpcli post list --post_type=iskt_termin --post_status=publish --format=count)"
	printf '  wpisy:     %s\n' "$(wpcli post list --post_type=post --post_status=publish --format=count)"
	printf '  strony:    %s\n' "$(wpcli post list --post_type=page --post_status=publish --format=count)"
	printf '  media:     %s\n' "$(wpcli post list --post_type=attachment --format=count)"
	printf '  pliki w bibliotece: %s\n' "$(find "$SCENA/wp-content/uploads" -type f | wc -l | tr -d ' ')"
	printf '\n'

	printf 'USTAWIENIA ŹRÓDŁA (do porównania po imporcie)\n'
	for opcja in blogname blogdescription permalink_structure show_on_front page_on_front date_format time_format timezone_string WPLANG blog_public; do
		printf '  %-20s %s\n' "$opcja" "$(wpcli option get "$opcja" 2>/dev/null || printf '(brak)')"
	done
	printf '  %-20s %s\n' 'motyw' "$(wpcli option get stylesheet)"
	printf '  %-20s %s\n' 'wtyczki' "$(wpcli plugin list --status=active --field=name | tr '\n' ' ')"
	printf '\n'
	printf '  UWAGA: blog_public powyżej to stan środowiska wewnętrznego, a NIE zalecenie\n'
	printf '  dla domeny docelowej. Import ustawia 0 (witryna poza indeksem na czas migracji),\n'
	printf '  a włączenie indeksowania jest osobnym, świadomym krokiem ISKT —\n'
	printf '  docs/EKSPORT-I-MIGRACJA.md §7.\n\n'

	printf 'CZEGO W PACZCE NIE MA — ŚWIADOMIE\n'
	printf '  wp-config.php        hasła bazy i klucze soli; docelowa instalacja ma własne\n'
	printf '  %-20s konto administratora środowiska wewnętrznego ma znane hasło\n' "${PREFIKS}users"
	printf '  %-20s j.w.\n' "${PREFIKS}usermeta"
	printf '  mu-plugins/          przechwytywanie poczty na potrzeby testów; na docelowej\n'
	printf '                       domenie połknęłoby prawdziwe zgłoszenia\n'
	printf '  wp-content/iskt-tests/  testy repozytorium podmontowane przez wp-env\n'
	printf '  motywy twenty*       dostarcza je sama instalacja WordPressa\n'
	printf '  hello.php            przykładowa wtyczka WordPressa, nie należy do serwisu\n'
	printf '  upgrade/, cache/     katalogi robocze, odtwarzają się same\n'
	for pominiety in "${POMINIETE_UPLOADS[@]+"${POMINIETE_UPLOADS[@]}"}"; do
		printf '  uploads/%-12s nie jest plikiem biblioteki mediów\n' "$pominiety"
	done
	printf '\n'

	printf 'SUMY KONTROLNE\n'
	( cd "$CEL" && shasum -a 256 baza.sql wp-content.tar.gz 2>/dev/null || sha256sum baza.sql wp-content.tar.gz )
} > "$CEL/MANIFEST.txt"

ok 'MANIFEST.txt'

# --- Sprawdzenie paczki ------------------------------------------------------
#
# §8 wymaga, żeby w paczce nie było niczego ze środowiska wewnętrznego. Wymóg
# sprawdzany, nie deklarowany — dlatego skrypt sam przegląda to, co właśnie zbudował,
# i kończy się błędem, gdy coś znajdzie.

krok 'Sprawdzenie paczki pod kątem śladów środowiska wewnętrznego'

ZNALEZIONO=0
zglos() { uwaga "$1"; ZNALEZIONO=1; }

# Przeglądamy zrzut, spis plików archiwum ORAZ treść każdego pliku w archiwum.
# Bez tego trzeciego składnika sprawdzenie dotyczyłoby wyłącznie bazy — a sekret
# najłatwiej wynieść właśnie w pliku. `-O` wypisuje treść na standardowe wyjście
# bez rozpakowywania na dysk; pliki binarne obsługuje `grep -a` niżej.
TRESC="$(mktemp)"
{
	cat "$CEL/baza.sql"
	tar -tzf "$CEL/wp-content.tar.gz"
	tar -xzOf "$CEL/wp-content.tar.gz"
} > "$TRESC"

# Rozdzielnikiem jest średnik, nie kreska pionowa: wzorce rozszerzonych wyrażeń
# regularnych same jej używają jako alternatywy.
# `LC_ALL=C`, bo przeglądamy strumień z plikami binarnymi w środku (zdjęcia, pliki
# .mo). Przy ustawieniach lokalnych UTF-8 grep uznaje taki strumień za nieprawidłowy
# i potrafi PRZEOCZYĆ trafienie zamiast zgłosić błąd — czyli zachować się dokładnie
# odwrotnie, niż powinno się zachować sprawdzenie bezpieczeństwa.
while IFS=';' read -r wzorzec opis; do
	[[ -n "$wzorzec" ]] || continue
	if LC_ALL=C grep -aqiE "$wzorzec" "$TRESC"; then
		zglos "$opis (wzorzec: $wzorzec)"
	fi
done <<-'WZORCE'
	localhost:[0-9]+;adres środowiska wewnętrznego
	127\.0\.0\.1:[0-9]+;adres środowiska wewnętrznego
	wp-env([^a-z]|$);ślad narzędzia środowiska wewnętrznego
	DB_PASSWORD|DB_USER|DB_HOST;dane dostępowe do bazy
	(AUTH|SECURE_AUTH|LOGGED_IN|NONCE)_(KEY|SALT);klucze soli WordPressa
	iskt-poczta-testowa;przechwytywanie poczty ze środowiska testowego
WZORCE

for zakazany in "${PREFIKS}users" "${PREFIKS}usermeta"; do
	if LC_ALL=C grep -aq "INSERT INTO \`$zakazany\`" "$CEL/baza.sql"; then
		zglos "zrzut zawiera tabelę $zakazany"
	fi
done

for zakazana_sciezka in 'wp-config.php' 'wp-content/mu-plugins' 'wp-content/iskt-tests'; do
	if tar -tzf "$CEL/wp-content.tar.gz" | grep -q "$zakazana_sciezka"; then
		zglos "archiwum zawiera $zakazana_sciezka"
	fi
done

if [[ "$ZNALEZIONO" != "0" ]]; then
	rm -f "$TRESC"
	blad 'Paczka nosi ślady środowiska wewnętrznego — nie przekazuj jej. Napraw eksport, nie opis.'
fi

ok 'Brak adresów środowiska wewnętrznego, kluczy, haseł i tabel z kontami.'

# --- Treści zastępcze --------------------------------------------------------
#
# Osobna lista i osobny status, bo to inny rodzaj znaleziska. Adres w rodzaju
# `kontakt@example.test` nie jest sekretem i nie unieważnia paczki — jest treścią,
# którą właściciel ma prawo edytować i której §9 zabrania pokazywać jako
# zatwierdzonej. Oznaczenie `_iskt_demo` z zadania 12 obejmuje wpisy i media,
# ale nie obejmuje widżetów i opcji, więc taka treść przechodzi przez sprzątanie
# nietknięta. Przerwanie eksportu byłoby tu za daleko idące: usuwalibyśmy cudzą
# treść bez pytania. Wypisujemy więc znalezione miejsca i powtarzamy je w manifeście,
# żeby nikt nie opublikował ich przez przeoczenie.

# Szukamy w bazie i w kodzie, ale NIE w `wp-content/languages`: pliki tłumaczeń
# WordPressa są pełne przykładowych adresów z komunikatów panelu
# (`wordpress@example.com`, `login@example.com`) i utopiłyby jedyne trafienie, które
# tu naprawdę o czymś mówi — treść wpisaną w tej witrynie.
rm -f "$TRESC"

ZASTEPCZE="$(
	{
		cat "$CEL/baza.sql"
		find "$SCENA/wp-content" -type f -not -path '*/languages/*' -exec cat {} +
	} | LC_ALL=C grep -aoiE '[a-z0-9._%+-]+@example\.(test|com|org|net)|(^|[^a-z0-9.-])example\.(test|com|org|net)' \
		| sed -E 's/^[^a-z0-9]//i' | sort -u || true
)"

{
	printf '\nDO PRZEJRZENIA PRZED PUBLIKACJĄ (§9 — treści niepotwierdzone)\n'
	if [[ -n "$ZASTEPCZE" ]]; then
		printf '  W paczce są adresy z domen zastrzeżonych do przykładów. Nie są sekretem,\n'
		printf '  ale nie mogą trafić na publiczną witrynę jako dane kontaktowe ISKT.\n'
		printf '  Gdzie ich szukać: Wygląd → Widżety, Szkolenia → Teksty globalne.\n'
		printf '%s\n' "$ZASTEPCZE" | sed 's/^/    /'
	else
		printf '  Nie znaleziono adresów z domen przykładowych.\n'
	fi
} >> "$CEL/MANIFEST.txt"

if [[ -n "$ZASTEPCZE" ]]; then
	uwaga 'Paczka niesie treści zastępcze — spis w MANIFEST.txt, do przejrzenia przed publikacją:'
	printf '%s\n' "$ZASTEPCZE" | sed 's/^/      /'
else
	ok 'Brak adresów z domen przykładowych.'
fi

krok "Gotowe: $CEL"
ls -la "$CEL"
