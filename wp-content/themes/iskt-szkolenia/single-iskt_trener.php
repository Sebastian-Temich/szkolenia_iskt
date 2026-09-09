<?php
/**
 * Profil trenera.
 *
 * Realizuje §4.5: własny trwały adres i wspólny szablon dla każdego trenera —
 * zdjęcie, rola, krótki opis, biografia, doświadczenie, wykształcenie i certyfikaty,
 * specjalizacje oraz lista prowadzonych szkoleń. Wszystkie treści pochodzą z panelu.
 *
 * Układ jest jednokolumnowy z rozmysłem. Panel boczny, jak na stronie szkolenia,
 * miałby tu tylko jedną zawartość — a trener bez niej zostawiałby pustą kolumnę.
 * §9 zostawia zdjęcia, biografie i certyfikaty do potwierdzenia praw, więc profil
 * z połową pól to na tym etapie stan typowy, nie wyjątek. Kolejne bloki po prostu
 * znikają, a strona zostaje kompletna.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>

	<main id="iskt-main" class="iskt-main" tabindex="-1">

		<article class="iskt-trener-profil">

			<?php
			/*
			 * Każda część sama decyduje, czy się pokazać — łącznie z własną sekcją
			 * i jej odstępami. Profil, w którym połowa pól czeka na potwierdzenie
			 * praw (§9), nie może pokazywać pustych pasów między nagłówkami.
			 */
			get_template_part( 'template-parts/trener/naglowek' );
			get_template_part( 'template-parts/trener/tresc' );
			get_template_part( 'template-parts/trener/szkolenia' );
			?>

		</article>

	</main>

	<?php
endwhile;

get_footer();
