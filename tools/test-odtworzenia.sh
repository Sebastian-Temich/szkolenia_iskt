#!/usr/bin/env bash
#
# Test odtworzenia witryny z paczki przekazania (ISK-17 §11 pkt 12, zadanie 13).
#
# Instrukcja migracji jest warta tyle, ile przejście po niej. Ten skrypt przechodzi
# ją całą, na czystym WordPressie, i sprawdza wynik — a nie log importu.
#
# Środowisko docelowe stawiamy CELOWO poza `wp-env`: dwa kontenery z oficjalnych
# obrazów, własna baza, własne konto administratora, żadnego katalogu z tego
# repozytorium podmontowanego w środku. Gdyby test biegł w `wp-env`, motyw i wtyczka
# byłyby na miejscu niezależnie od zawartości paczki i test niczego by nie dowodził.
#
# Adres testowy różni się od docelowego (`https://szkolenia.iskt.pl`), więc przy
# okazji sprawdzamy ścieżkę „import na inną domenę” z §5 instrukcji — tę samą,
# której ISKT użyje, jeśli zechce najpierw postawić kopię testową.
#
# Użycie:
#   tools/test-odtworzenia.sh [katalog-paczki] [katalog-na-dowody]

set -euo pipefail

KATALOG_PROJEKTU="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PACZKA="${1:-$KATALOG_PROJEKTU/export}"
DOWODY="${2:-$KATALOG_PROJEKTU/qa-artifacts/isk-17-m3-eksport}"
PORT="${ISKT_PORT_ODTWORZENIA:-8890}"
ADRES="http://localhost:$PORT"

SIEC=iskt-odtworzenie
BAZA=iskt-odtw-db
WEB=iskt-odtw-wp

blad() { printf '\033[31mBŁĄD:\033[0m %s\n' "$*" >&2; exit 1; }
krok() { printf '\n\033[1m» %s\033[0m\n' "$*"; }

BLEDY=0
sprawdz() {
	local opis="$1" oczekiwane="$2" otrzymane="$3"
	if [[ "$otrzymane" == "$oczekiwane" ]]; then
		printf '  \033[32m✓\033[0m %-62s %s\n' "$opis" "$otrzymane"
	else
		printf '  \033[31m✗\033[0m %-62s %s (oczekiwano: %s)\n' "$opis" "$otrzymane" "$oczekiwane"
		BLEDY=$((BLEDY + 1))
	fi
}

[[ -f "$PACZKA/baza.sql" ]] || blad "Brak paczki w $PACZKA — uruchom najpierw tools/eksport-witryny.sh."

mkdir -p "$DOWODY"
LOG="$DOWODY/przebieg-odtworzenia.txt"

# Cały przebieg leci równocześnie na ekran i do pliku dowodowego — bez barw, bo
# w pliku sekwencje sterujące terminala są nieczytelnym śmieciem. Zapis powstaje
# tak czy inaczej, także gdy skrypt przewróci się w połowie; to wtedy jest
# najbardziej potrzebny.
exec > >(tee >(sed $'s/\033\\[[0-9;]*m//g' > "$LOG")) 2>&1
printf 'ISK-17 M3 zadanie 13 — przebieg testu odtworzenia z paczki\n'
printf 'Paczka: %s\n' "$PACZKA"
grep -E '^(Adres źródłowy|WordPress w źródle|Prefiks tabel)' "$PACZKA/MANIFEST.txt" || true

DST() { docker exec -w /var/www/html "$WEB" wp --allow-root "$@"; }
ZRODLO_CLI="$(docker ps --format '{{.Names}}' | grep -E '^wp-env-.*-cli-1$' | head -1 || true)"
SRC() { docker exec -u www-data -w /var/www/html "$ZRODLO_CLI" wp "$@"; }

# --- Czysty WordPress --------------------------------------------------------

krok 'Czysty WordPress: dwa kontenery, pusta baza'

docker network create "$SIEC" >/dev/null 2>&1 || true
docker rm -f "$BAZA" "$WEB" >/dev/null 2>&1 || true

docker run -d --name "$BAZA" --network "$SIEC" \
	-e MARIADB_ROOT_PASSWORD=korzen-testowy \
	-e MARIADB_DATABASE=wordpress -e MARIADB_USER=wordpress -e MARIADB_PASSWORD=haslo-testowe \
	mariadb:lts >/dev/null

docker run -d --name "$WEB" --network "$SIEC" -p "$PORT:80" \
	-e WORDPRESS_DB_HOST="$BAZA" -e WORDPRESS_DB_NAME=wordpress \
	-e WORDPRESS_DB_USER=wordpress -e WORDPRESS_DB_PASSWORD=haslo-testowe \
	wordpress:6.6.2-php8.2-apache >/dev/null

# Czekamy na bazę, a nie na stałą liczbę sekund: kontener MariaDB wstaje raz
# w 5 sekund, raz w 40, zależnie od obciążenia maszyny.
for _ in $(seq 1 60); do
	docker exec "$BAZA" mariadb-admin ping -uwordpress -phaslo-testowe --silent >/dev/null 2>&1 && break
	sleep 2
done
docker exec "$BAZA" mariadb-admin ping -uwordpress -phaslo-testowe --silent >/dev/null 2>&1 \
	|| blad 'Baza kontenera testowego nie wstała.'

krok 'Narzędzia w kontenerze docelowym (WP-CLI, klient MySQL)'

if [[ ! -f "${TMPDIR:-/tmp}/wp-cli.phar" ]]; then
	curl -sSL -o "${TMPDIR:-/tmp}/wp-cli.phar" https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
fi
docker cp "${TMPDIR:-/tmp}/wp-cli.phar" "$WEB:/usr/local/bin/wp" >/dev/null
docker exec "$WEB" chmod +x /usr/local/bin/wp
docker exec "$WEB" bash -c 'apt-get update -qq && apt-get install -y -qq mariadb-client' >/dev/null 2>&1 \
	|| blad 'Nie udało się zainstalować klienta MySQL w kontenerze docelowym.'

krok 'Instalacja WordPressa z KONTEM WŁAŚCICIELA (paczka kont nie przenosi)'

# Hasło losowe i nigdzie niezapisane: nikt się na tę instalację nie loguje, a plik
# dowodowy z hasłem w środku byłby dokładnie tym, czego §8 zabrania w paczce.
DST core install --url="$ADRES" --title='Szkolenia ISKT' \
	--admin_user=wlasciciel --admin_password="$(openssl rand -base64 18)" \
	--admin_email=redakcja@iskt.pl --skip-email >/dev/null
printf '  WordPress %s, prefiks %s, motyw %s, wpisów: %s\n' \
	"$(DST core version)" "$(DST db prefix)" "$(DST option get stylesheet)" \
	"$(DST post list --post_type=post --format=count)"

# --- Import ------------------------------------------------------------------

krok 'Import paczki'

docker exec "$WEB" rm -rf /paczka /narzedzia
docker exec "$WEB" mkdir -p /paczka /narzedzia
docker cp "$PACZKA/." "$WEB:/paczka/" >/dev/null
docker cp "$KATALOG_PROJEKTU/tools/." "$WEB:/narzedzia/" >/dev/null

docker exec -w /var/www/html \
	-e ISKT_WP='wp --allow-root' -e ISKT_POTWIERDZAM_NADPISANIE=tak \
	"$WEB" bash /narzedzia/import-witryny.sh /paczka "$ADRES"

# --- Sprawdzenie odtworzonej witryny ----------------------------------------

krok 'Sprawdzenie odtworzonej witryny'

tresc() { curl -s "$ADRES$1"; }
kod() { curl -s -o /dev/null -w '%{http_code}' "$ADRES$1"; }
ile() { tresc "$1" | grep -c "$2" || true; }

printf '\n  \033[1mAdresy odpowiadają (§11 pkt 6 — po bezpośrednim wejściu, nie z panelu)\033[0m\n'
for sciezka in / /szkolenia/ /szkolenia/demo-audyt-energetyczny-budynkow/ /trenerzy/ \
	/trenerzy/demo-anna-kowalczyk/ /aktualnosci/ /zgloszenie/ /szkolenia/page/2/; do
	sprawdz "$sciezka" 200 "$(kod "$sciezka")"
done

printf '\n  \033[1mKatalog: wyszukiwanie, filtry, brak wyników (§4.2)\033[0m\n'
# „termowizja” występuje wyłącznie w OPISIE jednego szkolenia — trafienie dowodzi,
# że przeszukiwany jest opis, a nie sam tytuł.
sprawdz 'wyszukiwanie po opisie znajduje 1 szkolenie' 1 "$(ile '/szkolenia/?szukaj=termowizja' 'demo-audyt-energetyczny-budynkow/"')"
sprawdz 'filtr kategorii zwraca wyniki' 200 "$(kod '/szkolenia/?kategoria=ai-i-narzedzia-generatywne')"
sprawdz 'filtr formy zwraca wyniki' 200 "$(kod '/szkolenia/?forma=online')"
sprawdz 'stan braku wyników pokazuje komunikat' 1 "$(ile '/szkolenia/?szukaj=zzzznieistniejaca' 'Brak wyników ISK-34')"

printf '\n  \033[1mPowiązanie szkolenie ↔ trener widoczne z OBU stron (§11 pkt 8)\033[0m\n'
sprawdz 'szkolenie pokazuje pierwszego trenera' 1 "$(ile /szkolenia/demo-raportowanie-esg-w-praktyce/ 'Anna Kowalczyk')"
sprawdz 'szkolenie pokazuje drugiego trenera' 1 "$(ile /szkolenia/demo-raportowanie-esg-w-praktyce/ 'Marek Zieliński')"
sprawdz 'profil trenera pokazuje szkolenie A' 1 "$(ile /trenerzy/demo-anna-kowalczyk/ 'Audyt energetyczny budynków')"
sprawdz 'profil trenera pokazuje szkolenie B' 1 "$(ile /trenerzy/demo-anna-kowalczyk/ 'Raportowanie ESG w praktyce')"

printf '\n  \033[1mTerminy, w tym zakończony (§11 pkt 5)\033[0m\n'
# Oczekiwany napis bierzemy z tytułu terminu w ŹRÓDLE, a data na stronie powstaje
# przy odsłonie przez `date_i18n()`. Zgodność tych dwóch nie jest oczywista: bez
# plików tłumaczeń w paczce widok wypisuje „30 September 2026” przy tytule
# „30 września 2026” i to sprawdzenie właśnie na tym się przewraca.
sprawdz 'najbliższy termin jest na stronie szkolenia' 1 \
	"$(ile /szkolenia/demo-audyt-energetyczny-budynkow/ "$(SRC post get "$(SRC post list --post_type=iskt_termin --name=demo-termin-audyt-najblizszy --field=ID)" --field=post_title 2>/dev/null || echo NIEZNANY)")"
sprawdz 'termin zakończony NIE jest prezentowany jako nadchodzący' 0 \
	"$(ile /szkolenia/demo-audyt-energetyczny-budynkow/ "$(SRC post get "$(SRC post list --post_type=iskt_termin --name=demo-termin-audyt-zakonczony --field=ID)" --field=post_title 2>/dev/null || echo NIEZNANY)")"

printf '\n  \033[1mTeksty globalne z zadania 10\033[0m\n'
# Wartości niosą ciąg „ISK-34”, więc trafienie odróżnia „opcja iskt_teksty przetrwała”
# od „widok wrócił do wartości domyślnej, bo opcji nie ma”. Liczby porównujemy ze
# ŹRÓDŁEM, a nie z jedynką: ten sam napis potrafi wystąpić na stronie kilka razy
# i test ma pilnować zgodności, a nie krotności.
zrodlo_ile() { curl -s "${ISKT_ADRES_ZRODLA:-http://localhost:8888}$1" | grep -c "$2" || true; }
sprawdz 'tytuł katalogu z panelu' "$(zrodlo_ile /szkolenia/ 'Katalog szkoleń ISK-34')" "$(ile /szkolenia/ 'Katalog szkoleń ISK-34')"
sprawdz 'napis przycisku menu z panelu' "$(zrodlo_ile / 'Menu ISK-34')" "$(ile / 'Menu ISK-34')"
sprawdz 'komunikat pustego katalogu z panelu' "$(zrodlo_ile '/szkolenia/?szukaj=zzzznieistniejaca' 'Brak wyników ISK-34')" "$(ile '/szkolenia/?szukaj=zzzznieistniejaca' 'Brak wyników ISK-34')"

printf '\n  \033[1mMedia z biblioteki, nie puste ramki\033[0m\n'
sprawdz 'zdjęcie trenera osadzone w stronie' 1 "$(ile /trenerzy/demo-anna-kowalczyk/ 'demo-anna-kowalczyk-zdjecie')"
sprawdz 'plik zdjęcia serwowany' 200 "$(kod /wp-content/uploads/2026/09/demo-anna-kowalczyk-zdjecie-300x300.png)"
sprawdz 'rozmiar pochodny wygenerowany przy źródle też jest' 200 "$(kod /wp-content/uploads/2026/09/demo-anna-kowalczyk-zdjecie-150x150.png)"

printf '\n  \033[1mFormularz zgłoszeniowy (bez wysyłki — patrz §9 instrukcji)\033[0m\n'
sprawdz 'strona /zgloszenie/ istnieje' 200 "$(kod /zgloszenie/)"
sprawdz 'blok formularza jest na stronie' 1 "$(ile /zgloszenie/ 'iskt-formularz')"
sprawdz 'pole wyboru szkolenia wypełnione katalogiem' 1 "$(ile /zgloszenie/ 'Audyt energetyczny budynków')"

printf '\n  \033[1mŚlady środowiska wewnętrznego na odtworzonej witrynie\033[0m\n'
sprawdz 'brak adresu localhost:8888 na stronie głównej' 0 "$(ile / 'localhost:8888')"
sprawdz 'brak adresu localhost:8888 w katalogu' 0 "$(ile /szkolenia/ 'localhost:8888')"
# Zrzut bazy niesie `admin_email` środowiska wewnętrznego (`wordpress@example.com`)
# i nadpisuje nim tabelę opcji. Import musi zachować adres TEJ instalacji — inaczej
# powiadomienia witryny przestają docierać gdziekolwiek i nikt tego nie zauważa.
sprawdz 'adres administratora należy do instalacji docelowej' redakcja@iskt.pl "$(DST option get admin_email)"

printf '\n  \033[1mIndeksowanie — stan po imporcie (§7 instrukcji)\033[0m\n'
sprawdz 'blog_public = 0 (witryna poza indeksem do decyzji ISKT)' 0 "$(DST option get blog_public)"
sprawdz 'znacznik noindex obecny' 1 "$(ile / 'noindex')"

printf '\n  \033[1mZgodność liczb ze źródłem\033[0m\n'
if [[ -n "$ZRODLO_CLI" ]]; then
	for typ in iskt_szkolenie iskt_trener iskt_termin post page; do
		sprawdz "liczba wpisów: $typ" \
			"$(SRC post list --post_type="$typ" --post_status=publish --format=count)" \
			"$(DST post list --post_type="$typ" --post_status=publish --format=count)"
	done
	sprawdz 'wierszy w postmeta' \
		"$(SRC db query 'SELECT COUNT(*) FROM wp_postmeta' --skip-column-names)" \
		"$(DST db query 'SELECT COUNT(*) FROM wp_postmeta' --skip-column-names)"
else
	printf '  \033[33m!\033[0m Środowisko wewnętrzne nie działa — pomijam porównanie liczb.\n'
fi

krok 'Wynik odtworzenia'
if [[ "$BLEDY" == "0" ]]; then
	printf '  \033[32mWszystkie sprawdzenia przeszły.\033[0m Witryna działa pod %s\n' "$ADRES"
else
	printf '  \033[31m%d sprawdzeń nie przeszło.\033[0m\n' "$BLEDY"
fi

# --- Wycofanie ---------------------------------------------------------------
#
# Zlecenie wymaga, żeby wdrożenie dało się wycofać, więc wycofanie też jest przejściem
# do sprawdzenia, a nie akapitem w instrukcji. Robimy je NA KOŃCU, bo kasuje odtworzoną
# witrynę — zrzuty ekranu trzeba zrobić wcześniej.

if [[ "${ISKT_POMIN_WYCOFANIE:-}" == "tak" ]]; then
	krok 'Wycofanie pominięte (ISKT_POMIN_WYCOFANIE=tak) — witryna zostaje do obejrzenia.'
	printf '  Sprzątanie: docker rm -f %s %s\n' "$WEB" "$BAZA"
	exit "$BLEDY"
fi

krok 'Wycofanie importu — powrót do stanu sprzed'

docker exec -w /var/www/html \
	-e ISKT_WP='wp --allow-root' -e ISKT_POTWIERDZAM_NADPISANIE=tak \
	"$WEB" bash /narzedzia/wycofaj-import.sh /var/www/html/kopia-przed-importem

printf '\n'
sprawdz 'motyw wrócił do domyślnego WordPressa' twentytwentyfour "$(DST option get stylesheet)"
sprawdz 'wtyczka serwisu wyłączona' '' "$(DST plugin list --status=active --field=name | tr -d '\n')"
sprawdz 'katalogu szkoleń nie ma' 0 "$(DST post list --post_type=iskt_szkolenie --format=count 2>/dev/null || echo 0)"
sprawdz 'konto właściciela nietknięte' wlasciciel "$(DST user list --field=user_login | tr -d '\n')"
sprawdz 'witryna odpowiada' 200 "$(kod /)"

krok 'Wynik'
if [[ "$BLEDY" == "0" ]]; then
	printf '  \033[32mOdtworzenie i wycofanie przeszły w całości.\033[0m\n'
else
	printf '  \033[31m%d sprawdzeń nie przeszło.\033[0m\n' "$BLEDY"
fi
printf '  Zapis przebiegu: %s\n' "$LOG"
printf '  Sprzątanie: docker rm -f %s %s\n' "$WEB" "$BAZA"

exit "$BLEDY"
