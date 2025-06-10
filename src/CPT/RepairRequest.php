<?php
/**
 * Custom Post Type: Repair Request (repair_request)
 * This class registers and manages the 'repair_request' CPT.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WorkRequest_CPT_RepairRequest {

    /**
     * Constructor.
     * Hooks into WordPress to register the CPT and manage its messages.
     */
    public function __construct() {
        error_log( 'WorkRequest_CPT_RepairRequest constructor called.' ); // Add this line for debugging
        add_action( 'init', array( $this, 'register_cpt' ) );
        add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );
        // Removed: admin_init for capabilities, and column/sortable column filters.
        // These should be in src/Admin/RepairRequestMetaboxes.php or a dedicated Admin List Table class.
    }

    /**
     * Register the 'repair_request' Custom Post Type.
     */
    public function register_cpt() {
        error_log( 'WorkRequest_CPT_RepairRequest register_cpt method called.' ); // Add this line for debugging
        $labels = array(
            'name'                  => _x( 'Repair Requests', 'Post Type General Name', 'workrequest' ),
            'singular_name'         => _x( 'Repair Request', 'Post Type Singular Name', 'workrequest' ),
            'menu_name'             => __( 'Repair Requests', 'workrequest' ),
            'name_admin_bar'        => __( 'Repair Request', 'workrequest' ),
            'archives'              => __( 'Repair Request Archives', 'workrequest' ),
            'attributes'            => __( 'Repair Request Attributes', 'workrequest' ),
            'parent_item_colon'     => __( 'Parent Repair Request:', 'workrequest' ),
            'all_items'             => __( 'All Repair Requests', 'workrequest' ),
            'add_new_item'          => __( 'Add New Repair Request', 'workrequest' ),
            'add_new'               => __( 'Add New', 'workrequest' ),
            'new_item'              => __( 'New Repair Request', 'workrequest' ),
            'edit_item'             => __( 'Edit Repair Request', 'workrequest' ),
            'update_item'           => __( 'Update Repair Request', 'workrequest' ),
            'view_item'             => __( 'View Repair Request', 'workrequest' ),
            'view_items'            => __( 'View Repair Requests', 'workrequest' ),
            'search_items'          => __( 'Search Repair Requests', 'workrequest' ),
            'not_found'             => __( 'No Repair Requests found', 'workrequest' ),
            'not_found_in_trash'    => __( 'No Repair Requests found in Trash', 'workrequest' ),
            'featured_image'        => __( 'Featured Image', 'workrequest' ),
            'set_featured_image'    => __( 'Set featured image', 'workrequest' ),
            'remove_featured_image' => __( 'Remove featured image', 'workrequest' ),
            'use_featured_image'    => __( 'Use as featured image', 'workrequest' ),
            'insert_into_item'      => __( 'Insert into Repair Request', 'workrequest' ),
            'uploaded_to_this_item' => __( 'Uploaded to this Repair Request', 'workrequest' ),
            'items_list'            => __( 'Repair Requests list', 'workrequest' ),
            'items_list_navigation' => __( 'Repair Requests list navigation', 'workrequest' ),
            'filter_items_list'     => __( 'Filter Repair Requests list', 'workrequest' ),
        );
        $args = array(
            'label'                 => __( 'Repair Request', 'workrequest' ),
            'description'           => __( 'Repair requests submitted by users.', 'workrequest' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'author' ),
            'hierarchical'          => false,
            'public'                => true, // Make sure this is true for it to be visible in admin
            'show_ui'               => true, // Make sure this is true for it to be visible in admin
            'show_in_menu'          => true, // THIS IS KEY: Change to `true` to make it appear in the main admin menu.
                                            // Or keep it false if you intend to add it as a submenu under a custom parent.
                                            // For now, let's make it true to see it immediately.
            'menu_position'         => 20,
            'menu_icon'             => 'dashicons-hammer',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'rewrite'               => array(
                'slug'       => 'repair-request',
                'with_front' => true,
                'pages'      => true,
                'feeds'      => true,
            ),
            'map_meta_cap'          => true,
            'capabilities'          => array(
                'edit_post'          => 'edit_repair_request',
                'read_post'          => 'read_repair_request',
                'delete_post'        => 'delete_repair_request',
                'edit_posts'         => 'edit_repair_requests',
                'edit_others_posts'  => 'edit_others_repair_requests',
                'publish_posts'      => 'publish_repair_requests',
                'read_private_posts' => 'read_private_repair_requests',
                'create_posts'       => 'create_repair_requests',
            ),
        );
        register_post_type( 'repair_request', $args );
    }

    /**
     * Manage messages for the repair_request CPT.
     *
     * @param array $messages Existing post updated messages.
     * @return array Modified post updated messages.
     */
    public function updated_messages( $messages ) {
        global $post;
        $post_ID = $post->ID;
        $messages['repair_request'] = array(
            0 => '', // Unused. Messages start at index 1.
            1 => __( 'Repair Request updated.', 'workrequest' ),
            2 => __( 'Custom field updated.', 'workrequest' ),
            3 => __( 'Custom field deleted.', 'workrequest' ),
            4 => __( 'Repair Request updated.', 'workrequest' ),
            /* translators: %s: date and time of the revision */
            5 => isset( $_GET['revision'] ) ? sprintf( __( 'Repair Request restored to revision from %s.', 'workrequest' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
            6 => __( 'Repair Request published.', 'workrequest' ),
            7 => __( 'Repair Request saved.', 'workrequest' ),
            8 => __( 'Repair Request submitted.', 'workrequest' ),
            9 => sprintf( __( 'Repair Request scheduled for: <strong>%1$s</strong>.', 'workrequest' ),
                date_i18n( __( 'M j, Y @ H:i', 'workrequest' ), strtotime( get_post_field( 'post_date', $post_ID ) ) ) ),
            10 => __( 'Repair Request draft updated.', 'workrequest' ),
        );
        return $messages;
    }

    // Helper function for status label (will be moved to a utility class or made static if widely used)
    // For now, we'll keep it here, but it's a good candidate for `WorkRequest_Utils_Status::get_request_status_label()`
    public static function get_request_status_label( $status_slug ) {
        $statuses = array(
            'pending'           => __( 'Pending Review', 'workrequest' ),
            'in_progress'       => __( 'In Progress', 'workrequest' ),
            'awaiting_customer' => __( 'Awaiting Customer Response', 'workrequest' ),
            'completed'         => __( 'Completed', 'workrequest' ),
            'cancelled'         => __( 'Cancelled', 'workrequest' ),
            'on_hold'           => __( 'On Hold', 'workrequest' ),
        );
        return isset( $statuses[ $status_slug ] ) ? $statuses[ $status_slug ] : ucfirst( str_replace( '_', ' ', $status_slug ) );
    }
}