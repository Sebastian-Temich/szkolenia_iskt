<?php
/**
 * Ikony motywu — wbudowane SVG, bez zewnętrznych zależności.
 *
 * Dostarczony system projektowy ładował zestaw Lucide z CDN unpkg. Rezygnujemy
 * z tego z dwóch powodów: CDN to zewnętrzna usługa (przekazuje adres IP
 * odwiedzającego i jest kolejnym punktem awarii — ADR-002 §1), a ikony
 * dorysowywane skryptem migają przy wczytywaniu strony.
 *
 * Kształty odwzorowują zestaw Lucide (licencja ISC — wolno używać i osadzać).
 * Osadzamy je bezpośrednio w dokumencie: zero żądań sieciowych, zero migotania.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Zawartość ikon: sam wnętrze znacznika <svg>.
 *
 * @return array<string, string>
 */
function iskt_icon_shapes(): array {
	return array(
		'menu'         => '<path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/>',
		'close'        => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
		'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
		'arrow-right'  => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
		'arrow-left'   => '<path d="M19 12H5"/><path d="m12 19-7-7 7-7"/>',
		'search'       => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
		'mail'         => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
		'phone'        => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>',
		'map-pin'      => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
		'calendar'     => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4"/><path d="M8 2v4"/><path d="M3 10h18"/>',
		'clock'        => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
		'graduation'   => '<path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1.66 2.69 3 6 3s6-1.34 6-3v-5"/>',
		'leaf'         => '<path d="M11 20A7 7 0 0 1 4 13c0-6 4-9 16-10-1 12-4 16-9 17z"/><path d="M4 21c3-6 6.5-9.5 12-12"/>',
		'check'        => '<path d="M20 6 9 17l-5-5"/>',
	);
}

/**
 * Zwraca gotowy znacznik ikony.
 *
 * Ikona jest wyłącznie ozdobą: ma aria-hidden i nie jest osiągalna tabulatorem.
 * Znaczenie musi nieść tekst obok niej — także wtedy, gdy jest ukryty dla oka
 * klasą .screen-reader-text.
 *
 * @param string $name  Nazwa ikony z iskt_icon_shapes().
 * @param string $class Dodatkowe klasy CSS.
 */
function iskt_icon( string $name, string $class = '' ): string {
	$shapes = iskt_icon_shapes();

	if ( ! isset( $shapes[ $name ] ) ) {
		return '';
	}

	$classes = trim( 'iskt-icon iskt-icon--' . $name . ' ' . $class );

	return sprintf(
		'<svg class="%1$s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%2$s</svg>',
		esc_attr( $classes ),
		$shapes[ $name ]
	);
}

/**
 * Wypisuje ikonę.
 *
 * Kształty pochodzą z tablicy w tym pliku, nie z danych użytkownika — nie ma
 * tu czego sanityzować poza klasą, którą zabezpiecza iskt_icon().
 */
function iskt_the_icon( string $name, string $class = '' ): void {
	echo iskt_icon( $name, $class ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
