<?php
/**
 * Admin functions for Repair Order Item CPT.
 * This class handles metaboxes and saving custom fields for 'repair_order_item'.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WorkRequest_Admin_RepairOrderItemMetaboxes {

    /**
     * Constructor.
     * Registers all necessary hooks for metaboxes and saving.
     */
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );
        add_action( 'save_post_repair_order_item', array( $this, 'save_meta' ), 10, 2 );
    }

    /**
     * Add custom metaboxes for 'repair_order_item' CPT.
     */
    public function add_metaboxes() {
        add_meta_box(
            'workrequest_repair_order_item_details',
            __( 'Item Details', 'workrequest' ),
            array( $this, 'render_details_metabox' ),
            'repair_order_item',
            'normal',
            'high'
        );
        add_meta_box(
            'workrequest_repair_order_item_linked_tasks',
            __( 'Linked Tasks', 'workrequest' ),
            array( $this, 'render_linked_tasks_metabox' ),
            'repair_order_item',
            'normal',
            'high'
        );
    }

    /**
     * Callback for Item Details Metabox.
     * @param WP_Post $post The post object.
     */
    public function render_details_metabox( $post ) {
        wp_nonce_field( 'workrequest_save_repair_order_item_details', 'workrequest_repair_order_item_details_nonce' );

        $repair_request_id = get_post_meta( $post->ID, '_workrequest_repair_request_id', true );
        $item_cost         = get_post_meta( $post->ID, '_workrequest_repair_order_item_cost', true );
        $wc_product_id     = get_post_meta( $post->ID, '_workrequest_woocommerce_product_id', true );
        $wc_order_id       = get_post_meta( $post->ID, '_workrequest_woocommerce_order_id', true );
        $item_notes        = get_post_meta( $post->ID, '_workrequest_repair_order_item_notes', true );

        // Display the original Repair Request linked
        if ( $repair_request_id && get_post_type( $repair_request_id ) === 'repair_request' ) {
            $request_title = get_the_title( $repair_request_id );
            $edit_link = get_edit_post_link( $repair_request_id );
            echo '<p><strong>' . esc_html__( 'Linked Repair Request:', 'workrequest' ) . '</strong> <a href="' . esc_url( $edit_link ) . '">#' . absint( $repair_request_id ) . ' - ' . esc_html( $request_title ) . '</a></p>';
        } else {
            echo '<p><strong>' . esc_html__( 'Linked Repair Request:', 'workrequest' ) . '</strong> ' . esc_html__( 'None or invalid.', 'workrequest' ) . '</p>';
        }

        // Input for item cost
        echo '<p>';
        echo '<label for="workrequest_item_cost">' . esc_html__( 'Item Cost:', 'workrequest' ) . '</label>';
        echo '<input type="number" step="0.01" min="0" id="workrequest_item_cost" name="workrequest_item_cost" value="' . esc_attr( $item_cost ) . '" class="regular-text" />';
        echo '</p>';

        // Dropdown for linking to WooCommerce product (if WooCommerce is active)
        if ( class_exists( 'WooCommerce' ) ) {
            echo '<p>';
            echo '<label for="workrequest_wc_product_id">' . esc_html__( 'Link to WooCommerce Product:', 'workrequest' ) . '</label>';
            echo '<input type="number" id="workrequest_wc_product_id" name="workrequest_wc_product_id" value="' . esc_attr( $wc_product_id ) . '" placeholder="' . esc_attr__( 'Enter Product ID', 'workrequest' ) . '" class="regular-text" />';
            echo '<p class="description">' . esc_html__( 'Enter the ID of a WooCommerce product if this repair item corresponds to a specific product/service.', 'workrequest' ) . '</p>';
            if ( $wc_product_id ) {
                $product = wc_get_product( $wc_product_id );
                if ( $product ) {
                    echo '<p><strong>' . esc_html__( 'Current Product:', 'workrequest' ) . '</strong> <a href="' . esc_url( get_edit_post_link( $wc_product_id ) ) . '">' . esc_html( $product->get_name() ) . '</a> (ID: ' . absint( $wc_product_id ) . ')</p>';
                }
            }
            echo '</p>';

            // Display associated WooCommerce Order ID if available
            if ( $wc_order_id ) {
                $order = wc_get_order( $wc_order_id );
                if ( $order ) {
                    echo '<p><strong>' . esc_html__( 'Linked WooCommerce Order:', 'workrequest' ) . '</strong> <a href="' . esc_url( $order->get_edit_order_url() ) . '">#' . absint( $wc_order_id ) . '</a></p>';
                }
            }
        }

        // Textarea for notes
        echo '<p>';
        echo '<label for="workrequest_item_notes"><strong>' . esc_html__( 'Notes:', 'workrequest' ) . '</strong></label>';
        echo '<textarea id="workrequest_item_notes" name="workrequest_item_notes" rows="4" class="large-text">' . esc_textarea( $item_notes ) . '</textarea>';
        echo '</p>';

        // Hidden field to pass repair_request_id if adding from request page
        if (isset($_GET['workrequest_request_id'])) {
            echo '<input type="hidden" name="workrequest_repair_request_id_from_url" value="' . absint($_GET['workrequest_request_id']) . '" />';
        }
    }

    /**
     * Callback for Linked Tasks Metabox.
     * @param WP_Post $post The post object.
     */
    public function render_linked_tasks_metabox( $post ) {
        $args = array(
            'post_type'      => 'task',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'ASC',
            'meta_query'     => array(
                array(
                    'key'     => '_workrequest_task_linked_item_id',
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
            echo '<p>' . esc_html__( 'No tasks linked to this repair item yet.', 'workrequest' ) . '</p>';
        }
        echo '<p><a href="' . esc_url( admin_url( 'post-new.php?post_type=task&workrequest_item_id=' . $post->ID ) ) . '" class="button button-primary">' . esc_html__( 'Add New Task for this Item', 'workrequest' ) . '</a></p>';
    }

    /**
     * Save custom fields for 'repair_order_item' CPT.
     * @param int $post_id The post ID.
     * @param WP_Post $post The post object.
     * @return int|void
     */
    public function save_meta( $post_id, $post ) {
        // Verify nonce
        if ( ! isset( $_POST['workrequest_repair_order_item_details_nonce'] ) || ! wp_verify_nonce( $_POST['workrequest_repair_order_item_details_nonce'], 'workrequest_save_repair_order_item_details' ) ) {
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
        if ( ! current_user_can( 'edit_repair_order_item', $post_id ) ) {
            return $post_id;
        }

        // Save Item Cost
        if ( isset( $_POST['workrequest_item_cost'] ) ) {
            update_post_meta( $post_id, '_workrequest_repair_order_item_cost', floatval( $_POST['workrequest_item_cost'] ) );
        }

        // Save WooCommerce Product ID
        if ( isset( $_POST['workrequest_wc_product_id'] ) ) {
            $product_id = absint( $_POST['workrequest_wc_product_id'] );
            if ( $product_id > 0 && class_exists( 'WooCommerce' ) && wc_get_product( $product_id ) ) {
                update_post_meta( $post_id, '_workrequest_woocommerce_product_id', $product_id );
            } else {
                delete_post_meta( $post_id, '_workrequest_woocommerce_product_id' );
            }
        }

        // Save Item Notes
        if ( isset( $_POST['workrequest_item_notes'] ) ) {
            update_post_meta( $post_id, '_workrequest_repair_order_item_notes', sanitize_textarea_field( $_POST['workrequest_item_notes'] ) );
        }

        // Handle linking to a Repair Request if coming from a URL parameter
        // This is typically only set on initial creation from the Request page.
        if ( isset( $_POST['workrequest_repair_request_id_from_url'] ) && ! empty( $_POST['workrequest_repair_request_id_from_url'] ) ) {
            $request_id = absint( $_POST['workrequest_repair_request_id_from_url'] );
            // Only update if current meta is empty or the new request_id is valid
            if ( empty( get_post_meta( $post_id, '_workrequest_repair_request_id', true ) ) || get_post_type( $request_id ) === 'repair_request' ) {
                update_post_meta( $post_id, '_workrequest_repair_request_id', $request_id );
            }
        }
    }
}