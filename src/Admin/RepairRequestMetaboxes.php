<?php
/**
 * Admin functions for Repair Request CPT.
 * This class handles metaboxes and saving custom fields for 'repair_request'.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WorkRequest_Admin_RepairRequestMetaboxes {

    /**
     * Constructor.
     * Registers all necessary hooks for metaboxes and saving.
     */
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );
        add_action( 'save_post_repair_request', array( $this, 'save_meta' ), 10, 2 );
    }

    /**
     * Add custom metaboxes for 'repair_request' CPT.
     */
    public function add_metaboxes() {
        add_meta_box(
            'workrequest_repair_request_details',
            __( 'Request Details', 'workrequest' ),
            array( $this, 'render_details_metabox' ),
            'repair_request',
            'normal',
            'high'
        );
        add_meta_box(
            'workrequest_repair_request_status',
            __( 'Request Status', 'workrequest' ),
            array( $this, 'render_status_metabox' ),
            'repair_request',
            'side',
            'high'
        );
        add_meta_box(
            'workrequest_repair_request_tasks_overview',
            __( 'Linked Tasks Overview', 'workrequest' ),
            array( $this, 'render_tasks_overview_metabox' ),
            'repair_request',
            'normal',
            'high'
        );
        add_meta_box(
            'workrequest_repair_request_linked_items',
            __( 'Linked Repair Items', 'workrequest' ),
            array( $this, 'render_linked_items_metabox' ),
            'repair_request',
            'normal',
            'high'
        );
    }

    /**
     * Callback for Request Details Metabox.
     * @param WP_Post $post The post object.
     */
    public function render_details_metabox( $post ) {
        wp_nonce_field( 'workrequest_save_repair_request_details', 'workrequest_repair_request_details_nonce' );

        $customer_user_id = get_post_meta( $post->ID, '_workrequest_customer_user_id', true );
        $customer_name    = get_post_meta( $post->ID, '_workrequest_customer_name', true );
        $customer_email   = get_post_meta( $post->ID, '_workrequest_customer_email', true );
        $customer_phone   = get_post_meta( $post->ID, '_workrequest_customer_phone', true );
        $guest_email      = get_post_meta( $post->ID, '_workrequest_guest_email', true );

        if ( $customer_user_id ) {
            $user_data = get_userdata( $customer_user_id );
            if ( $user_data ) {
                $display_name = $user_data->display_name;
                $user_email   = $user_data->user_email;
                echo '<p><strong>' . esc_html__( 'Customer:', 'workrequest' ) . '</strong> ' . esc_html( $display_name ) . ' (ID: ' . absint( $customer_user_id ) . ')</p>';
                echo '<p><strong>' . esc_html__( 'Email:', 'workrequest' ) . '</strong> <a href="mailto:' . esc_attr( $user_email ) . '">' . esc_html( $user_email ) . '</a></p>';
            } else {
                echo '<p><strong>' . esc_html__( 'Customer:', 'workrequest' ) . '</strong> ' . esc_html__( 'User not found or deleted.', 'workrequest' ) . '</p>';
                if ( $guest_email ) {
                    echo '<p><strong>' . esc_html__( 'Guest Email:', 'workrequest' ) . '</strong> <a href="mailto:' . esc_attr( $guest_email ) . '">' . esc_html( $guest_email ) . '</a></p>';
                }
            }
        } elseif ( $guest_email ) {
            echo '<p><strong>' . esc_html__( 'Customer Name (Guest):', 'workrequest' ) . '</strong> ' . esc_html( $customer_name ) . '</p>';
            echo '<p><strong>' . esc_html__( 'Customer Email (Guest):', 'workrequest' ) . '</strong> <a href="mailto:' . esc_attr( $guest_email ) . '">' . esc_html( $guest_email ) . '</a></p>';
        } else {
            echo '<p>' . esc_html__( 'No customer information available.', 'workrequest' ) . '</p>';
        }

        echo '<p>';
        echo '<label for="workrequest_customer_phone"><strong>' . esc_html__( 'Customer Phone:', 'workrequest' ) . '</strong></label>';
        echo '<input type="text" id="workrequest_customer_phone" name="workrequest_customer_phone" value="' . esc_attr( $customer_phone ) . '" class="large-text" />';
        echo '</p>';

        $wc_order_id = get_post_meta( $post->ID, '_workrequest_woocommerce_order_id', true );
        if ( $wc_order_id && function_exists('wc_get_order') ) {
            $order = wc_get_order( $wc_order_id );
            if ( $order ) {
                echo '<p><strong>' . esc_html__( 'Linked WooCommerce Order:', 'workrequest' ) . '</strong> <a href="' . esc_url( $order->get_edit_order_url() ) . '">#' . absint( $wc_order_id ) . '</a></p>';
            }
        }
    }

    /**
     * Callback for Request Status Metabox.
     * @param WP_Post $post The post object.
     */
    public function render_status_metabox( $post ) {
        wp_nonce_field( 'workrequest_save_repair_request_status', 'workrequest_repair_request_status_nonce' );

        $current_status = get_post_meta( $post->ID, '_workrequest_status', true );
        if ( empty( $current_status ) ) {
            $current_status = 'pending';
        }

        $statuses = array(
            'pending'           => __( 'Pending Review', 'workrequest' ),
            'in_progress'       => __( 'In Progress', 'workrequest' ),
            'awaiting_customer' => __( 'Awaiting Customer Response', 'workrequest' ),
            'completed'         => __( 'Completed', 'workrequest' ),
            'cancelled'         => __( 'Cancelled', 'workrequest' ),
            'on_hold'           => __( 'On Hold', 'workrequest' ),
        );

        echo '<p>';
        echo '<label for="workrequest_status">' . esc_html__( 'Current Status:', 'workrequest' ) . '</label>';
        echo '<select name="workrequest_status" id="workrequest_status" class="postbox">';
        foreach ( $statuses as $slug => $label ) {
            echo '<option value="' . esc_attr( $slug ) . '" ' . selected( $current_status, $slug, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
        echo '</p>';
    }

    /**
     * Callback for Linked Tasks Overview Metabox.
     * @param WP_Post $post The post object.
     */
    public function render_tasks_overview_metabox( $post ) {
        $args = array(
            'post_type'      => 'task',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'ASC',
            'meta_query'     => array(
                array(
                    'key'     => '_workrequest_task_original_request_id',
                    'value'   => $post->ID,
                    'compare' => '=',
                ),
            ),
        );
        $tasks = get_posts( $args );

        if ( $tasks ) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>' . esc_html__( 'Task Title', 'workrequest' ) . '</th><th>' . esc_html__( 'Status', 'workrequest' ) . '</th><th>' . esc_html__( 'Cost', 'workrequest' ) . '</th><th>' . esc_html__( 'Assigned To', 'workrequest' ) . '</th><th>' . esc_html__( 'Actions', 'workrequest' ) . '</th></tr></thead>';
            echo '<tbody>';
            foreach ( $tasks as $task ) {
                $task_status = get_post_meta( $task->ID, '_workrequest_task_status', true );
                $task_cost = get_post_meta( $task->ID, '_workrequest_task_cost', true );
                $assigned_to_id = get_post_meta( $task->ID, '_workrequest_task_assigned_to', true );
                $assigned_to_user = $assigned_to_id ? get_userdata( $assigned_to_id ) : null;
                $assigned_to_name = $assigned_to_user ? $assigned_to_user->display_name : esc_html__( 'Unassigned', 'workrequest' );

                // Use the static helper from WorkRequest_CPT_Task if available
                $status_label = class_exists('WorkRequest_CPT_Task') ? WorkRequest_CPT_Task::get_task_status_label($task_status) : ucfirst(str_replace('_', ' ', $task_status));
                $cost_display = function_exists('wc_price') ? wc_price(floatval($task_cost)) : '$' . number_format(floatval($task_cost), 2);

                echo '<tr>';
                echo '<td><a href="' . esc_url( get_edit_post_link( $task->ID ) ) . '">' . esc_html( $task->post_title ) . '</a></td>';
                echo '<td>' . esc_html( $status_label ) . '</td>';
                echo '<td>' . esc_html( $cost_display ) . '</td>';
                echo '<td>' . esc_html( $assigned_to_name ) . '</td>';
                echo '<td><a href="' . esc_url( get_edit_post_link( $task->ID ) ) . '" class="button button-small">' . esc_html__( 'Edit Task', 'workrequest' ) . '</a></td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<p>' . esc_html__( 'No tasks linked to this repair request yet.', 'workrequest' ) . '</p>';
        }
        echo '<p><a href="' . esc_url( admin_url( 'post-new.php?post_type=task&workrequest_request_id=' . $post->ID ) ) . '" class="button button-primary">' . esc_html__( 'Add New Task', 'workrequest' ) . '</a></p>';
    }

    /**
     * Callback for Linked Repair Order Items Metabox.
     * @param WP_Post $post The post object.
     */
    public function render_linked_items_metabox( $post ) {
        $args = array(
            'post_type'      => 'repair_order_item',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'ASC',
            'meta_query'     => array(
                array(
                    'key'     => '_workrequest_repair_request_id',
                    'value'   => $post->ID,
                    'compare' => '=',
                ),
            ),
        );
        $linked_items = get_posts( $args );

        if ( $linked_items ) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>' . esc_html__( 'Item Title', 'workrequest' ) . '</th><th>' . esc_html__( 'Cost', 'workrequest' ) . '</th><th>' . esc_html__( 'WooCommerce Order', 'workrequest' ) . '</th><th>' . esc_html__( 'Actions', 'workrequest' ) . '</th></tr></thead>';
            echo '<tbody>';
            foreach ( $linked_items as $item ) {
                $item_cost = get_post_meta( $item->ID, '_workrequest_repair_order_item_cost', true );
                $wc_order_id = get_post_meta( $item->ID, '_workrequest_woocommerce_order_id', true );

                $cost_display = function_exists('wc_price') ? wc_price(floatval($item_cost)) : '$' . number_format(floatval($item_cost), 2);
                $order_link_html = '—';
                if ( $wc_order_id && function_exists('wc_get_order') ) {
                    $order = wc_get_order( $wc_order_id );
                    if ( $order ) {
                        $order_link_html = '<a href="' . esc_url( $order->get_edit_order_url() ) . '">#' . absint( $wc_order_id ) . '</a>';
                    }
                }

                echo '<tr>';
                echo '<td><a href="' . esc_url( get_edit_post_link( $item->ID ) ) . '">' . esc_html( $item->post_title ) . '</a></td>';
                echo '<td>' . esc_html( $cost_display ) . '</td>';
                echo '<td>' . $order_link_html . '</td>';
                echo '<td><a href="' . esc_url( get_edit_post_link( $item->ID ) ) . '" class="button button-small">' . esc_html__( 'Edit Item', 'workrequest' ) . '</a></td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<p>' . esc_html__( 'No repair items linked to this request yet.', 'workrequest' ) . '</p>';
        }
        echo '<p><a href="' . esc_url( admin_url( 'post-new.php?post_type=repair_order_item&workrequest_request_id=' . $post->ID ) ) . '" class="button button-primary">' . esc_html__( 'Add New Repair Item', 'workrequest' ) . '</a></p>';
    }

    /**
     * Save custom fields for 'repair_request' CPT.
     * @param int $post_id The post ID.
     * @param WP_Post $post The post object.
     * @return int|void
     */
    public function save_meta( $post_id, $post ) {
        // Verify nonces
        if ( ! isset( $_POST['workrequest_repair_request_details_nonce'] ) || ! wp_verify_nonce( $_POST['workrequest_repair_request_details_nonce'], 'workrequest_save_repair_request_details' ) ) {
            return $post_id;
        }
        if ( ! isset( $_POST['workrequest_repair_request_status_nonce'] ) || ! wp_verify_nonce( $_POST['workrequest_repair_request_status_nonce'], 'workrequest_save_repair_request_status' ) ) {
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
        if ( ! current_user_can( 'edit_repair_request', $post_id ) ) {
            return $post_id;
        }

        // Save Request Details
        if ( isset( $_POST['workrequest_customer_phone'] ) ) {
            update_post_meta( $post_id, '_workrequest_customer_phone', sanitize_text_field( $_POST['workrequest_customer_phone'] ) );
        }

        // Save Request Status
        if ( isset( $_POST['workrequest_status'] ) ) {
            $new_status = sanitize_text_field( $_POST['workrequest_status'] );
            $allowed_statuses = array( 'pending', 'in_progress', 'awaiting_customer', 'completed', 'cancelled', 'on_hold' );
            if ( in_array( $new_status, $allowed_statuses, true ) ) { // Use strict comparison
                update_post_meta( $post_id, '_workrequest_status', $new_status );
            }
        }
    }

    /**
     * Helper function to get the total cost of all tasks linked to a repair request.
     * This is a static method and might be moved to a WorkRequest_Utils_TaskCalculator class later.
     *
     * @param int $request_id The ID of the repair request.
     * @return float The total cost of tasks.
     */
    public static function get_total_repair_request_task_cost( $request_id ) {
        $total_cost = 0;
        $args = array(
            'post_type'      => 'task',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_workrequest_task_original_request_id',
                    'value'   => $request_id,
                    'compare' => '=',
                ),
                array(
                    'key'     => '_workrequest_task_cost',
                    'compare' => 'EXISTS',
                ),
            ),
            'fields'         => 'ids',
        );
        $task_ids = get_posts( $args );

        if ( $task_ids ) {
            foreach ( $task_ids as $task_id ) {
                $cost = get_post_meta( $task_id, '_workrequest_task_cost', true );
                $total_cost += floatval( $cost );
            }
        }
        return $total_cost;
    }
}