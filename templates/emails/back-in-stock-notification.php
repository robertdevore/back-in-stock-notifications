<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

do_action( 'woocommerce_email_header', $email_heading, $email );

$product = wc_get_product( $product_id );

// Ensure the product is valid
if ( $product ) {
    $product_name      = $product->get_name();
    $product_image_url = wp_get_attachment_url( $product->get_image_id() );
    $product_price     = $product->get_price_html();
    $product_url       = get_permalink( $product->get_id() );
} else {
    echo '<p>' . esc_html__( 'We are sorry, but we could not retrieve the product details.', 'bisn' ) . '</p>';
    return;
}
?>

    <p style="text-align:center; margin: 0 auto;"><?php printf( esc_html__( 'Great news! "%s" is now back in stock and available for purchase.', 'bisn' ), esc_html( $product_name ) ); ?></p>

    <?php if ( $product_image_url ) : ?>
        <img src="<?php echo esc_url( $product_image_url ); ?>" alt="<?php echo esc_attr( $product_name ); ?>" style="max-width: 100%; margin: 15px auto !important;display: block !important;position:relative;" />
    <?php endif; ?>

    <h2 style="margin: 12px auto; font-weight: bold; display: block; text-align: center;"><?php echo esc_html( $product_name ); ?></h2>

    <p style="font-size: 18px; color: #333; margin: 12px auto; display: block; text-align: center;">
        <?php echo wp_kses_post( $product_price ); ?>
    </p>

    <p style="font-size: 18px; color: #333; margin: 12px auto; display: block; text-align: center;">
        <a href="<?php echo esc_url( $product_url ); ?>" style="background-color: #0073aa; color: #fff; padding: 10px 20px; text-decoration: none; font-size: 16px; border-radius: 5px; margin: 0 auto; text-align: center; display:inline-block !important;">
            <?php esc_html_e( 'Shop Now', 'bisn' ); ?>
        </a>
    </p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
