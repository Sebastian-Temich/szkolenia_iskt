/**
 * Panel katalogu ISKT — powtarzalny program szkolenia.
 *
 * Zwykły JavaScript, bez zależności i bez kroku budowania (ADR-002 §3.2).
 * Skrypt jest wyłącznie ułatwieniem: bez niego redaktor nadal zapisze jeden moduł
 * na raz, bo formularz zawsze renderuje pusty wiersz.
 */
( function () {
	'use strict';

	/**
	 * Przenumerowuje pola modułów, żeby indeksy w nazwach szły po kolei.
	 *
	 * Bez tego usunięcie modułu ze środka zostawiłoby dziurę w indeksach. PHP
	 * poradziłby sobie z nią, ale kolejność modułów zależałaby wtedy od kolejności
	 * kluczy tablicy, a nie od tego, co redaktor widzi na ekranie.
	 *
	 * @param {HTMLElement} kontener Kontener programu.
	 */
	function przenumeruj( kontener ) {
		var moduly = kontener.querySelectorAll( '.iskt-program__modul' );

		Array.prototype.forEach.call( moduly, function ( modul, indeks ) {
			Array.prototype.forEach.call( modul.querySelectorAll( '[name]' ), function ( pole ) {
				pole.name = pole.name.replace( /\[\d+\]/, '[' + indeks + ']' );
			} );
		} );
	}

	/**
	 * Dokłada pusty moduł na końcu listy.
	 *
	 * @param {HTMLElement} kontener Kontener programu.
	 */
	function dodajModul( kontener ) {
		var moduly = kontener.querySelectorAll( '.iskt-program__modul' );

		if ( 0 === moduly.length ) {
			return;
		}

		var nowy = moduly[ moduly.length - 1 ].cloneNode( true );

		Array.prototype.forEach.call( nowy.querySelectorAll( 'input, textarea' ), function ( pole ) {
			pole.value = '';
		} );

		kontener.insertBefore( nowy, kontener.querySelector( '[data-iskt-dodaj-modul]' ) );
		przenumeruj( kontener );

		var pierwsze = nowy.querySelector( 'input' );

		if ( pierwsze ) {
			// Fokus na nowym module — inaczej użytkownik klawiatury wraca na początek formularza.
			pierwsze.focus();
		}
	}

	document.addEventListener( 'click', function ( zdarzenie ) {
		var przycisk = zdarzenie.target.closest( '[data-iskt-dodaj-modul]' );

		if ( ! przycisk ) {
			return;
		}

		var kontener = przycisk.closest( '[data-iskt-program]' );

		if ( kontener ) {
			dodajModul( kontener );
		}
	} );
}() );
