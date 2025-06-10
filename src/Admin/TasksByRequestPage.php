<?php
/**
 * Custom Admin Page: Tasks by Request
 * Displays repair requests and their associated tasks with approval checkboxes.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Renders the main "Tasks by Request" admin page.
 */
function workrequest_render_tasks_by_request_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Tasks by Repair Request', 'workrequest' ); ?></h1>

        <?php
        $request_id = isset( $_GET['request_id'] ) ? absint( $_GET['request_id'] ) : 0;

        if ( $request_id ) {
            // Display tasks for a specific request
            workrequest_display_single_request_tasks( $request_id );
        } else {
            // Display a list of all repair requests
            workrequest_display_all_repair_requests_list();
        }
        ?>
    </div>
    <?php
}

/**
 * Displays a list of all repair requests.
 */
function workrequest_display_all_repair_requests_list() {
    // Get all repair_request posts
    $args = array(
        'post_type'      => 'repair_request',
        'posts_per_page' => -1, // Get all posts
        'post_status'    => 'publish', // Or other relevant statuses
        'orderby'        => 'date',
        'order'          => 'DESC',
    );
    $requests = new WP_Query( $args );

    if ( $requests->have_posts() ) {
        ?>
        <p class="description"><?php esc_html_e( 'Select a Repair Request to view and manage its associated tasks and their approval statuses.', 'workrequest' ); ?></p>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-title column-primary"><?php esc_html_e( 'Request ID', 'workrequest' ); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e( 'Request Title', 'workrequest' ); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e( 'Customer', 'workrequest' ); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e( 'Date Created', 'workrequest' ); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e( 'Total Tasks', 'workrequest' ); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e( 'Actions', 'workrequest' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ( $requests->have_posts() ) : $requests->the_post();
                    $request_id = get_the_ID();
                    $customer_id = get_post_field( 'post_author', $request_id );
                    $customer_info = get_userdata( $customer_id );
                    $customer_display_name = $customer_info ? $customer_info->display_name : __( 'Guest', 'workrequest' );
                    $linked_tasks = get_post_meta( $request_id, '_workrequest_linked_tasks', true );
                    $task_count = is_array( $linked_tasks ) ? count( $linked_tasks ) : 0;
                    ?>
                    <tr>
                        <td class="title column-title has-row-actions column-primary">
                            <strong>#<?php echo absint( $request_id ); ?></strong>
                            <button type="button" class="toggle-row"><span class="screen-reader-text"><?php esc_html_e( 'Show more details', 'workrequest' ); ?></span></button>
                        </td>
                        <td><?php the_title(); ?></td>
                        <td><?php echo esc_html( $customer_display_name ); ?></td>
                        <td><?php echo esc_html( get_the_date() ); ?></td>
                        <td><?php echo absint( $task_count ); ?></td>
                        <td>
                            <a href="<?php echo esc_url( add_query_arg( 'request_id', $request_id, admin_url( 'admin.php?page=workrequest-tasks-by-request' ) ) ); ?>" class="button button-primary">
                                <?php esc_html_e( 'View Tasks', 'workrequest' ); ?>
                            </a>
                            <a href="<?php echo esc_url( get_edit_post_link( $request_id ) ); ?>" class="button">
                                <?php esc_html_e( 'Edit Request', 'workrequest' ); ?>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php
        wp_reset_postdata();
    } else {
        echo '<p>' . esc_html__( 'No Repair Requests found.', 'workrequest' ) . '</p>';
    }
}

/**
 * Displays tasks for a specific repair request.
 *
 * @param int $request_id The ID of the repair request.
 */
function workrequest_display_single_request_tasks( $request_id ) {
    $request_title = get_the_title( $request_id );
    if ( ! $request_title ) {
        echo '<p>' . esc_html__( 'Invalid Repair Request ID.', 'workrequest' ) . '</p>';
        return;
    }

    $back_link = admin_url( 'admin.php?page=workrequest-tasks-by-request' );

    ?>
    <h2>
        <?php
        echo sprintf(
            /* translators: %1$d: Repair Request ID, %2$s: Repair Request Title */
            __( 'Tasks for Request #%1$d: %2$s', 'workrequest' ),
            absint( $request_id ),
            esc_html( $request_title )
        );
        ?>
        <a href="<?php echo esc_url( $back_link ); ?>" class="page-title-action"><?php esc_html_e( '← Back to All Requests', 'workrequest' ); ?></a>
        <a href="<?php echo esc_url( get_edit_post_link( $request_id ) ); ?>" class="page-title-action"><?php esc_html_e( 'Edit Repair Request', 'workrequest' ); ?></a>
    </h2>

    <?php
    $linked_task_ids = get_post_meta( $request_id, '_workrequest_linked_tasks', true );
    if ( ! is_array( $linked_task_ids ) ) {
        $linked_task_ids = array();
    }

    if ( empty( $linked_task_ids ) ) {
        echo '<p>' . esc_html__( 'No tasks associated with this Repair Request.', 'workrequest' ) . '</p>';
        return;
    }

    // Filter out deleted/trashed tasks if any, and get them in order
    $valid_task_ids = array();
    foreach ( $linked_task_ids as $task_id ) {
        if ( get_post_status( $task_id ) === 'publish' ) { // Only show published tasks
            $valid_task_ids[] = $task_id;
        }
    }

    if ( empty( $valid_task_ids ) ) {
        echo '<p>' . esc_html__( 'No active tasks associated with this Repair Request.', 'workrequest' ) . '</p>';
        return;
    }

    $tasks_query_args = array(
        'post_type'      => 'task',
        'post__in'       => $valid_task_ids,
        'posts_per_page' => -1,
        'orderby'        => 'post__in', // Maintain the order of linked_task_ids
        'post_status'    => 'publish',
    );
    $tasks = new WP_Query( $tasks_query_args );

    if ( $tasks->have_posts() ) {
        ?>
        <table class="wp-list-table widefat fixed striped workrequest-task-approvals">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-id">ID</th>
                    <th scope="col" class="manage-column column-task-title"><?php esc_html_e( 'Task', 'workrequest' ); ?></th>
                    <th scope="col" class="manage-column column-assignee"><?php esc_html_e( 'Assigned', 'workrequest' ); ?></th>
                    <th scope="col" class="manage-column column-status"><?php esc_html_e( 'Status', 'workrequest' ); ?></th>
                    <th scope="col" class="manage-column column-approval"><?php esc_html_e( 'Storage Approved', 'workrequest' ); ?></th>
                    <th scope="col" class="manage-column column-approval"><?php esc_html_e( 'Operator Approved', 'workrequest' ); ?></th>
                    <th scope="col" class="manage-column column-approval"><?php esc_html_e( 'Owner Approved', 'workrequest' ); ?></th>
                    <th scope="col" class="manage-column column-approval"><?php esc_html_e( 'QC Approved', 'workrequest' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ( $tasks->have_posts() ) : $tasks->the_post();
                    $task_id = get_the_ID();
                    $assignee_id = get_post_meta( $task_id, '_workrequest_task_assignee_id', true );
                    $assignee_name = $assignee_id ? get_userdata( $assignee_id )->display_name : __( 'Unassigned', 'workrequest' );
                    $task_status = get_post_meta( $task_id, '_workrequest_task_status', true );
                    $task_status_label = workrequest_get_task_status_label( $task_status );

                    // Get approval statuses
                    $storage_approved = get_post_meta( $task_id, '_workrequest_task_storage_approved', true );
                    $operator_approved = get_post_meta( $task_id, '_workrequest_task_operator_approved', true );
                    $owner_approved = get_post_meta( $task_id, '_workrequest_task_owner_approved', true );
                    $qc_approved = get_post_meta( $task_id, '_workrequest_task_qc_approved', true );
                    ?>
                    <tr>
                        <td data-colname="ID">
                            #<?php echo absint( $task_id ); ?>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo esc_url( get_edit_post_link( $task_id ) ); ?>"><?php esc_html_e( 'Edit', 'workrequest' ); ?></a> |
                                </span>
                                <span class="view">
                                    <a href="<?php echo esc_url( get_permalink( $task_id ) ); ?>"><?php esc_html_e( 'View', 'workrequest' ); ?></a>
                                </span>
                            </div>
                        </td>
                        <td data-colname="Task"><strong><?php the_title(); ?></strong></td>
                        <td data-colname="Assigned"><?php echo esc_html( $assignee_name ); ?></td>
                        <td data-colname="Status"><?php echo esc_html( $task_status_label ); ?></td>
                        <td data-colname="Storage Approved">
                            <label class="workrequest-checkbox-label">
                                <input type="checkbox" class="workrequest-approval-checkbox"
                                       data-task-id="<?php echo absint( $task_id ); ?>"
                                       data-approval-type="storage_approved"
                                       <?php checked( $storage_approved, '1' ); ?>
                                       <?php disabled( ! current_user_can( 'manage_options' ), true ); ?> />
                                <span class="checkbox-indicator"></span>
                                <span class="status-message"></span>
                            </label>
                        </td>
                        <td data-colname="Operator Approved">
                            <label class="workrequest-checkbox-label">
                                <input type="checkbox" class="workrequest-approval-checkbox"
                                       data-task-id="<?php echo absint( $task_id ); ?>"
                                       data-approval-type="operator_approved"
                                       <?php checked( $operator_approved, '1' ); ?>
                                       <?php disabled( ! current_user_can( 'manage_options' ), true ); ?> />
                                <span class="checkbox-indicator"></span>
                                <span class="status-message"></span>
                            </label>
                        </td>
                        <td data-colname="Owner Approved">
                            <label class="workrequest-checkbox-label">
                                <input type="checkbox" class="workrequest-approval-checkbox"
                                       data-task-id="<?php echo absint( $task_id ); ?>"
                                       data-approval-type="owner_approved"
                                       <?php checked( $owner_approved, '1' ); ?>
                                       <?php disabled( ! current_user_can( 'manage_options' ), true ); ?> />
                                <span class="checkbox-indicator"></span>
                                <span class="status-message"></span>
                            </label>
                        </td>
                        <td data-colname="QC Approved">
                            <label class="workrequest-checkbox-label">
                                <input type="checkbox" class="workrequest-approval-checkbox"
                                       data-task-id="<?php echo absint( $task_id ); ?>"
                                       data-approval-type="qc_approved"
                                       <?php checked( $qc_approved, '1' ); ?>
                                       <?php disabled( ! current_user_can( 'manage_options' ), true ); ?> />
                                <span class="checkbox-indicator"></span>
                                <span class="status-message"></span>
                            </label>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php
        wp_reset_postdata();
    } else {
        echo '<p>' . esc_html__( 'No tasks found for this Repair Request.', 'workrequest' ) . '</p>';
    }
}