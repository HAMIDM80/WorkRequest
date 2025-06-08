<?php
/**
 * Frontend form for submitting repair requests.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Handles the submission of the repair request form.
 */
function workrequest_handle_repair_request_submission() {
    // Only process if it's a POST request and our form is submitted
    if ( isset( $_POST['workrequest_repair_request_submit'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'workrequest_repair_request_nonce' ) ) {

        // Ensure user is logged in
        if ( ! is_user_logged_in() ) {
            wp_die( __( 'You must be logged in to submit a repair request.', 'workrequest' ) );
        }

        // Sanitize and validate inputs
        $issue_description        = sanitize_textarea_field( $_POST['issue_description'] );
        $device_type              = sanitize_text_field( $_POST['device_type'] );
        $device_model             = sanitize_text_field( $_POST['device_model'] );
        $serial_number            = sanitize_text_field( $_POST['serial_number'] );
        $preferred_contact_method = sanitize_text_field( $_POST['preferred_contact_method'] );

        // Basic validation
        if ( empty( $issue_description ) || empty( $device_type ) || empty( $device_model ) || empty( $preferred_contact_method ) ) {
            wp_die( __( 'Please fill in all required fields.', 'workrequest' ) );
        }

        // Create the new Repair Request Post
        $post_data = [
            'post_title'   => sprintf( __( '%s - %s Repair Request', 'workrequest' ), $device_type, $device_model ),
            'post_content' => $issue_description,
            'post_status'  => 'publish', // Set to 'publish' so it appears in admin list immediately. We can change this to 'pending' later if needed.
            'post_type'    => 'repair_request',
            'post_author'  => get_current_user_id(),
        ];

        $post_id = wp_insert_post( $post_data );

        if ( is_wp_error( $post_id ) ) {
            wp_die( __( 'Error submitting request: ', 'workrequest' ) . $post_id->get_error_message() );
        }

        // Save Custom Fields using update_post_meta (standard WordPress function)
        if ( $post_id ) {
            update_post_meta( $post_id, 'wr_device_type', $device_type );
            update_post_meta( $post_id, 'wr_device_model', $device_model );
            update_post_meta( $post_id, 'wr_serial_number', $serial_number );
            update_post_meta( $post_id, 'wr_preferred_contact_method', $preferred_contact_method );
            update_post_meta( $post_id, 'wr_request_status', 'pending_review' ); // Explicitly set status to pending_review
            update_post_meta( $post_id, 'wr_is_converted_to_order', false ); // Default to false
            // Note: operator_notes, estimated_cost_range, linked_order_id are not set here by user.
        }

        // Handle Attachments (Gallery Field)
        // This part needs wp_get_attachment_image_src to work correctly if ACF is used to *read* the data later.
        // The save process below creates standard WordPress attachments.
        if ( ! empty( $_FILES['wr_attachments']['name'][0] ) ) {
            $attachment_ids = [];
            
            // WordPress's media_handle_upload requires these files to be loaded
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/media.php' );

            // Loop through each uploaded file
            $files_to_upload = [];
            foreach ( $_FILES['wr_attachments']['name'] as $key => $value ) {
                if ( ! empty( $value ) ) {
                    $files_to_upload[] = [
                        'name'     => $_FILES['wr_attachments']['name'][$key],
                        'type'     => $_FILES['wr_attachments']['type'][$key],
                        'tmp_name' => $_FILES['wr_attachments']['tmp_name'][$key],
                        'error'    => $_FILES['wr_attachments']['error'][$key],
                        'size'     => $_FILES['wr_attachments']['size'][$key],
                    ];
                }
            }

            // Temporarily set $_FILES global to process one file at a time
            $original_files = $_FILES; // Store original $_FILES
            foreach ( $files_to_upload as $file_data ) {
                $_FILES['wr_attachments_single'] = $file_data; // Use a temporary key for media_handle_upload

                $attachment_id = media_handle_upload( 'wr_attachments_single', $post_id );

                if ( ! is_wp_error( $attachment_id ) ) {
                    $attachment_ids[] = $attachment_id;
                } else {
                    // Log file upload error
                    error_log( 'WorkRequest: Error uploading file: ' . $attachment_id->get_error_message() );
                }
            }
            $_FILES = $original_files; // Restore original $_FILES

            if ( ! empty( $attachment_ids ) ) {
                // ACF Gallery field expects an array of IDs, so update_post_meta directly stores it.
                update_post_meta( $post_id, 'wr_attachments', $attachment_ids );
            }
        }

        // Redirect after successful submission
        wp_redirect( add_query_arg( 'request_submitted', 'true', home_url( '/my-repair-requests/' ) ) );
        exit;
    }
}
add_action( 'template_redirect', 'workrequest_handle_repair_request_submission' ); // Use template_redirect to run before any output

/**
 * Shortcode to display the repair request form.
 */
function workrequest_display_repair_request_form() {
    ob_start(); // Start output buffering

    if ( ! is_user_logged_in() ) {
        echo '<p>' . esc_html__( 'Please log in to submit a repair request.', 'workrequest' ) . '</p>';
        echo wp_login_form( [ 'echo' => false ] ); // Display login form
        return ob_get_clean();
    }

    // Display success message if redirected from submission
    if ( isset( $_GET['request_submitted'] ) && $_GET['request_submitted'] == 'true' ) {
        echo '<div class="workrequest-success-message">';
        echo '<p>' . esc_html__( 'Your repair request has been submitted successfully! We will contact you soon.', 'workrequest' ) . '</p>';
        echo '</div>';
    }
    ?>

    <div class="workrequest-form-wrapper">
        <h2><?php esc_html_e( 'Submit a New Repair Request', 'workrequest' ); ?></h2>
        <form action="" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field( 'workrequest_repair_request_nonce' ); ?>

            <p>
                <label for="issue_description"><?php esc_html_e( 'Description of the Issue:', 'workrequest' ); ?> <span class="required">*</span></label><br>
                <textarea id="issue_description" name="issue_description" rows="5" required></textarea>
            </p>

            <p>
                <label for="device_type"><?php esc_html_e( 'Type of Device:', 'workrequest' ); ?> <span class="required">*</span></label><br>
                <input type="text" id="device_type" name="device_type" required>
            </p>

            <p>
                <label for="device_model"><?php esc_html_e( 'Device Model:', 'workrequest' ); ?> <span class="required">*</span></label><br>
                <input type="text" id="device_model" name="device_model" required>
            </p>

            <p>
                <label for="serial_number"><?php esc_html_e( 'Serial Number (Optional):', 'workrequest' ); ?></label><br>
                <input type="text" id="serial_number" name="serial_number">
            </p>

            <p>
                <label for="wr_attachments"><?php esc_html_e('Upload Photos/Videos (Optional):', 'workrequest'); ?></label><br>
                <input type="file" id="wr_attachments" name="wr_attachments[]" multiple accept="image/*,video/*">
                <small><?php esc_html_e('You can upload multiple images or video files.', 'workrequest'); ?></small>
            </p>

            <p>
                <label><?php esc_html_e( 'Preferred Contact Method:', 'workrequest' ); ?> <span class="required">*</span></label><br>
                <input type="radio" id="contact_phone" name="preferred_contact_method" value="phone" required>
                <label for="contact_phone"><?php esc_html_e( 'Phone', 'workrequest' ); ?></label><br>
                <input type="radio" id="contact_email" name="preferred_contact_method" value="email">
                <label for="contact_email"><?php esc_html_e( 'Email', 'workrequest' ); ?></label>
            </p>

            <p>
                <input type="submit" name="workrequest_repair_request_submit" value="<?php esc_attr_e( 'Submit Request', 'workrequest' ); ?>">
            </p>
        </form>
    </div>

    <?php
    return ob_get_clean(); // Return the buffered output
}
add_shortcode( 'workrequest_repair_form', 'workrequest_display_repair_request_form' );