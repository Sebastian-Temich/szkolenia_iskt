/**
 * Wspólne kroki obsługi panelu WordPressa dla testów odbiorowych.
 *
 * Wszystkie testy w tym katalogu wykonują ścieżkę właściciela przez panel —
 * klikając, a nie przez WP-CLI ani REST. WP-CLI omija skrzynki metadanych,
 * `nonce` i kontrolę uprawnień, więc dowodziłby czegoś innego, niż wymaga §11 pkt 2.
 *
 * Kroki mieszkają w jednym pliku, bo edytor blokowy zmienia znaczniki między
 * wydaniami WordPressa. Gdy „opublikuj” zacznie wyglądać inaczej, poprawiamy to
 * w jednym miejscu, a nie w każdym teście osobno.
 */
const { expect } = require('@playwright/test');

const base = 'http://localhost:8888';

async function zaloguj(page) {
  await page.goto(base + '/wp-login.php');

  if (await page.locator('#user_login').isVisible().catch(() => false)) {
    await page.fill('#user_login', 'admin');
    await page.fill('#user_pass', 'password');
    await page.click('#wp-submit');
    await page.waitForURL(/wp-admin/);
  }
}

/**
 * Przenosi do kosza wpisy o tytule zawierającym podany fragment.
 *
 * Sprzątamy w panelu, zamiast uzależniać test od WP-CLI. Bez tego kolejne biegi
 * zostawiają w katalogu coraz więcej danych demonstracyjnych, a lista publiczna
 * jest stronicowana — trener z ostatniego biegu potrafi wylądować na drugiej
 * stronie i test przestaje sprawdzać to, co miał sprawdzać.
 */
async function usunPozostalosci(page, postType, szukanyTytul) {
  const adres = `${base}/wp-admin/edit.php?post_type=${postType}&s=${encodeURIComponent(szukanyTytul)}`;

  while (true) {
    await page.goto(adres);

    const wiersz = page.locator('#the-list tr').filter({
      has: page.locator('a.row-title', { hasText: szukanyTytul }),
    }).first();

    if (!(await wiersz.count())) {
      return;
    }

    // Akcja wiersza jest ukryta do czasu hovera. Przejście pod jej podpisany
    // nonce'em adres jest stabilniejsze od wymuszania kliknięcia poza viewportem.
    const adresKosza = await wiersz.locator('a.submitdelete').getAttribute('href');
    await page.goto(adresKosza);
  }
}

/** Modal powitalny edytora blokowego zasłania pole tytułu przy pierwszym wejściu. */
async function zamknijPowitanie(page) {
  const dialog = page.locator('.components-modal__frame');

  if (await dialog.isVisible().catch(() => false)) {
    await dialog.getByRole('button', { name: /close|zamknij/i }).first().click();
    await expect(dialog).toBeHidden();
  }
}

/** Kanwa edytora bywa w ramce (WP 6.6) — tytuł trzeba wpisać tam, gdzie faktycznie jest. */
async function polaTytulu(page) {
  const ramka = page.locator('iframe[name="editor-canvas"]');

  if (await ramka.count()) {
    return page.frameLocator('iframe[name="editor-canvas"]');
  }

  return page;
}

function poleTytulu(korzen) {
  return korzen.locator('.editor-post-title__input, .wp-block-post-title, [aria-label="Add title"]').first();
}

/** Otwiera panel boczny edytora i zwraca jego lokator. */
async function bokEdytora(page) {
  const bok = page.locator('.interface-interface-skeleton__sidebar, .editor-sidebar').first();

  if (!(await bok.isVisible().catch(() => false))) {
    await page.getByRole('button', { name: /^(Settings|Ustawienia)$/ }).first().click();
    await bok.waitFor({ state: 'visible' });
  }

  // Panele wpisu są na karcie wpisu, nie na karcie bloku.
  const kartaWpisu = bok.getByRole('tab', { name: /^(Post|Wpis|Szkolenie|Trener)$/ }).first();

  if (await kartaWpisu.isVisible().catch(() => false)) {
    await kartaWpisu.click();
  }

  return bok;
}

/** Rozwija panel boczny o podanym nagłówku. */
async function rozwinPanel(bok, nazwa) {
  const naglowek = bok.getByRole('button', { name: nazwa }).first();
  await naglowek.waitFor({ state: 'visible' });

  if ((await naglowek.getAttribute('aria-expanded')) !== 'true') {
    await naglowek.click();
  }

  return naglowek;
}

/**
 * Zaznacza pozycję taksonomii w panelu bocznym edytora blokowego.
 *
 * Kategoria i forma decydują o tym, co znajdzie się w katalogu i w sekcji
 * „obszary szkoleń" — bez nich strona główna pokazuje mniej, niż powinna.
 */
async function zaznaczTaksonomie(page, panel, pozycja) {
  const bok = await bokEdytora(page);

  await rozwinPanel(bok, panel);
  await bok.getByRole('checkbox', { name: pozycja }).first().check();
}

async function wpiszTytul(page, tytul) {
  const pole = poleTytulu(await polaTytulu(page));

  await pole.click();
  await pole.fill(tytul);
}

/**
 * Wpisuje treść wpisu — biografię trenera albo opis szkolenia.
 *
 * Wchodzimy w treść z pola tytułu klawiszem Enter, tak jak zrobi to redaktor.
 * Klikanie w kanwę trafia raz w blok, raz w jego obramowanie; przejście z tytułu
 * zawsze kończy się kursorem w pierwszym akapicie.
 */
async function wpiszTresc(page, tekst) {
  const pole = poleTytulu(await polaTytulu(page));

  await pole.click();
  await pole.press('End');
  await pole.press('Enter');
  await page.keyboard.type(tekst);
}

/**
 * Wpisuje krótki opis (zajawkę) w panelu bocznym edytora.
 *
 * §4.5 wymaga krótkiego opisu obok pełnej biografii. Zajawka jest na to miejscem
 * natywnym — redaktor nie musi się uczyć osobnego pola.
 */
async function wpiszZajawke(page, tekst) {
  const bok = await bokEdytora(page);

  // W WP 6.6 zajawka jest wierszem podsumowania wpisu, a pole otwiera się w dymku.
  await bok.getByRole('button', { name: /zajawk|excerpt/i }).first().click();

  const pole = page.locator('.editor-post-excerpt__dropdown__content textarea, .components-popover textarea').first();
  await pole.waitFor({ state: 'visible' });
  await pole.fill(tekst);

  /*
   * Edytor zapisuje zajawkę do swojego stanu dopiero przy opuszczeniu pola —
   * samo wpisanie treści nie wystarczy i publikacja poszłaby bez krótkiego opisu.
   * Dlatego najpierw wychodzimy z pola, a dopiero potem zamykamy dymek.
   */
  await pole.press('Tab');

  // Dymek zasłania przycisk publikacji, dopóki go nie zamkniemy.
  await page.keyboard.press('Escape');
  await expect(pole).toBeHidden();
}

/**
 * Publikuje wpis w edytorze blokowym i zwraca adres, który panel pokazał redaktorowi.
 *
 * Adres bierzemy z panelu, a nie zgadujemy z tytułu: przy powtórzonym tytule
 * WordPress dokleja przyrostek do odnośnika i zgadywanie prowadziłoby na inny wpis.
 */
async function opublikujBlokowy(page) {
  await page.getByRole('button', { name: /^(Publish|Opublikuj)$/ }).first().click();

  const potwierdzenie = page.locator('.editor-post-publish-button');
  await potwierdzenie.waitFor({ state: 'visible' });
  await potwierdzenie.click();

  await expect(page.locator('.post-publish-panel__postpublish-header').first())
    .toContainText(/is now|opublikowan/i, { timeout: 30000 });

  return page.locator('.post-publish-panel__postpublish-post-address input').first().inputValue();
}

module.exports = {
  base,
  zaloguj,
  usunPozostalosci,
  zamknijPowitanie,
  polaTytulu,
  bokEdytora,
  rozwinPanel,
  zaznaczTaksonomie,
  wpiszTytul,
  wpiszTresc,
  wpiszZajawke,
  opublikujBlokowy,
};
