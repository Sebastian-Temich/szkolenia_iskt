/**
 * Przełącznik odbiorcy „Dla Ciebie” / „Dla firm” — ulepszenie, nie warunek działania.
 *
 * Bez tego pliku przełącznik nadal działa: to formularz GET, który przeładowuje
 * stronę z parametrem `?odbiorca=…`, a serwer pokazuje właściwy wariant. Skrypt
 * wyłącznie skraca drogę — przełącza widoczność na miejscu i podmienia adres,
 * żeby odświeżenie i przycisk „Wstecz” dawały ten sam wynik co przed kliknięciem.
 *
 * Prototyp działał odwrotnie: bez JavaScriptu nie działał wcale, a po odświeżeniu
 * gubił wybór (docs/PROTOTYP-INWENTARYZACJA.md §2.1).
 */

( function () {
	'use strict';

	var PARAM = 'odbiorca';

	/**
	 * Ustawia widoczność paneli i stan przycisków dla wybranego wariantu.
	 *
	 * @param {string} wariant Identyfikator wariantu.
	 */
	function pokaz( wariant ) {
		var panele = document.querySelectorAll( '[data-iskt-switch-panel]' );

		Array.prototype.forEach.call( panele, function ( panel ) {
			/*
			 * `hidden` zamiast klasy CSS: czytniki ekranu pomijają treść ukrytą
			 * atrybutem, więc odwiedzający nie usłyszy dwóch wersji tego samego
			 * nagłówka. Klasa z `display:none` dawałaby ten sam efekt wizualny,
			 * ale wymagałaby wczytanego arkusza, żeby cokolwiek ukryć.
			 */
			panel.hidden = panel.getAttribute( 'data-iskt-switch-panel' ) !== wariant;
		} );

		var przelaczniki = document.querySelectorAll( '[data-iskt-switch]' );

		Array.prototype.forEach.call( przelaczniki, function ( przelacznik ) {
			przelacznik.setAttribute( 'data-iskt-switch-active', wariant );

			var przyciski = przelacznik.querySelectorAll( 'button[name="' + PARAM + '"]' );

			Array.prototype.forEach.call( przyciski, function ( przycisk ) {
				przycisk.setAttribute( 'aria-pressed', przycisk.value === wariant ? 'true' : 'false' );
			} );
		} );
	}

	/**
	 * Zapisuje wybór w adresie, nie dokładając wpisu do historii.
	 *
	 * `replaceState`, a nie `pushState`: przełącznik zmienia wariant tekstu, a nie
	 * stronę. Gdyby każde kliknięcie dokładało wpis, przycisk „Wstecz” przestałby
	 * wracać tam, skąd odwiedzający przyszedł.
	 *
	 * @param {string} wariant Identyfikator wariantu.
	 */
	function zapiszWAdresie( wariant ) {
		if ( ! window.history || ! window.history.replaceState || ! window.URL ) {
			return;
		}

		try {
			var adres = new URL( window.location.href );
			adres.searchParams.set( PARAM, wariant );
			window.history.replaceState( window.history.state, '', adres.toString() );
		} catch ( blad ) {
			// Adres, którego nie da się przetworzyć, zostaje bez zmian — wariant i tak jest już pokazany.
		}
	}

	function start() {
		var przelaczniki = document.querySelectorAll( '[data-iskt-switch]' );

		if ( 0 === przelaczniki.length ) {
			return;
		}

		Array.prototype.forEach.call( przelaczniki, function ( przelacznik ) {
			przelacznik.addEventListener( 'click', function ( zdarzenie ) {
				var przycisk = zdarzenie.target.closest( 'button[name="' + PARAM + '"]' );

				if ( ! przycisk || ! przelacznik.contains( przycisk ) ) {
					return;
				}

				// Dopiero tutaj przejmujemy sterowanie — do tej chwili formularz działał sam.
				zdarzenie.preventDefault();

				pokaz( przycisk.value );
				zapiszWAdresie( przycisk.value );
			} );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
} )();
