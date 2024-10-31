<?php

/**
 * Display a table of products the user has joined the waitlist for, in their WooCommerce account.
 *
 * @package    Back_In_Stock_Notifications
 * @since      1.0.0
 */

// Ensure direct access is blocked
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Function to add the waitlist table to the WooCommerce account page
function bisn_account_waitlist_endpoint_content() {
    $user_id = get_current_user_id();
    $user = wp_get_current_user();
    
    if ( ! $user_id || ! $user ) {
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'bisn_waitlist';

    // Get the waitlist entries associated with the current user by user ID or email
    $user_waitlist = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT product_id, date_added FROM $table_name WHERE user_id = %d OR email = %s ORDER BY date_added DESC",
            $user_id,
            $user->user_email
        )
    );

    // Display the table if there are any waitlist entries
    if ( $user_waitlist ) {
        echo '<h2>' . esc_html__( 'Your Waitlisted Products', 'bisn' ) . '</h2>';
        echo '<table class="shop_table shop_table_responsive">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__( 'Product', 'bisn' ) . '</th>';
        echo '<th>' . esc_html__( 'Signup Date', 'bisn' ) . '</th>';
        echo '<th>' . esc_html__( 'Waiting Time', 'bisn' ) . '</th>';
        echo '<th>' . esc_html__( 'Action', 'bisn' ) . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ( $user_waitlist as $entry ) {
            $product_id = $entry->product_id;
            $product = wc_get_product( $product_id );

            if ( ! $product ) {
                continue;
            }

            $product_name = $product->get_name();
            $product_link = get_permalink( $product_id );
            $signup_date = date_i18n( 'F j, Y', strtotime( $entry->date_added ) );
            $waiting_time = human_time_diff( strtotime( $entry->date_added ), current_time( 'timestamp' ) );

            echo '<tr>';
            echo '<td><a href="' . esc_url( $product_link ) . '">' . esc_html( $product_name ) . '</a></td>';
            echo '<td>' . esc_html( $signup_date ) . '</td>';
            echo '<td>' . esc_html( $waiting_time ) . ' ' . esc_html__( 'ago', 'bisn' ) . '</td>';
            echo '<td>';
            echo '<form method="post" action="">';
            echo '<input type="hidden" name="product_id" value="' . esc_attr( $product_id ) . '">';
            echo '<button type="submit" name="bisn_remove_waitlist" class="button">' . esc_html__( 'Remove', 'bisn' ) . '</button>';
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    } else {
        echo '<p>' . esc_html__( 'You are not currently waitlisted for any products.', 'bisn' ) . '</p>';
    }
}

// Add the new endpoint to the WooCommerce account menu
function bisn_add_account_waitlist_endpoint() {
    add_rewrite_endpoint( 'waitlist', EP_ROOT | EP_PAGES );
}
add_action( 'init', 'bisn_add_account_waitlist_endpoint' );

// Content for the endpoint
function bisn_waitlist_query_vars( $vars ) {
    $vars[] = 'waitlist';
    return $vars;
}
add_filter( 'query_vars', 'bisn_waitlist_query_vars' );

function bisn_add_waitlist_link_my_account( $items ) {
    $items['waitlist'] = __( 'Waitlist', 'bisn' );
    return $items;
}
add_filter( 'woocommerce_account_menu_items', 'bisn_add_waitlist_link_my_account' );

function bisn_waitlist_endpoint_content() {
    bisn_account_waitlist_endpoint_content();
}
add_action( 'woocommerce_account_waitlist_endpoint', 'bisn_waitlist_endpoint_content' );

// Handle the "Remove" action for waitlist entries
function bisn_handle_waitlist_removal() {
    if ( isset( $_POST['bisn_remove_waitlist'], $_POST['product_id'] ) && is_user_logged_in() ) {
        $user_id = get_current_user_id();
        $product_id = intval( $_POST['product_id'] );

        global $wpdb;
        $table_name = $wpdb->prefix . 'bisn_waitlist';

        $wpdb->delete( $table_name, array(
            'user_id'    => $user_id,
            'product_id' => $product_id,
        ), array( '%d', '%d' ) );

        wc_add_notice( __( 'You have been removed from the waitlist for this product.', 'bisn' ), 'success' );

        // Redirect to avoid form resubmission on page refresh
        wp_safe_redirect( wc_get_account_endpoint_url( 'waitlist' ) );
        exit;
    }
}
add_action( 'template_redirect', 'bisn_handle_waitlist_removal' );
