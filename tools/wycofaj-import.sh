#!/usr/bin/env bash
#
# Wycofanie importu — powrót do stanu sprzed uruchomienia `import-witryny.sh`.
#
# Zlecenie wymaga, żeby wdrożenie dało się wycofać. Wymóg jest sensowny tylko wtedy,
# gdy wycofanie jest poleceniem, a nie opisem: po nieudanym imporcie osoba przy
# klawiaturze ma zwykle kilkanaście minut i witrynę, która nie działa, więc nie jest
# to moment na odtwarzanie kroków z dokumentu.
#
# Kopię zakłada sam import, ZANIM cokolwiek nadpisze — ten skrypt tylko ją przywraca.
#
# Użycie (w katalogu głównym WordPressa):
#   ISKT_POTWIERDZAM_NADPISANIE=tak tools/wycofaj-import.sh [katalog-kopii]

set -euo pipefail

KOPIA="${1:-$PWD/kopia-przed-importem}"
KOPIA="$(cd "$KOPIA" && pwd)"

blad() { printf '\033[31mBŁĄD:\033[0m %s\n' "$*" >&2; exit 1; }
krok() { printf '\n\033[1m» %s\033[0m\n' "$*"; }
ok() { printf '  \033[32m✓\033[0m %s\n' "$*"; }

if [[ -n "${ISKT_WP:-}" ]]; then
	read -r -a WP_CMD <<< "$ISKT_WP"
else
	WP_CMD=(wp)
fi
wpcli() { "${WP_CMD[@]}" "$@"; }

[[ -f wp-config.php ]] || blad 'Uruchom skrypt w katalogu głównym WordPressa.'
[[ -f "$KOPIA/baza.sql" && -f "$KOPIA/wp-content.tar.gz" ]] || blad "W $KOPIA brakuje kopii bazy lub plików."

[[ "${ISKT_POTWIERDZAM_NADPISANIE:-}" == "tak" ]] || blad \
	'Wycofanie KASUJE obecną bazę i katalog wp-content. Uruchom ponownie z ISKT_POTWIERDZAM_NADPISANIE=tak.'

krok 'Przywracanie bazy'
wpcli db reset --yes --quiet
wpcli db import "$KOPIA/baza.sql" --quiet
ok 'Baza z chwili sprzed importu.'

krok 'Przywracanie wp-content'
# Katalog kasujemy w całości, a nie rozpakowujemy na wierzch: po imporcie leżą w nim
# pliki, których w kopii nie ma, a wycofanie ma dać stan sprzed importu, nie sumę.
rm -rf wp-content
tar -xzf "$KOPIA/wp-content.tar.gz" -C .
ok 'Motyw, wtyczki i media z chwili sprzed importu.'

krok 'Reguły adresów'
wpcli rewrite flush --hard --quiet
wpcli transient delete --all --quiet
ok 'Przeładowane.'

krok "Stan: $(wpcli option get siteurl), motyw $(wpcli option get stylesheet)"
