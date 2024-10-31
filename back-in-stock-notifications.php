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

// Include the custom waitlist table class.
require_once plugin_dir_path( __FILE__ ) . 'class-bisn-waitlist-table.php';

// Include the custom waitlist table class.
require_once plugin_dir_path( __FILE__ ) . 'bisn-woocommerce-my-account.php';

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
 * Render the admin waitlist page with tab navigation, statistics, and styling.
 * 
 * @since  1.0.0
 * @return void
 */
function bisn_waitlist_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'bisn_waitlist';

    // Query top 10 most wanted products based on customer count
    $most_wanted_products = $wpdb->get_results(
        "SELECT product_id, COUNT(*) AS customer_count 
         FROM $table_name 
         GROUP BY product_id 
         ORDER BY customer_count DESC 
         LIMIT 10"
    );

    // Query top 10 most overdue products based on days since sign-up
    $most_overdue_products = $wpdb->get_results(
        "SELECT product_id, MIN(DATEDIFF(NOW(), date_added)) AS days_waiting 
         FROM $table_name 
         GROUP BY product_id 
         ORDER BY days_waiting DESC 
         LIMIT 10"
    );

    // Query top 10 most signed-up products for "Most Signed-up" section
    $most_signed_up_products = $wpdb->get_results(
        "SELECT product_id, COUNT(*) AS customer_count 
         FROM $table_name 
         GROUP BY product_id 
         ORDER BY customer_count DESC 
         LIMIT 10"
    );

    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Back In Stock Notifications', 'bisn' ); ?></h1>
        
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
                            <span class="bisn-stat-number">120</span>
                            <span><?php esc_html_e( 'Sent Last Month', 'bisn' ); ?></span>
                        </div>
                        <div class="bisn-grid-item">
                            <span class="bisn-stat-number">5</span>
                            <span><?php esc_html_e( 'Sent Today', 'bisn' ); ?></span>
                        </div>
                        <div class="bisn-grid-item">
                            <span class="bisn-stat-number">15</span>
                            <span><?php esc_html_e( 'Queued', 'bisn' ); ?></span>
                        </div>
                    </div>
                </div>
                <div class="bisn-dashboard-box">
                    <h3><?php esc_html_e( 'Sign-ups', 'bisn' ); ?></h3>
                    <div class="bisn-grid">
                        <div class="bisn-grid-item">
                            <span class="bisn-stat-number">80</span>
                            <span><?php esc_html_e( 'Sign-ups Last Month', 'bisn' ); ?></span>
                        </div>
                        <div class="bisn-grid-item">
                            <span class="bisn-stat-number">3</span>
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
                            <?php foreach ($most_wanted_products as $product): 
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
                            <?php foreach ($most_overdue_products as $product): 
                                $product_name = get_the_title( $product->product_id );
                                $edit_link = get_edit_post_link( $product->product_id );
                                ?>
                                <tr>
                                    <td><a href="<?php echo esc_url($edit_link); ?>"><?php echo esc_html( $product_name ); ?></a></td>
                                    <td><?php echo esc_html( $product->days_waiting ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="bisn-dashboard-box">
                    <h3><?php esc_html_e( 'Most Signed-up', 'bisn' ); ?></h3>
                    <table class="bisn-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Product', 'bisn' ); ?></th>
                                <th><?php esc_html_e( 'Customers', 'bisn' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($most_signed_up_products as $product): 
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

    <!-- JavaScript for Tab Switching -->
    <script type="text/javascript">
        function showTab(event, tabId) {
            event.preventDefault();
            
            var tabContent = document.getElementsByClassName("tab-content");
            for (var i = 0; i < tabContent.length; i++) {
                tabContent[i].style.display = "none";
            }
            
            var tabs = document.getElementsByClassName("nav-tab");
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove("nav-tab-active");
            }
            
            document.getElementById(tabId).style.display = "block";
            event.currentTarget.classList.add("nav-tab-active");
        }

        // Default display the Dashboard tab
        document.addEventListener("DOMContentLoaded", function() {
            document.getElementById("dashboard").style.display = "block";
        });
    </script>

    <!-- CSS for Dashboard Styling -->
    <style>
        .bisn-dashboard-row {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        .bisn-dashboard-box {
            flex: 1;
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .bisn-dashboard-box h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 10px;
        }
        .bisn-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        .bisn-grid-item {
            background: #fff;
            padding: 10px;
            border-radius: 6px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
        }
        .bisn-stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #0073aa;
            margin: 12px 0;
        }
        .bisn-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .bisn-table th, .bisn-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        .bisn-table th {
            background-color: #f1f1f1;
            font-weight: bold;
        }
    </style>
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
