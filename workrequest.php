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

// *** Add these lines to include the missing Admin Page classes ***
require_once WORKREQUEST_PLUGIN_DIR . 'src/Admin/RepairOrderPreparationPage.php';
require_once WORKREQUEST_PLUGIN_DIR . 'src/Admin/TasksByRequestPage.php';

// *** Add this line to include the Roles/Capabilities class ***
require_once WORKREQUEST_PLUGIN_DIR . 'src/Roles/Capabilities.php';


/**
 * Plugin activation hook.
 * Register CPTs and flush rewrite rules.
 */
function workrequest_activate_plugin() {
    // Instantiate CPT classes on activation to ensure registration occurs
    // so rewrite rules can be flushed correctly.
    // Note: These will also be instantiated on 'plugins_loaded' for normal runtime.
    new WorkRequest_CPT_RepairRequest();
    new WorkRequest_CPT_RepairOrderItem();
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
    // Optionally, remove capabilities on deactivation if desired
    // (You'd need a method in WorkRequest_Roles_Capabilities for this)
    // $capabilities = new WorkRequest_Roles_Capabilities();
    // $capabilities->remove_custom_roles();
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

        // *** Instantiate Admin Page classes ***
        new WorkRequest_Admin_RepairOrderPreparationPage();
        new WorkRequest_Admin_TasksByRequestPage(); // This assumes TasksByRequestPage is a separate menu item/submenu

        // Instantiate Roles/Capabilities (can also be done on admin_init if it needs to hook into admin-specific stuff)
        // It's often sufficient to do this on plugins_loaded or activation.
        new WorkRequest_Roles_Capabilities();
    }

    // Add other initializations here as your plugin grows
    // For example, frontend forms, settings pages, etc.
}
add_action( 'plugins_loaded', 'workrequest_initialize_plugin' );