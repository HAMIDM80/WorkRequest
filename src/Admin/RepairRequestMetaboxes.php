<?php
// src/Admin/RepairRequestMetaboxes.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WorkRequest_Admin_RepairRequestMetaboxes {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_repair_request_metaboxes' ) );
        add_action( 'save_post_repair_request', array( $this, 'save_repair_request_metabox_data' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        // --- START: Custom Columns for Repair Request ---
        add_filter( 'manage_repair_request_posts_columns', array( $this, 'add_custom_columns' ) );
        add_action( 'manage_repair_request_posts_custom_column', array( $this, 'render_custom_columns' ), 10, 2 );
        // --- END: Custom Columns for Repair Request ---
    }

    public function enqueue_scripts( $hook ) {
        global $post_type;
        if ( 'post-new.php' == $hook || 'post.php' == $hook ) {
            if ( 'repair_request' == $post_type ) {
                // Enqueue select2 for product search dropdown
                wp_enqueue_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0-rc.0' );
                wp_enqueue_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array( 'jquery' ), '4.1.0-rc.0', true );

                // Custom script for product selection and AJAX
                wp_enqueue_script( 'workrequest-repair-request-admin', WORKREQUEST_PLUGIN_URL . 'assets/js/repair-request-admin.js', array( 'jquery', 'select2' ), WORKREQUEST_PLUGIN_VERSION, true );
                wp_localize_script( 'workrequest-repair-request-admin', 'workrequest_ajax_object', array(
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'security' => wp_create_nonce( 'workrequest_product_search_nonce' ),
                ) );
            }
        }
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

        add_meta_box(
            'workrequest_repair_products',
            __( 'Required Products', 'workrequest' ),
            array( $this, 'render_repair_products_metabox' ),
            'repair_request',
            'normal',
            'high'
        );

        add_meta_box(
            'workrequest_operator_notes',
            __( 'Operator Notes / Tasks Definition', 'workrequest' ),
            array( $this, 'render_operator_notes_metabox' ),
            'repair_request',
            'normal',
            'high'
        );
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

    public function render_repair_products_metabox( $post ) {
        // Nonce field for security
        wp_nonce_field( 'repair_products_nonce', 'repair_products_nonce_field' );

        // Get previously selected products
        $selected_products_data = get_post_meta( $post->ID, '_workrequest_selected_products', true );
        if ( ! is_array( $selected_products_data ) ) {
            $selected_products_data = array();
        }
        ?>
        <div id="workrequest-products-wrapper">
            <p>
                <label for="workrequest_product_search"><?php _e( 'Search and add products:', 'workrequest' ); ?></label>
                <select class="wc-product-search" id="workrequest_product_search" style="width: 100%;"></select>
            </p>

            <table class="wp-list-table widefat fixed striped products-table">
                <thead>
                    <tr>
                        <th><?php _e( 'Product', 'workrequest' ); ?></th>
                        <th><?php _e( 'Quantity', 'workrequest' ); ?></th>
                        <th><?php _e( 'Note', 'workrequest' ); ?></th>
                        <th><?php _e( 'Actions', 'workrequest' ); ?></th>
                    </tr>
                </thead>
                <tbody id="workrequest-selected-products-list">
                    <?php if ( empty( $selected_products_data ) ) : ?>
                        <tr class="no-products-row">
                            <td colspan="4"><?php _e( 'No products added yet.', 'workrequest' ); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $selected_products_data as $product_id => $data ) :
                            $product = wc_get_product( $product_id );
                            if ( ! $product ) continue;
                            $quantity = isset( $data['quantity'] ) ? absint( $data['quantity'] ) : 1;
                            $note = isset( $data['note'] ) ? esc_textarea( $data['note'] ) : '';
                            ?>
                            <tr data-product_id="<?php echo esc_attr( $product_id ); ?>">
                                <td><?php echo esc_html( $product->get_name() ); ?></td>
                                <td><input type="number" name="workrequest_products[<?php echo esc_attr( $product_id ); ?>][quantity]" value="<?php echo esc_attr( $quantity ); ?>" min="1" step="1" class="small-text workrequest-product-qty" /></td>
                                <td><input type="text" name="workrequest_products[<?php echo esc_attr( $product_id ); ?>][note]" value="<?php echo esc_attr( $note ); ?>" class="large-text" placeholder="<?php esc_attr_e( 'Add note (optional)', 'workrequest' ); ?>" /></td>
                                <td><button type="button" class="button button-small remove-product-row"><?php _e( 'Remove', 'workrequest' ); ?></button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <style>
            .products-table td { vertical-align: middle; }
            .products-table input[type="number"] { width: 70px; }
            .products-table input[type="text"] { width: 95%; }
            .select2-container { margin-top: 5px; }
            .select2-container--default .select2-selection--single {
                height: 30px;
                padding-top: 2px;
                border-color: #c3c4c7;
            }
        </style>
        <script>
            jQuery(document).ready(function($) {
                // Initialize Select2 for product search
                $('.wc-product-search').select2({
                    ajax: {
                        url: workrequest_ajax_object.ajax_url,
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                q: params.term, // search term
                                action: 'workrequest_search_products',
                                security: workrequest_ajax_object.security
                            };
                        },
                        processResults: function (data) {
                            return {
                                results: data
                            };
                        },
                        cache: true
                    },
                    minimumInputLength: 3,
                    placeholder: '<?php esc_attr_e( 'Search for a product...', 'workrequest' ); ?>',
                    allowClear: true
                });

                // Add product to the list when selected
                $('#workrequest_product_search').on('select2:select', function (e) {
                    var product_id = e.params.data.id;
                    var product_name = e.params.data.text;
                    var $productsList = $('#workrequest-selected-products-list');

                    // Check if product already exists
                    if ($productsList.find('tr[data-product_id="' + product_id + '"]').length > 0) {
                        alert('<?php esc_attr_e( 'This product is already added.', 'workrequest' ); ?>');
                        return;
                    }

                    // Remove "No products added yet." row if it exists
                    $productsList.find('.no-products-row').remove();

                    // Append new product row
                    var newRow = `
                        <tr data-product_id="${product_id}">
                            <td>${product_name}</td>
                            <td><input type="number" name="workrequest_products[${product_id}][quantity]" value="1" min="1" step="1" class="small-text workrequest-product-qty" /></td>
                            <td><input type="text" name="workrequest_products[${product_id}][note]" value="" class="large-text" placeholder="<?php esc_attr_e( 'Add note (optional)', 'workrequest' ); ?>" /></td>
                            <td><button type="button" class="button button-small remove-product-row"><?php _e( 'Remove', 'workrequest' ); ?></button></td>
                        </tr>
                    `;
                    $productsList.append(newRow);

                    // Clear the select2 field after selection
                    $(this).val(null).trigger('change');
                });

                // Remove product row
                $('#workrequest-selected-products-list').on('click', '.remove-product-row', function() {
                    $(this).closest('tr').remove();
                    if ($('#workrequest-selected-products-list').find('tr').length === 0) {
                        $('#workrequest-selected-products-list').append('<tr class="no-products-row"><td colspan="4"><?php _e( 'No products added yet.', 'workrequest' ); ?></td></tr>');
                    }
                });
            });
        </script>
        <?php
    }

    public function render_operator_notes_metabox( $post ) {
        // Nonce field for security
        wp_nonce_field( 'operator_notes_nonce', 'operator_notes_nonce_field' );

        $operator_notes = get_post_meta( $post->ID, '_workrequest_operator_notes', true );
        ?>
        <p>
            <label for="workrequest_operator_notes"><?php _e( 'Enter tasks for this repair, one task per line:', 'workrequest' ); ?></label>
            <textarea id="workrequest_operator_notes" name="workrequest_operator_notes" rows="10" class="large-text" placeholder="<?php esc_attr_e( 'e.g.,\nCheck circuit board for damage\nReplace faulty capacitor\nTest device functionality', 'workrequest' ); ?>"><?php echo esc_textarea( $operator_notes ); ?></textarea>
        </p>
        <p class="description"><?php _e( 'Each line will be considered a separate task when "Create Tasks" button is used on the Tasks by Request page.', 'workrequest' ); ?></p>
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

        // Sanitize and save the data for general details
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

        // Save selected products
        if ( isset( $_POST['workrequest_products'] ) && is_array( $_POST['workrequest_products'] ) ) {
            $sanitized_products = array();
            foreach ( $_POST['workrequest_products'] as $product_id => $data ) {
                $sanitized_products[absint( $product_id )] = array(
                    'quantity' => isset( $data['quantity'] ) ? absint( $data['quantity'] ) : 1,
                    'note'     => isset( $data['note'] ) ? sanitize_textarea_field( $data['note'] ) : '',
                );
            }
            update_post_meta( $post_id, '_workrequest_selected_products', $sanitized_products );
        } else {
            delete_post_meta( $post_id, '_workrequest_selected_products' ); // Remove if no products are selected
        }

        // Save operator notes
        if ( isset( $_POST['workrequest_operator_notes'] ) ) {
            update_post_meta( $post_id, '_workrequest_operator_notes', sanitize_textarea_field( $_POST['workrequest_operator_notes'] ) );
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
            'products_count' => __( 'Products', 'workrequest' ), // New column for product count
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
            case 'products_count':
                $products = get_post_meta( $post_id, '_workrequest_selected_products', true );
                echo count( is_array( $products ) ? $products : array() );
                break;
            // 'date' column will be handled by WordPress default if added by merge,
            // or you can implement custom date formatting here.
        }
    }
}