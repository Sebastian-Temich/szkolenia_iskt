<?php
/**
 * Pasek wyszukiwania i filtrów katalogu szkoleń.
 *
 * Zwykły formularz GET, bez ani jednej linijki JavaScriptu. Trzy powody, wszystkie
 * z zlecenia: filtry mają działać bez skryptów, stan ma siedzieć w adresie, żeby
 * wynik dało się odesłać linkiem i żeby działało „Wstecz” (§6), a lekcja z ISK-21
 * mówi wprost, że mechanizm oparty na skrypcie pada pierwszy.
 *
 * Dwa przyciski nie są pomyłką: „szukaj” i „filtruj” to dwie różne intencje
 * odwiedzającego, a że formularz jest jeden, każdy z nich wysyła komplet stanu —
 * fraza i filtry zawsze zawężają razem, niezależnie od tego, który kliknięto.
 *
 * Żaden napis nie jest tu wpisany na stałe: wszystkie pochodzą z rejestru tekstów
 * wtyczki (grupa „Katalog szkoleń”), więc właściciel zmienia je z panelu (§4.7).
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/** @var array{szukaj: string, kategoria: string, forma: string} $iskt_stan */
$iskt_stan = isset( $args['stan'] ) && is_array( $args['stan'] )
	? $args['stan']
	: array(
		'szukaj'    => '',
		'kategoria' => '',
		'forma'     => '',
	);

$iskt_wszystkie = iskt_tekst( 'katalog_filtr_wszystkie' );

/*
 * Lista filtra powstaje z kategorii i form, które mają opublikowane szkolenia.
 * Filtr prowadzący zawsze do pustej listy byłby gorszy niż jego brak (§4.2).
 */
$iskt_listy = array(
	array(
		'nazwa'    => ISKT_PARAM_KATEGORIA,
		'id'       => 'iskt-katalog-kategoria',
		'etykieta' => iskt_tekst( 'katalog_filtr_kategoria' ),
		'wybrane'  => $iskt_stan['kategoria'],
		'opcje'    => iskt_opcje_filtra( ISKT_TAX_KATEGORIA ),
	),
	array(
		'nazwa'    => ISKT_PARAM_FORMA,
		'id'       => 'iskt-katalog-forma',
		'etykieta' => iskt_tekst( 'katalog_filtr_forma' ),
		'wybrane'  => $iskt_stan['forma'],
		'opcje'    => iskt_opcje_filtra( ISKT_TAX_FORMA ),
	),
);
?>

<form
	class="iskt-filters"
	role="search"
	method="get"
	action="<?php echo esc_url( iskt_adres_katalogu() ); ?>"
	aria-label="<?php echo esc_attr( iskt_tekst( 'katalog_tytul' ) ); ?>"
>
	<div class="iskt-field iskt-filters__search">
		<label class="iskt-field__label" for="iskt-katalog-szukaj">
			<?php echo esc_html( iskt_tekst( 'katalog_szukaj_etykieta' ) ); ?>
		</label>

		<div class="iskt-search-form__row">
			<input
				class="iskt-field__control"
				id="iskt-katalog-szukaj"
				type="search"
				name="<?php echo esc_attr( ISKT_PARAM_SZUKAJ ); ?>"
				value="<?php echo esc_attr( $iskt_stan['szukaj'] ); ?>"
				placeholder="<?php echo esc_attr( iskt_tekst( 'katalog_szukaj_podpowiedz' ) ); ?>"
			>
			<button class="iskt-button iskt-button--primary" type="submit">
				<?php iskt_the_icon( 'search', 'iskt-button__icon' ); ?>
				<?php echo esc_html( iskt_tekst( 'katalog_szukaj_przycisk' ) ); ?>
			</button>
		</div>
	</div>

	<div class="iskt-filters__row">
		<?php foreach ( $iskt_listy as $iskt_lista ) : ?>
			<div class="iskt-field iskt-filters__field">
				<label class="iskt-field__label" for="<?php echo esc_attr( $iskt_lista['id'] ); ?>">
					<?php echo esc_html( $iskt_lista['etykieta'] ); ?>
				</label>

				<select
					class="iskt-field__control"
					id="<?php echo esc_attr( $iskt_lista['id'] ); ?>"
					name="<?php echo esc_attr( $iskt_lista['nazwa'] ); ?>"
				>
					<option value=""><?php echo esc_html( $iskt_wszystkie ); ?></option>

					<?php foreach ( $iskt_lista['opcje'] as $iskt_slug => $iskt_nazwa ) : ?>
						<option
							value="<?php echo esc_attr( (string) $iskt_slug ); ?>"
							<?php selected( (string) $iskt_slug, $iskt_lista['wybrane'] ); ?>
						>
							<?php echo esc_html( $iskt_nazwa ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
		<?php endforeach; ?>

		<div class="iskt-filters__actions">
			<button class="iskt-button iskt-button--secondary" type="submit">
				<?php echo esc_html( iskt_tekst( 'katalog_filtr_zastosuj' ) ); ?>
			</button>

			<?php if ( iskt_katalog_jest_filtrowany( $iskt_stan ) ) : ?>
				<a class="iskt-button iskt-button--ghost" href="<?php echo esc_url( iskt_adres_katalogu() ); ?>">
					<?php echo esc_html( iskt_tekst( 'katalog_filtr_wyczysc' ) ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</form>
