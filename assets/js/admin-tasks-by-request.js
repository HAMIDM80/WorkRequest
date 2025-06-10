jQuery(document).ready(function($) {
    // Handle AJAX for approval checkboxes
    $('.workrequest-approval-checkbox').on('change', function() {
        var $checkbox = $(this);
        var taskId = $checkbox.data('task-id');
        var approvalType = $checkbox.data('approval-type');
        var isApproved = $checkbox.is(':checked') ? 1 : 0;
        var $statusMessage = $checkbox.closest('.workrequest-checkbox-label').find('.status-message');

        // Show loading message
        $statusMessage.removeClass('success error').addClass('loading').text(WorkRequestTasksByRequest.loading_text);
        $checkbox.prop('disabled', true); // Disable checkbox during AJAX call

        $.ajax({
            url: WorkRequestTasksByRequest.ajax_url,
            type: 'POST',
            data: {
                action: 'workrequest_update_task_approval',
                task_id: taskId,
                approval_type: approvalType,
                is_approved: isApproved,
                _wpnonce: WorkRequestTasksByRequest.update_approval_nonce
            },
            success: function(response) {
                if (response.success) {
                    $statusMessage.removeClass('loading error').addClass('success').text(WorkRequestTasksByRequest.success_text);
                    // Optionally, you might want to remove the message after a short delay
                    setTimeout(function() {
                        $statusMessage.text(''); // Clear message
                        // Or show a permanent icon if needed
                    }, 2000);
                } else {
                    $statusMessage.removeClass('loading success').addClass('error').text(WorkRequestTasksByRequest.error_text + (response.data ? ': ' + response.data : ''));
                }
            },
            error: function(xhr, status, error) {
                $statusMessage.removeClass('loading success').addClass('error').text(WorkRequestTasksByRequest.error_text + ': ' + error);
                console.error("AJAX Error: ", status, error, xhr);
            },
            complete: function() {
                $checkbox.prop('disabled', false); // Re-enable checkbox after AJAX call
            }
        });
    });
});