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

        add_meta_box(
            'workrequest_task_approvals',
            __( 'Task Approvals', 'workrequest' ),
            array( $this, 'render_task_approvals_metabox' ),
            'task',
            'side', // 'side' for smaller metaboxes on the right
            'high'
        );
    }

    public function render_task_details_metabox( $post ) {
        wp_nonce_field( 'task_details_nonce', 'task_details_nonce_field' );

        $task_status = get_post_meta( $post->ID, '_workrequest_task_status', true );
        $assigned_technician_id = get_post_meta( $post->ID, '_workrequest_assigned_technician_id', true );
        $due_date = get_post_meta( $post->ID, '_workrequest_due_date', true );
        $related_request_id = get_post_meta( $post->ID, '_workrequest_related_request_id', true );
        $task_cost = get_post_meta( $post->ID, '_workrequest_task_cost', true ); // New field

        // Get available technicians (e.g., users with 'repair_technician' role)
        $technicians = get_users( array( 'role' => 'repair_technician' ) );

        // Fetch Repair Requests for dropdown
        $repair_requests = get_posts( array(
            'post_type'      => 'repair_request',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );
        ?>
        <table class="form-table">
            <tbody>
                <tr>
                    <th><label for="workrequest_task_status"><?php _e( 'Task Status', 'workrequest' ); ?></label></th>
                    <td>
                        <select name="workrequest_task_status" id="workrequest_task_status">
                            <option value="pending" <?php selected( $task_status, 'pending' ); ?>><?php _e( 'Pending', 'workrequest' ); ?></option>
                            <option value="assigned" <?php selected( $task_status, 'assigned' ); ?>><?php _e( 'Assigned', 'workrequest' ); ?></option>
                            <option value="in_progress" <?php selected( $task_status, 'in_progress' ); ?>><?php _e( 'In Progress', 'workrequest' ); ?></option>
                            <option value="completed" <?php selected( $task_status, 'completed' ); ?>><?php _e( 'Completed', 'workrequest' ); ?></option>
                            <option value="on_hold" <?php selected( $task_status, 'on_hold' ); ?>><?php _e( 'On Hold', 'workrequest' ); ?></option>
                            <option value="cancelled" <?php selected( $task_status, 'cancelled' ); ?>><?php _e( 'Cancelled', 'workrequest' ); ?></option>
                            <option value="needs_review" <?php selected( $task_status, 'needs_review' ); ?>><?php _e( 'Needs Review', 'workrequest' ); ?></option>
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
                    <th><label for="workrequest_task_cost"><?php _e( 'Task Cost', 'workrequest' ); ?></label></th>
                    <td><input type="number" step="0.01" min="0" id="workrequest_task_cost" name="workrequest_task_cost" value="<?php echo esc_attr( $task_cost ); ?>" class="regular-text" /> <?php echo get_woocommerce_currency_symbol(); ?></td>
                </tr>
                <tr>
                    <th><label for="workrequest_related_request_id"><?php _e( 'Related Repair Request', 'workrequest' ); ?></label></th>
                    <td>
                        <select name="workrequest_related_request_id" id="workrequest_related_request_id">
                            <option value="0"><?php _e( 'None (Standalone)', 'workrequest' ); ?></option>
                            <?php
                            foreach ( $repair_requests as $request ) {
                                $request_title = get_the_title( $request->ID );
                                echo '<option value="' . esc_attr( $request->ID ) . '" ' . selected( $related_request_id, $request->ID, false ) . '>' . esc_html( $request_title ) . ' (ID: ' . esc_html( $request->ID ) . ')</option>';
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

    public function render_task_approvals_metabox( $post ) {
        wp_nonce_field( 'task_approvals_nonce', 'task_approvals_nonce_field' );

        $approval_technician = get_post_meta( $post->ID, '_workrequest_approval_technician', true );
        $approval_warehouse = get_post_meta( $post->ID, '_workrequest_approval_warehouse', true );
        $approval_owner = get_post_meta( $post->ID, '_workrequest_approval_owner', true );
        $approval_quality = get_post_meta( $post->ID, '_workrequest_approval_quality', true );
        ?>
        <p><strong><?php _e( 'Check relevant approvals:', 'workrequest' ); ?></strong></p>
        <p>
            <label>
                <input type="checkbox" name="workrequest_approval_technician" value="yes" <?php checked( $approval_technician, 'yes' ); ?> />
                <?php _e( 'Technician Approved', 'workrequest' ); ?>
            </label><br/>
            <label>
                <input type="checkbox" name="workrequest_approval_warehouse" value="yes" <?php checked( $approval_warehouse, 'yes' ); ?> />
                <?php _e( 'Warehouse Approved', 'workrequest' ); ?>
            </label><br/>
            <label>
                <input type="checkbox" name="workrequest_approval_owner" value="yes" <?php checked( $approval_owner, 'yes' ); ?> />
                <?php _e( 'Owner Approved', 'workrequest' ); ?>
            </label><br/>
            <label>
                <input type="checkbox" name="workrequest_approval_quality" value="yes" <?php checked( $approval_quality, 'yes' ); ?> />
                <?php _e( 'Quality Approved', 'workrequest' ); ?>
            </label>
        </p>
        <?php
    }

    public function save_task_metabox_data( $post_id ) {
        // Nonce check for details metabox
        if ( ! isset( $_POST['task_details_nonce_field'] ) || ! wp_verify_nonce( $_POST['task_details_nonce_field'], 'task_details_nonce' ) ) {
            // Also check for approvals nonce if it's a separate save action or combined
            if ( ! isset( $_POST['task_approvals_nonce_field'] ) || ! wp_verify_nonce( $_POST['task_approvals_nonce_field'], 'task_approvals_nonce' ) ) {
                return $post_id;
            }
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }
        if ( ! current_user_can( 'edit_task', $post_id ) ) {
            return $post_id;
        }

        // Save details data
        if ( isset( $_POST['workrequest_task_status'] ) ) {
            update_post_meta( $post_id, '_workrequest_task_status', sanitize_text_field( $_POST['workrequest_task_status'] ) );
        }
        if ( isset( $_POST['workrequest_assigned_technician_id'] ) ) {
            update_post_meta( $post_id, '_workrequest_assigned_technician_id', intval( $_POST['workrequest_assigned_technician_id'] ) );
        }
        if ( isset( $_POST['workrequest_due_date'] ) ) {
            update_post_meta( $post_id, '_workrequest_due_date', sanitize_text_field( $_POST['workrequest_due_date'] ) );
        }
        if ( isset( $_POST['workrequest_task_cost'] ) ) { // Save Task Cost
            update_post_meta( $post_id, '_workrequest_task_cost', floatval( $_POST['workrequest_task_cost'] ) );
        }
        if ( isset( $_POST['workrequest_related_request_id'] ) ) {
            update_post_meta( $post_id, '_workrequest_related_request_id', intval( $_POST['workrequest_related_request_id'] ) );
        }

        // Save approval data
        update_post_meta( $post_id, '_workrequest_approval_technician', isset( $_POST['workrequest_approval_technician'] ) ? 'yes' : 'no' );
        update_post_meta( $post_id, '_workrequest_approval_warehouse', isset( $_POST['workrequest_approval_warehouse'] ) ? 'yes' : 'no' );
        update_post_meta( $post_id, '_workrequest_approval_owner', isset( $_POST['workrequest_approval_owner'] ) ? 'yes' : 'no' );
        update_post_meta( $post_id, '_workrequest_approval_quality', isset( $_POST['workrequest_approval_quality'] ) ? 'yes' : 'no' );
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
            'task_status'       => __( 'Status', 'workrequest' ),
            'assigned_to'       => __( 'Assigned To', 'workrequest' ),
            'task_cost'         => __( 'Cost', 'workrequest' ), // New column
            'due_date'          => __( 'Due Date', 'workrequest' ),
            'related_request'   => __( 'Related Request', 'workrequest' ),
            'approvals'         => __( 'Approvals', 'workrequest' ), // New column for approvals
            'date'              => __( 'Date', 'workrequest' ), // Re-add date at the end
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
                echo esc_html( WorkRequest_CPT_Task::get_task_status_label( $status ) );
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
            case 'task_cost':
                $cost = get_post_meta( $post_id, '_workrequest_task_cost', true );
                echo $cost ? wc_price( $cost ) : wc_price( 0 );
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
                        echo '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $request_title ) . ' (#' . esc_html( $related_request_id ) . ')</a>';
                    } else {
                        echo __( 'N/A', 'workrequest' );
                    }
                } else {
                    echo __( 'None', 'workrequest' );
                }
                break;
            case 'approvals':
                $approval_technician = get_post_meta( $post_id, '_workrequest_approval_technician', true );
                $approval_warehouse = get_post_meta( $post_id, '_workrequest_approval_warehouse', true );
                $approval_owner = get_post_meta( $post_id, '_workrequest_approval_owner', true );
                $approval_quality = get_post_meta( $post_id, '_workrequest_approval_quality', true );

                $approved_icon = '<span class="dashicons dashicons-yes" style="color: green;"></span>';
                $pending_icon = '<span class="dashicons dashicons-no-alt" style="color: grey;"></span>';

                echo 'T: ' . ( 'yes' === $approval_technician ? $approved_icon : $pending_icon ) . '<br/>';
                echo 'W: ' . ( 'yes' === $approval_warehouse ? $approved_icon : $pending_icon ) . '<br/>';
                echo 'O: ' . ( 'yes' === $approval_owner ? $approved_icon : $pending_icon ) . '<br/>';
                echo 'Q: ' . ( 'yes' === $approval_quality ? $approved_icon : $pending_icon );
                break;
        }
    }
}