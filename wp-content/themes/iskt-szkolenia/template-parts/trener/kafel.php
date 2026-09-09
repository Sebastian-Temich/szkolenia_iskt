<?php
/**
 * Kafel trenera: zdjęcie, imię i nazwisko, rola.
 *
 * Jeden znacznik dla panelu szkolenia i dla listy trenerów. §4.5 wymaga spójnej
 * prezentacji powiązania w obu widokach — a odwiedzający ocenia spójność wzrokiem,
 * nie zapytaniem do bazy. Gdyby każdy widok składał kafel po swojemu, ten sam
 * trener wyglądałby na stronie szkolenia inaczej niż na liście.
 *
 * Pojemnik (`li` albo `div` z klasą `iskt-trener`) należy do wywołującego —
 * tutaj jest wyłącznie zawartość kafla.
 *
 * Argumenty:
 * - `trener`          (WP_Post) — wymagany.
 * - `klasa_odnosnika` (string)  — dodatkowa klasa odnośnika, np. rozciągnięcie karty.
 *
 * @package ISKT\Szkolenia\Theme
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

$iskt_trener = $args['trener'] ?? null;

if ( ! $iskt_trener instanceof WP_Post ) {
	return;
}

$iskt_trener_id = (int) $iskt_trener->ID;
$iskt_adres     = (string) get_permalink( $iskt_trener );
$iskt_zdjecie   = iskt_zdjecie_osoby( $iskt_trener );
$iskt_rola      = trim( (string) get_post_meta( $iskt_trener_id, '_iskt_rola', true ) );

$iskt_klasa_odnosnika = trim( 'iskt-trener__nazwa ' . (string) ( $args['klasa_odnosnika'] ?? '' ) );

?>

<?php if ( '' !== $iskt_zdjecie ) : ?>
	<span class="iskt-trener__zdjecie">
		<?php echo wp_kses_post( $iskt_zdjecie ); ?>
	</span>
<?php endif; ?>

<span class="iskt-trener__opis">
	<a class="<?php echo esc_attr( $iskt_klasa_odnosnika ); ?>" href="<?php echo esc_url( $iskt_adres ); ?>">
		<?php echo esc_html( get_the_title( $iskt_trener ) ); ?>
	</a>

	<?php if ( '' !== $iskt_rola ) : ?>
		<span class="iskt-trener__rola"><?php echo esc_html( $iskt_rola ); ?></span>
	<?php endif; ?>
</span>
