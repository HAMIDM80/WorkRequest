<?php
// src/Admin/TaskMetaboxes.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WorkRequest_Admin_TaskMetaboxes {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_task_metaboxes' ) );
        add_action( 'save_post_task', array( $this, 'save_task_metabox_data' ) );

        // --- START: Custom Columns for Task ---
        add_filter( 'manage_task_posts_columns', array( $this, 'add_custom_columns' ) );
        add_action( 'manage_task_posts_custom_column', array( $this, 'render_custom_columns' ), 10, 2 );
        // --- END: Custom Columns for Task ---
    }

    public function add_task_metaboxes() {
        add_meta_box(
            'workrequest_task_details',
            __( 'Task Details', 'workrequest' ),
            array( $this, 'render_task_details_metabox' ),
            'task',
            'normal',
            'high'
        );
        // Add more metaboxes as needed for Task
    }

    public function render_task_details_metabox( $post ) {
        wp_nonce_field( 'task_details_nonce', 'task_details_nonce_field' );

        $task_status = get_post_meta( $post->ID, '_workrequest_task_status', true );
        $assigned_technician_id = get_post_meta( $post->ID, '_workrequest_assigned_technician_id', true );
        $due_date = get_post_meta( $post->ID, '_workrequest_due_date', true );
        $related_request_id = get_post_meta( $post->ID, '_workrequest_related_request_id', true );

        // Get available technicians (e.g., users with 'repair_technician' role)
        $technicians = get_users( array( 'role' => 'repair_technician' ) );

        // Fetch Repair Requests for dropdown
        $repair_requests = get_posts( array(
            'post_type'      => 'repair_request',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'fields'         => 'ids', // Get only IDs for performance
        ) );
        ?>
        <table class="form-table">
            <tbody>
                <tr>
                    <th><label for="workrequest_task_status"><?php _e( 'Task Status', 'workrequest' ); ?></label></th>
                    <td>
                        <select name="workrequest_task_status" id="workrequest_task_status">
                            <option value="pending" <?php selected( $task_status, 'pending' ); ?>><?php _e( 'Pending', 'workrequest' ); ?></option>
                            <option value="in_progress" <?php selected( $task_status, 'in_progress' ); ?>><?php _e( 'In Progress', 'workrequest' ); ?></option>
                            <option value="completed" <?php selected( $task_status, 'completed' ); ?>><?php _e( 'Completed', 'workrequest' ); ?></option>
                            <option value="on_hold" <?php selected( $task_status, 'on_hold' ); ?>><?php _e( 'On Hold', 'workrequest' ); ?></option>
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
                <tr>
                    <th><label for="workrequest_due_date"><?php _e( 'Due Date', 'workrequest' ); ?></label></th>
                    <td><input type="date" id="workrequest_due_date" name="workrequest_due_date" value="<?php echo esc_attr( $due_date ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="workrequest_related_request_id"><?php _e( 'Related Repair Request', 'workrequest' ); ?></label></th>
                    <td>
                        <select name="workrequest_related_request_id" id="workrequest_related_request_id">
                            <option value="0"><?php _e( 'None (Standalone)', 'workrequest' ); ?></option>
                            <?php
                            foreach ( $repair_requests as $request_id ) {
                                $request_title = get_the_title( $request_id );
                                echo '<option value="' . esc_attr( $request_id ) . '" ' . selected( $related_request_id, $request_id, false ) . '>' . esc_html( $request_title ) . ' (ID: ' . esc_html( $request_id ) . ')</option>';
                            }
                            ?>
                        </select>
                        <p class="description"><?php _e( 'Link this task to a specific Repair Request.', 'workrequest' ); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    public function save_task_metabox_data( $post_id ) {
        if ( ! isset( $_POST['task_details_nonce_field'] ) ) {
            return $post_id;
        }
        if ( ! wp_verify_nonce( $_POST['task_details_nonce_field'], 'task_details_nonce' ) ) {
            return $post_id;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }
        if ( ! current_user_can( 'edit_task', $post_id ) ) {
            return $post_id;
        }

        if ( isset( $_POST['workrequest_task_status'] ) ) {
            update_post_meta( $post_id, '_workrequest_task_status', sanitize_text_field( $_POST['workrequest_task_status'] ) );
        }
        if ( isset( $_POST['workrequest_assigned_technician_id'] ) ) {
            update_post_meta( $post_id, '_workrequest_assigned_technician_id', intval( $_POST['workrequest_assigned_technician_id'] ) );
        }
        if ( isset( $_POST['workrequest_due_date'] ) ) {
            update_post_meta( $post_id, '_workrequest_due_date', sanitize_text_field( $_POST['workrequest_due_date'] ) );
        }
         if ( isset( $_POST['workrequest_related_request_id'] ) ) {
            update_post_meta( $post_id, '_workrequest_related_request_id', intval( $_POST['workrequest_related_request_id'] ) );
        }
    }

    /**
     * Adds custom columns to the Task list table.
     *
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public function add_custom_columns( $columns ) {
        unset( $columns['date'] );
        $new_columns = array(
            'task_status'      => __( 'Status', 'workrequest' ),
            'assigned_to'      => __( 'Assigned To', 'workrequest' ),
            'due_date'         => __( 'Due Date', 'workrequest' ),
            'related_request'  => __( 'Related Request', 'workrequest' ),
            'date'             => __( 'Date', 'workrequest' ), // Re-add date at the end
        );
        return array_merge( $columns, $new_columns );
    }

    /**
     * Renders content for custom columns in the Task list table.
     *
     * @param string $column_name The name of the column to display.
     * @param int    $post_id     The ID of the current post.
     */
    public function render_custom_columns( $column_name, $post_id ) {
        switch ( $column_name ) {
            case 'task_status':
                $status = get_post_meta( $post_id, '_workrequest_task_status', true );
                echo esc_html( ucfirst( str_replace( '_', ' ', $status ) ) );
                break;
            case 'assigned_to':
                $technician_id = get_post_meta( $post_id, '_workrequest_assigned_technician_id', true );
                if ( $technician_id ) {
                    $user = get_user_by( 'ID', $technician_id );
                    echo $user ? esc_html( $user->display_name ) : __( 'N/A', 'workrequest' );
                } else {
                    echo __( 'Unassigned', 'workrequest' );
                }
                break;
            case 'due_date':
                $due_date = get_post_meta( $post_id, '_workrequest_due_date', true );
                echo $due_date ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $due_date ) ) ) : __( 'N/A', 'workrequest' );
                break;
            case 'related_request':
                $related_request_id = get_post_meta( $post_id, '_workrequest_related_request_id', true );
                if ( $related_request_id ) {
                    $request_title = get_the_title( $related_request_id );
                    $edit_link = get_edit_post_link( $related_request_id );
                    if ( $request_title && $edit_link ) {
                        echo '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $request_title ) . '</a>';
                    } else {
                        echo __( 'N/A', 'workrequest' );
                    }
                } else {
                    echo __( 'None', 'workrequest' );
                }
                break;
        }
    }
}