<?php

/**
 * The Back In Stock Notifications Email Notification Class.
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

class BISN_Back_In_Stock_Email extends WC_Email {

    public function __construct() {
        $this->id          = 'bisn_back_in_stock';
        $this->title       = __( 'Back in Stock Notification', 'bisn' );
        $this->description = __( 'Notification email sent to customers when a product is back in stock.', 'bisn' );
        
        $this->heading = __( 'Your Product is Back in Stock!', 'bisn' );
        $this->subject = __( '[{site_title}] Product Back in Stock: {product_name}', 'bisn' );

        $this->template_html  = 'emails/back-in-stock-notification.php';
        $this->template_plain = 'emails/plain/back-in-stock-notification.php';

        // Add template path to override WooCommerce templates
        $this->template_base = plugin_dir_path( __FILE__ ) . 'templates/';

        parent::__construct();

        // Triggers this email when 'bisn_send_back_in_stock_email' is called
        add_action( 'bisn_send_back_in_stock_email_notification', [ $this, 'trigger' ], 10, 2 );
    }

    public function trigger( $email, $product ) {
        if ( ! $email || ! $product ) {
            return;
        }
    
        $this->recipient    = $email;
        $this->product_name = $product->get_name();
        $this->product_url  = get_permalink( $product->get_id() );
    
        // Add product ID for use in the template.
        $this->product_id   = $product->get_id();
    
        // Log for debugging.
        error_log( 'Sending back-in-stock email for product ID: ' . $this->product_id );
    
        $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
    }
    
    public function get_content_html() {
        return wc_get_template_html( $this->template_html, [
            'email_heading' => $this->get_heading(),
            'product_id'    => $this->product_id, // Pass product_id here
            'sent_to_admin' => false,
            'plain_text'    => false,
            'email'         => $this,
        ], '', $this->template_base );
    }
    
    public function get_content_plain() {
        return wc_get_template_html( $this->template_plain, [
            'email_heading' => $this->get_heading(),
            'product_id'    => $this->product_id, // Pass product_id here
            'sent_to_admin' => false,
            'plain_text'    => true,
            'email'         => $this,
        ], '', $this->template_base );
    }
}
