<?php
// src/CPT/RepairOrderItem.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WorkRequest_CPT_RepairOrderItem {

    public function __construct() {
        add_action( 'init', array( $this, 'register_cpt' ) );
        add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );
        // No other admin UI hooks should be here (e.g., capability definitions, list table columns)
    }

    public function register_cpt() {
        $labels = array(
            'name'                  => _x( 'Repair Order Items', 'Post Type General Name', 'workrequest' ),
            'singular_name'         => _x( 'Repair Order Item', 'Post Type Singular Name', 'workrequest' ),
            'menu_name'             => _x( 'Repair Order Items', 'Admin Menu text', 'workrequest' ),
            'name_admin_bar'        => _x( 'Repair Order Item', 'Add New on Toolbar', 'workrequest' ),
            'archives'              => __( 'Repair Order Item Archives', 'workrequest' ),
            'attributes'            => __( 'Repair Order Item Attributes', 'workrequest' ),
            'parent_item_colon'     => __( 'Parent Repair Order Item:', 'workrequest' ),
            'all_items'             => __( 'All Repair Order Items', 'workrequest' ),
            'add_new_item'          => __( 'Add New Repair Order Item', 'workrequest' ),
            'add_new'               => __( 'Add New', 'workrequest' ),
            'new_item'              => __( 'New Repair Order Item', 'workrequest' ),
            'edit_item'             => __( 'Edit Repair Order Item', 'workrequest' ),
            'update_item'           => __( 'Update Repair Order Item', 'workrequest' ),
            'view_item'             => __( 'View Repair Order Item', 'workrequest' ),
            'view_items'            => __( 'View Repair Order Items', 'workrequest' ),
            'search_items'          => __( 'Search Repair Order Items', 'workrequest' ),
            'not_found'             => __( 'Not found', 'workrequest' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'workrequest' ),
            'featured_image'        => __( 'Featured Image', 'workrequest' ),
            'set_featured_image'    => __( 'Set featured image', 'workrequest' ),
            'remove_featured_image' => __( 'Remove featured image', 'workrequest' ),
            'use_featured_image'    => __( 'Use as featured image', 'workrequest' ),
            'insert_into_item'      => __( 'Insert into Repair Order Item', 'workrequest' ),
            'uploaded_to_this_item' => __( 'Uploaded to this Repair Order Item', 'workrequest' ),
            'items_list'            => __( 'Repair Order Items list', 'workrequest' ),
            'items_list_navigation' => __( 'Repair Order Items list navigation', 'workrequest' ),
            'filter_items_list'     => __( 'Filter Repair Order Items list', 'workrequest' ),
        );
        $args = array(
            'label'                 => _x( 'Repair Order Item', 'Post Type Label', 'workrequest' ),
            'description'           => __( 'Manages individual repair order items for repair requests.', 'workrequest' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'custom-fields' ), // Add any necessary supports
            'hierarchical'          => false,
            'public'                => true, // Must be true for show_ui to work correctly
            'show_ui'               => true, // Must be true for it to appear in admin UI
            // --- MODIFIED LINE START ---
            'show_in_menu'          => 'edit.php?post_type=repair_request', // Make it a submenu of Repair Requests
            // --- MODIFIED LINE END ---
            'menu_position'         => 35, // Position is still relevant for sorting within submenu
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => false, // Usually no archive for sub-items
            'exclude_from_search'   => true,
            'publicly_queryable'    => false, // Set to false if not meant to be directly viewed on frontend
            'capability_type'       => array( 'repair_order_item', 'repair_order_items' ), // Custom capability base
            'map_meta_cap'          => true, // Map to standard meta capabilities
            'query_var'             => false, // No query var needed if not publicly queryable
            'rewrite'               => false, // No rewrite needed if not publicly queryable
        );
        register_post_type( 'repair_order_item', $args );
    }

    public function updated_messages( $messages ) {
        global $post;
        $post_type = get_post_type( $post->ID );
        $post_type_object = get_post_type_object( $post_type );

        $messages['repair_order_item'] = array(
            0 => '', // Unused. Messages start at index 1.
            1 => __( 'Repair order item updated.', 'workrequest' ),
            2 => __( 'Custom field updated.', 'workrequest' ),
            3 => __( 'Custom field deleted.', 'workrequest' ),
            4 => __( 'Repair order item updated.', 'workrequest' ),
            /* translators: %s: Date and time of the revision */
            5 => isset( $_GET['revision'] ) ? sprintf( __( 'Repair order item restored to revision from %s.', 'workrequest' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
            6 => __( 'Repair order item published.', 'workrequest' ),
            7 => __( 'Repair order item saved.', 'workrequest' ),
            8 => __( 'Repair order item submitted.', 'workrequest' ),
            9 => sprintf(
                __( 'Repair order item scheduled for: %s.', 'workrequest' ),
                '<strong>' . date_i18n( __( 'M j, Y @ H:i', 'workrequest' ), strtotime( $post->post_date ) ) . '</strong>'
            ),
            10 => __( 'Repair order item draft updated.', 'workrequest' ),
        );

        if ( isset( $messages[ $post_type ] ) ) {
            return $messages;
        } else {
            return array_merge( $messages, $messages['repair_order_item'] ); // Fallback if no specific messages are set
        }
    }
}