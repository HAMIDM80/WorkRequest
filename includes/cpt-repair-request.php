<?php
/**
 * Registers the 'Repair Request' Custom Post Type.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function workrequest_register_repair_request_cpt() {
    $labels = [
        'name'               => _x( 'Repair Requests', 'Post Type General Name', 'workrequest' ),
        'singular_name'      => _x( 'Repair Request', 'Post Type Singular Name', 'workrequest' ),
        'menu_name'          => __( 'Repair Requests', 'workrequest' ),
        'parent_item_colon'  => __( 'Parent Repair Request:', 'workrequest' ),
        'all_items'          => __( 'All Requests', 'workrequest' ),
        'view_item'          => __( 'View Request', 'workrequest' ),
        'add_new_item'       => __( 'Add New Request', 'workrequest' ),
        'add_new'            => __( 'Add New', 'workrequest' ),
        'edit_item'          => __( 'Edit Request', 'workrequest' ),
        'update_item'        => __( 'Update Request', 'workrequest' ),
        'search_items'       => __( 'Search Request', 'workrequest' ),
        'not_found'          => __( 'Not Found', 'workrequest' ),
        'not_found_in_trash' => __( 'Not Found in Trash', 'workrequest' ),
    ];
    $args = [
        'label'               => __( 'Repair Requests', 'workrequest' ),
        'description'         => __( 'Manages repair requests submitted by users.', 'workrequest' ),
        'labels'              => $labels,
        'supports'            => [ 'title', 'editor', 'author', 'comments' ],
        'hierarchical'        => false,
        'public'              => false, // Set to false so it's not publicly queryable
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_admin_bar'   => true,
        'show_in_nav_menus'   => true,
        'can_export'          => true,
        'has_archive'         => false,
        'exclude_from_search' => true,
        'publicly_queryable'  => false,
        'capability_type'     => 'post',
        'menu_icon'           => 'dashicons-hammer',
        'menu_position'       => 25,
        'show_in_rest'        => true, // Enable for Gutenberg editor and REST API
    ];
    register_post_type( 'repair_request', $args );

    // Register a custom post status for 'Pending Review'
    register_post_status( 'pending_review', array(
        'label'                     => _x( 'Pending Review', 'repair_request', 'workrequest' ),
        'public'                    => true, // Set to false if you don't want it publicly accessible via direct URL
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Pending Review <span class="count">(%s)</span>', 'Pending Review <span class="count">(%s)</span>', 'workrequest' ),
    ) );

    // Register custom post status for 'Converted to Order'
    register_post_status( 'converted_to_order', array(
        'label'                     => _x( 'Converted to Order', 'repair_request', 'workrequest' ),
        'public'                    => true, // Set to false if you don't want it publicly accessible via direct URL
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Converted to Order <span class="count">(%s)</span>', 'Converted to Order <span class="count">(%s)</span>', 'workrequest' ),
    ) );
}
add_action( 'init', 'workrequest_register_repair_request_cpt' );

/**
 * Ensures custom capabilities are added for the repair_request CPT.
 * This function can be expanded later if you need specific roles/capabilities
 * beyond the default 'edit_posts', 'publish_posts', etc.
 */
function workrequest_add_repair_request_capabilities() {
    // For now, this is a placeholder. Custom capabilities will be more relevant
    // when defining roles for operators and contractors in later modules.
}
add_action( 'admin_init', 'workrequest_add_repair_request_capabilities' );

/**
 * Add custom post statuses to the dropdown in post edit screen.
 */
function workrequest_append_post_status_list() {
    global $post;

    if ( $post->post_type == 'repair_request' ) {
        $statuses = array(
            'pending_review'     => _x( 'Pending Review', 'repair_request status', 'workrequest' ),
            'converted_to_order' => _x( 'Converted to Order', 'repair_request status', 'workrequest' ),
        );

        echo '<script type="text/javascript">';
        echo 'jQuery(document).ready(function($){';
        foreach ( $statuses as $status => $label ) {
            $selected = '';
            if ( $status == $post->post_status ) {
                $selected = ' selected="selected"';
            }
            echo "$('select#post_status').append('<option value=\"{$status}\"{$selected}>{$label}</option>');";
        }
        echo '});';
        echo '</script>';
    }
}
add_action( 'admin_footer-post.php', 'workrequest_append_post_status_list' );
add_action( 'admin_footer-post-new.php', 'workrequest_append_post_status_list' );