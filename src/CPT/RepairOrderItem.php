<?php
/**
 * Custom Post Type: Repair Order Item (repair_order_item)
 * This class registers and manages the 'repair_order_item' CPT.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WorkRequest_CPT_RepairOrderItem {

    /**
     * Constructor.
     * Hooks into WordPress to register the CPT and manage its messages.
     */
    public function __construct() {
        error_log( 'WorkRequest_CPT_RepairOrderItem constructor called.' ); // Add this line
        add_action( 'init', array( $this, 'register_cpt' ) );
        add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );
        // Note: Custom columns are handled in a separate Admin class (RepairOrderItemListTable or similar)
        // because they relate to the admin list table UI, not the CPT definition itself.
    }

    /**
     * Register the 'repair_order_item' Custom Post Type.
     */
    public function register_cpt() {
        error_log( 'WorkRequest_CPT_RepairOrderItem register_cpt method called.' ); // Add this line
        $labels = array(
            'name'                  => _x( 'Repair Work Items', 'Post Type General Name', 'workrequest' ),
            'singular_name'         => _x( 'Repair Work Item', 'Post Type Singular Name', 'workrequest' ),
            'menu_name'             => __( 'Repair Work Items', 'workrequest' ),
            'name_admin_bar'        => __( 'Repair Work Item', 'workrequest' ),
            'archives'              => __( 'Repair Work Item Archives', 'workrequest' ),
            'attributes'            => __( 'Repair Work Item Attributes', 'workrequest' ),
            'parent_item_colon'     => __( 'Parent Work Item:', 'workrequest' ),
            'all_items'             => __( 'All Repair Work Items', 'workrequest' ),
            'add_new_item'          => __( 'Add New Repair Work Item', 'workrequest' ),
            'add_new'               => __( 'Add New', 'workrequest' ),
            'new_item'              => __( 'New Repair Work Item', 'workrequest' ),
            'edit_item'             => __( 'Edit Repair Work Item', 'workrequest' ),
            'update_item'           => __( 'Update Repair Work Item', 'workrequest' ),
            'view_item'             => __( 'View Repair Work Item', 'workrequest' ),
            'view_items'            => __( 'View Repair Work Items', 'workrequest' ),
            'search_items'          => __( 'Search Repair Work Items', 'workrequest' ),
            'not_found'             => __( 'No Repair Work Items found', 'workrequest' ),
            'not_found_in_trash'    => __( 'No Repair Work Items found in Trash', 'workrequest' ),
            'featured_image'        => __( 'Featured Image', 'workrequest' ),
            'set_featured_image'    => __( 'Set featured image', 'workrequest' ),
            'remove_featured_image' => __( 'Remove featured image', 'workrequest' ),
            'use_featured_image'    => __( 'Use as featured image', 'workrequest' ),
            'insert_into_item'      => __( 'Insert into work item', 'workrequest' ),
            'uploaded_to_this_item' => __( 'Uploaded to this work item', 'workrequest' ),
            'items_list'            => __( 'Repair Work Items list', 'workrequest' ),
            'items_list_navigation' => __( 'Repair Work Items list navigation', 'workrequest' ),
            'filter_items_list'     => __( 'Filter Repair Work Items list', 'workrequest' ),
        );
        $args = array(
            'label'                 => __( 'Repair Work Item', 'workrequest' ),
            'description'           => __( 'Individual items or services for a repair request.', 'workrequest' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'author' ),
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => false, // Handled by add_submenu_page in WorkRequest_Admin_Menus
            'menu_position'         => 25,
            'menu_icon'             => 'dashicons-admin-tools',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'post',
            'rewrite'               => false,
            'map_meta_cap'          => true,
            'capabilities'          => array(
                'edit_post'          => 'edit_repair_order_item',
                'read_post'          => 'read_repair_order_item',
                'delete_post'        => 'delete_repair_order_item',
                'edit_posts'         => 'edit_repair_order_items',
                'edit_others_posts'  => 'edit_others_repair_order_items',
                'publish_posts'      => 'publish_repair_order_items',
                'read_private_posts' => 'read_private_repair_order_items',
                'create_posts'       => 'create_repair_order_items',
            ),
        );
        register_post_type( 'repair_order_item', $args );
    }

    /**
     * Manage messages for the repair_order_item CPT.
     *
     * @param array $messages Existing post updated messages.
     * @return array Modified post updated messages.
     */
    public function updated_messages( $messages ) {
        global $post;
        $post_ID = $post->ID;
        $messages['repair_order_item'] = array(
            0 => '', // Unused. Messages start at index 1.
            1 => __( 'Repair Work Item updated.', 'workrequest' ),
            2 => __( 'Custom field updated.', 'workrequest' ),
            3 => __( 'Custom field deleted.', 'workrequest' ),
            4 => __( 'Repair Work Item updated.', 'workrequest' ),
            /* translators: %s: date and time of the revision */
            5 => isset( $_GET['revision'] ) ? sprintf( __( 'Repair Work Item restored to revision from %s.', 'workrequest' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
            6 => __( 'Repair Work Item published.', 'workrequest' ),
            7 => __( 'Repair Work Item saved.', 'workrequest' ),
            8 => __( 'Repair Work Item submitted.', 'workrequest' ),
            9 => sprintf( __( 'Repair Work Item scheduled for: <strong>%1$s</strong>.', 'workrequest' ),
                date_i18n( __( 'M j, Y @ H:i', 'workrequest' ), strtotime( get_post_field( 'post_date', $post_ID ) ) ) ),
            10 => __( 'Repair Work Item draft updated.', 'workrequest' ),
        );
        return $messages;
    }
}