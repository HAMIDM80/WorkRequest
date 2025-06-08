<?php
/**
 * Admin functionalities for Repair Request CPT.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Adds the Metabox for WooCommerce Order Management to the Repair Request CPT.
 */
function workrequest_add_order_management_metabox() {
    add_meta_box(
        'workrequest_order_management',
        __( 'WooCommerce Order Management', 'workrequest' ),
        'workrequest_render_order_management_metabox',
        'repair_request', // Post type where this metabox should appear
        'normal', // Context (where on the screen)
        'high'    // Priority
    );
}
add_action( 'add_meta_boxes', 'workrequest_add_order_management_metabox' );

/**
 * Renders the content of the WooCommerce Order Management Metabox.
 * This will include a product search and a button to create the order.
 */
function workrequest_render_order_management_metabox( $post ) {
    // Get current linked order if exists
    $linked_order_id = get_post_meta( $post->ID, 'wr_linked_order_id', true );
    $is_converted_to_order = get_post_meta( $post->ID, 'wr_is_converted_to_order', true );

    // If an order is already linked, display its information
    if ( $is_converted_to_order && $linked_order_id && $linked_order_id > 0 ) {
        $order = wc_get_order( $linked_order_id );
        if ( $order ) {
            $order_url = get_edit_post_link( $linked_order_id );
            echo '<div class="workrequest-order-linked-info">';
            echo '<p><strong>' . esc_html__( 'This request has been converted to a WooCommerce order:', 'workrequest' ) . '</strong></p>';
            echo '<p>' . sprintf( __( 'Order #%s', 'workrequest' ), '<a href="' . esc_url( $order_url ) . '" target="_blank">' . esc_html( $order->get_order_number() ) . '</a>' ) . '</p>';
            echo '<p>' . esc_html__( 'Status:', 'workrequest' ) . ' ' . esc_html( wc_get_order_status_name( $order->get_status() ) ) . '</p>';
            echo '<p>' . esc_html__( 'Total:', 'workrequest' ) . ' ' . wp_kses_post( $order->get_formatted_order_total() ) . '</p>';
            echo '</div>';
            echo '<hr>'; // Separator
            // Optionally, allow re-creating order or modifying. For simplicity, we'll disable the form once linked.
            echo '<p>' . esc_html__( 'To modify the order, please visit the linked WooCommerce order page.', 'workrequest' ) . '</p>';
            return; // Exit function, don't show the form to create new order
        }
    }

    // Only show form if no order is linked yet or if the linked order is invalid
    wp_nonce_field( 'workrequest_create_order_nonce', 'workrequest_create_order_nonce_field' );
    ?>
    <div id="workrequest-order-creation-form">
        <p><?php esc_html_e( 'Select WooCommerce products to add to the new order:', 'workrequest' ); ?></p>

        <div class="workrequest-product-search-wrapper">
        <input type="text" id="workrequest_product_search" class="workrequest-product-search" style="width: 100%;" placeholder="<?php esc_attr_e( 'Search for a product...', 'workrequest' ); ?>" />
        </div>

        <ul id="workrequest_selected_products_list" class="workrequest-selected-products-list">
            </ul>

        <button type="button" id="workrequest_create_order_button" class="button button-primary">
            <?php esc_html_e( 'Create WooCommerce Order', 'workrequest' ); ?>
        </button>

        <p class="description"><?php esc_html_e( 'Selected items will be added to a new WooCommerce order for the user who submitted this request.', 'workrequest' ); ?></p>
    </div>

    <style>
        .workrequest-selected-products-list {
            list-style: none;
            padding: 0;
            margin-top: 15px;
            border: 1px solid #ddd;
            max-height: 200px;
            overflow-y: auto;
            background: #fff;
            margin-bottom: 15px;
        }
        .workrequest-selected-products-list li {
            padding: 8px 10px;
            border-bottom: 1px dashed #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .workrequest-selected-products-list li:last-child {
            border-bottom: none;
        }
        .workrequest-product-quantity {
            display: flex;
            align-items: center;
        }
        .workrequest-product-quantity input {
            width: 50px;
            text-align: center;
            margin: 0 5px;
        }
        .workrequest-product-quantity button {
            background: none;
            border: 1px solid #ccc;
            padding: 2px 8px;
            cursor: pointer;
            font-size: 16px;
            line-height: 1;
            border-radius: 3px;
        }
        .workrequest-product-quantity button:hover {
            background: #f0f0f0;
        }
        .workrequest-product-remove {
            color: #a00;
            cursor: pointer;
            font-size: 18px;
            margin-left: 10px;
        }
        .workrequest-order-linked-info {
            background-color: #e6ffe6;
            border: 1px solid #82e682;
            padding: 15px;
            margin-bottom: 15px;
        }
    </style>
    <?php
}

/**
 * Handles AJAX requests for searching WooCommerce products.
 */
function workrequest_ajax_search_products() {
    check_ajax_referer( 'search-products', 'security' ); // WooCommerce's default nonce for product search

    if ( ! current_user_can( 'edit_shop_orders' ) ) { // Or a more specific capability
        wp_die( -1 );
    }

    $search_term = isset( $_GET['term'] ) ? wc_clean( wp_unslash( $_GET['term'] ) ) : '';

    $data = [];
    $product_query = new WP_Query( [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        's'              => $search_term,
        'posts_per_page' => 10,
        'fields'         => 'ids',
        'orderby'        => 'title',
        'order'          => 'ASC',
        'tax_query'      => [
            'relation' => 'AND',
            [
                'taxonomy' => 'product_type',
                'field'    => 'slug',
                'terms'    => [ 'simple', 'variable' ], // Only simple and variable products
                'operator' => 'IN',
            ],
        ],
    ] );

    if ( $product_query->have_posts() ) {
        foreach ( $product_query->posts as $product_id ) {
            $product = wc_get_product( $product_id );
            if ( $product ) {
                $data[] = [
                    'id'   => $product_id,
                    'text' => wp_strip_all_tags( $product->get_formatted_name() ), // Displays product name and SKU
                ];
            }
        }
    }

    wp_send_json( $data );
}
add_action( 'wp_ajax_workrequest_search_products', 'workrequest_ajax_search_products' );



add_action( 'admin_enqueue_scripts', 'workrequest_enqueue_admin_scripts' );

/**
 * Handles the AJAX request to create a WooCommerce order.
 */
function workrequest_ajax_create_woocommerce_order() {
    check_ajax_referer( 'workrequest_create_order_nonce', 'nonce' );

    if ( ! current_user_can( 'edit_shop_orders' ) ) { // Operators should have this capability
        wp_send_json_error( [ 'message' => __( 'You do not have permission to create orders.', 'workrequest' ) ] );
    }

    $post_id = intval( $_POST['post_id'] );
    $products = json_decode( wp_unslash( $_POST['products'] ), true ); // Products array with ID and quantity

    if ( ! $post_id || ! $products || ! is_array( $products ) ) {
        wp_send_json_error( [ 'message' => __( 'Invalid data provided.', 'workrequest' ) ] );
    }

    $repair_request_post = get_post( $post_id );
    if ( ! $repair_request_post || $repair_request_post->post_type !== 'repair_request' ) {
        wp_send_json_error( [ 'message' => __( 'Repair request not found.', 'workrequest' ) ] );
    }

    // Get the user who created the repair request
    $user_id = $repair_request_post->post_author;
    $customer_user = get_user_by( 'id', $user_id );

    if ( ! $customer_user ) {
        wp_send_json_error( [ 'message' => __( 'Customer user not found for this request.', 'workrequest' ) ] );
    }

    // Create a new WooCommerce order
    $order = wc_create_order();

    if ( is_wp_error( $order ) ) {
        wp_send_json_error( [ 'message' => __( 'Error creating WooCommerce order: ', 'workrequest' ) . $order->get_error_message() ] );
    }

    // Add selected products to the order
    foreach ( $products as $product_item ) {
        $product_id = intval( $product_item['id'] );
        $quantity = intval( $product_item['qty'] );

        if ( $product_id > 0 && $quantity > 0 ) {
            $product = wc_get_product( $product_id );
            if ( $product ) {
                $order->add_product( $product, $quantity );
            }
        }
    }

    // Set customer for the order
    $order->set_customer_id( $user_id );

    // Calculate totals
    $order->calculate_totals();

    // Set order status (e.g., 'pending' for payment)
    $order->set_status( 'pending' ); // Or 'on-hold' if you want operator to confirm first

    // Save the order
    $order_id = $order->save();

    if ( ! $order_id ) {
        wp_send_json_error( [ 'message' => __( 'Failed to save WooCommerce order.', 'workrequest' ) ] );
    }

    // Update the repair request post meta
    update_post_meta( $post_id, 'wr_linked_order_id', $order_id );
    update_post_meta( $post_id, 'wr_is_converted_to_order', true );
    update_post_meta( $post_id, 'wr_request_status', 'converted_to_order' ); // Update request status

    // Optionally, send an email to the user about the new order
    // (This requires another module or integration, e.g., using default WooCommerce email triggers)
    // wc_get_template( 'emails/customer-new-order.php', array( 'order' => $order ), '', WC()->plugin_path() . '/templates/' ); // This is for triggering email, not sending directly.
    // $mailer = WC()->mailer();
    // $mailer->emails['WC_Email_Customer_New_Order']->trigger( $order_id );

    wp_send_json_success( [
        'message'   => __( 'WooCommerce order created successfully!', 'workrequest' ),
        'order_id'  => $order_id,
        'order_url' => get_edit_post_link( $order_id ),
        'order_number' => $order->get_order_number(),
        'order_status_name' => wc_get_order_status_name( $order->get_status() ),
        'order_total' => wp_kses_post( $order->get_formatted_order_total() ),
    ] );
}
add_action( 'wp_ajax_workrequest_create_woocommerce_order', 'workrequest_ajax_create_woocommerce_order' );