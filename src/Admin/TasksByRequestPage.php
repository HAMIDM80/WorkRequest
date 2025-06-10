<?php
// src/Admin/TasksByRequestPage.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WorkRequest_Admin_TasksByRequestPage {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu_page' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_workrequest_create_tasks_from_notes', array( $this, 'create_tasks_from_notes_ajax_handler' ) );
    }

    public function enqueue_scripts( $hook_suffix ) {
        if ( 'repair_request_page_tasks-by-request' !== $hook_suffix ) {
            return;
        }

        wp_enqueue_script( 'workrequest-tasks-by-request', WORKREQUEST_PLUGIN_URL . 'assets/js/tasks-by-request.js', array( 'jquery' ), WORKREQUEST_PLUGIN_VERSION, true );
        wp_localize_script( 'workrequest-tasks-by-request', 'workrequest_tasks_ajax_object', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'security' => wp_create_nonce( 'workrequest_create_tasks_nonce' ),
            'create_tasks_confirm' => __( 'Are you sure you want to create tasks from the notes for this repair request? Existing tasks for this request might be affected or duplicated if run multiple times without care.', 'workrequest' ),
            'assign_task_confirm' => __( 'Are you sure you want to assign these tasks?', 'workrequest' ),
        ) );

        wp_enqueue_style( 'workrequest-admin-style', WORKREQUEST_PLUGIN_URL . 'assets/css/admin-style.css', array(), WORKREQUEST_PLUGIN_VERSION );
    }

    /**
     * Add the "Tasks by Request" submenu page under "Repair Requests".
     */
    public function add_admin_menu_page() {
        add_submenu_page(
            'edit.php?post_type=repair_request', // Parent slug for 'Repair Requests' CPT
            __( 'Tasks By Request', 'workrequest' ), // Page title
            __( 'Tasks By Request', 'workrequest' ), // Menu title
            'manage_repair_requests', // Capability required to access this page
            'tasks-by-request', // Menu slug
            array( $this, 'render_page_content' ) // Callback function to render the page content
        );
    }

    /**
     * Renders the content of the "Tasks by Request" admin page.
     */
    public function render_page_content() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'Tasks By Request', 'workrequest' ); ?></h1>
            <p><?php _e( 'Manage tasks for each repair request.', 'workrequest' ); ?></p>

            <?php
            $args = array(
                'post_type'      => 'repair_request',
                'posts_per_page' => -1,
                'post_status'    => array( 'publish', 'wc-processing' ), // Include published and orders in process
                'meta_query'     => array(
                    array(
                        'key'     => '_workrequest_operator_notes',
                        'compare' => 'EXISTS',
                    ),
                    array(
                        'key'     => '_workrequest_operator_notes',
                        'value'   => '',
                        'compare' => '!=',
                    ),
                ),
            );

            $repair_requests = new WP_Query( $args );

            // Get available technicians once
            $technicians = get_users( array( 'role' => 'repair_technician', 'fields' => array( 'ID', 'display_name' ) ) );
            $technician_options = '<option value="0">' . __( 'Unassigned', 'workrequest' ) . '</option>';
            foreach ( $technicians as $tech ) {
                $technician_options .= '<option value="' . esc_attr( $tech->ID ) . '">' . esc_html( $tech->display_name ) . '</option>';
            }

            if ( $repair_requests->have_posts() ) {
                ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e( 'Request ID', 'workrequest' ); ?></th>
                            <th><?php _e( 'Request Title', 'workrequest' ); ?></th>
                            <th><?php _e( 'Tasks', 'workrequest' ); ?></th>
                            <th><?php _e( 'Total Task Cost', 'workrequest' ); ?></th>
                            <th><?php _e( 'Actions', 'workrequest' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while ( $repair_requests->have_posts() ) {
                            $repair_requests->the_post();
                            $request_id = get_the_ID();
                            $operator_notes = get_post_meta( $request_id, '_workrequest_operator_notes', true );
                            $task_lines = array_filter( array_map( 'trim', explode( "\n", $operator_notes ) ) );

                            // Fetch existing tasks for this request
                            $existing_tasks = get_posts( array(
                                'post_type'      => 'task',
                                'posts_per_page' => -1,
                                'meta_key'       => '_workrequest_related_request_id',
                                'meta_value'     => $request_id,
                                'post_status'    => 'any',
                                'orderby'        => 'ID',
                                'order'          => 'ASC'
                            ) );

                            $total_task_cost = 0;
                            ?>
                            <tr id="request-row-<?php echo esc_attr( $request_id ); ?>">
                                <td>#<?php echo esc_html( $request_id ); ?></td>
                                <td><a href="<?php echo esc_url( get_edit_post_link( $request_id ) ); ?>"><?php the_title(); ?></a></td>
                                <td class="workrequest-tasks-list">
                                    <table class="tasks-sub-table widefat fixed striped">
                                        <thead>
                                            <tr>
                                                <th><?php _e( 'Task Description', 'workrequest' ); ?></th>
                                                <th style="width:120px;"><?php _e( 'Cost', 'workrequest' ); ?></th>
                                                <th style="width:180px;"><?php _e( 'Assignee', 'workrequest' ); ?></th>
                                                <th><?php _e( 'Status', 'workrequest' ); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody id="tasks-for-request-<?php echo esc_attr( $request_id ); ?>">
                                            <?php
                                            if ( ! empty( $existing_tasks ) ) {
                                                foreach ( $existing_tasks as $task ) {
                                                    $task_cost = get_post_meta( $task->ID, '_workrequest_task_cost', true );
                                                    $assigned_tech_id = get_post_meta( $task->ID, '_workrequest_assigned_technician_id', true );
                                                    $task_status = get_post_meta( $task->ID, '_workrequest_task_status', true );
                                                    $total_task_cost += floatval( $task_cost );
                                                    ?>
                                                    <tr data-task_id="<?php echo esc_attr( $task->ID ); ?>" data-is_existing="true">
                                                        <td><a href="<?php echo esc_url( get_edit_post_link( $task->ID ) ); ?>"><?php echo esc_html( $task->post_title ); ?></a></td>
                                                        <td><input type="number" step="0.01" min="0" class="task-cost small-text" value="<?php echo esc_attr( $task_cost ); ?>" disabled /></td>
                                                        <td>
                                                            <select class="task-assignee" disabled>
                                                                <?php echo $technician_options; // Re-use options ?>
                                                                <script>jQuery(document).ready(function($){ $('#tasks-for-request-<?php echo esc_attr( $request_id ); ?> tr[data-task_id="<?php echo esc_attr( $task->ID ); ?>"] .task-assignee').val('<?php echo esc_attr( $assigned_tech_id ); ?>'); });</script>
                                                            </select>
                                                        </td>
                                                        <td><?php echo esc_html( WorkRequest_CPT_Task::get_task_status_label( $task_status ) ); ?></td>
                                                    </tr>
                                                    <?php
                                                }
                                            } else {
                                                // Display notes as potential tasks if no tasks exist yet
                                                if ( ! empty( $task_lines ) ) {
                                                    foreach ( $task_lines as $index => $note_line ) {
                                                        ?>
                                                        <tr data-request_id="<?php echo esc_attr( $request_id ); ?>" data-task_index="<?php echo esc_attr( $index ); ?>" data-is_existing="false">
                                                            <td><?php echo esc_html( $note_line ); ?><input type="hidden" class="task-description-hidden" value="<?php echo esc_attr( $note_line ); ?>" /></td>
                                                            <td><input type="number" step="0.01" min="0" class="task-cost small-text" value="0.00" /></td>
                                                            <td>
                                                                <select class="task-assignee">
                                                                    <?php echo $technician_options; ?>
                                                                </select>
                                                            </td>
                                                            <td><?php _e( 'Pending Creation', 'workrequest' ); ?></td>
                                                        </tr>
                                                        <?php
                                                    }
                                                } else {
                                                    echo '<tr class="no-tasks-row"><td colspan="4">' . esc_html__( 'No tasks defined for this request yet.', 'workrequest' ) . '</td></tr>';
                                                }
                                            }
                                            ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="2" style="text-align: right;"><?php _e( 'Total Cost for Tasks:', 'workrequest' ); ?></th>
                                                <th class="total-task-cost-display" data-total_cost="<?php echo esc_attr( $total_task_cost ); ?>"><strong><?php echo wc_price( $total_task_cost ); ?></strong></th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </td>
                                <td>
                                    <?php if ( empty( $existing_tasks ) && ! empty( $task_lines ) ) : ?>
                                        <button type="button" class="button button-primary create-tasks-btn" data-request_id="<?php echo esc_attr( $request_id ); ?>">
                                            <?php esc_html_e( 'Create Tasks', 'workrequest' ); ?>
                                        </button>
                                    <?php elseif ( empty( $task_lines ) ) : ?>
                                        <p><?php _e( 'No notes to create tasks from.', 'workrequest' ); ?></p>
                                    <?php else: ?>
                                        <p><?php _e( 'Tasks already created.', 'workrequest' ); ?></p>
                                    <?php endif; ?>
                                    <div id="task-status-<?php echo esc_attr( $request_id ); ?>" class="workrequest-task-status-display"></div>
                                </td>
                            </tr>
                            <?php
                        }
                        wp_reset_postdata();
                        ?>
                    </tbody>
                </table>
                <?php
            } else {
                echo '<p>' . esc_html__( 'No repair requests found with operator notes for task creation.', 'workrequest' ) . '</p>';
            }
            ?>
        </div>
        <?php
    }

    /**
     * AJAX handler to create tasks from operator notes.
     */
    public function create_tasks_from_notes_ajax_handler() {
        check_ajax_referer( 'workrequest_create_tasks_nonce', 'security' );

        if ( ! current_user_can( 'manage_repair_requests' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to create tasks.', 'workrequest' ) ) );
        }

        $request_id = isset( $_POST['request_id'] ) ? absint( $_POST['request_id'] ) : 0;
        $tasks_data = isset( $_POST['tasks_data'] ) ? json_decode( wp_unslash( $_POST['tasks_data'] ), true ) : array();

        if ( empty( $request_id ) || empty( $tasks_data ) ) {
            wp_send_json_error( array( 'message' => __( 'Missing Request ID or Task Data.', 'workrequest' ) ) );
        }

        $repair_request = get_post( $request_id );
        if ( ! $repair_request || 'repair_request' !== $repair_request->post_type ) {
            wp_send_json_error( array( 'message' => __( 'Invalid Repair Request ID.', 'workrequest' ) ) );
        }

        $created_tasks_count = 0;
        $created_task_ids = [];

        foreach ( $tasks_data as $task_item ) {
            $task_title = sanitize_text_field( $task_item['description'] );
            $task_cost = floatval( $task_item['cost'] );
            $assigned_to = absint( $task_item['assignee_id'] );

            if ( empty( $task_title ) ) {
                continue;
            }

            $new_task_id = wp_insert_post( array(
                'post_title'    => $task_title,
                'post_content'  => '', // Can be extended later if needed
                'post_status'   => 'publish', // Or 'pending'
                'post_type'     => 'task',
                'post_author'   => get_current_user_id(), // The operator creating the task
            ) );

            if ( ! is_wp_error( $new_task_id ) ) {
                update_post_meta( $new_task_id, '_workrequest_related_request_id', $request_id );
                update_post_meta( $new_task_id, '_workrequest_task_cost', $task_cost );
                update_post_meta( $new_task_id, '_workrequest_assigned_technician_id', $assigned_to );
                update_post_meta( $new_task_id, '_workrequest_task_status', ( $assigned_to > 0 ? 'assigned' : 'pending' ) ); // Set initial status

                // Initialize approval statuses
                update_post_meta( $new_task_id, '_workrequest_approval_technician', 'no' );
                update_post_meta( $new_task_id, '_workrequest_approval_warehouse', 'no' );
                update_post_meta( $new_task_id, '_workrequest_approval_owner', 'no' );
                update_post_meta( $new_task_id, '_workrequest_approval_quality', 'no' );

                $created_tasks_count++;
                $created_task_ids[] = $new_task_id;
            } else {
                error_log( 'Error creating task: ' . $new_task_id->get_error_message() );
            }
        }

        if ( $created_tasks_count > 0 ) {
            // Optionally, clear the operator notes from the repair request after tasks are created
            // update_post_meta( $request_id, '_workrequest_operator_notes', '' );
            wp_send_json_success( array(
                'message'          => sprintf( __( '%d tasks created successfully for Request #%d.', 'workrequest' ), $created_tasks_count, $request_id ),
                'created_task_ids' => $created_task_ids,
                'reload_page'      => true, // Indicate that the page should be reloaded to show updated task list
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'No tasks were created.', 'workrequest' ) ) );
        }

        wp_die();
    }
}