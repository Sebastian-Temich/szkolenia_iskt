/**
 * ISKT Szkolenia — nawigacja.
 *
 * Zwykły JavaScript, bez zależności i bez kroku budowania (ADR-002 §1).
 * Skrypt wyłącznie ULEPSZA działającą nawigację: gdy się nie wykona, menu
 * pozostaje rozwinięte i w pełni używalne.
 *
 * Zakres:
 *   1. przełącznik menu na wąskim ekranie (aria-expanded, Escape, klik poza),
 *   2. przyciski rozwijania podmenu dokładane do pozycji z dziećmi,
 *   3. klasa .is-scrolled na nagłówku po przewinięciu strony.
 */
( function () {
	'use strict';

	var header = document.querySelector( '[data-iskt-header]' );

	if ( ! header ) {
		return;
	}

	var toggle = header.querySelector( '[data-iskt-nav-toggle]' );
	var panel = header.querySelector( '[data-iskt-nav]' );
	var mobileQuery = window.matchMedia( '(max-width: 63.99em)' );

	/* --------------------------------------------------- menu mobilne */

	if ( toggle && panel ) {
		// Dopiero teraz CSS ma prawo chować panel — patrz szkielet.css.
		header.classList.add( 'iskt-site-header--js' );
		toggle.hidden = false;

		var setExpanded = function ( expanded ) {
			toggle.setAttribute( 'aria-expanded', expanded ? 'true' : 'false' );
			panel.classList.toggle( 'is-open', expanded );
		};

		var isExpanded = function () {
			return 'true' === toggle.getAttribute( 'aria-expanded' );
		};

		setExpanded( false );

		toggle.addEventListener( 'click', function () {
			setExpanded( ! isExpanded() );
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' !== event.key || ! isExpanded() ) {
				return;
			}

			setExpanded( false );
			toggle.focus();
		} );

		// Kliknięcie poza nagłówkiem zamyka menu — typowe oczekiwanie na telefonie.
		document.addEventListener( 'click', function ( event ) {
			if ( ! isExpanded() || header.contains( event.target ) ) {
				return;
			}

			setExpanded( false );
		} );

		// Przejście do układu pulpitu porządkuje stan przełącznika.
		var onBreakpoint = function ( event ) {
			if ( ! event.matches ) {
				setExpanded( false );
			}
		};

		if ( mobileQuery.addEventListener ) {
			mobileQuery.addEventListener( 'change', onBreakpoint );
		} else if ( mobileQuery.addListener ) {
			// Starsze Safari.
			mobileQuery.addListener( onBreakpoint );
		}
	}

	/* ------------------------------------------------------- podmenu */

	var parents = header.querySelectorAll( '.iskt-menu .menu-item-has-children' );

	Array.prototype.forEach.call( parents, function ( item, index ) {
		var link = item.querySelector( ':scope > a' );
		var submenu = item.querySelector( ':scope > .sub-menu' );

		if ( ! link || ! submenu ) {
			return;
		}

		if ( ! submenu.id ) {
			submenu.id = 'iskt-podmenu-' + ( index + 1 );
		}

		var button = document.createElement( 'button' );

		button.type = 'button';
		button.className = 'iskt-menu__expand';
		button.setAttribute( 'aria-expanded', 'false' );
		button.setAttribute( 'aria-controls', submenu.id );

		var label = document.createElement( 'span' );

		label.className = 'screen-reader-text';
		// Tekst tłumaczony po stronie PHP i podany w atrybucie danych nagłówka.
		label.textContent = ( header.getAttribute( 'data-iskt-submenu-label' ) || 'Rozwiń podmenu' )
			.replace( '%s', link.textContent.trim() );
		button.appendChild( label );

		button.addEventListener( 'click', function () {
			var expanded = 'true' === button.getAttribute( 'aria-expanded' );

			closeSubmenus();
			button.setAttribute( 'aria-expanded', expanded ? 'false' : 'true' );
			submenu.classList.toggle( 'is-open', ! expanded );
		} );

		link.insertAdjacentElement( 'afterend', button );
	} );

	function closeSubmenus() {
		var open = header.querySelectorAll( '.iskt-menu__expand[aria-expanded="true"]' );

		Array.prototype.forEach.call( open, function ( button ) {
			button.setAttribute( 'aria-expanded', 'false' );

			var submenu = document.getElementById( button.getAttribute( 'aria-controls' ) );

			if ( submenu ) {
				submenu.classList.remove( 'is-open' );
			}
		} );
	}

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key ) {
			closeSubmenus();
		}
	} );

	document.addEventListener( 'click', function ( event ) {
		if ( ! header.contains( event.target ) ) {
			closeSubmenus();
		}
	} );

	/* ---------------------------------------------- stan po przewinięciu */

	var ticking = false;

	var syncScrollState = function () {
		header.classList.toggle( 'is-scrolled', window.scrollY > 8 );
		ticking = false;
	};

	window.addEventListener(
		'scroll',
		function () {
			if ( ticking ) {
				return;
			}

			ticking = true;
			window.requestAnimationFrame( syncScrollState );
		},
		{ passive: true }
	);

	syncScrollState();
} )();
