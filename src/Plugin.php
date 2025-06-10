<?php
/**
 * Main plugin bootstrapper class.
 * This class handles the initialization of all plugin components.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Autoloader for Work Request classes.
 * This function is crucial for loading our classes without requiring each file manually.
 */
spl_autoload_register( function( $class ) {
    // Only load classes for our plugin
    if ( strpos( $class, 'WorkRequest_' ) !== 0 ) {
        return;
    }

    // Convert class name to file path format
    // e.g., WorkRequest_CPT_RepairRequest => CPT/RepairRequest.php
    $class_path = str_replace( 'WorkRequest_', '', $class ); // Remove plugin prefix
    $class_path = str_replace( '_', DIRECTORY_SEPARATOR, $class_path ); // Convert underscores to directory separators
    $file_path  = WORKREQUEST_PLUGIN_DIR . 'src/' . $class_path . '.php';

    if ( file_exists( $file_path ) ) {
        require_once $file_path;
    }
});

/**
 * Main plugin class.
 * This class will be responsible for orchestrating all other components of the plugin.
 */
class WorkRequest_Plugin {

    /**
     * Constructor for the plugin.
     * Initializes all necessary components.
     */
    public function __construct() {
        // Load text domain for translations
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

        // Initialize custom post types
        add_action( 'init', array( $this, 'init_cpts' ) );

        // Initialize admin menus
        add_action( 'admin_menu', array( $this, 'init_admin_menus' ) );

        // Initialize asset enqueuing
        add_action( 'admin_enqueue_scripts', array( $this, 'init_admin_assets' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'init_frontend_assets' ) );

        // Initialize AJAX handlers
        add_action( 'wp_ajax_workrequest_update_task_approval', array( $this, 'init_ajax_handlers' ) );
        add_action( 'wp_ajax_workrequest_create_order', array( $this, 'init_ajax_handlers' ) );
        // Add other AJAX actions here as they are moved

        // Initialize roles and capabilities
        add_action( 'plugins_loaded', array( $this, 'init_roles_capabilities' ) );

        // Frontend forms and display (might need to be moved to separate classes later)
        // For now, these include remain here or are moved to a frontend bootstrapper.
        // We'll keep them here temporarily and refactor their content into classes.
        require_once WORKREQUEST_PLUGIN_DIR . 'public/form-repair-request.php';
        require_once WORKREQUEST_PLUGIN_DIR . 'public/display-user-requests.php';
    }

    /**
     * Load plugin text domain.
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'workrequest', false, basename( WORKREQUEST_PLUGIN_DIR ) . '/languages' );
    }

    /**
     * Initialize Custom Post Types.
     */
    public function init_cpts() {
        new WorkRequest_CPT_RepairRequest();
        new WorkRequest_CPT_RepairOrderItem();
        new WorkRequest_CPT_Task();
    }

    /**
     * Initialize Admin Menus.
     */
    public function init_admin_menus() {
        new WorkRequest_Admin_Menus(); // This class will handle all menu registrations
        // Register callback for custom 'Tasks by Request' page
        add_action( 'load-toplevel_page_workrequest-main-menu', array( $this, 'handle_tasks_by_request_page_load' ) );
        add_action( 'load-workrequest_page_workrequest-tasks-by-request', array( $this, 'handle_tasks_by_request_page_load' ) );
    }
    
    /**
     * Handles the loading of the "Tasks by Request" custom admin page.
     */
    public function handle_tasks_by_request_page_load() {
        // Instantiate the page class, which will handle rendering and its own assets
        new WorkRequest_Admin_TasksByRequestPage();
    }


    /**
     * Initialize Admin Assets.
     */
    public function init_admin_assets( $hook_suffix ) {
        new WorkRequest_Assets_Enqueuer( $hook_suffix );
    }

    /**
     * Initialize Frontend Assets.
     */
    public function init_frontend_assets() {
        new WorkRequest_Assets_Enqueuer(); // Frontend hooks don't pass $hook_suffix
    }
    
    /**
     * Initialize AJAX Handlers.
     * This method will instantiate classes that contain our AJAX callback functions.
     * We're calling this repeatedly for different AJAX hooks, which is fine.
     */
    public function init_ajax_handlers() {
        // Instantiate AJAX handler classes
        new WorkRequest_AJAX_TaskHandler();
        new WorkRequest_AJAX_OrderCreationHandler();
        // Add other AJAX handler instantiations here as they are moved
    }

    /**
     * Initialize Roles and Capabilities.
     */
    public function init_roles_capabilities() {
        new WorkRequest_Roles_Capabilities();
    }
}