<?php

/**
 * The Back In Stock Notifications Data Helper Class
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

class BISN_Data_Helper {
    private static $instance = null;
    private $wpdb;
    private $waitlist_table;
    private $history_table;
    private $notifications_table;

    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->waitlist_table      = $this->wpdb->prefix . 'bisn_waitlist';
        $this->history_table       = $this->wpdb->prefix . 'bisn_waitlist_history';
        $this->notifications_table = $this->wpdb->prefix . 'bisn_notifications';
    }

    /**
     * Retrieves the singleton instance of the class.
     *
     * Ensures that only one instance of the class is created and returned.
     *
     * @since  1.0.0
     * @return mixed The singleton instance of the class.
     */
    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retrieves the top 10 most wanted products based on waitlist signups.
     *
     * Queries the waitlist table to count the number of customers signed up per product
     * and orders them by the highest demand.
     *
     * @since  1.0.0
     * @return array List of the most wanted products, including product ID and customer count.
     */
    public function get_most_wanted_products() {
        return $this->wpdb->get_results(
            "SELECT product_id, COUNT(*) AS customer_count 
            FROM $this->waitlist_table 
            GROUP BY product_id 
            ORDER BY customer_count DESC 
            LIMIT 10"
        );
    }

    /**
     * Retrieves the top 10 most overdue products based on the longest waiting time.
     *
     * Queries the waitlist table to determine the number of days each product has been
     * on the waitlist, ordering the results by the longest wait time.
     *
     * @since  1.0.0
     * @return array List of the most overdue products, including product ID and days waiting.
     */
    public function get_most_overdue_products() {
        return $this->wpdb->get_results(
            "SELECT product_id, MIN(DATEDIFF(NOW(), date_added)) AS days_waiting 
            FROM $this->waitlist_table 
            GROUP BY product_id 
            ORDER BY days_waiting DESC 
            LIMIT 10"
        );
    }

    /**
     * Retrieves the top 10 most signed-up products on the waitlist.
     *
     * Queries the waitlist table to count signups per product and orders them by the highest count.
     * This function may be similar to `get_most_wanted_products()`, but is kept for clarity.
     *
     * @since  1.0.0
     * @return array List of the most signed-up products, including product ID and customer count.
     */
    public function get_most_signed_up_products() {
        return $this->wpdb->get_results(
            "SELECT product_id, COUNT(*) AS customer_count 
            FROM $this->waitlist_table 
            GROUP BY product_id 
            ORDER BY customer_count DESC 
            LIMIT 10"
        );
    }

    /**
     * Retrieves the top 10 most signed-up products of all time.
     *
     * Queries the history table to count signups per product and orders by the highest count.
     *
     * @since  1.0.0
     * @return array List of products with the highest signups, including product ID and customer count.
     */
    public function get_most_signed_up_all_time() {
        return $this->wpdb->get_results(
            "SELECT product_id, COUNT(*) AS customer_count 
            FROM $this->history_table 
            GROUP BY product_id 
            ORDER BY customer_count DESC 
            LIMIT 10"
        );
    }

    /**
     * Retrieves the top 10 most signed-up products from the last week.
     *
     * Queries the history table to count signups per product within the past week and orders by the highest count.
     *
     * @since  1.0.0
     * @return array List of products with the highest signups in the past week, including product ID and customer count.
     */
    public function get_most_signed_up_last_week() {
        return $this->wpdb->get_results(
            "SELECT product_id, COUNT(*) AS customer_count 
            FROM $this->history_table 
            WHERE signup_date >= DATE_SUB(NOW(), INTERVAL 1 WEEK) 
            GROUP BY product_id 
            ORDER BY customer_count DESC 
            LIMIT 10"
        );
    }

    /**
     * Retrieves the top 10 most signed-up products from the last month.
     *
     * Queries the history table to count signups per product within the past month and orders by the highest count.
     *
     * @since  1.0.0
     * @return array List of products with the highest signups in the past month, including product ID and customer count.
     */
    public function get_most_signed_up_last_month() {
        return $this->wpdb->get_results(
            "SELECT product_id, COUNT(*) AS customer_count 
            FROM $this->history_table 
            WHERE signup_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH) 
            GROUP BY product_id 
            ORDER BY customer_count DESC 
            LIMIT 10"
        );
    }

    /**
     * Retrieves the top 10 most signed-up products from the last year.
     *
     * Queries the history table to count signups per product within the past year and orders by the highest count.
     *
     * @since  1.0.0
     * @return array List of products with the highest signups in the past year, including product ID and customer count.
     */
    public function get_most_signed_up_last_year() {
        return $this->wpdb->get_results(
            "SELECT product_id, COUNT(*) AS customer_count 
            FROM $this->history_table 
            WHERE signup_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR) 
            GROUP BY product_id 
            ORDER BY customer_count DESC 
            LIMIT 10"
        );
    }


    /**
     * Retrieves the number of signups from the last month.
     *
     * Queries the history table to count signups that occurred within the last month.
     *
     * @since  1.0.0
     * @return ?string The number of signups in the last month.
     */
    public function get_signups_last_month() {
        return $this->wpdb->get_var(
            "SELECT COUNT(*) FROM $this->history_table WHERE signup_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)"
        );
    }

    /**
     * Retrieves the number of signups from today.
     *
     * Queries the history table to count signups that occurred since the start of the current day.
     *
     * @since  1.0.0
     * @return ?string The number of signups today.
     */
    public function get_signups_today() {
        return $this->wpdb->get_var(
            "SELECT COUNT(*) FROM $this->history_table WHERE signup_date >= DATE(NOW())"
        );
    }

    /**
     * Retrieves the number of notifications sent in the last month.
     *
     * Queries the notifications table to count sent notifications within the last month.
     *
     * @since  1.0.0
     * @return ?string The number of notifications sent in the last month.
     */
    public function get_sent_last_month() {
        return $this->wpdb->get_var(
            "SELECT COUNT(*) FROM $this->notifications_table WHERE status = 'sent' AND send_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)"
        );
    }

    /**
     * Retrieves the number of notifications sent today.
     *
     * Queries the notifications table to count sent notifications since the start of the current day.
     *
     * @since  1.0.0
     * @return ?string The number of notifications sent today.
     */
    public function get_sent_today() {
        return $this->wpdb->get_var(
            "SELECT COUNT(*) FROM $this->notifications_table WHERE status = 'sent' AND send_date >= DATE(NOW())"
        );
    }

    /**
     * Retrieves the number of queued notifications.
     *
     * Queries the waitlist table to count all pending notifications.
     *
     * @since  1.0.0
     * @return ?string The number of queued notifications.
     */
    public function get_queued_notifications() {
        return $this->wpdb->get_var(
            "SELECT COUNT(*) FROM $this->waitlist_table"
        );
    }
}
