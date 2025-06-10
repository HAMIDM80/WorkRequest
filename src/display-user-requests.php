<?php
/**
 * Frontend display for a logged-in user's submitted repair requests.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Shortcode to display a logged-in user's repair requests.
 */
add_shortcode( 'workrequest_user_requests_list', 'workrequest_display_user_requests_list' );
function workrequest_display_user_requests_list() {
    ob_start(); // Start output buffering

    if ( ! is_user_logged_in() ) {
        echo '<div class="workrequest-message notice notice-info"><p>' . esc_html__( 'Please log in to view your repair requests.', 'workrequest' ) . '</p></div>';
        echo wp_login_form( [ 'echo' => false ] );
        return ob_get_clean();
    }

    $current_user_id = get_current_user_id();

    $args = array(
        'post_type'      => 'repair_request',
        'author'         => $current_user_id,
        'posts_per_page' => -1, // Get all requests for the user
        'post_status'    => array( 'publish', 'pending_review', 'pending', 'draft' ), // Include relevant statuses
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    $user_requests = new WP_Query( $args );

    ?>
    <div class="workrequest-user-requests-wrapper">
        <h2><?php esc_html_e( 'My Repair Requests', 'workrequest' ); ?></h2>

        <?php
        $form_message = get_transient( 'workrequest_form_message' );
        $form_type = get_transient( 'workrequest_form_type' );

        if ( $form_message ) {
            echo '<div class="workrequest-message notice notice-' . esc_attr( $form_type ) . '"><p>' . esc_html( $form_message ) . '</p></div>';
            delete_transient( 'workrequest_form_message' );
            delete_transient( 'workrequest_form_type' );
        }
        ?>

        <?php if ( $user_requests->have_posts() ) : ?>
            <table class="workrequest-requests-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Request ID', 'workrequest' ); ?></th>
                        <th><?php esc_html_e( 'Device', 'workrequest' ); ?></th>
                        <th><?php esc_html_e( 'Issue Description', 'workrequest' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'workrequest' ); ?></th>
                        <th><?php esc_html_e( 'Submitted On', 'workrequest' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'workrequest' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ( $user_requests->have_posts() ) : $user_requests->the_post();
                        $request_id = get_the_ID();
                        $device_type = get_post_meta( $request_id, 'wr_device_type', true );
                        $device_model = get_post_meta( $request_id, 'wr_device_model', true );
                        $request_status = get_post_meta( $request_id, 'wr_request_status', true );
                        if ( empty( $request_status ) ) $request_status = 'pending_review'; // Default
                    ?>
                        <tr>
                            <td>#<?php echo absint( $request_id ); ?></td>
                            <td><?php echo esc_html( $device_type . ' ' . $device_model ); ?></td>
                            <td><?php echo wp_trim_words( get_the_content(), 10 ); ?></td>
                            <td><?php echo esc_html( workrequest_get_request_status_label( $request_status ) ); ?></td>
                            <td><?php echo esc_html( get_the_date() ); ?></td>
                            <td>
                                <a href="<?php echo esc_url( get_permalink( $request_id ) ); ?>" class="button button-small"><?php esc_html_e( 'View Details', 'workrequest' ); ?></a>
                                </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php wp_reset_postdata(); ?>
        <?php else : ?>
            <p><?php esc_html_e( 'You have not submitted any repair requests yet.', 'workrequest' ); ?></p>
            <p><a href="<?php echo esc_url( home_url( '/my-repair-requests/' ) ); ?>"><?php esc_html_e( 'Submit a new request.', 'workrequest' ); ?></a></p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}