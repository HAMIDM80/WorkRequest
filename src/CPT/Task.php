<?php
/**
 * Custom Post Type: Task (task)
 * This class registers and manages the 'task' CPT.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WorkRequest_CPT_Task {

    /**
     * Constructor.
     * Hooks into WordPress to register the CPT and manage its messages.
     */
    public function __construct() {
        error_log( 'WorkRequest_CPT_Task constructor called.' ); // Add this line for debugging
        add_action( 'init', array( $this, 'register_cpt' ) );
        add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );
        // Note: Custom columns and filters are handled in a separate Admin class (TaskListTable or similar)
        // because they relate to the admin list table UI, not the CPT definition itself.
    }

    /**
     * Register the 'task' Custom Post Type.
     */
    public function register_cpt() {
        error_log( 'WorkRequest_CPT_Task register_cpt method called.' ); // Add this line for debugging
        $labels = array(
            'name'                  => _x( 'Tasks', 'Post Type General Name', 'workrequest' ),
            'singular_name'         => _x( 'Task', 'Post Type Singular Name', 'workrequest' ),
            'menu_name'             => __( 'Tasks', 'workrequest' ),
            'name_admin_bar'        => __( 'Task', 'workrequest' ),
            'archives'              => __( 'Task Archives', 'workrequest' ),
            'attributes'            => __( 'Task Attributes', 'workrequest' ),
            'parent_item_colon'     => __( 'Parent Task:', 'workrequest' ),
            'all_items'             => __( 'All Tasks', 'workrequest' ),
            'add_new_item'          => __( 'Add New Task', 'workrequest' ),
            'add_new'               => __( 'Add New', 'workrequest' ),
            'new_item'              => __( 'New Task', 'workrequest' ),
            'edit_item'             => __( 'Edit Task', 'workrequest' ),
            'update_item'           => __( 'Update Task', 'workrequest' ),
            'view_item'             => __( 'View Task', 'workrequest' ),
            'view_items'            => __( 'View Tasks', 'workrequest' ),
            'search_items'          => __( 'Search Tasks', 'workrequest' ),
            'not_found'             => __( 'No Tasks found', 'workrequest' ),
            'not_found_in_trash'    => __( 'No Tasks found in Trash', 'workrequest' ),
            'featured_image'        => __( 'Featured Image', 'workrequest' ),
            'set_featured_image'    => __( 'Set featured image', 'workrequest' ),
            'remove_featured_image' => __( 'Remove featured image', 'workrequest' ),
            'use_featured_image'    => __( 'Use as featured image', 'workrequest' ),
            'insert_into_item'      => __( 'Insert into task', 'workrequest' ),
            'uploaded_to_this_item' => __( 'Uploaded to this task', 'workrequest' ),
            'items_list'            => __( 'Tasks list', 'workrequest' ),
            'items_list_navigation' => __( 'Tasks list navigation', 'workrequest' ),
            'filter_items_list'     => __( 'Filter tasks list', 'workrequest' ),
        );
        $args = array(
            'label'                 => __( 'Task', 'workrequest' ),
            'description'           => __( 'Individual tasks associated with repair items.', 'workrequest' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'author' ),
            'hierarchical'          => false,
            'public'                => true, // Changed to true to make it publicly queryable (if needed)
            'show_ui'               => true,
            'show_in_menu'          => true, // **Changed to true to display in the main admin menu.**
                                            // If you plan to register this as a submenu, change back to false and use add_submenu_page().
            'menu_position'         => 30,
            'menu_icon'             => 'dashicons-list-view',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => true, // Changed to true to align with public
            'capability_type'       => 'post',
            'rewrite'               => false,
            'map_meta_cap'          => true,
            'capabilities'          => array(
                'edit_post'          => 'edit_task',
                'read_post'          => 'read_task',
                'delete_post'        => 'delete_task',
                'edit_posts'         => 'edit_tasks',
                'edit_others_posts'  => 'edit_others_tasks',
                'publish_posts'      => 'publish_tasks',
                'read_private_posts' => 'read_private_tasks',
                'create_posts'       => 'create_tasks',
            ),
        );
        register_post_type( 'task', $args );
    }

    /**
     * Manage messages for the task CPT.
     *
     * @param array $messages Existing post updated messages.
     * @return array Modified post updated messages.
     */
    public function updated_messages( $messages ) {
        global $post;
        $post_ID = $post->ID;
        $messages['task'] = array(
            0 => '', // Unused. Messages start at index 1.
            1 => __( 'Task updated.', 'workrequest' ),
            2 => __( 'Custom field updated.', 'workrequest' ),
            3 => __( 'Custom field deleted.', 'workrequest' ),
            4 => __( 'Task updated.', 'workrequest' ),
            /* translators: %s: date and time of the revision */
            5 => isset( $_GET['revision'] ) ? sprintf( __( 'Task restored to revision from %s.', 'workrequest' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
            6 => __( 'Task published.', 'workrequest' ),
            7 => __( 'Task saved.', 'workrequest' ),
            8 => __( 'Task submitted.', 'workrequest' ),
            9 => sprintf( __( 'Task scheduled for: <strong>%1$s</strong>.', 'workrequest' ),
                date_i18n( __( 'M j, Y @ H:i', 'workrequest' ), strtotime( get_post_field( 'post_date', $post_ID ) ) ) ),
            10 => __( 'Task draft updated.', 'workrequest' ),
        );
        return $messages;
    }

    // Helper function for status label (will be moved to a utility class or made static if widely used)
    public static function get_task_status_label( $status_slug ) {
        $statuses = array(
            'pending'      => __( 'Pending', 'workrequest' ),
            'assigned'     => __( 'Assigned', 'workrequest' ),
            'in_progress'  => __( 'In Progress', 'workrequest' ),
            'completed'    => __( 'Completed', 'workrequest' ),
            'on_hold'      => __( 'On Hold', 'workrequest' ),
            'cancelled'    => __( 'Cancelled', 'workrequest' ),
            'needs_review' => __( 'Needs Review', 'workrequest' ),
        );
        return isset( $statuses[ $status_slug ] ) ? $statuses[ $status_slug ] : ucfirst( str_replace( '_', ' ', $status_slug ) );
    }
}