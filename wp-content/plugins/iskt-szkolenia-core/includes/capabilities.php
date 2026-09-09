<?php
/**
 * Uprawnienia katalogu.
 *
 * Katalog dostaje własne uprawnienia zamiast korzystać z uprawnień wpisów.
 * Powód: §6 wymaga, aby panel był dostępny wyłącznie dla administracji i redakcji,
 * a §10 traktuje uprawnienia jako pozycję ryzyka. Własne uprawnienia pozwalają
 * później odebrać redaktorowi dostęp do katalogu bez odbierania mu wpisów —
 * przy `capability_type => 'post'` byłoby to niemożliwe.
 *
 * @package ISKT\Szkolenia\Core
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

/**
 * Zwraca komplet uprawnień dla typu treści o podanej nazwie w liczbie pojedynczej
 * i mnogiej, w formacie oczekiwanym przez `register_post_type()`.
 *
 * @param string $pojedyncza Nazwa w liczbie pojedynczej, np. `iskt_szkolenie`.
 * @param string $mnoga      Nazwa w liczbie mnogiej, np. `iskt_szkolenia`.
 *
 * @return array<string, string>
 */
function iskt_capabilities_dla( string $pojedyncza, string $mnoga ): array {
	return array(
		'edit_post'              => "edit_{$pojedyncza}",
		'read_post'              => "read_{$pojedyncza}",
		'delete_post'            => "delete_{$pojedyncza}",
		'edit_posts'             => "edit_{$mnoga}",
		'edit_others_posts'      => "edit_others_{$mnoga}",
		'publish_posts'          => "publish_{$mnoga}",
		'read_private_posts'     => "read_private_{$mnoga}",
		'delete_posts'           => "delete_{$mnoga}",
		'delete_private_posts'   => "delete_private_{$mnoga}",
		'delete_published_posts' => "delete_published_{$mnoga}",
		'delete_others_posts'    => "delete_others_{$mnoga}",
		'edit_private_posts'     => "edit_private_{$mnoga}",
		'edit_published_posts'   => "edit_published_{$mnoga}",
		'create_posts'           => "edit_{$mnoga}",
	);
}

/**
 * Mapa typów treści katalogu na nazwy używane przy budowaniu uprawnień.
 *
 * @return array<string, array{0: string, 1: string}>
 */
function iskt_typy_z_uprawnieniami(): array {
	return array(
		ISKT_CPT_SZKOLENIE => array( 'iskt_szkolenie', 'iskt_szkolenia' ),
		ISKT_CPT_TRENER    => array( 'iskt_trener', 'iskt_trenerzy' ),
		ISKT_CPT_TERMIN    => array( 'iskt_termin', 'iskt_terminy' ),
	);
}

/**
 * Zwraca płaską listę wszystkich uprawnień katalogu.
 *
 * @return string[]
 */
function iskt_wszystkie_capabilities(): array {
	$lista = array();

	foreach ( iskt_typy_z_uprawnieniami() as $nazwy ) {
		foreach ( iskt_capabilities_dla( $nazwy[0], $nazwy[1] ) as $cap ) {
			$lista[ $cap ] = true;
		}
	}

	return array_keys( $lista );
}

/**
 * Nadaje uprawnienia katalogu rolom administratora i redaktora.
 *
 * Wywoływane przy aktywacji. Redaktor dostaje pełne zarządzanie katalogiem —
 * §6 wymienia redakcję obok administracji. Autor i współpracownik nie dostają nic:
 * zlecenie nie przewiduje takiego podziału, a nadawanie uprawnień „na zapas”
 * poszerza powierzchnię ataku.
 */
function iskt_nadaj_capabilities(): void {
	foreach ( array( 'administrator', 'editor' ) as $nazwa_roli ) {
		$rola = get_role( $nazwa_roli );

		if ( ! $rola instanceof WP_Role ) {
			continue;
		}

		foreach ( iskt_wszystkie_capabilities() as $cap ) {
			$rola->add_cap( $cap );
		}
	}
}

/**
 * Odbiera uprawnienia katalogu wszystkim rolom.
 *
 * Wywoływane przy dezaktywacji. Same treści zostają w bazie — usuwamy tylko
 * pozwolenia, żeby po wyłączeniu wtyczki nie zostawały uprawnienia bez pokrycia.
 */
function iskt_odbierz_capabilities(): void {
	$role = wp_roles();

	foreach ( array_keys( $role->roles ) as $nazwa_roli ) {
		$rola = get_role( $nazwa_roli );

		if ( ! $rola instanceof WP_Role ) {
			continue;
		}

		foreach ( iskt_wszystkie_capabilities() as $cap ) {
			$rola->remove_cap( $cap );
		}
	}
}

/**
 * Sprawdza, czy bieżący użytkownik może edytować dane katalogu danego wpisu.
 *
 * Używane jako `auth_callback` przy rejestracji pól — dzięki temu kontrola
 * uprawnień obowiązuje niezależnie od tego, którą drogą dane trafiają do bazy.
 *
 * @param bool   $dozwolone Wartość domyślna przekazana przez WordPress.
 * @param string $meta_key  Nazwa pola.
 * @param int    $post_id   Identyfikator wpisu.
 *
 * @return bool
 */
function iskt_moze_edytowac_pole( bool $dozwolone, string $meta_key, int $post_id ): bool {
	unset( $dozwolone, $meta_key );

	return current_user_can( 'edit_post', $post_id );
}
