<?php
/**
 * Nagłówek strony szkolenia: ścieżka powrotu, plakietki, tytuł, zajawka i fakty.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

$iskt_id        = (int) get_the_ID();
$iskt_kategoria = iskt_kategoria_szkolenia( $iskt_id );
$iskt_katalog   = get_post_type_archive_link( (string) get_post_type() );

/**
 * Fakty o szkoleniu: etykieta → wartość. Puste pomijamy.
 *
 * @var array<string, string>
 */
$iskt_fakty = array_filter(
	array(
		iskt_tekst( 'szkolenie_etykieta_czas' )   => (string) get_post_meta( $iskt_id, '_iskt_czas_trwania', true ),
		iskt_tekst( 'szkolenie_etykieta_forma' )  => implode( ' / ', iskt_formy_szkolenia( $iskt_id ) ),
		iskt_tekst( 'szkolenie_etykieta_poziom' ) => iskt_poziom_szkolenia( $iskt_id ),
	),
	static fn ( string $wartosc ): bool => '' !== $wartosc
);

?>
<header class="iskt-szkolenie__naglowek iskt-section iskt-section--muted iskt-section--tight">
	<div class="iskt-container">

		<?php if ( is_string( $iskt_katalog ) && '' !== $iskt_katalog ) : ?>
			<nav class="iskt-breadcrumb" aria-label="<?php esc_attr_e( 'Ścieżka nawigacji', 'iskt-szkolenia' ); ?>">
				<a href="<?php echo esc_url( $iskt_katalog ); ?>">
					<?php iskt_the_icon( 'arrow-left' ); ?>
					<?php echo esc_html( iskt_tekst( 'szkolenie_sciezka_katalog' ) ); ?>
				</a>

				<?php if ( $iskt_kategoria instanceof WP_Term ) : ?>
					<span class="iskt-breadcrumb__sep" aria-hidden="true">/</span>
					<a href="<?php echo esc_url( (string) get_term_link( $iskt_kategoria ) ); ?>">
						<?php echo esc_html( $iskt_kategoria->name ); ?>
					</a>
				<?php endif; ?>
			</nav>
		<?php endif; ?>

		<div class="iskt-szkolenie__intro iskt-stack">

			<p class="iskt-cluster iskt-szkolenie__plakietki">
				<?php if ( $iskt_kategoria instanceof WP_Term ) : ?>
					<span class="iskt-badge iskt-badge--outline"><?php echo esc_html( $iskt_kategoria->name ); ?></span>
				<?php endif; ?>

				<?php if ( (bool) get_post_meta( $iskt_id, '_iskt_wyroznione', true ) ) : ?>
					<span class="iskt-badge iskt-badge--solid"><?php echo esc_html( iskt_tekst( 'szkolenie_odznaka_wyroznione' ) ); ?></span>
				<?php endif; ?>

				<?php
				/*
				 * Plakietka mówi wyłącznie, że szkolenie kwalifikuje się do wsparcia.
				 * Żadnego procentu i żadnej kwoty — §4.3 zabrania obiecywania poziomu
				 * dofinansowania, którego nikt jeszcze nie przyznał.
				 */
				if ( (bool) get_post_meta( $iskt_id, '_iskt_dofinansowanie', true ) ) :
					?>
					<span class="iskt-badge iskt-badge--accent">
						<?php iskt_the_icon( 'banknote' ); ?>
						<?php echo esc_html( iskt_tekst( 'szkolenie_odznaka_dofinansowanie' ) ); ?>
					</span>
				<?php endif; ?>
			</p>

			<h1 class="iskt-title-lg iskt-balance"><?php the_title(); ?></h1>

			<?php if ( has_excerpt() ) : ?>
				<p class="iskt-lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>

			<?php if ( array() !== $iskt_fakty ) : ?>
				<dl class="iskt-facts">
					<?php foreach ( $iskt_fakty as $iskt_etykieta => $iskt_wartosc ) : ?>
						<div class="iskt-facts__item">
							<dt><?php echo esc_html( $iskt_etykieta ); ?></dt>
							<dd><?php echo esc_html( $iskt_wartosc ); ?></dd>
						</div>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>

		</div>

		<?php if ( has_post_thumbnail() ) : ?>
			<div class="iskt-szkolenie__media">
				<?php the_post_thumbnail( 'large', array( 'loading' => 'eager' ) ); ?>
			</div>
		<?php endif; ?>

	</div>
</header>
