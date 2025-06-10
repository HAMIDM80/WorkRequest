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
 * Autoload classes (optional but recommended for larger projects)
 * For simplicity in this refactoring, we will manually require.
 * In a real-world plugin, you'd use Composer for autoloading.
 */
// spl_autoload_register( function ( $class_name ) {
//     $file = WORKREQUEST_PLUGIN_DIR . 'src/' . str_replace( '_', '/', $class_name ) . '.php';
//     if ( file_exists( $file ) ) {
//         require_once $file;
//     }
// });

/**
 * Require necessary classes.
 * Load our core classes.
 */
require_once WORKREQUEST_PLUGIN_DIR . 'src/CPT/RepairRequest.php';
require_once WORKREQUEST_PLUGIN_DIR . 'src/CPT/RepairOrderItem.php';
require_once WORKREQUEST_PLUGIN_DIR . 'src/CPT/Task.php';

require_once WORKREQUEST_PLUGIN_DIR . 'src/Admin/RepairRequestMetaboxes.php';
require_once WORKREQUEST_PLUGIN_DIR . 'src/Admin/RepairOrderItemMetaboxes.php';
require_once WORKREQUEST_PLUGIN_DIR . 'src/Admin/TaskMetaboxes.php';

// You might have a main plugin class that handles instantiation,
// but for now, we'll instantiate them directly in the main file.
// In a more complex plugin, you would likely have a central 'WorkRequest' class
// that initializes all these components.

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

    // Flush rewrite rules after CPTs are registered
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'workrequest_activate_plugin' );

/**
 * Plugin deactivation hook.
 */
function workrequest_deactivate_plugin() {
    flush_rewrite_rules();
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

    // Instantiate Admin Metaboxes classes
    if ( is_admin() ) {
        new WorkRequest_Admin_RepairRequestMetaboxes();
        new WorkRequest_Admin_RepairOrderItemMetaboxes();
        new WorkRequest_Admin_TaskMetaboxes();
    }

    // Add other initializations here as your plugin grows
    // For example, frontend forms, settings pages, etc.
}
add_action( 'plugins_loaded', 'workrequest_initialize_plugin' );