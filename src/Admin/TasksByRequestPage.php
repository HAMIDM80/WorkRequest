<?php
// src/Admin/TasksByRequestPage.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WorkRequest_Admin_TasksByRequestPage {

    public function __construct() {
        // Hook to add the admin menu page
        add_action( 'admin_menu', array( $this, 'add_admin_menu_page' ) );
        // You might have other hooks here for enqueuing scripts/styles specific to this page,
        // or handling form submissions for this page.
    }

    /**
     * Add the "Tasks by Request" submenu page under "Repair Requests".
     */
    public function add_admin_menu_page() {
        add_submenu_page(
            'edit.php?post_type=repair_request', // Parent slug for 'Repair Requests' CPT
            __( 'Tasks By Request', 'workrequest' ), // Page title
            __( 'Tasks By Request', 'workrequest' ), // Menu title
            'manage_repair_requests', // Capability required to access this page
            'tasks-by-request', // Menu slug
            array( $this, 'render_page_content' ) // Callback function to render the page content
        );
    }

    /**
     * Renders the content of the "Tasks by Request" admin page.
     */
    public function render_page_content() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'Tasks By Request', 'workrequest' ); ?></h1>
            <p><?php _e( 'This page will display tasks grouped by repair request.', 'workrequest' ); ?></p>
            <?php
            // Here you would typically include logic to fetch and display tasks.
            // For example, you might have a custom WP_List_Table for tasks.
            // Or a form to filter tasks by repair request.
            ?>
        </div>
        <?php
    }
}