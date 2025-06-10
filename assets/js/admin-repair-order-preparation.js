// assets/js/admin-repair-order-preparation.js
jQuery(function($) {
    console.log('admin-repair-order-preparation.js loaded and executing.');

    if (typeof WorkRequestOrderPreparation === 'undefined') {
        console.error('WorkRequestOrderPreparation object not found. wp_localize_script might not be working correctly for this page.');
        return;
    }

    console.log('WorkRequestOrderPreparation data:', WorkRequestOrderPreparation);

    // Handle click on "Create WooCommerce Order" button
    $('.workrequest-create-order-btn').on('click', function(e) {
        e.preventDefault();

        var $button = $(this);
        var request_id = $button.data('request_id');
        var $statusDiv = $('#status-' + request_id);
        var $productsList = $('.workrequest-product-list[data-request_id="' + request_id + '"]');

        var product_data = [];
        $productsList.find('li').each(function() {
            var $listItem = $(this);
            var product_id = $listItem.data('product_id');
            var quantity = $listItem.find('.workrequest-product-qty').val();
            var note = $listItem.find('.workrequest-product-note').val();

            if (product_id) { // Ensure product_id exists
                product_data.push({
                    product_id: product_id,
                    quantity: parseInt(quantity) || 1, // Default to 1 if not a valid number
                    note: note || ''
                });
            }
        });

        if (product_data.length === 0) {
            $statusDiv.html('<p class="notice notice-warning">' + WorkRequestOrderPreparation.no_products_selected_text + '</p>');
            return;
        }

        if (!confirm(WorkRequestOrderPreparation.confirm_create_order)) {
            return;
        }

        // Disable button and show loading indicator
        $button.prop('disabled', true).text(WorkRequestOrderPreparation.order_creating_text);
        $statusDiv.html(''); // Clear previous messages

        $.ajax({
            url: WorkRequestOrderPreparation.ajax_url,
            type: 'POST',
            data: {
                action: 'workrequest_create_order', // The AJAX action defined in PHP
                security: WorkRequestOrderPreparation.create_order_nonce,
                request_id: request_id,
                product_data: JSON.stringify(product_data) // Send product data as JSON string
            },
            beforeSend: function() {
                $statusDiv.html('<p class="notice notice-info">' + WorkRequestOrderPreparation.order_creating_text + '</p>');
            },
            success: function(response) {
                console.log('Order Creation AJAX Response:', response);
                if (response.success) {
                    var orderNumber = response.data.order_id;
                    var orderStatus = response.data.order_status;
                    var orderTotal = response.data.order_total;
                    var editOrderUrl = WorkRequestOrderPreparation.edit_order_url.replace('%d', orderNumber);

                    var successMessage = '<p class="notice notice-success"><strong>' + WorkRequestOrderPreparation.order_success_text.replace('%s', orderNumber) + '</strong></p>';
                    successMessage += '<p>' + WorkRequestOrderPreparation.order_number_text + ': <a href="' + editOrderUrl + '">#' + orderNumber + '</a></p>';
                    successMessage += '<p>' + WorkRequestOrderPreparation.order_status_text + ': ' + orderStatus + '</p>';
                    successMessage += '<p>' + WorkRequestOrderPreparation.order_total_text + ': ' + orderTotal + '</p>';

                    $statusDiv.html(successMessage);
                    
                    // Hide the current row or mark it as converted, maybe remove the button
                    // For now, let's just replace the button with a converted message.
                    $button.hide();
                    $statusDiv.append('<p class="description">' + WorkRequestOrderPreparation.order_converted_text + '</p>');
                    
                    // Optional: Remove the row from the table or disable the inputs
                    $('#repair-request-row-' + request_id).css('background-color', '#e0ffe0'); // Visually mark as done
                    $('#repair-request-row-' + request_id).find('.workrequest-product-qty, .workrequest-product-note').prop('disabled', true);


                } else {
                    var errorMessage = WorkRequestOrderPreparation.order_error_text + ' ' + (response.data && response.data.message ? response.data.message : 'Unknown error.');
                    $statusDiv.html('<p class="notice notice-error">' + errorMessage + '</p>');
                    $button.prop('disabled', false).text(WorkRequestOrderPreparation.order_create_button_text || 'Create WooCommerce Order'); // Re-enable button
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error, xhr);
                $statusDiv.html('<p class="notice notice-error">' + WorkRequestOrderPreparation.order_ajax_error_text + ' ' + error + '</p>');
                $button.prop('disabled', false).text(WorkRequestOrderPreparation.order_create_button_text || 'Create WooCommerce Order'); // Re-enable button
            }
        });
    });
});