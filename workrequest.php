<?php
/**
 * Plugin Name: Work Request Management
 * Plugin URI:  https://example.com/workrequest
 * Description: A plugin to manage repair requests, tasks, and WooCommerce order creation for repair needs.
 * Version:     1.0.5
 * Author:      SeyedHamid
 * Author URI:  https://projecit.com
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: workrequest
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Define plugin constants
if ( ! defined( 'WORKREQUEST_PLUGIN_DIR' ) ) {
    define( 'WORKREQUEST_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WORKREQUEST_PLUGIN_URL' ) ) {
    define( 'WORKREQUEST_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'WORKREQUEST_PLUGIN_VERSION' ) ) {
    define( 'WORKREQUEST_PLUGIN_VERSION', '1.0.4' );
}

/**
 * Require necessary classes.
 * Load our core classes.
 */
// Core CPTs
require_once WORKREQUEST_PLUGIN_DIR . 'src/CPT/RepairRequest.php';
// require_once WORKREQUEST_PLUGIN_DIR . 'src/CPT/RepairOrderItem.php'; // REMOVED as per discussion
require_once WORKREQUEST_PLUGIN_DIR . 'src/CPT/Task.php';

// Admin-related classes
require_once WORKREQUEST_PLUGIN_DIR . 'src/Admin/RepairRequestMetaboxes.php';
// require_once WORKREQUEST_PLUGIN_DIR . 'src/Admin/RepairOrderItemMetaboxes.php'; // REMOVED as per discussion
require_once WORKREQUEST_PLUGIN_DIR . 'src/Admin/TaskMetaboxes.php';

// Admin Pages and Roles
require_once WORKREQUEST_PLUGIN_DIR . 'src/Admin/RepairOrderPreparationPage.php';
require_once WORKREQUEST_PLUGIN_DIR . 'src/Admin/TasksByRequestPage.php';
require_once WORKREQUEST_PLUGIN_DIR . 'src/Roles/Capabilities.php';

/**
 * Plugin activation hook.
 * Register CPTs and flush rewrite rules.
 */
function workrequest_activate_plugin() {
    // Instantiate CPT classes on activation to ensure registration occurs
    new WorkRequest_CPT_RepairRequest();
    // new WorkRequest_CPT_RepairOrderItem(); // REMOVED
    new WorkRequest_CPT_Task();

    // Instantiate capabilities on activation to ensure roles are registered
    new WorkRequest_Roles_Capabilities(); 

    // Flush rewrite rules after CPTs are registered
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'workrequest_activate_plugin' );

/**
 * Plugin deactivation hook.
 * Flush rewrite rules on deactivation.
 */
function workrequest_deactivate_plugin() {
    flush_rewrite_rules();
    // It's generally not recommended to remove custom roles/capabilities on deactivation
    // as it can leave orphaned data if the plugin is reactivated.
    // However, if you want to, you'd add a method in WorkRequest_Roles_Capabilities for it.
}
register_deactivation_hook( __FILE__, 'workrequest_deactivate_plugin' );


/**
 * Initialize all plugin classes.
 * This function will be called on WordPress 'plugins_loaded' hook.
 */
function workrequest_initialize_plugin() {
    // Check if WooCommerce is active
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'workrequest_admin_notice_woocommerce_missing' );
        return;
    }

    // Instantiate CPT classes
    new WorkRequest_CPT_RepairRequest();
    // new WorkRequest_CPT_RepairOrderItem(); // REMOVED
    new WorkRequest_CPT_Task();

    // Instantiate Admin classes only if in admin area
    if ( is_admin() ) {
        new WorkRequest_Admin_RepairRequestMetaboxes();
        // new WorkRequest_Admin_RepairOrderItemMetaboxes(); // REMOVED
        new WorkRequest_Admin_TaskMetaboxes();

        // Admin Pages and Roles
        new WorkRequest_Admin_RepairOrderPreparationPage();
        new WorkRequest_Admin_TasksByRequestPage();
        new WorkRequest_Roles_Capabilities(); // Ensure roles/capabilities are loaded during normal admin requests

        // AJAX handler for product search (needed by RepairRequestMetaboxes)
        add_action( 'wp_ajax_workrequest_search_products', 'workrequest_ajax_search_products' );
        add_action( 'wp_ajax_nopriv_workrequest_search_products', 'workrequest_ajax_search_products' ); // In case needed for non-logged-in users (unlikely for admin search)
    }
}
add_action( 'plugins_loaded', 'workrequest_initialize_plugin' );

/**
 * Admin notice if WooCommerce is not active.
 */
function workrequest_admin_notice_woocommerce_missing() {
    ?>
    <div class="notice notice-error">
        <p><strong><?php esc_html_e( 'Work Request Management requires WooCommerce to be installed and active.', 'workrequest' ); ?></strong></p>
        <p><?php printf( esc_html__( 'Please install and activate %sWooCommerce%s to use this plugin.', 'workrequest' ), '<a href="' . esc_url( admin_url( 'plugin-install.php?tab=plugin-information&plugin=woocommerce&TB_iframe=true&width=772&height=533' ) ) . '" class="thickbox open-plugin-details-modal">', '</a>' ); ?></p>
    </div>
    <?php
}

/**
 * AJAX callback for product search (for Select2 in RepairRequestMetaboxes).
 */
function workrequest_ajax_search_products() {
    check_ajax_referer( 'workrequest_product_search_nonce', 'security' );

    if ( ! current_user_can( 'edit_products' ) ) { // Capability to search products
        wp_send_json_error( array() );
    }

    $search_term = isset( $_GET['q'] ) ? sanitize_text_field( $_GET['q'] ) : '';

    if ( empty( $search_term ) ) {
        wp_send_json_success( array() );
    }

    $products = wc_get_products( array(
        'status'  => 'publish',
        'limit'   => 20,
        's'       => $search_term,
        'return'  => 'ids',
    ) );

    $results = array();
    foreach ( $products as $product_id ) {
        $product = wc_get_product( $product_id );
        if ( $product ) {
            $results[] = array(
                'id'   => $product->get_id(),
                'text' => $product->get_formatted_name(), // Includes variation details
            );
        }
    }

    wp_send_json_success( $results );
}