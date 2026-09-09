<?php
/**
 * Nawigacja główna.
 *
 * Przycisk „Menu” startuje z atrybutem hidden i pokazuje go dopiero skrypt.
 * Dzięki temu przy wyłączonym JavaScripcie nie ma martwego przycisku, a menu
 * pozostaje rozwinięte i w pełni używalne (patrz assets/css/szkielet.css).
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

?>
<nav class="iskt-site-nav" aria-label="<?php esc_attr_e( 'Nawigacja główna', 'iskt-szkolenia' ); ?>">
	<button
		class="iskt-site-header__toggle"
		type="button"
		data-iskt-nav-toggle
		aria-expanded="false"
		aria-controls="iskt-menu-glowne"
		hidden
	>
		<?php
		iskt_the_icon( 'menu', 'iskt-icon--menu' );
		iskt_the_icon( 'close', 'iskt-icon--close' );
		?>
		<span><?php esc_html_e( 'Menu', 'iskt-szkolenia' ); ?></span>
	</button>

	<div class="iskt-site-nav__panel" id="iskt-menu-glowne" data-iskt-nav>
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => 'iskt-menu',
				'depth'          => 2,
				'fallback_cb'    => 'iskt_primary_menu_fallback',
			)
		);
		?>
	</div>
</nav>
