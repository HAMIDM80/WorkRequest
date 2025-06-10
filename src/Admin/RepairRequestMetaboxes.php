<?php
// src/Admin/RepairRequestMetaboxes.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WorkRequest_Admin_RepairRequestMetaboxes {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_repair_request_metaboxes' ) );
        add_action( 'save_post_repair_request', array( $this, 'save_repair_request_metabox_data' ) );

        // --- START: Custom Columns for Repair Request ---
        add_filter( 'manage_repair_request_posts_columns', array( $this, 'add_custom_columns' ) );
        add_action( 'manage_repair_request_posts_custom_column', array( $this, 'render_custom_columns' ), 10, 2 );
        // --- END: Custom Columns for Repair Request ---
    }

    public function add_repair_request_metaboxes() {
        add_meta_box(
            'workrequest_repair_request_details',
            __( 'Repair Request Details', 'workrequest' ),
            array( $this, 'render_repair_request_details_metabox' ),
            'repair_request',
            'normal',
            'high'
        );
        // Add more metaboxes as needed for RepairRequest
    }

    public function render_repair_request_details_metabox( $post ) {
        // Add a nonce field so we can check it later for security
        wp_nonce_field( 'repair_request_details_nonce', 'repair_request_details_nonce_field' );

        $status = get_post_meta( $post->ID, '_workrequest_status', true );
        $customer_name = get_post_meta( $post->ID, '_workrequest_customer_name', true );
        $customer_email = get_post_meta( $post->ID, '_workrequest_customer_email', true );
        $issue_description = get_post_meta( $post->ID, '_workrequest_issue_description', true );
        $priority = get_post_meta( $post->ID, '_workrequest_priority', true );
        $assigned_technician_id = get_post_meta( $post->ID, '_workrequest_assigned_technician_id', true );

        // Get available technicians (e.g., users with 'repair_technician' role)
        $technicians = get_users( array( 'role' => 'repair_technician' ) );

        ?>
        <table class="form-table">
            <tbody>
                <tr>
                    <th><label for="workrequest_status"><?php _e( 'Status', 'workrequest' ); ?></label></th>
                    <td>
                        <select name="workrequest_status" id="workrequest_status">
                            <option value="pending" <?php selected( $status, 'pending' ); ?>><?php _e( 'Pending', 'workrequest' ); ?></option>
                            <option value="in_progress" <?php selected( $status, 'in_progress' ); ?>><?php _e( 'In Progress', 'workrequest' ); ?></option>
                            <option value="completed" <?php selected( $status, 'completed' ); ?>><?php _e( 'Completed', 'workrequest' ); ?></option>
                            <option value="cancelled" <?php selected( $status, 'cancelled' ); ?>><?php _e( 'Cancelled', 'workrequest' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="workrequest_customer_name"><?php _e( 'Customer Name', 'workrequest' ); ?></label></th>
                    <td><input type="text" id="workrequest_customer_name" name="workrequest_customer_name" value="<?php echo esc_attr( $customer_name ); ?>" class="large-text" /></td>
                </tr>
                <tr>
                    <th><label for="workrequest_customer_email"><?php _e( 'Customer Email', 'workrequest' ); ?></label></th>
                    <td><input type="email" id="workrequest_customer_email" name="workrequest_customer_email" value="<?php echo esc_attr( $customer_email ); ?>" class="large-text" /></td>
                </tr>
                <tr>
                    <th><label for="workrequest_issue_description"><?php _e( 'Issue Description', 'workrequest' ); ?></label></th>
                    <td><textarea id="workrequest_issue_description" name="workrequest_issue_description" rows="5" class="large-text"><?php echo esc_textarea( $issue_description ); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="workrequest_priority"><?php _e( 'Priority', 'workrequest' ); ?></label></th>
                    <td>
                        <select name="workrequest_priority" id="workrequest_priority">
                            <option value="low" <?php selected( $priority, 'low' ); ?>><?php _e( 'Low', 'workrequest' ); ?></option>
                            <option value="medium" <?php selected( $priority, 'medium' ); ?>><?php _e( 'Medium', 'workrequest' ); ?></option>
                            <option value="high" <?php selected( $priority, 'high' ); ?>><?php _e( 'High', 'workrequest' ); ?></option>
                            <option value="urgent" <?php selected( $priority, 'urgent' ); ?>><?php _e( 'Urgent', 'workrequest' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="workrequest_assigned_technician_id"><?php _e( 'Assigned Technician', 'workrequest' ); ?></label></th>
                    <td>
                        <select name="workrequest_assigned_technician_id" id="workrequest_assigned_technician_id">
                            <option value="0"><?php _e( 'Unassigned', 'workrequest' ); ?></option>
                            <?php foreach ( $technicians as $technician ) : ?>
                                <option value="<?php echo esc_attr( $technician->ID ); ?>" <?php selected( $assigned_technician_id, $technician->ID ); ?>>
                                    <?php echo esc_html( $technician->display_name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    public function save_repair_request_metabox_data( $post_id ) {
        // Check if our nonce is set.
        if ( ! isset( $_POST['repair_request_details_nonce_field'] ) ) {
            return $post_id;
        }

        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $_POST['repair_request_details_nonce_field'], 'repair_request_details_nonce' ) ) {
            return $post_id;
        }

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }

        // Check the user's permissions.
        if ( ! current_user_can( 'edit_repair_request', $post_id ) ) { // Use specific capability
            return $post_id;
        }

        // Sanitize and save the data
        if ( isset( $_POST['workrequest_status'] ) ) {
            update_post_meta( $post_id, '_workrequest_status', sanitize_text_field( $_POST['workrequest_status'] ) );
        }
        if ( isset( $_POST['workrequest_customer_name'] ) ) {
            update_post_meta( $post_id, '_workrequest_customer_name', sanitize_text_field( $_POST['workrequest_customer_name'] ) );
        }
        if ( isset( $_POST['workrequest_customer_email'] ) ) {
            update_post_meta( $post_id, '_workrequest_customer_email', sanitize_email( $_POST['workrequest_customer_email'] ) );
        }
        if ( isset( $_POST['workrequest_issue_description'] ) ) {
            update_post_meta( $post_id, '_workrequest_issue_description', sanitize_textarea_field( $_POST['workrequest_issue_description'] ) );
        }
        if ( isset( $_POST['workrequest_priority'] ) ) {
            update_post_meta( $post_id, '_workrequest_priority', sanitize_text_field( $_POST['workrequest_priority'] ) );
        }
        if ( isset( $_POST['workrequest_assigned_technician_id'] ) ) {
            update_post_meta( $post_id, '_workrequest_assigned_technician_id', intval( $_POST['workrequest_assigned_technician_id'] ) );
        }
    }


    /**
     * Adds custom columns to the Repair Request list table.
     *
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public function add_custom_columns( $columns ) {
        // Remove 'date' column if you want to replace it or move it
        unset( $columns['date'] );
        // Add your custom columns
        $new_columns = array(
            'customer_name' => __( 'Customer', 'workrequest' ),
            'status'        => __( 'Status', 'workrequest' ),
            'priority'      => __( 'Priority', 'workrequest' ),
            'technician'    => __( 'Technician', 'workrequest' ),
            'date'          => __( 'Date', 'workrequest' ), // Re-add date at the end if desired
        );
        return array_merge( $columns, $new_columns );
    }

    /**
     * Renders content for custom columns in the Repair Request list table.
     *
     * @param string $column_name The name of the column to display.
     * @param int    $post_id     The ID of the current post.
     */
    public function render_custom_columns( $column_name, $post_id ) {
        switch ( $column_name ) {
            case 'customer_name':
                echo esc_html( get_post_meta( $post_id, '_workrequest_customer_name', true ) );
                break;
            case 'status':
                $status = get_post_meta( $post_id, '_workrequest_status', true );
                echo esc_html( ucfirst( str_replace( '_', ' ', $status ) ) ); // Format status nicely
                break;
            case 'priority':
                $priority = get_post_meta( $post_id, '_workrequest_priority', true );
                echo esc_html( ucfirst( $priority ) ); // Display priority
                break;
            case 'technician':
                $technician_id = get_post_meta( $post_id, '_workrequest_assigned_technician_id', true );
                if ( $technician_id ) {
                    $user = get_user_by( 'ID', $technician_id );
                    echo $user ? esc_html( $user->display_name ) : __( 'N/A', 'workrequest' );
                } else {
                    echo __( 'Unassigned', 'workrequest' );
                }
                break;
            // 'date' column will be handled by WordPress default if added by merge,
            // or you can implement custom date formatting here.
        }
    }
}