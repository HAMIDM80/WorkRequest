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
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    public function enqueue_scripts( $hook_suffix ) {
        if ( 'repair_request_page_workrequest-repair-order-preparation' !== $hook_suffix ) {
            return;
        }

        wp_enqueue_script( 'workrequest-order-preparation', WORKREQUEST_PLUGIN_URL . 'assets/js/order-preparation.js', array( 'jquery' ), WORKREQUEST_PLUGIN_VERSION, true );
        wp_localize_script( 'workrequest-order-preparation', 'workrequest_order_prep_ajax_object', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'security' => wp_create_nonce( 'workrequest_create_order_nonce' ),
            'confirm_text' => __( 'Are you sure you want to create a WooCommerce order for this repair request?', 'workrequest' ),
        ) );

        wp_enqueue_style( 'workrequest-admin-style', WORKREQUEST_PLUGIN_URL . 'assets/css/admin-style.css', array(), WORKREQUEST_PLUGIN_VERSION );
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
                            <th><?php esc_html_e( 'Customer', 'workrequest' ); ?></th>
                            <th><?php esc_html_e( 'Date', 'workrequest' ); ?></th>
                            <th><?php esc_html_e( 'Products for Order', 'workrequest' ); ?></th>
                            <th><?php esc_html_e( 'Total Amount', 'workrequest' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'workrequest' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while ( $repair_requests->have_posts() ) {
                            $repair_requests->the_post();
                            $post_id = get_the_ID();
                            $selected_products_data = get_post_meta( $post_id, '_workrequest_selected_products', true );
                            if ( ! is_array( $selected_products_data ) ) {
                                $selected_products_data = array();
                            }

                            $customer_name = get_post_meta( $post_id, '_workrequest_customer_name', true );
                            $total_amount = 0;
                            ?>
                            <tr id="repair-request-row-<?php echo esc_attr( $post_id ); ?>">
                                <td>#<?php echo esc_html( $post_id ); ?></td>
                                <td><a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>"><?php the_title(); ?></a></td>
                                <td><?php echo esc_html( $customer_name ); ?></td>
                                <td><?php echo esc_html( get_the_date() ); ?></td>
                                <td class="workrequest-products-for-order">
                                    <?php if ( ! empty( $selected_products_data ) ) : ?>
                                        <table class="products-sub-table">
                                            <thead>
                                                <tr>
                                                    <th><?php _e( 'Product', 'workrequest' ); ?></th>
                                                    <th><?php _e( 'Price', 'workrequest' ); ?></th>
                                                    <th><?php _e( 'Qty', 'workrequest' ); ?></th>
                                                    <th><?php _e( 'Note', 'workrequest' ); ?></th>
                                                    <th><?php _e( 'Subtotal', 'workrequest' ); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                foreach ( $selected_products_data as $product_id => $data ) {
                                                    $product = wc_get_product( $product_id );
                                                    if ( ! $product ) continue;
                                                    $quantity = isset( $data['quantity'] ) ? absint( $data['quantity'] ) : 1;
                                                    $note = isset( $data['note'] ) ? esc_textarea( $data['note'] ) : '';
                                                    $product_price = floatval( $product->get_price() );
                                                    $item_subtotal = $product_price * $quantity;
                                                    $total_amount += $item_subtotal;
                                                    ?>
                                                    <tr data-product_id="<?php echo esc_attr( $product_id ); ?>">
                                                        <td><?php echo esc_html( $product->get_name() ); ?></td>
                                                        <td><?php echo wc_price( $product_price ); ?></td>
                                                        <td><input type="number" class="workrequest-product-qty small-text" value="<?php echo esc_attr( $quantity ); ?>" min="1" data-product_id="<?php echo esc_attr( $product_id ); ?>" onchange="updateRowTotal(this)"></td>
                                                        <td><input type="text" class="workrequest-product-note large-text" value="<?php echo esc_attr( $note ); ?>" placeholder="<?php esc_attr_e( 'Add note (optional)', 'workrequest' ); ?>" data-product_id="<?php echo esc_attr( $product_id ); ?>"></td>
                                                        <td class="item-subtotal"><?php echo wc_price( $item_subtotal ); ?></td>
                                                    </tr>
                                                    <?php
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    <?php else : ?>
                                        <p><?php esc_html_e( 'No products selected for this request.', 'workrequest' ); ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="total-amount-display" data-total_amount="<?php echo esc_attr( $total_amount ); ?>">
                                    <strong><?php echo wc_price( $total_amount ); ?></strong>
                                </td>
                                <td>
                                    <button type="button" class="button button-primary workrequest-create-order-btn" data-request_id="<?php echo esc_attr( $post_id ); ?>">
                                        <?php esc_html_e( 'Convert to WooCommerce Order', 'workrequest' ); ?>
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
        <script>
            function updateRowTotal(qtyInput) {
                var $row = jQuery(qtyInput).closest('tr');
                var productId = $row.data('product_id');
                var newQty = parseInt(qtyInput.value);

                // Fetch original product price from data or a hidden field if needed.
                // For simplicity, let's assume we have it in a global JS object or can fetch via AJAX.
                // For now, we will simulate fetching product price. In a real scenario, you'd fetch it.
                // A better approach would be to store product prices in the initial render data.
                // For demonstration, we'll use a placeholder.
                // **IMPORTANT**: For production, never rely on client-side price calculation directly.
                // Always validate and recalculate on the server side for order creation.
                var productPrice = <?php echo json_encode(array_combine(array_keys($selected_products_data), array_map(function($id) { $p = wc_get_product($id); return $p ? floatval($p->get_price()) : 0; }, array_keys($selected_products_data)))); ?>[productId];
                
                if (isNaN(newQty) || newQty < 1) {
                    newQty = 1;
                    qtyInput.value = 1;
                }

                var newSubtotal = productPrice * newQty;
                $row.find('.item-subtotal').text(wc_price_format(newSubtotal)); // Assuming wc_price_format is available or you define it

                updateGrandTotal();
            }

            function updateGrandTotal() {
                var grandTotal = 0;
                jQuery('.products-sub-table tbody tr').each(function() {
                    var $row = jQuery(this);
                    var productId = $row.data('product_id');
                    var qty = parseInt($row.find('.workrequest-product-qty').val());
                    var productPrice = <?php echo json_encode(array_combine(array_keys($selected_products_data), array_map(function($id) { $p = wc_get_product($id); return $p ? floatval($p->get_price()) : 0; }, array_keys($selected_products_data)))); ?>[productId];
                    
                    if (!isNaN(qty) && qty > 0 && productPrice) {
                        grandTotal += (productPrice * qty);
                    }
                });
                jQuery('.total-amount-display').each(function() {
                     jQuery(this).find('strong').text(wc_price_format(grandTotal));
                     jQuery(this).data('total_amount', grandTotal); // Update data attribute too
                });
            }

            // Simple price formatter (WooCommerce's wc_price() is PHP-based)
            function wc_price_format(price) {
                // This is a simplified version. For full WooCommerce price formatting,
                // you'd typically pass it through AJAX to a server-side function.
                // Assuming currency symbol and decimal separator are standard for display.
                return '<?php echo get_woocommerce_currency_symbol(); ?>' + parseFloat(price).toFixed(<?php echo wc_get_price_decimals(); ?>);
            }

             jQuery(document).ready(function($) {
                // Initial total amount calculation on load
                updateGrandTotal();
            });
        </script>
        <?php
    }

    /**
     * AJAX handler for creating WooCommerce order from repair request.
     */
    public function create_order_ajax_handler() {
        check_ajax_referer( 'workrequest_create_order_nonce', 'security' );

        if ( ! current_user_can( 'manage_repair_requests' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to create orders.', 'workrequest' ) ) );
        }

        $request_id = isset( $_POST['request_id'] ) ? absint( $_POST['request_id'] ) : 0;
        $product_data_raw = isset( $_POST['product_data'] ) ? wp_unslash( $_POST['product_data'] ) : '[]';
        $product_data = json_decode( $product_data_raw, true );

        if ( empty( $request_id ) || ! is_array( $product_data ) ) {
            wp_send_json_error( array( 'message' => __( 'Missing Request ID or invalid Product Data.', 'workrequest' ) ) );
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
            $customer_user_id = $repair_request->post_author; // Get the user who created the repair request
            if ( $customer_user_id > 0 ) {
                $order->set_customer_id( $customer_user_id );
                // Try to get billing info from the customer's repair request
                $customer_name = get_post_meta($request_id, '_workrequest_customer_name', true);
                $customer_email = get_post_meta($request_id, '_workrequest_customer_email', true);

                $order->set_billing_first_name( $customer_name ); // Simple assignment, you might need to parse name
                $order->set_billing_email( $customer_email );
            } else {
                // If repair request isn't linked to a user, use customer name/email from the request
                $order->set_billing_first_name( get_post_meta( $request_id, '_workrequest_customer_name', true ) );
                $order->set_billing_email( get_post_meta( $request_id, '_workrequest_customer_email', true ) );
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
                'message'        => __( 'Order created successfully!', 'workrequest' ),
                'order_id'       => $order->get_id(),
                'order_status'   => wc_get_order_status_name( $order->get_status() ),
                'order_total'    => $order->get_total(),
                'edit_order_url' => admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' )
            ) );

        } catch ( Exception $e ) {
            wp_send_json_error( array( 'message' => $e->getMessage() ) );
        }

        wp_die(); // Always die for AJAX handlers
    }
}