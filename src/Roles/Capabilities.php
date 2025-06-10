<?php
// src/Roles/Capabilities.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WorkRequest_Roles_Capabilities {

    public function __construct() {
        // We will register roles and capabilities on plugin activation
        // and also ensure they are present on every admin page load,
        // in case they were removed or plugin wasn't activated properly.
        add_action( 'init', array( $this, 'add_custom_roles_and_capabilities' ) );
    }

    /**
     * Adds custom roles and capabilities to WordPress.
     * This method is designed to be called on plugin activation AND on 'init' hook.
     */
    public function add_custom_roles_and_capabilities() {
        if ( get_option( 'workrequest_custom_roles_added' ) !== 'yes' ) {
            // Add custom roles only once or if somehow they were removed
            $this->add_roles();
            update_option( 'workrequest_custom_roles_added', 'yes' );
        }
        // Always ensure capabilities are assigned to roles on every init in admin,
        // especially during development or if roles were manually edited.
        $this->assign_capabilities_to_roles();
    }


    /**
     * Define and add custom roles.
     * Should only be called on plugin activation.
     */
    private function add_roles() {
        // Define your custom roles here
        add_role(
            'repair_technician',
            __( 'Repair Technician', 'workrequest' ),
            array(
                'read' => true, // Basic read access
            )
        );
        add_role(
            'customer_service',
            __( 'Customer Service', 'workrequest' ),
            array(
                'read' => true, // Basic read access
            )
        );
        // Add more roles as needed
    }

    /**
     * Assigns specific capabilities to roles.
     * This is called on every 'init' hook.
     */
    private function assign_capabilities_to_roles() {
        $roles = $this->get_custom_roles();
        foreach ( $roles as $role_name => $capabilities ) {
            $role = get_role( $role_name );
            if ( $role ) {
                foreach ( $capabilities as $cap => $grant ) {
                    if ( $grant ) {
                        $role->add_cap( $cap );
                    } else {
                        $role->remove_cap( $cap );
                    }
                }
            }
        }

        // Ensure Administrator always has all custom capabilities
        $admin_role = get_role( 'administrator' );
        if ( $admin_role ) {
            $all_custom_caps = $this->get_all_custom_capabilities();
            foreach ( $all_custom_caps as $cap ) {
                $admin_role->add_cap( $cap );
            }
        }
    }


    /**
     * Defines the custom capabilities for each post type.
     * These should EXACTLY match the 'capability_type' used in CPT registration.
     *
     * @return array All custom capabilities defined by the plugin.
     */
    private function get_all_custom_capabilities() {
        return array(
            // Repair Request Capabilities
            'edit_repair_request',
            'read_repair_request',
            'delete_repair_request',
            'edit_repair_requests',
            'edit_others_repair_requests',
            'publish_repair_requests',
            'read_private_repair_requests',
            'delete_repair_requests',
            'delete_private_repair_requests',
            'delete_published_repair_requests',
            'delete_others_repair_requests',
            'edit_published_repair_requests',
            'create_repair_requests',
            // Repair Order Item Capabilities
            'edit_repair_order_item',
            'read_repair_order_item',
            'delete_repair_order_item',
            'edit_repair_order_items',
            'edit_others_repair_order_items',
            'publish_repair_order_items',
            'read_private_repair_order_items',
            'delete_repair_order_items',
            'delete_private_repair_order_items',
            'delete_published_repair_order_items',
            'delete_others_repair_order_items',
            'edit_published_repair_order_items',
            'create_repair_order_items',
            // Task Capabilities
            'edit_task',
            'read_task',
            'delete_task',
            'edit_tasks',
            'edit_others_tasks',
            'publish_tasks',
            'read_private_tasks',
            'delete_tasks',
            'delete_private_tasks',
            'delete_published_tasks',
            'delete_others_tasks',
            'edit_published_tasks',
            'create_tasks',
            // Capabilities for accessing specific admin pages
            'manage_repair_requests', // Used for Repair Order Preparation Page and Tasks By Request Page
        );
    }

    /**
     * Defines the capabilities for each custom role.
     *
     * @return array An array of custom roles and their capabilities.
     */
    private function get_custom_roles() {
        return array(
            'administrator' => array(
                // Administrator should have all capabilities by default, handled in assign_capabilities_to_roles()
            ),
            'repair_technician' => array(
                'read' => true,
                'edit_tasks' => true,
                'edit_published_tasks' => true,
                'read_task' => true,
                'read_private_tasks' => true, // If technicians can see private tasks
                'delete_tasks' => true, // If technicians can delete their own tasks
                'edit_repair_requests' => true, // Can edit repair requests assigned to them
                'read_repair_request' => true,
                'read_private_repair_requests' => true, // Can read private repair requests
                'edit_repair_order_items' => true,
                'read_repair_order_item' => true,
                'manage_repair_requests' => true, // If they need access to the admin pages
            ),
            'customer_service' => array(
                'read' => true,
                'create_repair_requests' => true,
                'edit_repair_requests' => true,
                'edit_published_repair_requests' => true,
                'read_repair_request' => true,
                'read_private_repair_requests' => true,
                'delete_repair_requests' => true, // If they can delete requests
                'manage_repair_requests' => true, // If they need access to the admin pages
            ),
        );
    }

    /**
     * Optionally remove custom roles and capabilities on plugin deactivation.
     * Implement this method if you want to clean up roles/capabilities when plugin is deactivated.
     * This method would be called from the `workrequest_deactivate_plugin` function.
     */
    public function remove_custom_roles_and_caps() {
        // Get all custom capabilities
        $all_custom_caps = $this->get_all_custom_capabilities();

        // Remove capabilities from roles
        $roles_to_clean = array( 'administrator', 'repair_technician', 'customer_service' ); // Add any other custom roles
        foreach ( $roles_to_clean as $role_name ) {
            $role = get_role( $role_name );
            if ( $role ) {
                foreach ( $all_custom_caps as $cap ) {
                    $role->remove_cap( $cap );
                }
            }
        }

        // Remove custom roles themselves
        remove_role( 'repair_technician' );
        remove_role( 'customer_service' );

        // Clean up the option that prevents re-adding roles unnecessarily
        delete_option( 'workrequest_custom_roles_added' );
    }
}