jQuery(document).ready(function($) {
    // Check if WorkRequestOrderItemAdmin object is available (localized data from PHP)
    if (typeof WorkRequestOrderItemAdmin === 'undefined') {
        console.error('WorkRequestOrderItemAdmin object not found. Is workrequest-admin-order-item.js correctly localized?');
        return;
    }

    const ajaxUrl = WorkRequestOrderItemAdmin.ajax_url;
    const createOrderNonce = WorkRequestOrderItemAdmin.create_order_nonce;

    /**
     * Handles the click event for the 'Create WooCommerce Order' button.
     */
    $(document).on('click', '.workrequest-create-order-btn', function(e) {
        e.preventDefault();

        const $button = $(this);
        const repairOrderItemId = $button.data('request_item_id');
        const $statusDiv = $('#status-' + repairOrderItemId);
        
        // Disable button to prevent multiple clicks
        $button.prop('disabled', true).text('Creating Order...');
        $statusDiv.html('<p class="notice notice-info is-dismissible">Creating WooCommerce order...</p>');

        // Confirmation dialog
        if (!confirm(WorkRequestOrderItemAdmin.confirm_create_order)) {
            $button.prop('disabled', false).text('Create WooCommerce Order');
            $statusDiv.empty();
            return;
        }

        // Collect all product data (product_id, quantity, note) from the table
        const productData = [];
        $statusDiv.closest('.options_group').find('.workrequest-order-item-products tbody tr').each(function() {
            const $row = $(this);
            const productId = $row.find('input[name*="[product_id]"]').val();
            const quantity = $row.find('input[name*="[quantity]"]').val();
            const note = $row.find('input[name*="[note]"]').val();

            if (productId && quantity > 0) { // Only include valid products with quantity > 0
                productData.push({
                    product_id: parseInt(productId),
                    quantity: parseInt(quantity),
                    note: note
                });
            }
        });

        if (productData.length === 0) {
            $statusDiv.html('<p class="notice notice-error is-dismissible">No products found to create an order. Please add products and save the Repair Request.</p>');
            $button.prop('disabled', false).text('Create WooCommerce Order');
            return;
        }

        // AJAX call to create the WooCommerce order
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'workrequest_create_order', // The AJAX action defined in PHP
                security: createOrderNonce,
                repair_order_item_id: repairOrderItemId,
                product_data: JSON.stringify(productData) // Send product data as JSON string
            },
            success: function(response) {
                if (response.success) {
                    $statusDiv.html('<p class="notice notice-success is-dismissible">' + WorkRequestOrderItemAdmin.order_creation_success + '</p>');
                    // Update order status display immediately
                    const $orderStatusDisplay = $statusDiv.closest('.options_group').find('.workrequest-order-status-display');
                    if ($orderStatusDisplay.length) {
                        $orderStatusDisplay.find('a').attr('href', response.data.edit_order_url).text('#' + response.data.order_id);
                        $orderStatusDisplay.find('p:nth-child(2)').html('<strong>Status:</strong> ' + response.data.order_status);
                        $orderStatusDisplay.find('p:nth-child(3)').html('<strong>Total:</strong> ' + parseFloat(response.data.order_total).toLocaleString('en-US', { style: 'currency', currency: 'USD' })); // Adjust currency as needed
                        $orderStatusDisplay.show(); // Ensure it's visible

                        // If successful, we can optionally hide the button and show the new status
                        $button.hide(); 
                        $statusDiv.html(
                            '<p class="notice notice-success is-dismissible">' + 
                            WorkRequestOrderItemAdmin.order_creation_success + 
                            ' <a href="' + response.data.edit_order_url + '" target="_blank">#' + response.data.order_id + '</a></p>'
                        );

                        // Optionally, redirect to the new order edit page or reload the current page.
                        // window.location.href = response.data.edit_order_url; 
                        // Or just reload the page to update all metaboxes and ensure correct state:
                        // location.reload(); 
                    } else {
                        // If the status display wasn't found, just show success message and refresh
                        alert(WorkRequestOrderItemAdmin.order_creation_success + '\nOrder ID: #' + response.data.order_id);
                        location.reload();
                    }
                } else {
                    $statusDiv.html('<p class="notice notice-error is-dismissible">' + WorkRequestOrderItemAdmin.order_creation_error + ' ' + (response.data.message || 'Unknown error.') + '</p>');
                    $button.prop('disabled', false).text('Create WooCommerce Order');
                }
            },
            error: function(xhr, status, error) {
                $statusDiv.html('<p class="notice notice-error is-dismissible">' + WorkRequestOrderItemAdmin.order_creation_error + ' ' + error + '</p>');
                $button.prop('disabled', false).text('Create WooCommerce Order');
                console.error('AJAX Error:', status, error, xhr.responseText);
            }
        });
    });
});