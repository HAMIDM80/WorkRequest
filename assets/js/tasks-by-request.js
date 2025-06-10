// assets/js/tasks-by-request.js

jQuery(document).ready(function($) {

    // Function to update the grand total for tasks in a request
    function updateTasksGrandTotal($totalDisplayElement) {
        var grandTotal = 0;
        $totalDisplayElement.closest('.tasks-sub-table').find('tbody tr').each(function() {
            var $row = $(this);
            var taskCost = parseFloat($row.find('.task-cost').val());

            if (!isNaN(taskCost)) {
                grandTotal += taskCost;
            }
        });
        $totalDisplayElement.find('strong').text(wc_price_format(grandTotal));
        $totalDisplayElement.data('total_cost', grandTotal);
    }

    // Helper for simple price formatting (replicates basic wc_price functionality)
    function wc_price_format(price) {
        var currency_symbol = '<?php echo get_woocommerce_currency_symbol(); ?>'; // Ensure this is localized or fetched
        var price_decimals = <?php echo wc_get_price_decimals(); ?>; // Ensure this is localized or fetched
        return currency_symbol + parseFloat(price).toFixed(price_decimals);
    }

    // Handle change on task cost input to update total
    $('.tasks-sub-table').on('input', '.task-cost', function() {
        var $totalDisplayElement = $(this).closest('.tasks-sub-table').find('.total-task-cost-display');
        updateTasksGrandTotal($totalDisplayElement);
    });

    // Handle click on "Create Tasks" button
    $('.create-tasks-btn').on('click', function() {
        var $button = $(this);
        var request_id = $button.data('request_id');
        var $statusDiv = $('#task-status-' + request_id);
        var $tasksSubTable = $button.closest('tr').find('.tasks-sub-table');

        if (!confirm(workrequest_tasks_ajax_object.create_tasks_confirm)) {
            return;
        }

        $button.prop('disabled', true).text('<?php esc_html_e( 'Creating...', 'workrequest' ); ?>');
        $statusDiv.html('<span class="spinner is-active"></span> <?php esc_html_e( 'Processing...', 'workrequest' ); ?>').show();

        var tasks_data = [];
        $tasksSubTable.find('tbody tr[data-is_existing="false"]').each(function() {
            var $row = $(this);
            var description = $row.find('.task-description-hidden').val();
            var cost = $row.find('.task-cost').val();
            var assignee_id = $row.find('.task-assignee').val();

            tasks_data.push({
                description: description,
                cost: cost,
                assignee_id: assignee_id
            });
        });

        if (tasks_data.length === 0) {
            $statusDiv.html('<span class="dashicons dashicons-info" style="color: blue;"></span> <?php esc_html_e( 'No new tasks to create.', 'workrequest' ); ?>').css('color', 'blue').show();
            $button.prop('disabled', false).text('<?php esc_html_e( 'Create Tasks', 'workrequest' ); ?>');
            return;
        }

        $.ajax({
            url: workrequest_tasks_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'workrequest_create_tasks_from_notes',
                security: workrequest_tasks_ajax_object.security,
                request_id: request_id,
                tasks_data: JSON.stringify(tasks_data)
            },
            success: function(response) {
                if (response.success) {
                    $statusDiv.html('<span class="dashicons dashicons-yes" style="color: green;"></span> ' + response.data.message).css('color', 'green');
                    if (response.data.reload_page) {
                        setTimeout(function() {
                            location.reload(); // Reload to show newly created tasks as existing
                        }, 1500);
                    }
                } else {
                    $statusDiv.html('<span class="dashicons dashicons-no" style="color: red;"></span> ' + response.data.message).css('color', 'red');
                    $button.prop('disabled', false).text('<?php esc_html_e( 'Create Tasks', 'workrequest' ); ?>');
                }
            },
            error: function(xhr, status, error) {
                $statusDiv.html('<span class="dashicons dashicons-no" style="color: red;"></span> <?php esc_html_e( 'AJAX error:', 'workrequest' ); ?> ' + error).css('color', 'red');
                $button.prop('disabled', false).text('<?php esc_html_e( 'Create Tasks', 'workrequest' ); ?>');
            }
        });
    });

    // Initial total amount calculation for all requests when page loads
    $('.total-task-cost-display').each(function() {
        updateTasksGrandTotal($(this));
    });

});