/**
 * Rejestracja bloków ISKT w edytorze.
 *
 * Zwykły JavaScript, bez JSX i bez kroku budowania (ADR-002 §3.3). Powód nie jest
 * ideologiczny: JSX wymagałby `node_modules` w repozytorium i budowania paczki
 * przed każdym przekazaniem, a bloki mają proste panele ustawień, na których JSX
 * nic by nie zyskał. Właściciel dostaje pliki, które da się otworzyć i przeczytać.
 *
 * Bloki są renderowane po stronie serwera, więc `save` zwraca `null` — wyjątkiem
 * jest „Treść dla odbiorcy”, która musi zapisać bloki zagnieżdżone, żeby serwer
 * miał co opakować.
 */

( function ( wp ) {
	'use strict';

	var blocks = wp.blocks;
	var element = wp.element;
	var blockEditor = wp.blockEditor;
	var components = wp.components;
	var ServerSideRender = wp.serverSideRender;
	var __ = wp.i18n.__;

	var el = element.createElement;

	/**
	 * Panel z nagłówkiem sekcji: nadtytuł, tytuł, wstęp.
	 *
	 * Trzy bloki mają identyczny nagłówek, więc kontrolki opisujemy raz.
	 *
	 * @param {Object}   atrybuty Atrybuty bloku.
	 * @param {Function} ustaw    Funkcja zapisu atrybutów.
	 * @return {Array} Kontrolki panelu.
	 */
	function poleNaglowka( atrybuty, ustaw ) {
		return [
			el( components.TextControl, {
				key: 'nadtytul',
				label: __( 'Nadtytuł', 'iskt-szkolenia-core' ),
				help: __( 'Krótki tekst nad tytułem, np. „Obszary szkoleń”.', 'iskt-szkolenia-core' ),
				value: atrybuty.nadtytul,
				onChange: function ( wartosc ) {
					ustaw( { nadtytul: wartosc } );
				},
			} ),
			el( components.TextControl, {
				key: 'tytul',
				label: __( 'Tytuł sekcji', 'iskt-szkolenia-core' ),
				value: atrybuty.tytul,
				onChange: function ( wartosc ) {
					ustaw( { tytul: wartosc } );
				},
			} ),
			el( components.TextareaControl, {
				key: 'wstep',
				label: __( 'Wstęp', 'iskt-szkolenia-core' ),
				value: atrybuty.wstep,
				onChange: function ( wartosc ) {
					ustaw( { wstep: wartosc } );
				},
			} ),
		];
	}

	/**
	 * Podgląd bloku renderowanego przez serwer.
	 *
	 * Redaktor widzi dokładnie to, co zobaczy odwiedzający — łącznie z sytuacją,
	 * w której sekcja jest pusta i nie renderuje się wcale.
	 *
	 * @param {string} nazwa    Nazwa bloku.
	 * @param {Object} atrybuty Atrybuty bloku.
	 * @return {Object} Element podglądu.
	 */
	function podglad( nazwa, atrybuty ) {
		return el( ServerSideRender, {
			block: nazwa,
			attributes: atrybuty,
			EmptyResponsePlaceholder: function () {
				return el(
					components.Placeholder,
					{ label: __( 'Sekcja bez treści', 'iskt-szkolenia-core' ) },
					__( 'Ta sekcja nie pojawi się na stronie, dopóki nie będzie miała czego pokazać.', 'iskt-szkolenia-core' )
				);
			},
		} );
	}

	blocks.registerBlockType( 'iskt/przelacznik-odbiorcy', {
		edit: function ( props ) {
			var atrybuty = props.attributes;

			return el(
				'div',
				blockEditor.useBlockProps(),
				el(
					blockEditor.InspectorControls,
					{ key: 'ustawienia' },
					el(
						components.PanelBody,
						{ title: __( 'Etykiety', 'iskt-szkolenia-core' ) },
						el( components.TextControl, {
							label: __( 'Wariant indywidualny', 'iskt-szkolenia-core' ),
							placeholder: __( 'Dla Ciebie', 'iskt-szkolenia-core' ),
							value: atrybuty.etykietaIndywidualny,
							onChange: function ( wartosc ) {
								props.setAttributes( { etykietaIndywidualny: wartosc } );
							},
						} ),
						el( components.TextControl, {
							label: __( 'Wariant firmowy', 'iskt-szkolenia-core' ),
							placeholder: __( 'Dla firm', 'iskt-szkolenia-core' ),
							value: atrybuty.etykietaFirmowy,
							onChange: function ( wartosc ) {
								props.setAttributes( { etykietaFirmowy: wartosc } );
							},
						} ),
						el( components.TextControl, {
							label: __( 'Opis grupy przycisków', 'iskt-szkolenia-core' ),
							help: __( 'Czyta go czytnik ekranu. Nie jest widoczny na stronie.', 'iskt-szkolenia-core' ),
							placeholder: __( 'Wybierz wariant komunikacji', 'iskt-szkolenia-core' ),
							value: atrybuty.opis,
							onChange: function ( wartosc ) {
								props.setAttributes( { opis: wartosc } );
							},
						} )
					)
				),
				podglad( 'iskt/przelacznik-odbiorcy', atrybuty )
			);
		},
		save: function () {
			return null;
		},
	} );

	blocks.registerBlockType( 'iskt/tresc-odbiorcy', {
		edit: function ( props ) {
			var warianty = [
				{ label: __( 'Dla Ciebie (osoba indywidualna)', 'iskt-szkolenia-core' ), value: 'indywidualny' },
				{ label: __( 'Dla firm', 'iskt-szkolenia-core' ), value: 'firmowy' },
			];

			var wlasnosci = blockEditor.useBlockProps( {
				className: 'iskt-edytor-odbiorca iskt-edytor-odbiorca--' + props.attributes.odbiorca,
			} );

			var wnetrze = blockEditor.useInnerBlocksProps( wlasnosci, {
				template: [ [ 'core/paragraph', {} ] ],
				templateLock: false,
			} );

			return el(
				element.Fragment,
				null,
				el(
					blockEditor.InspectorControls,
					{ key: 'ustawienia' },
					el(
						components.PanelBody,
						{ title: __( 'Wariant odbiorcy', 'iskt-szkolenia-core' ) },
						el( components.SelectControl, {
							label: __( 'Pokazuj tę treść dla', 'iskt-szkolenia-core' ),
							help: __( 'Odwiedzający widzi jeden wariant naraz. Przełącza go blok „Przełącznik odbiorcy”.', 'iskt-szkolenia-core' ),
							value: props.attributes.odbiorca,
							options: warianty,
							onChange: function ( wartosc ) {
								props.setAttributes( { odbiorca: wartosc } );
							},
						} )
					)
				),
				el( 'div', wnetrze )
			);
		},
		/*
		 * Zapis bez własnego opakowania. Blok jest renderowany po stronie serwera,
		 * a `render_callback` dostaje zapisaną treść i sam ją opakowuje — razem
		 * z atrybutem `hidden` dla nieaktywnego wariantu. Gdyby `save` dokładał tu
		 * własny `<div>`, w dokumencie byłyby dwa zagnieżdżone pojemniki.
		 */
		save: function () {
			return el( blockEditor.InnerBlocks.Content );
		},
	} );

	blocks.registerBlockType( 'iskt/obszary-szkolen', {
		edit: function ( props ) {
			var atrybuty = props.attributes;

			return el(
				'div',
				blockEditor.useBlockProps(),
				el(
					blockEditor.InspectorControls,
					{ key: 'ustawienia' },
					el(
						components.PanelBody,
						{ title: __( 'Nagłówek sekcji', 'iskt-szkolenia-core' ) },
						poleNaglowka( atrybuty, props.setAttributes )
					),
					el(
						components.PanelBody,
						{ title: __( 'Zakres', 'iskt-szkolenia-core' ), initialOpen: false },
						el( components.RangeControl, {
							label: __( 'Najwyżej tyle kategorii', 'iskt-szkolenia-core' ),
							help: __( 'Zero oznacza wszystkie kategorie z opublikowanymi szkoleniami.', 'iskt-szkolenia-core' ),
							value: atrybuty.liczba,
							min: 0,
							max: 12,
							onChange: function ( wartosc ) {
								props.setAttributes( { liczba: undefined === wartosc ? 0 : wartosc } );
							},
						} )
					)
				),
				podglad( 'iskt/obszary-szkolen', atrybuty )
			);
		},
		save: function () {
			return null;
		},
	} );

	blocks.registerBlockType( 'iskt/wyroznione-szkolenia', {
		edit: function ( props ) {
			var atrybuty = props.attributes;

			return el(
				'div',
				blockEditor.useBlockProps(),
				el(
					blockEditor.InspectorControls,
					{ key: 'ustawienia' },
					el(
						components.PanelBody,
						{ title: __( 'Nagłówek sekcji', 'iskt-szkolenia-core' ) },
						poleNaglowka( atrybuty, props.setAttributes )
					),
					el(
						components.PanelBody,
						{ title: __( 'Zakres', 'iskt-szkolenia-core' ), initialOpen: false },
						el( components.RangeControl, {
							label: __( 'Liczba szkoleń', 'iskt-szkolenia-core' ),
							value: atrybuty.liczba,
							min: 1,
							max: 12,
							onChange: function ( wartosc ) {
								props.setAttributes( { liczba: undefined === wartosc ? 4 : wartosc } );
							},
						} ),
						el( components.TextControl, {
							label: __( 'Etykieta odnośnika do katalogu', 'iskt-szkolenia-core' ),
							placeholder: __( 'Cały katalog', 'iskt-szkolenia-core' ),
							value: atrybuty.etykietaKatalogu,
							onChange: function ( wartosc ) {
								props.setAttributes( { etykietaKatalogu: wartosc } );
							},
						} )
					)
				),
				podglad( 'iskt/wyroznione-szkolenia', atrybuty )
			);
		},
		save: function () {
			return null;
		},
	} );
	blocks.registerBlockType( 'iskt/formularz-zgloszeniowy', {
		edit: function ( props ) {
			var atrybuty = props.attributes;

			return el(
				'div',
				blockEditor.useBlockProps(),
				el(
					blockEditor.InspectorControls,
					{ key: 'ustawienia' },
					el(
						components.PanelBody,
						{ title: __( 'Nagłówek', 'iskt-szkolenia-core' ) },
						el( components.ToggleControl, {
							label: __( 'Pokaż tytuł i wstęp formularza', 'iskt-szkolenia-core' ),
							help: __( 'Wyłącz, gdy sekcja ma już własny nagłówek. Treść tytułu zmienia się w Szkolenia → Teksty serwisu.', 'iskt-szkolenia-core' ),
							checked: false !== atrybuty.pokazNaglowek,
							onChange: function ( wartosc ) {
								props.setAttributes( { pokazNaglowek: wartosc } );
							},
						} )
					)
				),
				/*
				 * Podgląd z serwera pokazuje etykiety wpisane w panelu, ale nie ma być
				 * klikalny: przycisk wysyłki w edytorze przeładowałby ekran redaktora.
				 */
				el(
					'div',
					{ style: { pointerEvents: 'none' } },
					podglad( 'iskt/formularz-zgloszeniowy', atrybuty )
				)
			);
		},
		save: function () {
			return null;
		},
	} );

} )( window.wp );
