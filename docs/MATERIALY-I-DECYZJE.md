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
| D1 | Start realizacji | **zamknięte 2026-09-09** | ISKT zatwierdziło start w zmienionym zakresie — patrz `ADR-002-*.md` |
| D2 | Edytor pól | **zamknięte 2026-09-09** | ACF Pro odrzucone. Realizujemy natywnie: `register_post_meta` + skrzynki metadanych w edytorze blokowym. Zero zależności |
| D3 | Termin i budżet zlecenia | **[START] — nadal otwarte** | Odpowiedź ISKT ich nie podała. Nie blokuje realizacji; wraca przy odbiorze M1 |
| D4 | Repozytorium źródeł | ustalone | `github.com/Sebastian-Temich/szkolenia_iskt` — potwierdzić, że to właściwe miejsce |
| D5 | Czy zapisywać zgłoszenia w bazie i na jak długo | **[BUDOWA]** | Domyślnie **nie zapisujemy** — tylko email. Włączenie to przełącznik |
| D6 | Czy zgłaszający ma otrzymywać email potwierdzający | **[BUDOWA]** | §5 „do uzgodnienia” |
| D7 | ~~Cloudflare Turnstile~~ | **zamknięte 2026-09-09** | Odrzucone — usługa zewnętrzna. Antyspam wyłącznie własny: honeypot, pułapka czasowa, `nonce`, limit na IP |
| D8 | ~~Analityka~~ | **zamknięte 2026-09-09** | Poza bieżącym zakresem (pkt 4 decyzji) |
| D9 | Akceptacja projektu widoków **terminów** i **aktualności** | **[BUDOWA]** | Brak wzorca w załączniku (§4.6) — przygotujemy propozycję |
| D10 | Czy istnieje licencjonowany krój firmowy | **[BUDOWA]** | Brak plików w paczce; design system sam oznacza Inter jako substytut |
| D11 | Sposób prezentacji ceny: netto/brutto i jednostka (za osobę / za grupę) | **[BUDOWA]** | Pole już istnieje i wymusza wybór ze słownika; potrzebujemy tylko wartości domyślnej dla oferty |
| D12 | **Uruchomienie środowiska wewnętrznego** — demon Dockera nie działa w środowisku wykonawczym | **[START] — nowe** | Blokuje uruchomienie i test wizualny, nie blokuje pisania kodu. Właściciel działania: organizacja |
| D13 | Kiedy wrócić do warstwy SEO i danych strukturalnych | **[PUBLIKACJA]** | Wyłączone decyzją pkt 4. Wycena osobna: ok. 1,5 dnia, bez migracji danych |

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
   Zadanie 9 sprawdziło wszystko po naszej stronie: wiadomość z poprawnym odbiorcą, tematem,
   nagłówkami i treścią zostaje przekazana do mechanizmu wysyłkowego, a nieudana wysyłka daje
   komunikat błędu zamiast podziękowania. Dostawa i filtry antyspamowe odbiorcy — po migracji.
   Szczegóły: `docs/FORMULARZ-ZGLOSZENIOWY.md` §8.
2. Poprawność SPF/DKIM/DMARC dla nadawcy w domenie iskt.pl (wymaga DNS — poza zakresem §13).
3. Działanie na docelowym hostingu SEOHost: wersja PHP, limity, cron, HTTPS.
4. Pomiar wydajności na docelowej infrastrukturze — mierzymy w środowisku wewnętrznym.
5. Indeksowanie na szkolenia.iskt.pl — włączenie jest punktem instrukcji publikacji, nie czynnością wykonywaną tutaj (§7).
