<?php
/**
 * Znak i nazwa serwisu w nagłówku.
 *
 * Logo pochodzi z Personalizacji (custom-logo). Renderujemy je sami, zamiast
 * wywoływać the_custom_logo(), bo ta funkcja opakowuje obraz we własny odnośnik —
 * a nam potrzebny jest JEDEN odnośnik obejmujący znak i nazwę. Zagnieżdżone
 * odnośniki są niepoprawne i mylące dla czytnika ekranu.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

$iskt_logo_id = (int) get_theme_mod( 'custom_logo' );
$iskt_name    = iskt_site_name();
$iskt_tagline = iskt_site_tagline();

/*
 * Gdy nazwa serwisu jest widoczna obok znaku, tekst alternatywny obrazu zostaje
 * pusty — inaczej czytnik ekranu przeczytałby tę samą treść dwa razy.
 */
$iskt_logo_alt = '' !== $iskt_name ? '' : __( 'Strona główna', 'iskt-szkolenia' );
?>
<a class="iskt-site-header__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
	<span class="iskt-site-header__logo">
		<?php
		if ( $iskt_logo_id > 0 ) {
			echo wp_get_attachment_image(
				$iskt_logo_id,
				'full',
				false,
				array(
					'class' => 'custom-logo',
					'alt'   => $iskt_logo_alt,
				)
			);
		} else {
			printf(
				'<img src="%1$s" width="193" height="160" alt="%2$s" decoding="async">',
				esc_url( iskt_default_logo_url() ),
				esc_attr( $iskt_logo_alt )
			);
		}
		?>
	</span>

	<?php if ( '' !== $iskt_name || '' !== $iskt_tagline ) : ?>
		<span class="iskt-site-header__identity">
			<?php if ( '' !== $iskt_name ) : ?>
				<span class="iskt-site-header__name"><?php echo esc_html( $iskt_name ); ?></span>
			<?php endif; ?>

			<?php if ( '' !== $iskt_tagline ) : ?>
				<span class="iskt-site-header__tagline"><?php echo esc_html( $iskt_tagline ); ?></span>
			<?php endif; ?>
		</span>
	<?php endif; ?>
</a>
