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
 * Version:     1.0.1
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

// Include the custom waitlist table class.
require_once plugin_dir_path( __FILE__ ) . 'classes/class-bisn-waitlist-table.php';

// Include the data helper class.
require_once plugin_dir_path( __FILE__ ) . 'classes/class-bisn-data-helper.php';

// Include the custom waitlist table class.
require_once plugin_dir_path( __FILE__ ) . 'bisn-woocommerce-my-account.php';

require 'vendor/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/robertdevore/back-in-stock-notifications/',
	__FILE__,
	'back-in-stock-notifications'
);

// Set the branch that contains the stable release.
$myUpdateChecker->setBranch( 'main' );

// Check if Composer's autoloader is already registered globally.
if ( ! class_exists( 'RobertDevore\WPComCheck\WPComPluginHandler' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use RobertDevore\WPComCheck\WPComPluginHandler;

new WPComPluginHandler( plugin_basename( __FILE__ ), 'https://robertdevore.com/why-this-plugin-doesnt-support-wordpress-com-hosting/' );

// Plugin version.
define( 'BACK_IN_STOCK_NOTIFICATIONS_VERSION', '1.0.1' );

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
 * Clean up plugin data upon uninstallation.
 *
 * Deletes custom database tables created by the plugin.
 *
 * @since  1.0.0
 * @return void
 */
function bisn_uninstall() {
    global $wpdb;

    // Define table names.
    $waitlist_table         = $wpdb->prefix . 'bisn_waitlist';
    $waitlist_history_table = $wpdb->prefix . 'bisn_waitlist_history';
    $notifications_table    = $wpdb->prefix . 'bisn_notifications';

    // Drop tables.
    $wpdb->query( "DROP TABLE IF EXISTS $waitlist_table" );
    $wpdb->query( "DROP TABLE IF EXISTS $waitlist_history_table" );
    $wpdb->query( "DROP TABLE IF EXISTS $notifications_table" );
}
register_uninstall_hook( __FILE__, 'bisn_uninstall' );

/**
 * Create waitlist table on plugin activation.
 * 
 * @since  1.0.0
 * @return void
 */
function bisn_create_waitlist_table() {
    // Ensure WooCommerce is active before creating the table.
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    global $wpdb;
    $table_name      = $wpdb->prefix . 'bisn_waitlist';
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
 * Create the waitlist history table on plugin activation.
 * 
 * @since  1.0.0
 * @return void
 */
function bisn_create_waitlist_history_table() {
    // Ensure WooCommerce is active before creating the table.
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    global $wpdb;
    $table_name      = $wpdb->prefix . 'bisn_waitlist_history';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        product_id bigint(20) NOT NULL,
        user_id bigint(20) NULL,
        email varchar(255) NOT NULL,
        signup_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'bisn_create_waitlist_history_table' );

/**
 * Create the notifications table on plugin activation.
 * 
 * @since  1.0.0
 * @return void
 */
function bisn_create_notifications_table() {
    // Ensure WooCommerce is active before creating the table.
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    global $wpdb;
    $table_name      = $wpdb->prefix . 'bisn_notifications';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        product_id bigint(20) NOT NULL,
        email varchar(255) NOT NULL,
        send_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        status varchar(20) DEFAULT 'queued' NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'bisn_create_notifications_table' );

/**
 * Enqueue front-end JavaScript file only on out-of-stock single product pages.
 * 
 * @since  1.0.0
 * @return void
 */
function bisn_enqueue_scripts() {
    // Check if we're on a single product page.
    if ( is_product() ) {
        // Get the global product object.
        $product = wc_get_product( get_the_ID() );

        // Make sure the product exists and is also out of stock.
        if ( $product && ! $product->is_in_stock() ) {
            wp_enqueue_script( 'bisn-js', plugin_dir_url( __FILE__ ) . 'assets/js/bisn.js', [ 'jquery' ], null, true );

            wp_localize_script( 'bisn-js', 'bisnAjax', [
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'bisn_nonce' ),
            ] );
        }
    }
}
add_action( 'wp_enqueue_scripts', 'bisn_enqueue_scripts' );

/**
 * Enqueue admin-specific JavaScript and CSS.
 *
 * @since 1.0.0
 * @return void
 */
function bisn_enqueue_admin_assets() {
    // Only load these assets on the Back In Stock admin page.
    $screen = get_current_screen();
    if ( isset( $screen->id ) && $screen->id === 'woocommerce_page_bisn_waitlist' ) {
        wp_enqueue_script( 'bisn-admin-js', plugin_dir_url( __FILE__ ) . 'assets/js/bisn-admin.js', [ 'jquery' ], null, true );
        wp_enqueue_style( 'bisn-admin-css', plugin_dir_url( __FILE__ ) . 'assets/css/bisn-admin.css', [], null );
    }
}
add_action( 'admin_enqueue_scripts', 'bisn_enqueue_admin_assets' );

/**
 * Handle AJAX request to add email to waitlist and log to history.
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

    // Get the DB table names.
    $table_name         = $wpdb->prefix . 'bisn_waitlist';
    $history_table_name = $wpdb->prefix . 'bisn_waitlist_history';

    // Get the user ID or set it to null.
    $user_id = get_current_user_id() ?: null;

    // Insert into current waitlist table
    $wpdb->insert( $table_name, [
        'product_id' => $product_id,
        'email'      => $email,
        'user_id'    => $user_id,
        'date_added' => current_time( 'mysql' ),
    ]);

    // Insert into history table
    $wpdb->insert( $history_table_name, [
        'product_id'  => $product_id,
        'email'       => $email,
        'user_id'     => $user_id,
        'signup_date' => current_time( 'mysql' ),
    ]);

    wp_send_json_success( [ 'message' => esc_html__( 'You have been added to the waitlist.', 'bisn' ) ] );
}
add_action( 'wp_ajax_nopriv_bisn_add_to_waitlist', 'bisn_add_to_waitlist' );
add_action( 'wp_ajax_bisn_add_to_waitlist', 'bisn_add_to_waitlist' );

/**
 * Notify waitlist customers when product is restocked and log the notification in batches.
 *
 * @param WC_Product $product WooCommerce product object.
 * 
 * @since  1.0.0
 * @return void
 */
function bisn_notify_waitlist_on_restock( $product ) {
    global $wpdb;

    if ( $product->get_stock_quantity() > 0 ) {
        $table_name          = $wpdb->prefix . 'bisn_waitlist';
        $notifications_table = $wpdb->prefix . 'bisn_notifications';
        $product_id          = $product->get_id();
        $batch_size          = 50; // Number of emails to process per batch
        $delay_seconds       = 1;  // Delay between each batch (in seconds)

        // Get all emails for this product
        $emails = $wpdb->get_results( 
            $wpdb->prepare( "SELECT email FROM $table_name WHERE product_id = %d", $product_id ) 
        );

        if ( $emails ) {
            // Split emails into batches
            $email_batches = array_chunk( $emails, $batch_size );

            foreach ( $email_batches as $batch ) {
                foreach ( $batch as $row ) {
                    $email = sanitize_email( $row->email );

                    // Send 'Back in Stock' notification email
                    $email_instance = WC()->mailer()->emails['BISN_Back_In_Stock_Email'];
                    $email_instance->trigger( $email, $product );

                    // Log the sent notification
                    $wpdb->insert( $notifications_table, [
                        'product_id' => $product_id,
                        'email'      => $email,
                        'send_date'  => current_time( 'mysql' ),
                        'status'     => 'sent',
                    ]);
                }

                // Delay before processing the next batch
                sleep( $delay_seconds );
            }

            // Remove users from the waitlist after sending all notifications
            $wpdb->delete( $table_name, [ 'product_id' => $product_id ], [ '%d' ] );
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
 * Render the admin waitlist page with tab navigation, statistics, and styling.
 * 
 * @since  1.0.0
 * @return void
 */
function bisn_waitlist_page() {
    $bisn_data = BISN_Data_Helper::get_instance();
    // Variables for each data retrieval function.
    $most_wanted_products    = $bisn_data->get_most_wanted_products();
    $most_overdue_products   = $bisn_data->get_most_overdue_products();
    $most_signed_up_all_time = $bisn_data->get_most_signed_up_all_time();
    $signups_last_month      = $bisn_data->get_signups_last_month();
    $signups_today           = $bisn_data->get_signups_today();
    $sent_last_month         = $bisn_data->get_sent_last_month();
    $sent_today              = $bisn_data->get_sent_today();
    $queued_notifications    = $bisn_data->get_queued_notifications();
    ?>
    <div class="wrap">
        <h1>
            <?php esc_html_e( 'Back In Stock Notifications', 'bisn' ); ?>
            <span style="vertical-align: middle; margin-right: 5px; font-size:12px;">
                v<?php esc_html_e( BACK_IN_STOCK_NOTIFICATIONS_VERSION ); ?>
            </span>
            <a style="float:right;" href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=bisn_export_csv' ) ); ?>" class="button">
                <span class="dashicons dashicons-download" style="vertical-align: middle; margin-right: 5px;"></span>
                <?php esc_html_e( 'Export Emails', 'bisn' ); ?>
            </a>
            <a style="float:right; margin-right: 10px;" href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=bisn_export_dashboard_csv' ) ); ?>" class="button button-primary">
                <span class="dashicons dashicons-download" style="vertical-align: middle; margin-right: 5px;"></span>
                <?php esc_html_e( 'Export Data', 'bisn' ); ?>
            </a>
        </h1>

        <!-- Tabs Navigation -->
        <h2 class="nav-tab-wrapper">
            <a href="#dashboard" class="nav-tab nav-tab-active" onclick="showTab(event, 'dashboard')"><?php esc_html_e( 'Dashboard', 'bisn' ); ?></a>
            <a href="#waitlist" class="nav-tab" onclick="showTab(event, 'waitlist')"><?php esc_html_e( 'Waitlist', 'bisn' ); ?></a>
        </h2>

        <!-- Tab Content -->
        <div id="dashboard" class="tab-content">
            <!-- Row 1: Notifications and Sign-ups -->
            <div class="bisn-dashboard-row">
                <div class="bisn-dashboard-box">
                    <h3><?php esc_html_e( 'Notifications', 'bisn' ); ?></h3>
                    <div class="bisn-grid">
                        <div class="bisn-grid-item">
                            <span class="bisn-stat-number"><?php esc_html_e( $sent_last_month ); ?></span>
                            <span><?php esc_html_e( 'Sent Last Month', 'bisn' ); ?></span>
                        </div>
                        <div class="bisn-grid-item">
                            <span class="bisn-stat-number"><?php esc_html_e( $sent_today ); ?></span>
                            <span><?php esc_html_e( 'Sent Today', 'bisn' ); ?></span>
                        </div>
                        <div class="bisn-grid-item">
                            <span class="bisn-stat-number"><?php esc_html_e( $queued_notifications ); ?></span>
                            <span><?php esc_html_e( 'Queued', 'bisn' ); ?></span>
                        </div>
                    </div>
                </div>
                <div class="bisn-dashboard-box">
                    <h3><?php esc_html_e( 'Sign-ups', 'bisn' ); ?></h3>
                    <div class="bisn-grid">
                        <div class="bisn-grid-item">
                            <span class="bisn-stat-number"><?php esc_html_e( $signups_last_month ); ?></span>
                            <span><?php esc_html_e( 'Sign-ups Last Month', 'bisn' ); ?></span>
                        </div>
                        <div class="bisn-grid-item">
                            <span class="bisn-stat-number"><?php esc_html_e( $signups_today ); ?></span>
                            <span><?php esc_html_e( 'Signed up Today', 'bisn' ); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Row 2: Product Leaderboards in Tables -->
            <div class="bisn-dashboard-row">
                <div class="bisn-dashboard-box">
                    <h3><?php esc_html_e( 'Most Wanted', 'bisn' ); ?></h3>
                    <table class="bisn-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Product', 'bisn' ); ?></th>
                                <th><?php esc_html_e( 'Customers', 'bisn' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $most_wanted_products as $product ): 
                                $product_name = get_the_title( $product->product_id );
                                $edit_link = get_edit_post_link( $product->product_id );
                                ?>
                                <tr>
                                    <td><a href="<?php echo esc_url( $edit_link ); ?>"><?php echo esc_html( $product_name ); ?></a></td>
                                    <td><?php echo esc_html( $product->customer_count ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div> 
                <div class="bisn-dashboard-box">
                    <h3><?php esc_html_e( 'Most Overdue', 'bisn' ); ?></h3>
                    <table class="bisn-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Product', 'bisn' ); ?></th>
                                <th><?php esc_html_e( 'Days Waiting', 'bisn' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $most_overdue_products as $product ): 
                                $product_name = get_the_title( $product->product_id );
                                $edit_link    = get_edit_post_link( $product->product_id );
                                ?>
                                <tr>
                                    <td><a href="<?php echo esc_url( $edit_link ); ?>"><?php echo esc_html( $product_name ); ?></a></td>
                                    <td><?php echo esc_html( $product->days_waiting ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="bisn-dashboard-box">
                    <h3><?php esc_html_e( 'Most Signed-up', 'bisn' ); ?></h3>
                    <table id="most-signedup-table" class="bisn-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Product', 'bisn' ); ?></th>
                                <th><?php esc_html_e( 'Customers', 'bisn' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $most_signed_up_all_time as $product ): 
                                $product_name = get_the_title( $product->product_id );
                                $edit_link = get_edit_post_link( $product->product_id );
                            ?>
                                <tr>
                                    <td><a href="<?php echo esc_url($edit_link); ?>"><?php echo esc_html( $product_name ); ?></a></td>
                                    <td><?php echo esc_html( $product->customer_count ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="waitlist" class="tab-content" style="display:none;">
            <?php
                $waitlist_table = new BISN_Waitlist_Table();
                $waitlist_table->prepare_items();
            ?>
            <form method="post">
                <?php $waitlist_table->display(); ?>
            </form>
        </div>
    </div>

    <?php
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

/**
 * Export all email addresses from bisn_waitlist_history table as CSV.
 *
 * @since  1.0.0
 * @return never
 */
function bisn_export_csv() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( esc_html__( 'Unauthorized request.', 'bisn' ) );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'bisn_waitlist_history';

    // Set headers for CSV download.
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=bisn_emails_' . date( 'Y-m-d_H-i-s' ) . '.csv' );

    // Open PHP output stream as file.
    $output = fopen( 'php://output', 'w' );

    // Add header row if needed.
    fputcsv( $output, [ 'Email' ] );

    // Fetch emails in chunks.
    $offset     = 0;
    $batch_size = apply_filters( 'bisn_export_csv_batch_size', 500 );

    while ( $emails = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT email FROM $table_name WHERE email != '' LIMIT %d OFFSET %d", $batch_size, $offset ) ) ) {
        foreach ( $emails as $email ) {
            fputcsv( $output, [ $email ] );
        }
        $offset += $batch_size;
    }

    // Close the output stream.
    fclose( $output );

    // Exit to prevent additional output.
    exit;
}
add_action( 'wp_ajax_bisn_export_csv', 'bisn_export_csv' );

/**
 * Handle exporting dashboard data as CSV with product names.
 *
 * @since 1.0.0
 * @return void
 */
function bisn_export_dashboard_csv() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( esc_html__( 'You do not have permission to access this page.', 'bisn' ) );
    }

    global $wpdb;
    
    // Get data for most wanted, most overdue, and most signed-up products
    $table_name         = $wpdb->prefix . 'bisn_waitlist';
    $history_table_name = $wpdb->prefix . 'bisn_waitlist_history';

    $most_wanted_products = $wpdb->get_results(
        "SELECT product_id, COUNT(*) AS customer_count FROM $table_name GROUP BY product_id ORDER BY customer_count DESC LIMIT 10"
    );

    $most_overdue_products = $wpdb->get_results(
        "SELECT product_id, MIN(DATEDIFF(NOW(), date_added)) AS days_waiting FROM $table_name GROUP BY product_id ORDER BY days_waiting DESC LIMIT 10"
    );

    $most_signed_up_all_time = $wpdb->get_results(
        "SELECT product_id, COUNT(*) AS customer_count FROM $history_table_name GROUP BY product_id ORDER BY customer_count DESC LIMIT 10"
    );

    // Set up CSV headers
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=bisn_dashboard_data_' . date( 'Y-m-d_H-i-s' ) . '.csv' );

    $output = fopen( 'php://output', 'w' );

    // Add headers for each section
    fputcsv( $output, [ 'Most Wanted Products' ] );
    fputcsv( $output, [ 'Product Name', 'Customer Count' ] );
    foreach ( $most_wanted_products as $product ) {
        $product_name = get_the_title( $product->product_id );
        fputcsv( $output, [ $product_name, $product->customer_count ] );
    }

    fputcsv( $output, [] ); // Blank line separator

    fputcsv( $output, [ 'Most Overdue Products' ] );
    fputcsv( $output, [ 'Product Name', 'Days Waiting' ] );
    foreach ( $most_overdue_products as $product ) {
        $product_name = get_the_title( $product->product_id );
        fputcsv( $output, [ $product_name, $product->days_waiting ] );
    }

    fputcsv( $output, [] ); // Blank line separator

    fputcsv( $output, [ 'Most Signed-up Products All-Time' ] );
    fputcsv( $output, [ 'Product Name', 'Customer Count' ] );
    foreach ( $most_signed_up_all_time as $product ) {
        $product_name = get_the_title( $product->product_id );
        fputcsv( $output, [ $product_name, $product->customer_count ] );
    }

    fclose( $output );
    exit;
}
add_action( 'wp_ajax_bisn_export_dashboard_csv', 'bisn_export_dashboard_csv' );

function bisn_register_back_in_stock_email( $email_classes ) {
    require_once plugin_dir_path( __FILE__ ) . 'classes/class-bisn-back-in-stock-email.php';
    $email_classes['BISN_Back_In_Stock_Email'] = new BISN_Back_In_Stock_Email();
    return $email_classes;
}
add_filter( 'woocommerce_email_classes', 'bisn_register_back_in_stock_email' );
