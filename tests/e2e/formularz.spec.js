/**
 * ISK-17 M2 zadanie 9 — formularz zgłoszeniowy w przeglądarce.
 *
 * Test sprawdza cztery rzeczy, które zlecenie stawia jako warunek odbioru (§5):
 *
 * 1. poprawne zgłoszenie DOCIERA do mechanizmu wysyłkowego — dowodem jest
 *    przechwycona wiadomość z nagłówkami i treścią, a nie sam napis na ekranie;
 * 2. przy błędzie wpisane dane ZOSTAJĄ w polach;
 * 3. wejście ze strony szkolenia uzupełnia szkolenie i termin;
 * 4. podziękowanie NIE pojawia się, gdy wysyłka zawiodła — ani gdy adres
 *    z potwierdzeniem ktoś wpisze ręcznie.
 *
 * Wysyłkę przechwytuje `tests/env/mu-plugins/iskt-poczta-testowa.php`, podpięty do
 * `wp-env` przez `mappings`. Kontener nie ma agenta pocztowego, więc bez tego
 * `wp_mail()` zwracałoby `false` przy każdej próbie i ścieżki powodzenia nie dałoby
 * się w ogóle sprawdzić. Dostawa na docelową skrzynkę `szkolenia@iskt.pl` jest poza
 * zasięgiem tego zadania (§8 — brak dostępu do poczty ISKT); odnotowane w
 * `docs/FORMULARZ-ZGLOSZENIOWY.md`.
 *
 * Przygotowanie:
 *   npx @wordpress/env start
 *   npx @wordpress/env run cli wp eval-file wp-content/iskt-tests/e2e/dane-formularza.php
 */
const { test, expect } = require('@playwright/test');
const { execFileSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const base = 'http://localhost:8888';
const formularz = base + '/zgloszenie/';
const DZIENNIK = path.join(__dirname, '..', '..', '.wp-env', 'uploads', 'iskt-poczta.jsonl');
const SZKOLENIE = 'Demo — ESG w praktyce';

/** Minimalny czas wypełniania formularza (3 s) z zapasem na opóźnienie żądania. */
const CZAS_WYPELNIANIA = 3500;

test.describe.configure({ mode: 'serial' });
test.setTimeout(120000);

/** Uruchamia polecenie WP-CLI w kontenerze wp-env. */
function wpcli(...argumenty) {
  return execFileSync('npx', ['@wordpress/env', 'run', 'cli', 'wp', ...argumenty], {
    cwd: path.join(__dirname, '..', '..'),
    encoding: 'utf8',
  });
}

/** Zwraca przechwycone wiadomości, od najnowszej. */
function przechwyconePoczty() {
  if (!fs.existsSync(DZIENNIK)) {
    return [];
  }

  return fs
    .readFileSync(DZIENNIK, 'utf8')
    .split('\n')
    .filter((wiersz) => wiersz.trim() !== '')
    .map((wiersz) => JSON.parse(wiersz))
    .reverse();
}

/**
 * Wypełnia formularz danymi zgłaszającego.
 *
 * Nie dotyka pól kontekstu (szkolenie, termin) — sprawdza je osobny przypadek.
 */
async function wypelnij(page, dane = {}) {
  await page.check('#iskt-zgl-typ-' + (dane.typ || 'firma'));
  await page.fill('#iskt-zgl-imie', dane.imie ?? 'Demo — Anna Kowalska');
  await page.fill('#iskt-zgl-email', dane.email ?? 'anna@example.org');
  await page.fill('#iskt-zgl-telefon', dane.telefon ?? '+48 32 000 00 00');
  await page.fill('#iskt-zgl-firma', dane.firma ?? 'Demo sp. z o.o.');
  await page.fill('#iskt-zgl-wiadomosc', dane.wiadomosc ?? 'Proszę o ofertę dla ośmiu osób.');
}

/** Wyłącza walidację przeglądarki, żeby żądanie doszło do walidacji serwerowej. */
async function bezWalidacjiPrzegladarki(page) {
  await page.locator('form.iskt-form').evaluate((form) => {
    form.noValidate = true;
  });
}

test.beforeAll(() => {
  wpcli('eval-file', 'wp-content/iskt-tests/e2e/dane-formularza.php');
});

test.afterAll(() => {
  wpcli('option', 'delete', 'iskt_test_poczta_awaria');
  wpcli('option', 'delete', 'iskt_test_odstep');
});

test('poprawne zgłoszenie dociera do wysyłki z pełnym kontekstem', async ({ page }) => {
  await page.goto(formularz + '?iskt_zgl_szkolenie=' + (await idSzkolenia(page)));

  await wypelnij(page);
  await page.waitForTimeout(CZAS_WYPELNIANIA);
  await page.click('form.iskt-form button[type="submit"]');

  await expect(page.locator('.iskt-notice--success')).toContainText('zapytanie do nas dotarło');

  // Formularza nie ma po wysłaniu — puste pola pod podziękowaniem zapraszałyby
  // do wysłania tego samego zapytania drugi raz.
  await expect(page.locator('form.iskt-form')).toHaveCount(0);

  const wiadomosc = przechwyconePoczty()[0];

  expect(wiadomosc, 'wiadomość została przekazana do wysyłki').toBeTruthy();
  expect(wiadomosc.to).toBe('szkolenia@iskt.pl');
  expect(wiadomosc.subject).toContain(SZKOLENIE);

  const naglowki = [].concat(wiadomosc.headers).join('\n');

  expect(naglowki).toContain('Content-Type: text/plain; charset=UTF-8');
  expect(naglowki).toContain('Reply-To: "Demo — Anna Kowalska" <anna@example.org>');

  // Nadawcy nie podstawiamy — adres zgłaszającego nie może trafić do `From`.
  expect(naglowki).not.toContain('From: anna@example.org');

  expect(wiadomosc.message).toContain('Piszę jako: Firma');
  expect(wiadomosc.message).toContain('Imię i nazwisko: Demo — Anna Kowalska');
  expect(wiadomosc.message).toContain('Adres e-mail: anna@example.org');
  // Etykiety w wiadomości są tymi samymi napisami, które właściciel widzi
  // przy polach formularza — jedno źródło, jedna zmiana w panelu (§4.7).
  expect(wiadomosc.message).toContain('Telefon (opcjonalnie): +48 32 000 00 00');
  expect(wiadomosc.message).toContain('Nazwa firmy (opcjonalnie): Demo sp. z o.o.');
  expect(wiadomosc.message).toContain('Szkolenie lub obszar zainteresowania: ' + SZKOLENIE);
  expect(wiadomosc.message).toContain('Proszę o ofertę dla ośmiu osób.');

  // Adres w stopce wiadomości jest pełny — w skrzynce nie ma czego dopełnić.
  expect(wiadomosc.message).toContain('Zapytanie wysłane ze strony: http://localhost:8888/zgloszenie/');
});

test('błędy walidacji serwerowej nie kasują wpisanych danych', async ({ page }) => {
  await page.goto(formularz);

  /*
   * Same spacje przechodzą przez `required` przeglądarki, a `a@b` przez `type="email"`.
   * Odrzucenie obu dowodzi, że decyduje serwer, a nie przeglądarka.
   */
  await wypelnij(page, { imie: '   ', email: 'a@b', wiadomosc: 'Treść, której nie chcę pisać drugi raz.' });
  await bezWalidacjiPrzegladarki(page);
  await page.waitForTimeout(CZAS_WYPELNIANIA);
  await page.click('form.iskt-form button[type="submit"]');

  await expect(page.locator('.iskt-notice--error')).toContainText('Popraw zaznaczone pola');
  await expect(page.locator('#iskt-zgl-imie-blad')).toHaveText('To pole jest wymagane.');
  await expect(page.locator('#iskt-zgl-email-blad')).toContainText('nazwa@domena.pl');
  await expect(page.locator('#iskt-zgl-imie')).toHaveAttribute('aria-invalid', 'true');

  // Sedno przypadku: to, co odwiedzający wpisał, wraca do pól.
  await expect(page.locator('#iskt-zgl-email')).toHaveValue('a@b');
  await expect(page.locator('#iskt-zgl-wiadomosc')).toHaveValue('Treść, której nie chcę pisać drugi raz.');
  await expect(page.locator('#iskt-zgl-telefon')).toHaveValue('+48 32 000 00 00');
  await expect(page.locator('#iskt-zgl-firma')).toHaveValue('Demo sp. z o.o.');
  await expect(page.locator('#iskt-zgl-typ-firma')).toBeChecked();
});

test('wejście ze strony szkolenia uzupełnia szkolenie i termin', async ({ page }) => {
  await page.goto(base + '/szkolenia/demo-formularz-esg-w-praktyce/');

  // Przycisk nad listą terminów niesie samo szkolenie…
  const przycisk = page.locator('.iskt-panel-oferta a.iskt-button').first();
  await expect(przycisk).toHaveAttribute('href', /iskt_zgl_szkolenie=\d+/);

  // …a odnośnik przy konkretnym terminie dokłada datę.
  await page.locator('.iskt-termin__akcja a').first().click();

  await expect(page.locator('#iskt-zgl-temat')).toHaveValue(/^s-\d+$/);
  await expect(page.locator('#iskt-zgl-temat option:checked')).toHaveText(SZKOLENIE);

  const termin = page.locator('#iskt-zgl-termin');
  await expect(termin).toHaveValue(/^\d+$/);
  await expect(page.locator('#iskt-zgl-termin option:checked')).not.toHaveText('Termin do ustalenia');

  /*
   * Uzupełnione pole to dopiero połowa wymagania §5: kontekst terminu ma dotrzeć
   * do WIADOMOŚCI, a nie tylko wyglądać poprawnie na ekranie. Bez tego przejścia
   * nikt nie sprawdza całej drogi od kliknięcia przy dacie do treści w skrzynce.
   */
  const etykietaTerminu = (await page.locator('#iskt-zgl-termin option:checked').textContent()).trim();

  await wypelnij(page);
  await page.waitForTimeout(CZAS_WYPELNIANIA);
  await page.click('form.iskt-form button[type="submit"]');

  await expect(page.locator('.iskt-notice--success')).toBeVisible();

  const wiadomosc = przechwyconePoczty()[0];

  expect(wiadomosc.message).toContain('Szkolenie lub obszar zainteresowania: ' + SZKOLENIE);
  expect(wiadomosc.message).toContain('Wybrany termin: ' + etykietaTerminu);
});

test('zapytanie ogólne bez wskazanej oferty przechodzi', async ({ page }) => {
  await page.goto(formularz);

  await expect(page.locator('#iskt-zgl-temat')).toHaveValue('');

  // Bez wybranego szkolenia nie ma czego wybierać w terminach — pola nie pokazujemy.
  await expect(page.locator('#iskt-zgl-termin')).toHaveCount(0);

  await wypelnij(page, { typ: 'osoba', wiadomosc: 'Szukam czegoś o raportowaniu, jeszcze nie wiem czego.' });
  await page.waitForTimeout(CZAS_WYPELNIANIA);
  await page.click('form.iskt-form button[type="submit"]');

  await expect(page.locator('.iskt-notice--success')).toBeVisible();

  const wiadomosc = przechwyconePoczty()[0];
  expect(wiadomosc.subject).toContain('Zapytanie ogólne');
  expect(wiadomosc.message).toContain('Piszę jako: Osoba indywidualna');
});

test('pułapka antyspamowa zatrzymuje wysyłkę', async ({ page }) => {
  const przed = przechwyconePoczty().length;

  await page.goto(formularz);
  await wypelnij(page);

  // Automat wypełnia wszystko, co znajdzie w dokumencie — łącznie z ukrytym polem.
  await page.locator('input[name="iskt_adres_www"]').fill('https://spam.example', { force: true });

  await page.waitForTimeout(CZAS_WYPELNIANIA);
  await page.click('form.iskt-form button[type="submit"]');

  await expect(page.locator('.iskt-notice--error')).toContainText('wysłał człowiek');
  await expect(page.locator('.iskt-notice--success')).toHaveCount(0);

  expect(przechwyconePoczty().length, 'zatrzymane zgłoszenie nie trafia do wysyłki').toBe(przed);
});

test('wysyłka szybsza niż czytanie formularza jest odrzucana', async ({ page }) => {
  const przed = przechwyconePoczty().length;

  await page.goto(formularz);
  await wypelnij(page);

  // Bez odczekania: automat wysyła gotowy zestaw pól natychmiast po wczytaniu strony.
  await page.click('form.iskt-form button[type="submit"]');

  await expect(page.locator('.iskt-notice--error')).toContainText('wysłał człowiek');
  expect(przechwyconePoczty().length).toBe(przed);
});

test('nieudana wysyłka daje komunikat błędu, nie podziękowanie', async ({ page }) => {
  wpcli('option', 'update', 'iskt_test_poczta_awaria', '1');

  try {
    await page.goto(formularz);
    await wypelnij(page);
    await page.waitForTimeout(CZAS_WYPELNIANIA);
    await page.click('form.iskt-form button[type="submit"]');

    /*
     * To jest błąd z prototypu, którego nie powtarzamy: podziękowanie mimo braku
     * wysyłki. Formularz z danymi ma zostać na ekranie, żeby dało się spróbować.
     */
    await expect(page.locator('.iskt-notice--error')).toContainText('Nie udało się dostarczyć wiadomości');
    await expect(page.locator('.iskt-notice--success')).toHaveCount(0);
    await expect(page.locator('#iskt-zgl-imie')).toHaveValue('Demo — Anna Kowalska');
  } finally {
    wpcli('option', 'delete', 'iskt_test_poczta_awaria');
  }
});

test('adres z potwierdzeniem wpisany ręcznie nie udaje sukcesu', async ({ page }) => {
  await page.goto(formularz + '?iskt_wyslano=1');

  await expect(page.locator('.iskt-notice--success')).toHaveCount(0);
  await expect(page.locator('form.iskt-form')).toHaveCount(1);
});

test('limit odstępu między zgłoszeniami z jednego łącza działa', async ({ page }) => {
  wpcli('option', 'update', 'iskt_test_odstep', '60');

  try {
    await page.goto(formularz);
    await wypelnij(page);
    await page.waitForTimeout(CZAS_WYPELNIANIA);
    await page.click('form.iskt-form button[type="submit"]');
    await expect(page.locator('.iskt-notice--success')).toBeVisible();

    await page.goto(formularz);
    await wypelnij(page);
    await page.waitForTimeout(CZAS_WYPELNIANIA);
    await page.click('form.iskt-form button[type="submit"]');

    await expect(page.locator('.iskt-notice--error')).toContainText('Odczekaj minutę');
  } finally {
    wpcli('option', 'delete', 'iskt_test_odstep');
    wpcli('transient', 'delete', '--all');
  }
});

/** Zwraca identyfikator szkolenia demonstracyjnego odczytany ze strony szkolenia. */
async function idSzkolenia(page) {
  await page.goto(base + '/szkolenia/demo-formularz-esg-w-praktyce/');

  const adres = await page.locator('.iskt-panel-oferta a.iskt-button').first().getAttribute('href');
  const dopasowanie = /iskt_zgl_szkolenie=(\d+)/.exec(adres || '');

  expect(dopasowanie, 'przycisk zgłoszenia niesie identyfikator szkolenia').toBeTruthy();

  return dopasowanie[1];
}
