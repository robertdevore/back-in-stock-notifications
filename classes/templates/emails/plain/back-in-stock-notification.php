<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

echo "= " . $email_heading . " =\n\n";

$product_name  = $product->get_name();
$product_price = $product->get_price();
$product_url   = get_permalink( $product->get_id() );

echo sprintf( __( 'Great news! "%s" is now back in stock and available for purchase.', 'bisn' ), $product_name ) . "\n\n";

echo "Product: {$product_name}\n";
echo "Price: \${$product_price}\n";
echo "Link: {$product_url}\n\n";

echo "Shop Now: {$product_url}\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

do_action( 'woocommerce_email_footer_text', $email );
