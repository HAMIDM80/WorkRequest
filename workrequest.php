<?php
/*
Plugin Name: WorkRequest
Description: A custom plugin for managing repair requests and WooCommerce orders.
Version: 1.0.0
Author: seyedhamid
Text Domain: workrequest
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Define plugin constants for easier path management and versioning
if ( ! defined( 'WORKREQUEST_PLUGIN_DIR' ) ) {
    define( 'WORKREQUEST_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WORKREQUEST_PLUGIN_URL' ) ) {
    define( 'WORKREQUEST_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'WORKREQUEST_VERSION' ) ) {
    define( 'WORKREQUEST_VERSION', '1.0.1' ); // Increment version for cache busting
}

// Include Custom Post Type for Repair Requests
require_once WORKREQUEST_PLUGIN_DIR . 'includes/cpt-repair-request.php';

// Include Admin functionalities for Repair Request (Metaboxes, AJAX handlers)
require_once WORKREQUEST_PLUGIN_DIR . 'includes/admin-repair-request.php';

// Include frontend form file
require_once WORKREQUEST_PLUGIN_DIR . 'public/form-repair-request.php';

// --- Plugin Core Functions ---

/**
 * Checks if WooCommerce is active and displays an admin notice if it's not.
 */
function workrequest_check_woocommerce_active() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'workrequest_woocommerce_inactive_notice' );
    }
}
add_action( 'admin_init', 'workrequest_check_woocommerce_active' );

/**
 * Displays the WooCommerce inactive notice.
 */
function workrequest_woocommerce_inactive_notice() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p><strong><?php esc_html_e( 'WorkRequest requires WooCommerce to be active!', 'workrequest' ); ?></strong> <?php esc_html_e( 'Please install and activate WooCommerce.', 'workrequest' ); ?></p>
    </div>
    <?php
}

/**
 * Enqueues admin scripts and styles specifically for the 'repair_request' post type edit screen.
 * This function is defined ONLY once in this file to avoid redeclaration errors.
 */
function workrequest_enqueue_admin_scripts( $hook ) {
    global $pagenow; // Get the current admin page filename

    // Check if we are on the 'post.php' (edit post) or 'post-new.php' (new post) screen
    // AND if the current post type (for editing) or requested post type (for new) is 'repair_request'.
    if ( ( 'post.php' === $pagenow || 'post-new.php' === $pagenow ) ) {
        // Get the post type; for 'post-new.php', it might be in $_GET, for 'post.php', it's $post->post_type
        $current_post_type = '';
        if ( 'post.php' === $pagenow && isset( $_GET['post'] ) ) {
            $post = get_post( (int) $_GET['post'] );
            if ( $post ) {
                $current_post_type = $post->post_type;
            }
        } elseif ( 'post-new.php' === $pagenow && isset( $_GET['post_type'] ) ) {
            $current_post_type = sanitize_text_field( $_GET['post_type'] );
        }

        if ( 'repair_request' === $current_post_type ) {
            // Enqueue WooCommerce's enhanced select script (selectWoo) and its styles
            // These are crucial for the product search autocomplete functionality
            wp_enqueue_script( 'selectWoo' ); // selectWoo.full.js
            wp_enqueue_script( 'wc-enhanced-select' ); // Wraps selectWoo with WC-specific enhancements
            wp_enqueue_style( 'woocommerce_admin_styles' ); // For WC admin styles

            // Enqueue our custom admin metabox script
            wp_enqueue_script(
                'workrequest-admin-metabox', // Script handle
                WORKREQUEST_PLUGIN_URL . 'assets/js/admin-metabox.js', // Correct path relative to plugin base
                array( 'jquery', 'selectWoo', 'wc-enhanced-select' ), // Dependencies
                WORKREQUEST_VERSION, // Version for cache busting
                true // Load in footer
            );

            // Localize script for passing PHP variables to JavaScript
            wp_localize_script(
                'workrequest-admin-metabox', // Script handle to attach data to
                'WorkRequestAdmin', // JavaScript object name
                array(
                    'ajax_url'                   => admin_url( 'admin-ajax.php' ),
                    'search_products_nonce'      => wp_create_nonce( 'search-products' ), // Nonce for product search
                    'create_order_nonce'         => wp_create_nonce( 'workrequest_create_order_nonce' ), // Nonce for order creation
                    'post_id'                    => isset( $post ) ? $post->ID : 0, // Current post ID, or 0 for new posts
                    'search_placeholder_text'    => __( 'Search for a product...', 'workrequest' ),
                    'no_products_selected_text'  => __( 'Please select at least one product to create an order.', 'workrequest' ),
                    'order_success_text'         => __( 'Order created successfully! Order #%s.', 'workrequest' ),
                    'order_error_text'           => __( 'Error creating order:', 'workrequest' ),
                    'order_ajax_error_text'      => __( 'AJAX error during order creation:', 'workrequest' ),
                    'order_converted_text'       => __( 'This request has been converted to a WooCommerce order.', 'workrequest' ),
                    'order_number_text'          => __( 'Order Number', 'workrequest' ),
                    'order_status_text'          => __( 'Order Status', 'workrequest' ),
                    'order_total_text'           => __( 'Order Total', 'workrequest' ),
                    'edit_order_url'             => admin_url( 'post.php?post=%d&action=edit' ), // URL template to edit the created order
                    'order_edit_note'            => __( 'To edit the order details, please go to the WooCommerce order page.', 'workrequest' ),
                )
            );
        }
    }
}
// Hook our enqueue function to the admin_enqueue_scripts action
add_action( 'admin_enqueue_scripts', 'workrequest_enqueue_admin_scripts' );