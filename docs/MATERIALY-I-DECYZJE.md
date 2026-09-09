# Materiały i decyzje potrzebne od ISKT

Data: 2026-09-09 · Dotyczy ISK-17 §9 i §14

Zgodnie z §14 braki treściowe **nie blokują** niezależnych prac technicznych po
zatwierdzeniu startu. Poniższa lista jest jawna, tak jak wymaga §11 pkt 14.

Legenda pilności:
**[START]** — potrzebne, aby zacząć · **[BUDOWA]** — potrzebne w trakcie ·
**[PUBLIKACJA]** — potrzebne dopiero przed uruchomieniem domeny

---

## 1. Decyzje

| # | Sprawa | Pilność | Uwaga |
|---|---|---|---|
| D1 | Start realizacji planu i estymacji ≈ 36 dni | **[START]** | Bramka §12 |
| D2 | Edytor pól: ACF Pro 49 USD/rok czy Carbon Fields 0 zł (+2,5 dnia) | **[START]** | Płatny zakup = bramka §12. Rekomendacja: ACF Pro |
| D3 | Termin i budżet zlecenia | **[START]** | §1 pozostawia do ustalenia po estymacji |
| D4 | Repozytorium źródeł | ustalone | `github.com/Sebastian-Temich/szkolenia_iskt` — potwierdzić, że to właściwe miejsce |
| D5 | Czy zapisywać zgłoszenia w bazie i na jak długo | **[BUDOWA]** | Domyślnie **nie zapisujemy** — tylko email. Włączenie to przełącznik |
| D6 | Czy zgłaszający ma otrzymywać email potwierdzający | **[BUDOWA]** | §5 „do uzgodnienia” |
| D7 | Cloudflare Turnstile jako dodatkowa ochrona antyspamowa (0 zł) | **[BUDOWA]** | Bez niego: honeypot + pułapka czasowa + limit na IP |
| D8 | Analityka — czy i jaka | **[PUBLIKACJA]** | §6: nie blokuje budowy |
| D9 | Akceptacja projektu widoków **terminów** i **aktualności** | **[BUDOWA]** | Brak wzorca w załączniku (§4.6) — przygotujemy propozycję |
| D10 | Czy istnieje licencjonowany krój firmowy | **[BUDOWA]** | Brak plików w paczce; design system sam oznacza Inter jako substytut |
| D11 | Sposób prezentacji ceny: netto/brutto i jednostka (za osobę / za grupę) | **[BUDOWA]** | §4.3 wymaga jednoznaczności |

## 2. Dostępy techniczne

| # | Zasób | Pilność | Uwaga |
|---|---|---|---|
T1 | Dane SMTP autoryzowanego nadawcy w domenie iskt.pl | **[BUDOWA]** | Do wysyłki z `From` w domenie i `Reply-To` zgłaszającego. Do środowiska wewnętrznego wystarczy skrzynka testowa |
| T2 | Potwierdzenie adresu odbiorcy zgłoszeń | **[BUDOWA]** | Zakładamy szkolenia@iskt.pl (§5) |
| T3 | Dostęp do SEOHost | **niepotrzebny** | §8: brak dostępu nie blokuje budowy; wdrożenie poza zakresem (§13) |
| T4 | Dostęp do obecnego WordPressa właściciela | **niepotrzebny** | §8 wprost tego nie wymaga |

Sekrety przekazywać kanałem bezpiecznym, **nie** w treści zgłoszenia ani w repozytorium.
Nie trafią do paczki przekazania (§8).

## 3. Treści wymagające potwierdzenia przed publikacją (§9)

Do czasu potwierdzenia pozostają oznaczone jako demonstracyjne w środowisku
wewnętrznym albo ukryte przed publikacją.

| # | Treść | Stan w materiałach | Pilność |
|---|---|---|---|
| C1 | Numer telefonu | W prototypie `+48 32 000 00 00` — wygląda na wypełniacz | **[PUBLIKACJA]** |
| C2 | Nazwa prawna i pozostałe dane firmy | Brak | **[PUBLIKACJA]** |
| C3 | Prawdziwe szkolenia: nazwy, opisy, program, poziom, czas | 9 pozycji demonstracyjnych | **[BUDOWA]** — choćby 2–3 prawdziwe pozwolą zweryfikować szablon |
| C4 | Ceny | 1 490 – 2 900 zł, do weryfikacji | **[PUBLIKACJA]** |
| C5 | Prawdziwe terminy | Brak — model terminów nie istnieje w prototypie | **[PUBLIKACJA]** |
| C6 | Prawdziwi trenerzy: nazwiska, role, biografie, doświadczenie, certyfikaty | Dane przykładowe (np. „dr Anna Nowak”) | **[PUBLIKACJA]** |
| C7 | Prawa do zdjęć, biografii i certyfikatów trenerów | Nieudokumentowane | **[PUBLIKACJA]** |
| C8 | Wskaźniki „15+ lat”, „200+ szkoleń”, „4.9/5” | W prototypie bez źródła | **[PUBLIKACJA]** — twierdzenia weryfikowalne, wymagają podstawy |
| C9 | Warunki dofinansowania, KFS/BUR, procenty wsparcia | Ogólne w prototypie | **[PUBLIKACJA]** |
| C10 | Obietnica odpowiedzi w 24 godziny | W prototypie | **[PUBLIKACJA]** — zobowiązanie wobec klienta |
| C11 | Polityka prywatności i ewentualny regulamin | Brak | **[PUBLIKACJA]** — formularz musi się do niej odwoływać (§5) |
| C12 | Zatwierdzone teksty obu wariantów odbiorcy | Wersje demonstracyjne | **[BUDOWA]** |

Zgodnie z §4.3 **nie** wyliczamy automatycznie ceny po dofinansowaniu z założenia, że
każdy uczestnik otrzyma wsparcie na określonym poziomie.

## 4. Testy niewykonane w tym środowisku (§11 pkt 14)

Jawna lista — nie da się ich wykonać przed migracją i nie blokują odbioru budowy:

1. Dostarczenie zgłoszenia na prawdziwą skrzynkę szkolenia@iskt.pl (wymaga poczty docelowej).
2. Poprawność SPF/DKIM/DMARC dla nadawcy w domenie iskt.pl (wymaga DNS — poza zakresem §13).
3. Działanie na docelowym hostingu SEOHost: wersja PHP, limity, cron, HTTPS.
4. Pomiar wydajności na docelowej infrastrukturze — mierzymy w środowisku wewnętrznym.
5. Indeksowanie na szkolenia.iskt.pl — włączenie jest punktem instrukcji publikacji, nie czynnością wykonywaną tutaj (§7).
