<?php
/**
 * Admin Page for Repair Order Preparation
 * Displays a list of repair requests and allows creating WooCommerce orders from them.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WorkRequest_Admin_RepairOrderPreparationPage {

    public function __construct() {
        // Hook to add the admin menu page
        add_action( 'admin_menu', array( $this, 'add_admin_menu_page' ) );

        // Hook for AJAX handler to create WooCommerce order
        add_action( 'wp_ajax_workrequest_create_order', array( $this, 'create_order_ajax_handler' ) );

        // IMPORTANT: Ensure this line is NOT present in this file if it was previously there.
        // It should ONLY be in workrequest.php:
        // require_once WORKREQUEST_PLUGIN_DIR . 'src/Admin/TasksByRequestPage.php';
    }

    /**
     * Add "Repair Order Preparation" page to the admin menu.
     */
    public function add_admin_menu_page() {
        add_submenu_page(
            'edit.php?post_type=repair_request', // Parent slug (under "Repair Requests" CPT menu)
            __( 'Repair Order Preparation', 'workrequest' ), // Page title
            __( 'Order Preparation', 'workrequest' ), // Menu title
            'manage_repair_requests', // Capability required to access (using our custom capability now)
            'workrequest-repair-order-preparation', // Menu slug
            array( $this, 'render_page_content' ) // Callback method to display page content
        );
    }

    /**
     * Displays the content of the "Repair Order Preparation" admin page.
     */
    public function render_page_content() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Repair Order Preparation', 'workrequest' ); ?></h1>
            <p><?php esc_html_e( 'Manage repair requests and convert them into WooCommerce orders.', 'workrequest' ); ?></p>

            <?php
            $args = array(
                'post_type'      => 'repair_request',
                'posts_per_page' => -1, // Get all repair requests
                'post_status'    => 'publish', // Or other relevant statuses
                'meta_query'     => array(
                    'relation' => 'AND', // Ensure both conditions are met
                    array(
                        'key'     => '_workrequest_selected_products',
                        'compare' => 'EXISTS', // The meta key must exist
                    ),
                    array(
                        'key'     => '_workrequest_selected_products',
                        'value'   => 'a:0:{}', // Exclude empty arrays
                        'compare' => '!=',
                        'type'    => 'CHAR' // For serialized data comparison
                    ),
                    array(
                        'key'     => '_workrequest_order_id',
                        'compare' => 'NOT EXISTS', // Only show requests that don't have an order ID yet
                    ),
                ),
            );

            $repair_requests = new WP_Query( $args );

            if ( $repair_requests->have_posts() ) {
                ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Request ID', 'workrequest' ); ?></th>
                            <th><?php esc_html_e( 'Request Title', 'workrequest' ); ?></th>
                            <th><?php esc_html_e( 'Date', 'workrequest' ); ?></th>
                            <th><?php esc_html_e( 'Products for Order', 'workrequest' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'workrequest' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while ( $repair_requests->have_posts() ) {
                            $repair_requests->the_post();
                            $post_id = get_the_ID();
                            $selected_product_ids = get_post_meta( $post_id, '_workrequest_selected_products', true );
                            if ( ! is_array( $selected_product_ids ) ) {
                                $selected_product_ids = array();
                            }
                            ?>
                            <tr id="repair-request-row-<?php echo esc_attr( $post_id ); ?>">
                                <td>#<?php echo esc_html( $post_id ); ?></td>
                                <td><a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>"><?php the_title(); ?></a></td>
                                <td><?php echo esc_html( get_the_date() ); ?></td>
                                <td class="workrequest-products-for-order">
                                    <ul class="workrequest-product-list" data-request_id="<?php echo esc_attr( $post_id ); ?>">
                                        <?php
                                        if ( ! empty( $selected_product_ids ) ) {
                                            foreach ( $selected_product_ids as $product_id ) {
                                                $product = wc_get_product( $product_id );
                                                if ( $product ) {
                                                    ?>
                                                    <li data-product_id="<?php echo esc_attr( $product_id ); ?>">
                                                        <strong><?php echo esc_html( $product->get_formatted_name() ); ?></strong>
                                                        <br>
                                                        <label><?php esc_html_e( 'Quantity:', 'workrequest' ); ?> <input type="number" class="workrequest-product-qty" value="1" min="1"></label>
                                                        <label><?php esc_html_e( 'Note:', 'workrequest' ); ?> <input type="text" class="workrequest-product-note" placeholder="<?php esc_attr_e( 'Add note (optional)', 'workrequest' ); ?>"></label>
                                                    </li>
                                                    <?php
                                                }
                                            }
                                        } else {
                                            echo '<li>' . esc_html__( 'No products selected for this request.', 'workrequest' ) . '</li>';
                                        }
                                        ?>
                                    </ul>
                                </td>
                                <td>
                                    <button type="button" class="button button-primary workrequest-create-order-btn" data-request_id="<?php echo esc_attr( $post_id ); ?>">
                                        <?php esc_html_e( 'Create WooCommerce Order', 'workrequest' ); ?>
                                    </button>
                                    <div id="status-<?php echo esc_attr( $post_id ); ?>" class="workrequest-order-status-display"></div>
                                </td>
                            </tr>
                            <?php
                        }
                        wp_reset_postdata(); // Reset post data after the custom query
                        ?>
                    </tbody>
                </table>
                <?php
            } else {
                echo '<p>' . esc_html__( 'No repair requests found with selected products awaiting order creation.', 'workrequest' ) . '</p>';
            }
            ?>
        </div>
        <?php
    }

    /**
     * AJAX handler for creating WooCommerce order from repair request.
     */
    public function create_order_ajax_handler() {
        check_ajax_referer( 'workrequest_create_order_nonce', 'security' ); // Check nonce from JS localize

        // Using 'manage_repair_requests' for consistency with our custom capabilities
        if ( ! current_user_can( 'manage_repair_requests' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to create orders.', 'workrequest' ) ) );
        }

        $request_id = isset( $_POST['request_id'] ) ? absint( $_POST['request_id'] ) : 0;
        $product_data = isset( $_POST['product_data'] ) ? json_decode( wp_unslash( $_POST['product_data'] ), true ) : array();

        if ( empty( $request_id ) || empty( $product_data ) ) {
            wp_send_json_error( array( 'message' => __( 'Missing Request ID or Product Data.', 'workrequest' ) ) );
        }

        $repair_request = get_post( $request_id );
        if ( ! $repair_request || 'repair_request' !== $repair_request->post_type ) {
            wp_send_json_error( array( 'message' => __( 'Invalid Repair Request ID.', 'workrequest' ) ) );
        }

        try {
            // Create a new WooCommerce order
            $order = wc_create_order();

            if ( is_wp_error( $order ) ) {
                throw new Exception( $order->get_error_message() );
            }

            // Add products to the order with quantity and note
            foreach ( $product_data as $item ) {
                $product_id = absint( $item['product_id'] );
                $quantity   = isset( $item['quantity'] ) ? absint( $item['quantity'] ) : 1;
                $note       = isset( $item['note'] ) ? sanitize_textarea_field( $item['note'] ) : '';

                $product = wc_get_product( $product_id );
                if ( $product && $quantity > 0 ) {
                    $item_id = $order->add_product( $product, $quantity );
                    if ( $item_id && ! empty( $note ) ) {
                        // Add custom meta to the order item for the note
                        wc_add_order_item_meta( $item_id, '_repair_request_item_note', $note );
                    }
                }
            }

            // Set customer based on the repair request author if available
            $customer_user_id = $repair_request->post_author;
            if ( $customer_user_id > 0 ) {
                $order->set_customer_id( $customer_user_id );
                // Optionally, pull and set billing/shipping details from the user profile
            }

            // Add a note to the order linking it back to the repair request
            $order->add_order_note( sprintf( __( 'Order created from Repair Request #%d: %s', 'workrequest' ), $request_id, $repair_request->post_title ) );

            // Link the repair request ID to the order for reverse lookup (optional but good practice)
            $order->update_meta_data( '_workrequest_repair_request_id', $request_id );
            $order->save(); // Save after updating meta data

            // Calculate totals (must be called AFTER all items are added and saved if meta is updated)
            $order->calculate_totals();
            $order->save();

            // Update the repair request post meta with the new order details
            update_post_meta( $request_id, '_workrequest_order_id', $order->get_id() );
            update_post_meta( $request_id, '_workrequest_order_status', wc_get_order_status_name( $order->get_status() ) );
            update_post_meta( $request_id, '_workrequest_order_total', $order->get_total() );
            // Also update the repair request post status to indicate it's converted (optional)
            wp_update_post( array( 'ID' => $request_id, 'post_status' => 'wc-processing' ) ); // Example status: 'wc-processing', or a custom status like 'converted'

            wp_send_json_success( array(
                'message'      => __( 'Order created successfully!', 'workrequest' ),
                'order_id'     => $order->get_id(),
                'order_status' => wc_get_order_status_name( $order->get_status() ),
                'order_total'  => $order->get_total(),
                'edit_order_url' => admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' )
            ) );

        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }

        wp_die(); // Always die for AJAX handlers
    }
}