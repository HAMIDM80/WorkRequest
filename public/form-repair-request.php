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
add_action( 'template_redirect', 'workrequest_handle_repair_request_submission' ); // Use template_redirect to run before any output
function workrequest_handle_repair_request_submission() {
    // Only process if it's a POST request and our form is submitted
    if ( isset( $_POST['workrequest_repair_request_submit'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'workrequest_repair_request_nonce' ) ) {

        // Ensure user is logged in
        if ( ! is_user_logged_in() ) {
            // Redirect to login page or show an error
            wp_safe_redirect( wp_login_url( home_url( '/my-repair-requests/' ) ) );
            exit;
        }

        // Sanitize and validate inputs
        $issue_description        = sanitize_textarea_field( $_POST['issue_description'] );
        $device_type              = sanitize_text_field( $_POST['device_type'] );
        $device_model             = sanitize_text_field( $_POST['device_model'] );
        $serial_number            = sanitize_text_field( $_POST['serial_number'] );
        $preferred_contact_method = sanitize_text_field( $_POST['preferred_contact_method'] );

        // Basic validation
        if ( empty( $issue_description ) || empty( $device_type ) || empty( $device_model ) || empty( $preferred_contact_method ) ) {
            // Set a transient or session variable for error message and redirect back
            set_transient( 'workrequest_form_message', __( 'Please fill in all required fields.', 'workrequest' ), 30 );
            set_transient( 'workrequest_form_type', 'error', 30 );
            wp_safe_redirect( wp_get_referer() ? wp_get_referer() : home_url( '/my-repair-requests/' ) );
            exit;
        }

        // Create the new Repair Request Post
        $post_data = [
            'post_title'   => sprintf( __( '%s - %s Repair Request', 'workrequest' ), $device_type, $device_model ),
            'post_content' => $issue_description,
            'post_status'  => 'publish', // Use 'publish' for now, or 'pending' if you have a custom post status
            'post_type'    => 'repair_request',
            'post_author'  => get_current_user_id(),
        ];

        $post_id = wp_insert_post( $post_data );

        if ( is_wp_error( $post_id ) ) {
            set_transient( 'workrequest_form_message', __( 'Error submitting request: ', 'workrequest' ) . $post_id->get_error_message(), 30 );
            set_transient( 'workrequest_form_type', 'error', 30 );
            wp_safe_redirect( wp_get_referer() ? wp_get_referer() : home_url( '/my-repair-requests/' ) );
            exit;
        }

        // Save Custom Fields (using update_post_meta, not ACF update_field directly)
        // Assuming you've moved from ACF fields to standard post meta for these,
        // or ACF fields are set up to save to these meta keys automatically.
        // If you are still using ACF, ensure ACF plugin is active and fields are correctly setup.
        if ( $post_id ) {
            update_post_meta( $post_id, 'wr_device_type', $device_type );
            update_post_meta( $post_id, 'wr_device_model', $device_model );
            update_post_meta( $post_id, 'wr_serial_number', $serial_number );
            update_post_meta( $post_id, 'wr_preferred_contact_method', $preferred_contact_method );
            update_post_meta( $post_id, 'wr_request_status', 'pending_review' ); // Explicitly set status
            update_post_meta( $post_id, 'wr_is_converted_to_order', false );
        }


        // Handle Attachments (Standard WordPress Media Upload)
        if ( ! empty( $_FILES['wr_attachments']['name'][0] ) ) {
            $attachment_ids = [];
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/media.php' );

            // Loop through each file in the $_FILES array structure
            foreach ( $_FILES['wr_attachments']['name'] as $key => $filename ) {
                if ( $_FILES['wr_attachments']['error'][$key] === UPLOAD_ERR_OK ) {
                    // Create a single $_FILES entry for media_handle_upload
                    $file_temp = [
                        'name'     => $_FILES['wr_attachments']['name'][$key],
                        'type'     => $_FILES['wr_attachments']['type'][$key],
                        'tmp_name' => $_FILES['wr_attachments']['tmp_name'][$key],
                        'error'    => $_FILES['wr_attachments']['error'][$key],
                        'size'     => $_FILES['wr_attachments']['size'][$key],
                    ];

                    // This is crucial: WordPress's media_handle_upload expects $_FILES to contain
                    // the single file data, not an array of files. We need to temporarily set it.
                    // Store the original $_FILES content to restore it later.
                    $original_files = $_FILES;
                    $_FILES = ['wr_attachments' => $file_temp];

                    $attachment_id = media_handle_upload( 'wr_attachments', $post_id );

                    // Restore the original $_FILES content
                    $_FILES = $original_files;

                    if ( ! is_wp_error( $attachment_id ) ) {
                        $attachment_ids[] = $attachment_id;
                    } else {
                        error_log( 'WorkRequest: Error uploading file: ' . $attachment_id->get_error_message() );
                        // Optionally set an error message for the user
                        set_transient( 'workrequest_form_message', __( 'Request submitted, but some files failed to upload.', 'workrequest' ), 30 );
                        set_transient( 'workrequest_form_type', 'warning', 30 );
                    }
                }
            }

            if ( ! empty( $attachment_ids ) ) {
                // Save attachment IDs as a post meta array
                update_post_meta( $post_id, 'wr_attachments', $attachment_ids );
            }
        }

        set_transient( 'workrequest_form_message', __( 'Your repair request has been submitted successfully! We will contact you soon.', 'workrequest' ), 30 );
        set_transient( 'workrequest_form_type', 'success', 30 );
        wp_safe_redirect( home_url( '/my-repair-requests/' ) );
        exit;
    }
}

/**
 * Shortcode to display the repair request form.
 */
add_shortcode( 'workrequest_repair_form', 'workrequest_display_repair_request_form' );
function workrequest_display_repair_request_form() {
    ob_start(); // Start output buffering

    if ( ! is_user_logged_in() ) {
        echo '<div class="workrequest-message notice notice-info"><p>' . esc_html__( 'Please log in to submit a repair request.', 'workrequest' ) . '</p></div>';
        echo wp_login_form( [ 'echo' => false ] ); // Display login form
        return ob_get_clean();
    }

    // Display messages (success/error/warning)
    $form_message = get_transient( 'workrequest_form_message' );
    $form_type = get_transient( 'workrequest_form_type' );

    if ( $form_message ) {
        echo '<div class="workrequest-message notice notice-' . esc_attr( $form_type ) . '"><p>' . esc_html( $form_message ) . '</p></div>';
        delete_transient( 'workrequest_form_message' );
        delete_transient( 'workrequest_form_type' );
    }
    ?>

    <div class="workrequest-form-wrapper">
        <h2><?php esc_html_e( 'Submit a New Repair Request', 'workrequest' ); ?></h2>
        <form action="" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field( 'workrequest_repair_request_nonce' ); ?>

            <p class="form-field">
                <label for="issue_description"><?php esc_html_e( 'Description of the Issue:', 'workrequest' ); ?> <span class="required">*</span></label><br>
                <textarea id="issue_description" name="issue_description" rows="5" required></textarea>
            </p>

            <p class="form-field">
                <label for="device_type"><?php esc_html_e( 'Type of Device:', 'workrequest' ); ?> <span class="required">*</span></label><br>
                <input type="text" id="device_type" name="device_type" required>
            </p>

            <p class="form-field">
                <label for="device_model"><?php esc_html_e( 'Device Model:', 'workrequest' ); ?> <span class="required">*</span></label><br>
                <input type="text" id="device_model" name="device_model" required>
            </p>

            <p class="form-field">
                <label for="serial_number"><?php esc_html_e( 'Serial Number (Optional):', 'workrequest' ); ?></label><br>
                <input type="text" id="serial_number" name="serial_number">
            </p>

            <p class="form-field">
                <label for="wr_attachments"><?php esc_html_e('Upload Photos/Videos (Optional):', 'workrequest'); ?></label><br>
                <input type="file" id="wr_attachments" name="wr_attachments[]" multiple accept="image/*,video/*">
                <small><?php esc_html_e('You can upload multiple images or video files.', 'workrequest'); ?></small>
            </p>

            <p class="form-field">
                <label><?php esc_html_e( 'Preferred Contact Method:', 'workrequest' ); ?> <span class="required">*</span></label><br>
                <input type="radio" id="contact_phone" name="preferred_contact_method" value="phone" required>
                <label for="contact_phone"><?php esc_html_e( 'Phone', 'workrequest' ); ?></label><br>
                <input type="radio" id="contact_email" name="preferred_contact_method" value="email">
                <label for="contact_email"><?php esc_html_e( 'Email', 'workrequest' ); ?></label>
            </p>

            <p class="form-field">
                <input type="submit" name="workrequest_repair_request_submit" value="<?php esc_attr_e( 'Submit Request', 'workrequest' ); ?>" class="button">
            </p>
        </form>
    </div>

    <?php
    return ob_get_clean(); // Return the buffered output
}