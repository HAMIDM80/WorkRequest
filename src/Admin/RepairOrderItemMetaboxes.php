<?php
// src/Admin/RepairOrderItemMetaboxes.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WorkRequest_Admin_RepairOrderItemMetaboxes {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_repair_order_item_metaboxes' ) );
        add_action( 'save_post_repair_order_item', array( $this, 'save_repair_order_item_metabox_data' ) );

        // --- START: Custom Columns for Repair Order Item ---
        add_filter( 'manage_edit-repair_order_item_columns', array( $this, 'add_custom_columns' ) );
        add_action( 'manage_repair_order_item_posts_custom_column', array( $this, 'render_custom_columns' ), 10, 2 );
        // --- END: Custom Columns for Repair Order Item ---
    }

    public function add_repair_order_item_metaboxes() {
        add_meta_box(
            'workrequest_repair_order_item_details',
            __( 'Repair Order Item Details', 'workrequest' ),
            array( $this, 'render_repair_order_item_details_metabox' ),
            'repair_order_item',
            'normal',
            'high'
        );
        // Add more metaboxes as needed for RepairOrderItem
    }

    public function render_repair_order_item_details_metabox( $post ) {
        wp_nonce_field( 'repair_order_item_details_nonce', 'repair_order_item_details_nonce_field' );

        $parent_request_id = get_post_meta( $post->ID, '_workrequest_parent_request_id', true );
        $item_status = get_post_meta( $post->ID, '_workrequest_item_status', true );
        $estimated_cost = get_post_meta( $post->ID, '_workrequest_estimated_cost', true );
        $item_description = get_post_meta( $post->ID, '_workrequest_item_description', true );

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
                    <th><label for="workrequest_parent_request_id"><?php _e( 'Parent Repair Request', 'workrequest' ); ?></label></th>
                    <td>
                        <select name="workrequest_parent_request_id" id="workrequest_parent_request_id">
                            <option value="0"><?php _e( 'None (Standalone)', 'workrequest' ); ?></option>
                            <?php
                            foreach ( $repair_requests as $request_id ) {
                                $request_title = get_the_title( $request_id );
                                echo '<option value="' . esc_attr( $request_id ) . '" ' . selected( $parent_request_id, $request_id, false ) . '>' . esc_html( $request_title ) . ' (ID: ' . esc_html( $request_id ) . ')</option>';
                            }
                            ?>
                        </select>
                        <p class="description"><?php _e( 'Link this item to a specific Repair Request.', 'workrequest' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="workrequest_item_status"><?php _e( 'Item Status', 'workrequest' ); ?></label></th>
                    <td>
                        <select name="workrequest_item_status" id="workrequest_item_status">
                            <option value="pending" <?php selected( $item_status, 'pending' ); ?>><?php _e( 'Pending', 'workrequest' ); ?></option>
                            <option value="in_progress" <?php selected( $item_status, 'in_progress' ); ?>><?php _e( 'In Progress', 'workrequest' ); ?></option>
                            <option value="completed" <?php selected( $item_status, 'completed' ); ?>><?php _e( 'Completed', 'workrequest' ); ?></option>
                            <option value="needs_parts" <?php selected( $item_status, 'needs_parts' ); ?>><?php _e( 'Needs Parts', 'workrequest' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="workrequest_estimated_cost"><?php _e( 'Estimated Cost', 'workrequest' ); ?></label></th>
                    <td><input type="number" step="0.01" id="workrequest_estimated_cost" name="workrequest_estimated_cost" value="<?php echo esc_attr( $estimated_cost ); ?>" class="regular-text" min="0" /></td>
                </tr>
                 <tr>
                    <th><label for="workrequest_item_description"><?php _e( 'Item Description', 'workrequest' ); ?></label></th>
                    <td><textarea id="workrequest_item_description" name="workrequest_item_description" rows="3" class="large-text"><?php echo esc_textarea( $item_description ); ?></textarea></td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    public function save_repair_order_item_metabox_data( $post_id ) {
        if ( ! isset( $_POST['repair_order_item_details_nonce_field'] ) ) {
            return $post_id;
        }
        if ( ! wp_verify_nonce( $_POST['repair_order_item_details_nonce_field'], 'repair_order_item_details_nonce' ) ) {
            return $post_id;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }
        if ( ! current_user_can( 'edit_repair_order_item', $post_id ) ) {
            return $post_id;
        }

        if ( isset( $_POST['workrequest_parent_request_id'] ) ) {
            update_post_meta( $post_id, '_workrequest_parent_request_id', intval( $_POST['workrequest_parent_request_id'] ) );
        }
        if ( isset( $_POST['workrequest_item_status'] ) ) {
            update_post_meta( $post_id, '_workrequest_item_status', sanitize_text_field( $_POST['workrequest_item_status'] ) );
        }
        if ( isset( $_POST['workrequest_estimated_cost'] ) ) {
            update_post_meta( $post_id, '_workrequest_estimated_cost', floatval( $_POST['workrequest_estimated_cost'] ) );
        }
        if ( isset( $_POST['workrequest_item_description'] ) ) {
            update_post_meta( $post_id, '_workrequest_item_description', sanitize_textarea_field( $_POST['workrequest_item_description'] ) );
        }
    }

    /**
     * Adds custom columns to the Repair Order Item list table.
     * Note: For custom post types that are submenus, the filter hook usually needs 'edit-CPT_SLUG'
     *
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public function add_custom_columns( $columns ) {
        // Remove 'date' if you want to replace it or move it
        unset( $columns['date'] );
        $new_columns = array(
            'parent_request' => __( 'Parent Request', 'workrequest' ),
            'item_status'    => __( 'Status', 'workrequest' ),
            'estimated_cost' => __( 'Est. Cost', 'workrequest' ),
            'date'           => __( 'Date', 'workrequest' ), // Re-add date at the end
        );
        return array_merge( $columns, $new_columns );
    }

    /**
     * Renders content for custom columns in the Repair Order Item list table.
     *
     * @param string $column_name The name of the column to display.
     * @param int    $post_id     The ID of the current post.
     */
    public function render_custom_columns( $column_name, $post_id ) {
        switch ( $column_name ) {
            case 'parent_request':
                $parent_request_id = get_post_meta( $post_id, '_workrequest_parent_request_id', true );
                if ( $parent_request_id ) {
                    $parent_request_title = get_the_title( $parent_request_id );
                    $edit_link = get_edit_post_link( $parent_request_id );
                    if ( $parent_request_title && $edit_link ) {
                        echo '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $parent_request_title ) . '</a>';
                    } else {
                        echo __( 'N/A', 'workrequest' );
                    }
                } else {
                    echo __( 'None', 'workrequest' );
                }
                break;
            case 'item_status':
                $status = get_post_meta( $post_id, '_workrequest_item_status', true );
                echo esc_html( ucfirst( str_replace( '_', ' ', $status ) ) );
                break;
            case 'estimated_cost':
                $cost = get_post_meta( $post_id, '_workrequest_estimated_cost', true );
                echo $cost ? esc_html( number_format( $cost, 2 ) ) : __( 'N/A', 'workrequest' );
                break;
        }
    }
}