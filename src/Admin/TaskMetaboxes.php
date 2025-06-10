<?php
/**
 * Admin functions for Task CPT.
 * This class handles metaboxes and saving custom fields for 'task'.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WorkRequest_Admin_TaskMetaboxes {

    /**
     * Constructor.
     * Registers all necessary hooks for metaboxes and saving.
     */
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );
        add_action( 'save_post_task', array( $this, 'save_meta' ), 10, 2 );
    }

    /**
     * Add custom metaboxes for 'task' CPT.
     */
    public function add_metaboxes() {
        add_meta_box(
            'workrequest_task_details',
            __( 'Task Details', 'workrequest' ),
            array( $this, 'render_details_metabox' ),
            'task',
            'normal',
            'high'
        );
        add_meta_box(
            'workrequest_task_status',
            __( 'Task Status & Approvals', 'workrequest' ),
            array( $this, 'render_status_metabox' ),
            'task',
            'side',
            'high'
        );
    }

    /**
     * Callback for Task Details Metabox.
     * @param WP_Post $post The post object.
     */
    public function render_details_metabox( $post ) {
        wp_nonce_field( 'workrequest_save_task_details', 'workrequest_task_details_nonce' );

        $original_request_id = get_post_meta( $post->ID, '_workrequest_task_original_request_id', true );
        $linked_item_id      = get_post_meta( $post->ID, '_workrequest_task_linked_item_id', true );
        $assigned_to_id      = get_post_meta( $post->ID, '_workrequest_task_assigned_to', true );
        $task_cost           = get_post_meta( $post->ID, '_workrequest_task_cost', true );
        $task_due_date       = get_post_meta( $post->ID, '_workrequest_task_due_date', true );
        $task_priority       = get_post_meta( $post->ID, '_workrequest_task_priority', true );

        // Display original Repair Request link
        if ( $original_request_id && get_post_type( $original_request_id ) === 'repair_request' ) {
            $request_title = get_the_title( $original_request_id );
            $edit_link = get_edit_post_link( $original_request_id );
            echo '<p><strong>' . esc_html__( 'Linked Repair Request:', 'workrequest' ) . '</strong> <a href="' . esc_url( $edit_link ) . '">#' . absint( $original_request_id ) . ' - ' . esc_html( $request_title ) . '</a></p>';
        } else {
            echo '<p><strong>' . esc_html__( 'Linked Repair Request:', 'workrequest' ) . '</strong> ' . esc_html__( 'None or invalid.', 'workrequest' ) . '</p>';
        }

        // Display linked Repair Order Item
        if ( $linked_item_id && get_post_type( $linked_item_id ) === 'repair_order_item' ) {
            $item_title = get_the_title( $linked_item_id );
            $edit_link = get_edit_post_link( $linked_item_id );
            echo '<p><strong>' . esc_html__( 'Linked Repair Item:', 'workrequest' ) . '</strong> <a href="' . esc_url( $edit_link ) . '">#' . absint( $linked_item_id ) . ' - ' . esc_html( $item_title ) . '</a></p>';
        } else {
            echo '<p><strong>' . esc_html__( 'Linked Repair Item:', 'workrequest' ) . '</strong> ' . esc_html__( 'None or invalid.', 'workrequest' ) . '</p>';
        }

        // Assign Task To
        echo '<p>';
        echo '<label for="workrequest_task_assigned_to"><strong>' . esc_html__( 'Assigned To:', 'workrequest' ) . '</strong></label>';
        wp_dropdown_users( array(
            'name'             => 'workrequest_task_assigned_to',
            'id'               => 'workrequest_task_assigned_to',
            'selected'         => $assigned_to_id,
            'show_option_none' => esc_html__( '— Select User —', 'workrequest' ),
            'who'              => 'authors', // Or 'all' or specific roles
            'class'            => 'widefat',
        ) );
        echo '</p>';

        // Task Cost
        echo '<p>';
        echo '<label for="workrequest_task_cost">' . esc_html__( 'Task Cost:', 'workrequest' ) . '</label>';
        echo '<input type="number" step="0.01" min="0" id="workrequest_task_cost" name="workrequest_task_cost" value="' . esc_attr( $task_cost ) . '" class="regular-text" />';
        echo '</p>';

        // Due Date
        echo '<p>';
        echo '<label for="workrequest_task_due_date"><strong>' . esc_html__( 'Due Date:', 'workrequest' ) . '</strong></label>';
        echo '<input type="date" id="workrequest_task_due_date" name="workrequest_task_due_date" value="' . esc_attr( $task_due_date ) . '" class="regular-text" />';
        echo '</p>';

        // Priority
        $priorities = array(
            'low'    => __( 'Low', 'workrequest' ),
            'medium' => __( 'Medium', 'workrequest' ),
            'high'   => __( 'High', 'workrequest' ),
            'urgent' => __( 'Urgent', 'workrequest' ),
        );
        echo '<p>';
        echo '<label for="workrequest_task_priority">' . esc_html__( 'Priority:', 'workrequest' ) . '</label>';
        echo '<select name="workrequest_task_priority" id="workrequest_task_priority" class="postbox">';
        foreach ( $priorities as $slug => $label ) {
            echo '<option value="' . esc_attr( $slug ) . '" ' . selected( $task_priority, $slug, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
        echo '</p>';

        // Hidden fields to pass linked IDs if adding from another CPT page
        if (isset($_GET['workrequest_request_id'])) {
            echo '<input type="hidden" name="workrequest_task_original_request_id_from_url" value="' . absint($_GET['workrequest_request_id']) . '" />';
        }
        if (isset($_GET['workrequest_item_id'])) {
            echo '<input type="hidden" name="workrequest_task_linked_item_id_from_url" value="' . absint($_GET['workrequest_item_id']) . '" />';
        }
    }

    /**
     * Callback for Task Status & Approvals Metabox.
     * @param WP_Post $post The post object.
     */
    public function render_status_metabox( $post ) {
        wp_nonce_field( 'workrequest_save_task_status', 'workrequest_task_status_nonce' );

        $current_status = get_post_meta( $post->ID, '_workrequest_task_status', true );
        if ( empty( $current_status ) ) {
            $current_status = 'pending';
        }

        $statuses = array(
            'pending'   => __( 'Pending', 'workrequest' ),
            'assigned'  => __( 'Assigned', 'workrequest' ),
            'in_progress' => __( 'In Progress', 'workrequest' ),
            'completed' => __( 'Completed', 'workrequest' ),
            'on_hold'   => __( 'On Hold', 'workrequest' ),
            'cancelled' => __( 'Cancelled', 'workrequest' ),
            'needs_review' => __( 'Needs Review', 'workrequest' ),
        );

        echo '<p>';
        echo '<label for="workrequest_task_status">' . esc_html__( 'Current Status:', 'workrequest' ) . '</label>';
        echo '<select name="workrequest_task_status" id="workrequest_task_status" class="postbox">';
        foreach ( $statuses as $slug => $label ) {
            echo '<option value="' . esc_attr( $slug ) . '" ' . selected( $current_status, $slug, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
        echo '</p>';

        // Approval fields
        $owner_approved    = get_post_meta( $post->ID, '_workrequest_task_owner_approved', true );
        $storage_approved  = get_post_meta( $post->ID, '_workrequest_task_storage_approved', true );
        $operator_approved = get_post_meta( $post->ID, '_workrequest_task_operator_approved', true );
        $qc_approved       = get_post_meta( $post->ID, '_workrequest_task_qc_approved', true );

        echo '<h4>' . esc_html__( 'Approvals:', 'workrequest' ) . '</h4>';
        echo '<p>';
        echo '<label for="workrequest_task_owner_approved">';
        echo '<input type="checkbox" id="workrequest_task_owner_approved" name="workrequest_task_owner_approved" value="1" ' . checked( $owner_approved, 1, false ) . ' />';
        echo ' ' . esc_html__( 'Owner Approved', 'workrequest' ) . '</label>';
        echo '</p>';

        echo '<p>';
        echo '<label for="workrequest_task_storage_approved">';
        echo '<input type="checkbox" id="workrequest_task_storage_approved" name="workrequest_task_storage_approved" value="1" ' . checked( $storage_approved, 1, false ) . ' />';
        echo ' ' . esc_html__( 'Storage Approved', 'workrequest' ) . '</label>';
        echo '</p>';

        echo '<p>';
        echo '<label for="workrequest_task_operator_approved">';
        echo '<input type="checkbox" id="workrequest_task_operator_approved" name="workrequest_task_operator_approved" value="1" ' . checked( $operator_approved, 1, false ) . ' />';
        echo ' ' . esc_html__( 'Operator Approved', 'workrequest' ) . '</label>';
        echo '</p>';

        echo '<p>';
        echo '<label for="workrequest_task_qc_approved">';
        echo '<input type="checkbox" id="workrequest_task_qc_approved" name="workrequest_task_qc_approved" value="1" ' . checked( $qc_approved, 1, false ) . ' />';
        echo ' ' . esc_html__( 'QC Approved', 'workrequest' ) . '</label>';
        echo '</p>';
    }

    /**
     * Save custom fields for 'task' CPT.
     * @param int $post_id The post ID.
     * @param WP_Post $post The post object.
     * @return int|void
     */
    public function save_meta( $post_id, $post ) {
        // Verify nonces
        if ( ! isset( $_POST['workrequest_task_details_nonce'] ) || ! wp_verify_nonce( $_POST['workrequest_task_details_nonce'], 'workrequest_save_task_details' ) ) {
            return $post_id;
        }
        if ( ! isset( $_POST['workrequest_task_status_nonce'] ) || ! wp_verify_nonce( $_POST['workrequest_task_status_nonce'], 'workrequest_save_task_status' ) ) {
            return $post_id;
        }

        // Check autosave and revisions
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }
        if ( wp_is_post_revision( $post_id ) ) {
            return $post_id;
        }

        // Check user capabilities
        if ( ! current_user_can( 'edit_task', $post_id ) ) {
            return $post_id;
        }

        // Save Task Details
        if ( isset( $_POST['workrequest_task_assigned_to'] ) ) {
            $assigned_to = absint( $_POST['workrequest_task_assigned_to'] );
            if ( $assigned_to > 0 ) {
                update_post_meta( $post_id, '_workrequest_task_assigned_to', $assigned_to );
            } else {
                delete_post_meta( $post_id, '_workrequest_task_assigned_to' );
            }
        }

        if ( isset( $_POST['workrequest_task_cost'] ) ) {
            update_post_meta( $post_id, '_workrequest_task_cost', floatval( $_POST['workrequest_task_cost'] ) );
        }

        if ( isset( $_POST['workrequest_task_due_date'] ) ) {
            update_post_meta( $post_id, '_workrequest_task_due_date', sanitize_text_field( $_POST['workrequest_task_due_date'] ) );
        }

        if ( isset( $_POST['workrequest_task_priority'] ) ) {
            $new_priority = sanitize_text_field( $_POST['workrequest_task_priority'] );
            $allowed_priorities = array( 'low', 'medium', 'high', 'urgent' );
            if ( in_array( $new_priority, $allowed_priorities, true ) ) { // Use strict comparison
                update_post_meta( $post_id, '_workrequest_task_priority', $new_priority );
            }
        }

        // Save Task Status
        if ( isset( $_POST['workrequest_task_status'] ) ) {
            $new_status = sanitize_text_field( $_POST['workrequest_task_status'] );
            $allowed_statuses = array( 'pending', 'assigned', 'in_progress', 'completed', 'on_hold', 'cancelled', 'needs_review' );
            if ( in_array( $new_status, $allowed_statuses, true ) ) { // Use strict comparison
                update_post_meta( $post_id, '_workrequest_task_status', $new_status );
            }
        }

        // Save Approvals
        update_post_meta( $post_id, '_workrequest_task_owner_approved', isset( $_POST['workrequest_task_owner_approved'] ) ? 1 : 0 );
        update_post_meta( $post_id, '_workrequest_task_storage_approved', isset( $_POST['workrequest_task_storage_approved'] ) ? 1 : 0 );
        update_post_meta( $post_id, '_workrequest_task_operator_approved', isset( $_POST['workrequest_task_operator_approved'] ) ? 1 : 0 );
        update_post_meta( $post_id, '_workrequest_task_qc_approved', isset( $_POST['workrequest_task_qc_approved'] ) ? 1 : 0 );

        // Handle linking to a Repair Request/Repair Order Item if coming from a URL parameter
        // This is typically only set on initial creation from a Request or Item page.
        if ( isset( $_POST['workrequest_task_original_request_id_from_url'] ) && ! empty( $_POST['workrequest_task_original_request_id_from_url'] ) ) {
            $request_id = absint( $_POST['workrequest_task_original_request_id_from_url'] );
            // Only update if current meta is empty or the new request_id is valid
            if ( empty( get_post_meta( $post_id, '_workrequest_task_original_request_id', true ) ) || get_post_type( $request_id ) === 'repair_request' ) {
                update_post_meta( $post_id, '_workrequest_task_original_request_id', $request_id );
            }
        }

        if ( isset( $_POST['workrequest_task_linked_item_id_from_url'] ) && ! empty( $_POST['workrequest_task_linked_item_id_from_url'] ) ) {
            $item_id = absint( $_POST['workrequest_task_linked_item_id_from_url'] );
            // Only update if current meta is empty or the new item_id is valid
            if ( empty( get_post_meta( $post_id, '_workrequest_task_linked_item_id', true ) ) || get_post_type( $item_id ) === 'repair_order_item' ) {
                update_post_meta( $post_id, '_workrequest_task_linked_item_id', $item_id );
            }
        }
    }
}