<?php
/**
 * Handles custom roles and capabilities for the Work Request plugin.
 * Defines roles on plugin activation and removes them on deactivation.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WorkRequest_Roles_Capabilities {

    public function __construct() {
        // Register activation and deactivation hooks
        register_activation_hook( WORKREQUEST_PLUGIN_DIR . 'workrequest.php', array( $this, 'activate_roles_capabilities' ) );
        register_deactivation_hook( WORKREQUEST_PLUGIN_DIR . 'workrequest.php', array( $this, 'deactivate_roles_capabilities' ) );
    }

    /**
     * Define custom capabilities used by the plugin.
     * This ensures consistency and centralizes capability definitions.
     *
     * @return array
     */
    private function get_custom_capabilities() {
        return array(
            // General Work Request Capabilities
            'read_work_request'          => __( 'Read Work Request', 'workrequest' ),
            'edit_work_request'          => __( 'Edit Work Request', 'workrequest' ),
            'publish_work_request'       => __( 'Publish Work Request', 'workrequest' ), // For internal use/admin
            'delete_work_request'        => __( 'Delete Work Request', 'workrequest' ),
            'edit_others_work_requests'  => __( 'Edit Others Work Requests', 'workrequest' ),
            'delete_others_work_requests' => __( 'Delete Others Work Requests', 'workrequest' ),
            'read_private_work_requests' => __( 'Read Private Work Requests', 'workrequest' ),

            // Task Capabilities
            'read_task'                  => __( 'Read Task', 'workrequest' ),
            'edit_task'                  => __( 'Edit Task', 'workrequest' ),
            'delete_task'                => __( 'Delete Task', 'workrequest' ),
            'edit_others_tasks'          => __( 'Edit Others Tasks', 'workrequest' ),
            'delete_others_tasks'        => __( 'Delete Others Tasks', 'workrequest' ),

            // Role-specific Capabilities
            'manage_repair_requests'     => __( 'Manage Repair Requests', 'workrequest' ), // For Admin, Finance, QC
            'view_all_tasks'             => __( 'View All Tasks', 'workrequest' ), // For Admin, Finance, QC
            'view_own_tasks'             => __( 'View Own Tasks', 'workrequest' ), // For Executor
            'edit_own_tasks'             => __( 'Edit Own Tasks', 'workrequest' ), // For Executor (status, cost, notes)
            'manage_finance_aspects'     => __( 'Manage Finance Aspects', 'workrequest' ), // For Finance Manager
            'manage_inventory'           => __( 'Manage Inventory', 'workrequest' ), // For Storekeeper
            'approve_task_quality'       => __( 'Approve Task Quality', 'workrequest' ), // For QC
            'manage_marketing_leads'     => __( 'Manage Marketing Leads', 'workrequest' ), // For Marketer (view own clients requests)
            'submit_repair_request'      => __( 'Submit Repair Request', 'workrequest' ), // For Customer/Frontend user
        );
    }

    /**
     * Define custom roles and assign capabilities to them.
     */
    private function get_custom_roles() {
        return array(
            'executor' => array(
                'name'         => __( 'Executor', 'workrequest' ),
                'capabilities' => array(
                    'read'                   => true, // Basic read access
                    'view_own_tasks'         => true,
                    'edit_own_tasks'         => true,
                    'submit_repair_request'  => true, // Can also submit requests if needed
                    'upload_files'           => true, // To upload images for tasks
                ),
            ),
            'finance_manager' => array(
                'name'         => __( 'Finance Manager', 'workrequest' ),
                'capabilities' => array(
                    'read'                       => true,
                    'manage_repair_requests'     => true,
                    'view_all_tasks'             => true,
                    'manage_finance_aspects'     => true,
                    'edit_work_request'          => true, // To update financial details of requests
                    'edit_repair_order_item'     => true, // To manage order items
                    'delete_repair_order_item'   => true,
                    'read_private_repair_order_items' => true,
                    'submit_repair_request'      => true, // Can also submit requests
                ),
            ),
            'storekeeper' => array(
                'name'         => __( 'Storekeeper', 'workrequest' ),
                'capabilities' => array(
                    'read'                   => true,
                    'manage_inventory'       => true,
                    'view_all_tasks'         => true, // To see what items are needed for tasks
                    'read_work_request'      => true, // To see items needed for requests
                    'submit_repair_request'  => true, // Can also submit requests
                ),
            ),
            'quality_control' => array(
                'name'         => __( 'Quality Control', 'workrequest' ),
                'capabilities' => array(
                    'read'                   => true,
                    'view_all_tasks'         => true,
                    'approve_task_quality'   => true,
                    'read_work_request'      => true, // To see related requests
                    'submit_repair_request'  => true, // Can also submit requests
                ),
            ),
            'marketer' => array(
                'name'         => __( 'Marketer', 'workrequest' ),
                'capabilities' => array(
                    'read'                   => true,
                    'manage_marketing_leads' => true,
                    'submit_repair_request'  => true, // Their primary way to create leads
                    'read_work_request'      => true, // To view requests they submitted
                ),
            ),
            // Default WordPress roles adjustments if needed, e.g., 'customer' might have 'submit_repair_request'
        );
    }

    /**
     * Adds custom roles and capabilities on plugin activation.
     */
    public function activate_roles_capabilities() {
        $this->add_custom_roles_and_capabilities();
    }

    /**
     * Removes custom roles and capabilities on plugin deactivation.
     */
    public function deactivate_roles_capabilities() {
        $this->remove_custom_roles_and_capabilities();
    }

    /**
     * Helper to add all custom roles and their capabilities.
     */
    private function add_custom_roles_and_capabilities() {
        // Add custom capabilities to Administrator role
        $admin_role = get_role( 'administrator' );
        if ( $admin_role ) {
            foreach ( $this->get_custom_capabilities() as $cap_slug => $cap_name ) {
                if ( ! $admin_role->has_cap( $cap_slug ) ) {
                    $admin_role->add_cap( $cap_slug );
                }
            }
        }

        // Add custom roles
        foreach ( $this->get_custom_roles() as $role_slug => $role_data ) {
            if ( null === get_role( $role_slug ) ) {
                add_role( $role_slug, $role_data['name'], $role_data['capabilities'] );
            } else {
                // If role exists, ensure capabilities are updated
                $existing_role = get_role( $role_slug );
                foreach ( $role_data['capabilities'] as $cap => $grant ) {
                    if ( $grant ) {
                        $existing_role->add_cap( $cap );
                    } else {
                        $existing_role->remove_cap( $cap );
                    }
                }
            }
        }

        // Ensure 'customer' role can submit repair requests (if WooCommerce is active and 'customer' role exists)
        $customer_role = get_role( 'customer' );
        if ( $customer_role && ! $customer_role->has_cap( 'submit_repair_request' ) ) {
            $customer_role->add_cap( 'submit_repair_request' );
        }
    }

    /**
     * Helper to remove all custom roles and their capabilities.
     */
    private function remove_custom_roles_and_capabilities() {
        // Remove custom capabilities from Administrator role
        $admin_role = get_role( 'administrator' );
        if ( $admin_role ) {
            foreach ( $this->get_custom_capabilities() as $cap_slug => $cap_name ) {
                $admin_role->remove_cap( $cap_slug );
            }
        }

        // Remove custom roles
        foreach ( $this->get_custom_roles() as $role_slug => $role_data ) {
            remove_role( $role_slug );
        }

        // Remove 'submit_repair_request' cap from 'customer' role
        $customer_role = get_role( 'customer' );
        if ( $customer_role ) {
            $customer_role->remove_cap( 'submit_repair_request' );
        }
    }
}

// Instantiate the class to register hooks
new WorkRequest_Roles_Capabilities();