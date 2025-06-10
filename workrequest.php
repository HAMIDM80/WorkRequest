<?php
/**
 * Plugin Name: Work Request Management
 * Plugin URI:  https://example.com/workrequest
 * Description: A plugin to manage repair requests, tasks, and repair order items.
 * Version:     1.0.3
 * Author:      Your Name
 * Author URI:  https://example.com
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

/**
 * Require necessary classes.
 * Load our core classes.
 */
// Core CPTs
require_once WORKREQUEST_PLUGIN_DIR . 'src/CPT/RepairRequest.php';
require_once WORKREQUEST_PLUGIN_DIR . 'src/CPT/RepairOrderItem.php';
require_once WORKREQUEST_PLUGIN_DIR . 'src/CPT/Task.php';

// Admin-related classes
require_once WORKREQUEST_PLUGIN_DIR . 'src/Admin/RepairRequestMetaboxes.php';
require_once WORKREQUEST_PLUGIN_DIR . 'src/Admin/RepairOrderItemMetaboxes.php';
require_once WORKREQUEST_PLUGIN_DIR . 'src/Admin/TaskMetaboxes.php';

// --- START: Added/Modified for Admin Pages and Roles ---
require_once WORKREQUEST_PLUGIN_DIR . 'src/Admin/RepairOrderPreparationPage.php';
require_once WORKREQUEST_PLUGIN_DIR . 'src/Admin/TasksByRequestPage.php';
require_once WORKREQUEST_PLUGIN_DIR . 'src/Roles/Capabilities.php';
// --- END: Added/Modified for Admin Pages and Roles ---


/**
 * Plugin activation hook.
 * Register CPTs and flush rewrite rules.
 */
function workrequest_activate_plugin() {
    // Instantiate CPT classes on activation to ensure registration occurs
    // so rewrite rules can be flushed correctly.
    new WorkRequest_CPT_RepairRequest();
    new WorkRequest_CPT_RepairOrderItem();
    new WorkRequest_CPT_Task();

    // Instantiate capabilities on activation to ensure roles are registered
    // This calls the constructor of Capabilities class, which should add roles/caps.
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
    // Optionally, remove capabilities on deactivation if desired
    // To remove roles/capabilities on deactivation, you'd need a dedicated method in WorkRequest_Roles_Capabilities
    // Example: (assuming you add a remove_roles_and_caps method)
    // $capabilities_handler = new WorkRequest_Roles_Capabilities();
    // $capabilities_handler->remove_custom_roles_and_caps(); // You'd need to implement this method
}
register_deactivation_hook( __FILE__, 'workrequest_deactivate_plugin' );


/**
 * Initialize all plugin classes.
 * This function will be called on WordPress 'plugins_loaded' hook.
 */
function workrequest_initialize_plugin() {
    // Instantiate CPT classes
    new WorkRequest_CPT_RepairRequest();
    new WorkRequest_CPT_RepairOrderItem();
    new WorkRequest_CPT_Task();

    // Instantiate Admin classes only if in admin area
    if ( is_admin() ) {
        new WorkRequest_Admin_RepairRequestMetaboxes();
        new WorkRequest_Admin_RepairOrderItemMetaboxes();
        new WorkRequest_Admin_TaskMetaboxes();

        // --- START: Instantiating Admin Page classes and Roles/Capabilities ---
        new WorkRequest_Admin_RepairOrderPreparationPage();
        new WorkRequest_Admin_TasksByRequestPage();
        new WorkRequest_Roles_Capabilities(); // Ensure roles/capabilities are loaded during normal admin requests
        // --- END: Instantiating Admin Page classes and Roles/Capabilities ---
    }

    // Add other initializations here as your plugin grows
    // For example, frontend forms, settings pages, etc.
}
add_action( 'plugins_loaded', 'workrequest_initialize_plugin' );