<?php

/**
 * The Back In Stock Notifications - Helper Functions
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

/**
 * Allowed HTML tags
 * 
 * This function extends the wp_kses_allowed_html function to include
 * a handful of additional HTML fields that are used throughout
 * this plugin
 * 
 * @since  1.0.0
 * @return array
 */
function bisn_allowed_tags() {
    $my_allowed = wp_kses_allowed_html( 'post' );

    // Allow form element
    $my_allowed['form'] = [
        'method' => [],
        'action' => [],
        'class'  => [],
        'id'     => [],
    ];

    // Allow button element
    $my_allowed['button'] = [
        'type'  => [],
        'name'  => [],
        'class' => [],
        'id'    => [],
        'value' => [],
    ];

    // Allow input fields
    $my_allowed['input'] = [
        'class'   => [],
        'id'      => [],
        'name'    => [],
        'value'   => [],
        'type'    => [],
        'checked' => [],
    ];

    // Allow select
    $my_allowed['select'] = [
        'class' => [],
        'id'    => [],
        'name'  => [],
        'value' => [],
        'type'  => [],
    ];

    // Allow select options
    $my_allowed['option'] = [
        'selected' => [],
        'value'    => [],
    ];

    // Allow inline styles
    $my_allowed['style'] = [
        'types' => [],
    ];

    // Allow iframe
    $my_allowed['iframe'] = [
        'src'             => [],
        'height'          => [],
        'width'           => [],
        'frameborder'     => [],
        'allowfullscreen' => [],
    ];

    // Allow SVG elements
    $my_allowed['svg'] = [
        'xmlns'           => [],
        'width'           => [],
        'height'          => [],
        'viewBox'         => [],
        'stroke-width'    => [],
        'stroke'          => [],
        'fill'            => [],
        'stroke-linecap'  => [],
        'stroke-linejoin' => [],
        'class'           => [],
    ];
    $my_allowed['path'] = [
        'd'      => [],
        'stroke' => [],
        'fill'   => [],
    ];
    $my_allowed['line'] = [
        'x1'     => [],
        'y1'     => [],
        'x2'     => [],
        'y2'     => [],
        'stroke' => [],
    ];

    return $my_allowed;
}
