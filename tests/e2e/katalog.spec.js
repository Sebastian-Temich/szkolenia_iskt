/**
 * ISK-17 M2 zadanie 6 — katalog szkoleń oczami odwiedzającego.
 *
 * §4.2 wymaga katalogu z wyszukiwaniem, filtrowaniem po kategorii i formie,
 * wyróżnianiem wybranych szkoleń i paginacją. Ten test przechodzi tę ścieżkę tak,
 * jak zrobi to odwiedzający: wpisuje frazę w pole, wybiera pozycje z list i klika
 * przyciski — nie doklejając parametrów do adresu.
 *
 * Lekcja z M1 zastosowana wprost: widok sprawdzamy na danych, a nie na pustym
 * katalogu. Testy M1 przepuściły szablon, który nigdy się nie wyrenderował, bo
 * sprawdzały wyłącznie stan bez danych.
 *
 * Dane demonstracyjne zakłada `dane-katalogu.php` przez WP-CLI. To świadomy wybór:
 * ścieżkę WŁAŚCICIELA (dodanie szkolenia przez panel) sprawdza `odbior-m1.spec.js`,
 * a tutaj sprawdzamy ścieżkę ODWIEDZAJĄCEGO, dla której źródło danych nie ma
 * znaczenia — liczy się, że katalog ma z czego wybierać.
 */
const { test, expect } = require('@playwright/test');
const { execFileSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const base = 'http://localhost:8888';
const shots = 'qa-artifacts/isk-17-katalog';
const katalog = base + '/szkolenia/';

test.describe.configure({ mode: 'serial' });
test.setTimeout(120000);

/** Domyślne napisy z rejestru tekstów (grupa „Katalog szkoleń”). */
const SZUKAJ_ETYKIETA = 'Szukaj szkolenia';
const SZUKAJ_PRZYCISK = 'Szukaj';
const FILTR_KATEGORIA = 'Kategoria';
const FILTR_FORMA = 'Forma realizacji';
const FILTR_ZASTOSUJ = 'Pokaż wyniki';
const FILTR_WYCZYSC = 'Wyczyść filtry';
const BRAK_WYNIKOW = 'Nie znaleźliśmy szkoleń dla tych kryteriów';
const BRAK_OFERTY = 'Katalog jest w przygotowaniu';

/** Fraza obecna wyłącznie w OPISIE jednego szkolenia — dowodzi, że szukamy też w opisie. */
const FRAZA_Z_OPISU = 'termowizja';
const SZKOLENIE_Z_TERMOWIZJA = 'Demo — Audyt energetyczny budynków';

const NA_STRONE = 9;

/**
 * Uruchamia polecenie WP-CLI w środowisku wp-env.
 *
 * Jedna ponowna próba nie jest ukrywaniem błędu testu: kontener `cli` bywa
 * chwilowo niedostępny, gdy ktoś równolegle podnosi środowisko, a to awaria
 * maszyny, nie serwisu. Gdy druga próba też zawiedzie, test upada z komunikatem
 * z Dockera.
 */
function wp(...argumenty) {
  const uruchom = () =>
    execFileSync('npx', ['@wordpress/env', 'run', 'cli', 'wp', ...argumenty], {
      cwd: process.cwd(),
      encoding: 'utf8',
      stdio: ['ignore', 'pipe', 'pipe'],
    });

  try {
    return uruchom();
  } catch (blad) {
    execFileSync('sleep', ['10']);

    return uruchom();
  }
}

/** Wyłuskuje listę identyfikatorów z wyniku `--format=ids`. */
function identyfikatory(wynik) {
  return (wynik.match(/^\d+(?:\s+\d+)*$/m) || [''])[0].split(/\s+/).filter(Boolean);
}

/** Identyfikatory szkoleń o podanym stanie publikacji. */
function idSzkolen(status) {
  return identyfikatory(wp('post', 'list', '--post_type=iskt_szkolenie', `--post_status=${status}`, '--format=ids'));
}

/** Identyfikator szkolenia o podanym slugu. */
function idSzkolenia(slug) {
  return identyfikatory(wp('post', 'list', '--post_type=iskt_szkolenie', `--name=${slug}`, '--post_status=any', '--format=ids'));
}

function ustawStan(lista, status) {
  if (lista.length > 0) {
    wp('post', 'update', ...lista, `--post_status=${status}`);
  }
}

function karty(page) {
  return page.locator('.iskt-card');
}

/** Pasek filtrów — kontrolki bierzemy z niego, a nie z całej strony. */
function filtry(page) {
  return page.locator('.iskt-filters');
}

test.beforeAll(() => {
  // Skrypt musi być widoczny w kontenerze — katalog uploadów jest jedynym wspólnym.
  const zrodlo = path.join(__dirname, 'dane-katalogu.php');
  const cel = path.join(process.cwd(), '.wp-env', 'uploads', 'iskt-dane-katalogu.php');

  fs.mkdirSync(path.dirname(cel), { recursive: true });
  fs.copyFileSync(zrodlo, cel);

  wp('eval-file', 'wp-content/uploads/iskt-dane-katalogu.php');

  /*
   * wp-env instaluje WordPressa z prośbą o nieindeksowanie, przez co KAŻDA strona
   * dostaje `noindex`. Bez tego ustawienia asercja o widokach filtrowanych
   * przechodziłaby zawsze, także gdyby reguła z §7 w ogóle nie istniała.
   */
  wp('option', 'update', 'blog_public', '1');
});

test('katalog pokazuje ofertę, stronicuje i wyróżnia wybrane szkolenia', async ({ page }) => {
  await page.goto(katalog);

  await expect(page.locator('h1')).toContainText('Katalog szkoleń');
  await expect(karty(page)).toHaveCount(NA_STRONE);

  // §4.2 — wyróżnienie widać na karcie, a wyróżnione idą na początek listy.
  await expect(karty(page).first()).toHaveClass(/iskt-card--featured/);
  await expect(karty(page).first().locator('.iskt-card__flag')).toContainText('Wyróżnione');

  const pierwszaStrona = await karty(page).locator('.iskt-card__title').allInnerTexts();

  await page.getByRole('link', { name: 'Następna' }).click();
  await expect(page).toHaveURL(/\/szkolenia\/page\/2\//);

  const drugaStrona = await karty(page).locator('.iskt-card__title').allInnerTexts();

  expect(drugaStrona.length).toBeGreaterThan(0);
  expect(drugaStrona.filter((tytul) => pierwszaStrona.includes(tytul))).toHaveLength(0);

  // Powrót „Wstecz” ma wrócić na pierwszą stronę katalogu (§6).
  await page.goBack();
  await expect(karty(page)).toHaveCount(NA_STRONE);
});

test('wyszukiwanie znajduje szkolenie po treści opisu, nie tylko po tytule', async ({ page }) => {
  await page.goto(katalog);

  // getByLabel przechodzi tylko wtedy, gdy etykieta jest powiązana z polem przez for/id (§7).
  await filtry(page).getByLabel(SZUKAJ_ETYKIETA).fill(FRAZA_Z_OPISU);
  await filtry(page).getByRole('button', { name: SZUKAJ_PRZYCISK }).click();

  await expect(page).toHaveURL(new RegExp(`szukaj=${FRAZA_Z_OPISU}`));
  await expect(karty(page)).toHaveCount(1);
  await expect(karty(page).first()).toContainText(SZKOLENIE_Z_TERMOWIZJA);

  // Stan wyszukiwania zostaje w polu — odwiedzający widzi, czego szuka.
  await expect(filtry(page).getByLabel(SZUKAJ_ETYKIETA)).toHaveValue(FRAZA_Z_OPISU);

  // §7 — widoki z parametrami filtrów zostają poza indeksem, katalog bez nich nie.
  await expect(page.locator('meta[name="robots"]')).toHaveAttribute('content', /noindex/);

  await page.goto(katalog);
  await expect(page.locator('meta[name="robots"]')).not.toHaveAttribute('content', /noindex/);
});

test('filtr kategorii i formy zawężają katalog, także razem', async ({ page }) => {
  await page.goto(katalog);

  await filtry(page).getByLabel(FILTR_KATEGORIA).selectOption({ label: 'Język angielski' });
  await filtry(page).getByRole('button', { name: FILTR_ZASTOSUJ }).click();

  await expect(page).toHaveURL(/kategoria=jezyk-angielski/);
  await expect(karty(page)).toHaveCount(2);
  await expect(karty(page).first()).toContainText('Angielski');

  // Wybór zostaje widoczny w kontrolce, a nie tylko w adresie.
  await expect(filtry(page).getByLabel(FILTR_KATEGORIA)).toHaveValue('jezyk-angielski');

  await filtry(page).getByLabel(FILTR_FORMA).selectOption({ label: 'Online' });
  await filtry(page).getByRole('button', { name: FILTR_ZASTOSUJ }).click();

  await expect(page).toHaveURL(/kategoria=jezyk-angielski/);
  await expect(page).toHaveURL(/forma=online/);
  await expect(karty(page)).toHaveCount(1);
  await expect(karty(page).first()).toContainText('Angielski techniczny dla inżynierów');

  // Adres niesie komplet stanu, więc wynik da się odesłać linkiem (§6).
  const link = page.url();
  await page.goto(base + '/');
  await page.goto(link);
  await expect(karty(page)).toHaveCount(1);
});

test('brak trafień tłumaczy sytuację i daje drogę powrotną', async ({ page }) => {
  await page.goto(katalog);

  await filtry(page).getByLabel(SZUKAJ_ETYKIETA).fill('kwantowa kryptografia');
  await filtry(page).getByRole('button', { name: SZUKAJ_PRZYCISK }).click();

  await expect(karty(page)).toHaveCount(0);
  await expect(page.locator('.iskt-katalog__pusto')).toContainText(BRAK_WYNIKOW);

  // Komunikat pustego katalogu to inna sytuacja — tu nie może się pojawić.
  await expect(page.locator('body')).not.toContainText(BRAK_OFERTY);

  await page.locator('.iskt-katalog__pusto').getByRole('link', { name: FILTR_WYCZYSC }).click();

  await expect(page).toHaveURL(katalog);
  await expect(karty(page)).toHaveCount(NA_STRONE);
  await expect(filtry(page).getByLabel(SZUKAJ_ETYKIETA)).toHaveValue('');
});

test('filtry działają bez JavaScriptu', async ({ browser }) => {
  const kontekst = await browser.newContext({ javaScriptEnabled: false });
  const page = await kontekst.newPage();

  await page.goto(katalog);

  await filtry(page).getByLabel(FILTR_FORMA).selectOption({ label: 'Stacjonarnie' });
  await filtry(page).getByRole('button', { name: FILTR_ZASTOSUJ }).click();

  await expect(page).toHaveURL(/forma=stacjonarnie/);
  await expect(karty(page).first()).toBeVisible();
  await expect(filtry(page).getByLabel(FILTR_FORMA)).toHaveValue('stacjonarnie');

  await page.screenshot({ path: `${shots}/bez-js-1440.png`, fullPage: true });

  await kontekst.close();
});

test('wycofane szkolenie znika z katalogu, wyszukiwania i filtrów', async ({ page }) => {
  const wszystkie = [
    ...idSzkolenia('demo-wniosek-o-dofinansowanie-br'),
    ...idSzkolenia('demo-zarzadzanie-projektem-badawczym'),
  ];
  expect(wszystkie.length).toBe(2);

  try {
    ustawStan(wszystkie, 'draft');

    await page.goto(katalog + '?szukaj=badawczym');
    await expect(karty(page)).toHaveCount(0);

    // Kategoria bez opublikowanego szkolenia znika z listy filtra — §4.2.
    await page.goto(katalog);
    await expect(filtry(page).getByLabel(FILTR_KATEGORIA)).not.toContainText('Projekty B+R');
  } finally {
    ustawStan(wszystkie, 'publish');
  }

  await page.goto(katalog);
  await expect(filtry(page).getByLabel(FILTR_KATEGORIA)).toContainText('Projekty B+R');
});

test('pusty katalog mówi co innego niż brak trafień', async ({ page }) => {
  const opublikowane = idSzkolen('publish');

  expect(opublikowane.length).toBeGreaterThan(0);

  try {
    ustawStan(opublikowane, 'draft');

    await page.goto(katalog);

    await expect(page.locator('.iskt-notice')).toContainText(BRAK_OFERTY);
    await expect(page.locator('body')).not.toContainText(BRAK_WYNIKOW);

    // Pusty katalog nie proponuje czyszczenia filtrów — nie ma czego czyścić.
    await expect(page.getByRole('link', { name: FILTR_WYCZYSC })).toHaveCount(0);
  } finally {
    ustawStan(opublikowane, 'publish');
  }

  await page.goto(katalog);
  await expect(karty(page)).toHaveCount(NA_STRONE);
});

test('napisy katalogu pochodzą z panelu, nie z szablonu', async ({ page }) => {
  const ekran = base + '/wp-admin/edit.php?post_type=iskt_szkolenie&page=iskt-teksty';
  const wlasny = 'Nasza oferta szkoleniowa';

  await page.goto(base + '/wp-login.php');
  await page.fill('#user_login', 'admin');
  await page.fill('#user_pass', 'password');
  await page.click('#wp-submit');
  await page.waitForURL(/wp-admin/);

  try {
    await page.goto(ekran);
    await page.fill('#iskt-tekst-katalog_tytul', wlasny);
    await page.click('#submit');

    await page.goto(katalog);
    await expect(page.locator('h1')).toContainText(wlasny);
  } finally {
    await page.goto(ekran);
    await page.fill('#iskt-tekst-katalog_tytul', '');
    await page.click('#submit');
  }

  await page.goto(katalog);
  await expect(page.locator('h1')).toContainText('Katalog szkoleń');
});

test('katalog trzyma się na 360, 768 i 1440 px', async ({ page }) => {
  for (const szerokosc of [360, 768, 1440]) {
    await page.setViewportSize({ width: szerokosc, height: 900 });

    await page.goto(katalog);
    await expect(karty(page)).toHaveCount(NA_STRONE);
    await page.screenshot({ path: `${shots}/katalog-${szerokosc}.png`, fullPage: true });

    await page.goto(katalog + '?kategoria=esg-i-zrownowazony-rozwoj&forma=online');
    await page.screenshot({ path: `${shots}/filtr-${szerokosc}.png`, fullPage: true });

    await page.goto(katalog + '?szukaj=kwantowa');
    await page.screenshot({ path: `${shots}/brak-wynikow-${szerokosc}.png`, fullPage: true });
  }

  // §11 pkt 10 — żadnego „#” zamiast prawdziwego odnośnika.
  await page.goto(katalog);
  expect(await page.locator('a[href="#"]').count()).toBe(0);
});
