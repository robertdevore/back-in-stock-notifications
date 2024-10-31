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

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_most_wanted_products() {
        return $this->wpdb->get_results(
            "SELECT product_id, COUNT(*) AS customer_count 
            FROM $this->waitlist_table 
            GROUP BY product_id 
            ORDER BY customer_count DESC 
            LIMIT 10"
        );
    }

    public function get_most_overdue_products() {
        return $this->wpdb->get_results(
            "SELECT product_id, MIN(DATEDIFF(NOW(), date_added)) AS days_waiting 
            FROM $this->waitlist_table 
            GROUP BY product_id 
            ORDER BY days_waiting DESC 
            LIMIT 10"
        );
    }

    public function get_most_signed_up_products() {
        return $this->wpdb->get_results(
            "SELECT product_id, COUNT(*) AS customer_count 
            FROM $this->waitlist_table 
            GROUP BY product_id 
            ORDER BY customer_count DESC 
            LIMIT 10"
        );
    }

    public function get_most_signed_up_all_time() {
        return $this->wpdb->get_results(
            "SELECT product_id, COUNT(*) AS customer_count 
            FROM $this->history_table 
            GROUP BY product_id 
            ORDER BY customer_count DESC 
            LIMIT 10"
        );
    }

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

    public function get_signups_last_month() {
        return $this->wpdb->get_var(
            "SELECT COUNT(*) FROM $this->history_table WHERE signup_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)"
        );
    }

    public function get_signups_today() {
        return $this->wpdb->get_var(
            "SELECT COUNT(*) FROM $this->history_table WHERE signup_date >= DATE(NOW())"
        );
    }

    public function get_sent_last_month() {
        return $this->wpdb->get_var(
            "SELECT COUNT(*) FROM $this->notifications_table WHERE status = 'sent' AND send_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)"
        );
    }

    public function get_sent_today() {
        return $this->wpdb->get_var(
            "SELECT COUNT(*) FROM $this->notifications_table WHERE status = 'sent' AND send_date >= DATE(NOW())"
        );
    }

    public function get_queued_notifications() {
        return $this->wpdb->get_var(
            "SELECT COUNT(*) FROM $this->waitlist_table"
        );
    }
}
