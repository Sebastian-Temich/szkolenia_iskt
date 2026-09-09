<?php
/**
 * Początek dokumentu i nagłówek serwisu.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="iskt-skip-link" href="#iskt-main"><?php esc_html_e( 'Przejdź do treści', 'iskt-szkolenia' ); ?></a>

<header
	class="iskt-site-header"
	data-iskt-header
	data-iskt-submenu-label="<?php echo esc_attr__( 'Rozwiń podmenu: %s', 'iskt-szkolenia' ); ?>"
>
	<div class="iskt-container iskt-site-header__inner">
		<?php
		get_template_part( 'template-parts/header/branding' );
		get_template_part( 'template-parts/header/navigation' );
		?>
	</div>
</header>

<div class="iskt-site-content">
