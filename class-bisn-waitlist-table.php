<?php

/**
 * The Back In Stock Notifications Waitlist Table Class.
 *
 * @package    Back_In_Stock_Notifications
 * @author     Robert DeVore <me@robertdevore.com>
 * @license    GPL-2.0+ http://www.gnu.org/licenses/gpl-2.0.txt
 * @link       https://www.robertdevore.com
 * @since      1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Define the Back In Stock Waitlist Table.
 */
class BISN_Waitlist_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => esc_html__( 'Waitlist Entry', 'bisn' ),
            'plural'   => esc_html__( 'Waitlist Entries', 'bisn' ),
            'ajax'     => false
        ] );
    }

    /**
     * Retrieve waitlist data from the database.
     */
    public static function get_waitlist_data( $per_page = 20, $page_number = 1 ) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bisn_waitlist';
        $sql = "SELECT * FROM $table_name";

        if ( ! empty( $_REQUEST['orderby'] ) ) {
            $sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
            $sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
        }

        $sql .= " LIMIT $per_page";
        $sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

        return $wpdb->get_results( $sql, 'ARRAY_A' );
    }

    /**
     * Get total waitlist item count.
     */
    public static function record_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bisn_waitlist';
        return $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
    }

    /**
     * Define columns.
     */
    public function get_columns() {
        return [
            'product_id' => esc_html__( 'Product', 'bisn' ),
            'email'      => esc_html__( 'Email', 'bisn' ),
            'signed_up'  => esc_html__( 'Signed Up', 'bisn' ),
            'waiting'    => esc_html__( 'Waiting', 'bisn' ),
        ];
    }

    /**
     * Render the product_id column with a clickable link to the Edit screen.
     */
    protected function column_product_id( $item ) {
        $product_id = absint( $item['product_id'] );
        $title      = get_the_title( $product_id );
        $edit_link  = get_edit_post_link( $product_id );

        return sprintf(
            '<strong><a href="%1$s">%2$s</a></strong>',
            esc_url( $edit_link ),
            esc_html( $title )
        );
    }

    /**
     * Render the email column with a clickable link to the user profile (if associated).
     */
    protected function column_email( $item ) {
        $user_id = absint( $item['user_id'] );
        $email   = esc_html( $item['email'] );

        if ( $user_id ) {
            $user_edit_link = get_edit_user_link( $user_id );
            return sprintf( '<a href="%1$s">%2$s</a>', esc_url( $user_edit_link ), $email );
        }

        return $email;
    }

    /**
     * Render the signed_up column with a readable date and time based on WordPress timezone.
     */
    protected function column_signed_up( $item ) {
        $timestamp = strtotime( $item['date_added'] );
        return esc_html( date_i18n( 'F j, Y g:i A', $timestamp + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) );
    }

    /**
     * Render the waiting column showing time elapsed since sign-up, based on WordPress timezone.
     */
    protected function column_waiting( $item ) {
        $signup_time  = strtotime( $item['date_added'] );
        $current_time = current_time( 'timestamp' );
        $interval     = abs( $current_time - $signup_time );
        $waiting_time = '';

        $days    = floor( $interval / DAY_IN_SECONDS );
        $hours   = floor( ( $interval % DAY_IN_SECONDS ) / HOUR_IN_SECONDS );
        $minutes = floor( ( $interval % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS );

        if ( $days ) $waiting_time .= $days . ' days, ';
        if ( $hours ) $waiting_time .= $hours . ' hours, ';
        if ( $minutes ) $waiting_time .= $minutes . ' minutes';

        return esc_html( rtrim( $waiting_time, ', ' ) );
    }

    /**
     * Prepare the items for display in the table.
     */
    public function prepare_items() {
        $this->_column_headers = [
            $this->get_columns(),
            [],
            $this->get_sortable_columns()
        ];

        $per_page     = $this->get_items_per_page( 'waitlist_per_page', 20 );
        $current_page = $this->get_pagenum();
        $total_items  = self::record_count();

        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page,
        ] );

        $this->items = self::get_waitlist_data( $per_page, $current_page );
    }

    /**
     * Display sortable columns.
     */
    public function get_sortable_columns() {
        return [
            'product_id' => [ 'product_id', true ],
            'signed_up'  => [ 'date_added', true ],
        ];
    }

    /**
     * Default column rendering.
     */
    protected function column_default( $item, $column_name ) {
        return $item[ $column_name ];
    }
}
