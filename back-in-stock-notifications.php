<?php
/**
 * The plugin bootstrap file
 *
 * @link              https://robertdevore.com
 * @since             1.0.0
 * @package           Back_In_Stock_Notifications
 *
 * @wordpress-plugin
 *
 * Plugin Name: Back In Stock Notifications for WooCommerce®
 * Description: Automatically notify customers when their favorite products are restocked, and track what products are most in demand.
 * Plugin URI:  https://github.com/robertdevore/back-in-stock-notifications/
 * Version:     1.0.0
 * Author:      Robert DeVore
 * Author URI:  https://robertdevore.com/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: bisn
 * Domain Path: /languages
 * Update URI:  https://github.com/robertdevore/back-in-stock-notifications/
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

require 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/robertdevore/back-in-stock-notifications/',
	__FILE__,
	'back-in-stock-notifications'
);

// Set the branch that contains the stable release.
$myUpdateChecker->setBranch( 'main' );

/**
 * Current plugin version.
 */
define( 'BACK_IN_STOCK_NOTIFICATIONS_VERSION', '1.0.0' );

/**
 * Check if WooCommerce is active, and prevent activation if it's not.
 * 
 * @since  1.0.0
 * @return void
 */
function bisn_check_woocommerce_dependency() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        // Deactivate the plugin
        deactivate_plugins( plugin_basename( __FILE__ ) );

        // Display an admin error message
        wp_die(
            esc_html__( 'Back In Stock Notifications requires WooCommerce to be installed and active. Please activate WooCommerce and try again.', 'bisn' ),
            esc_html__( 'Plugin Activation Error', 'bisn' ),
            [ 'back_link' => true ]
        );
    }
}
register_activation_hook( __FILE__, 'bisn_check_woocommerce_dependency' );

/**
 * Show an admin notice if WooCommerce is deactivated after plugin activation.
 * 
 * @since  1.0.0
 * @return void
 */
function bisn_check_woocommerce_admin_notice() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        echo '<div class="error"><p>' . esc_html__( 'Back In Stock Notifications requires WooCommerce to be active. Please activate WooCommerce to use this plugin.', 'bisn' ) . '</p></div>';
    }
}
add_action( 'admin_notices', 'bisn_check_woocommerce_admin_notice' );

/**
 * Create waitlist table on plugin activation.
 * 
 * @since  1.0.0
 * @return void
 */
function bisn_create_waitlist_table() {
    // Ensure WooCommerce is active before creating the table
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'bisn_waitlist';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        product_id bigint(20) NOT NULL,
        user_id bigint(20) NULL,
        email varchar(255) NOT NULL,
        date_added datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'bisn_create_waitlist_table' );

/**
 * Enqueue front-end JavaScript file.
 * 
 * @since  1.0.0
 * @return void
 */
function bisn_enqueue_scripts() {
    wp_enqueue_script( 'bisn-js', plugin_dir_url( __FILE__ ) . 'js/bisn.js', [ 'jquery' ], null, true );

    wp_localize_script( 'bisn-js', 'bisnAjax', [
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'bisn_nonce' ),
    ] );
}
add_action( 'wp_enqueue_scripts', 'bisn_enqueue_scripts' );

/**
 * Handle AJAX request to add email to waitlist.
 * 
 * @since  1.0.0
 * @return void
 */
function bisn_add_to_waitlist() {
    check_ajax_referer( 'bisn_nonce', 'security' );

    global $wpdb;

    $product_id = intval( $_POST['product_id'] );
    $email      = sanitize_email( $_POST['email'] );

    if ( ! is_email( $email ) ) {
        wp_send_json_error( [ 'message' => esc_html__( 'Invalid email address.', 'bisn' ) ] );
    }

    $table_name = $wpdb->prefix . 'bisn_waitlist';

    $wpdb->insert( $table_name, [
        'product_id' => $product_id,
        'email'      => $email,
        'user_id'    => get_current_user_id() ?: null,
        'date_added' => current_time( 'mysql' ),
    ] );

    wp_send_json_success( [ 'message' => esc_html__( 'You have been added to the waitlist.', 'bisn' ) ] );
}
add_action( 'wp_ajax_nopriv_bisn_add_to_waitlist', 'bisn_add_to_waitlist' );
add_action( 'wp_ajax_bisn_add_to_waitlist', 'bisn_add_to_waitlist' );

/**
 * Notify waitlist customers when product is restocked.
 *
 * @param WC_Product $product WooCommerce product object.
 * 
 * @since  1.0.0
 * @return void
 */
function bisn_notify_waitlist_on_restock( $product ) {
    global $wpdb;

    if ( $product->get_stock_quantity() > 0 ) {
        $table_name  = $wpdb->prefix . 'bisn_waitlist';
        $product_id  = $product->get_id();
        $emails      = $wpdb->get_results( $wpdb->prepare( "SELECT email FROM $table_name WHERE product_id = %d", $product_id ) );

        if ( $emails ) {
            foreach ( $emails as $row ) {
                wp_mail(
                    sanitize_email( $row->email ),
                    esc_html__( 'Product Back in Stock', 'bisn' ),
                    sprintf(
                        esc_html__( 'Your requested product is back in stock. Click here to purchase: %s', 'bisn' ),
                        esc_url( get_permalink( $product_id ) )
                    )
                );
            }

            $wpdb->delete( $table_name, array( 'product_id' => $product_id ), array( '%d' ) );
        }
    }
}
add_action( 'woocommerce_product_set_stock', 'bisn_notify_waitlist_on_restock', 10, 1 );

/**
 * Add waitlist sub-menu under WooCommerce.
 * 
 * @since  1.0.0
 * @return void
 */
function bisn_add_admin_menu() {
    add_submenu_page(
        'woocommerce',
        esc_html__( 'Back In Stock Waitlist', 'bisn' ),
        esc_html__( 'Back In Stock', 'bisn' ),
        'manage_woocommerce',
        'bisn_waitlist',
        'bisn_waitlist_page'
    );
}
add_action( 'admin_menu', 'bisn_add_admin_menu' );

/**
 * Render the admin waitlist page.
 * 
 * @since  1.0.0
 * @return void
 */
function bisn_waitlist_page() {
    global $wpdb;
    $table_name    = $wpdb->prefix . 'bisn_waitlist';
    $waitlist_data = $wpdb->get_results( "SELECT * FROM $table_name" );

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__( 'Back In Stock Waitlist', 'bisn' ) . '</h1>';
    echo '<table class="widefat fixed">';
    echo '<thead><tr><th>' . esc_html__( 'Product', 'bisn' ) . '</th><th>' . esc_html__( 'Email', 'bisn' ) . '</th><th>' . esc_html__( 'Date Added', 'bisn' ) . '</th></tr></thead>';
    echo '<tbody>';

    foreach ( $waitlist_data as $row ) {
        echo '<tr>';
        echo '<td>' . esc_html( get_the_title( $row->product_id ) ) . '</td>';
        echo '<td>' . esc_html( $row->email ) . '</td>';
        echo '<td>' . esc_html( $row->date_added ) . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

/**
 * Add hidden product ID field for AJAX purposes.
 * 
 * @since  1.0.0
 * @return void
 */
function bisn_add_product_id_field() {
    if ( ! is_product() ) return;
    
    global $product;
    echo '<input type="hidden" name="product_id" value="' . esc_attr( $product->get_id() ) . '">';
}
add_action( 'woocommerce_single_product_summary', 'bisn_add_product_id_field', 5 );
