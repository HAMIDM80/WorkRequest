// assets/js/order-preparation.js

jQuery(document).ready(function($) {

    // Function to update the subtotal for a product row
    window.updateRowTotal = function(qtyInput) {
        var $row = $(qtyInput).closest('tr');
        var productId = $row.data('product_id');
        var newQty = parseInt(qtyInput.value);

        // Fetch original product price from a hidden data attribute or global object
        // For security and accuracy, this should ideally be fetched from server
        // For now, we'll assume a 'data-price' attribute on the product row or a global map.
        // In this example, we'll need to pass `productPrices` from PHP or fetch it.
        // Let's assume for this JS that the price is available or comes with the initial render data.
        // A more robust solution would be to load all product prices into a JS object on page load.
        // For demonstration, let's just make sure the `productPrices` object is available.
        // This will be handled in the PHP side (RepairOrderPreparationPage.php)
        var productPrice = parseFloat($row.find('.item-subtotal').text().replace(/[^0-9.-]+/g,"")); // Extract price from text for simplicity. **NOT RECOMMENDED FOR PRODUCTION**

        // Fallback for getting price if not available directly from HTML
        if (isNaN(productPrice) || productPrice === 0) {
            // This is a placeholder. In a real application, you'd have a JS object
            // like `window.workrequest_product_prices = { 'product_id': 'price' };`
            // Or you'd fetch it via AJAX.
            // For now, we'll just log an error.
            console.error('Could not get product price for product ID: ' + productId);
            return;
        }

        if (isNaN(newQty) || newQty < 1) {
            newQty = 1;
            qtyInput.value = 1; // Correct the input field
        }

        var newSubtotal = productPrice * newQty;
        $row.find('.item-subtotal').text(wc_price_format(newSubtotal));
        updateGrandTotal($row.closest('table').closest('td').next('.total-amount-display'));
    };

    // Function to update the grand total for a specific repair request's product list
    window.updateGrandTotal = function($totalDisplayElement) {
        var grandTotal = 0;
        $totalDisplayElement.closest('tr').find('.products-sub-table tbody tr').each(function() {
            var $row = $(this);
            var currentSubtotalText = $row.find('.item-subtotal').text();
            var currentSubtotal = parseFloat(currentSubtotalText.replace(/[^0-9.-]+/g,"")); // Extract price from text

            if (!isNaN(currentSubtotal)) {
                grandTotal += currentSubtotal;
            }
        });
        $totalDisplayElement.find('strong').text(wc_price_format(grandTotal));
        $totalDisplayElement.data('total_amount', grandTotal);
    };

    // Helper for simple price formatting (replicates basic wc_price functionality)
    window.wc_price_format = function(price) {
        // This is a simplified version. For exact WooCommerce formatting,
        // you'd retrieve settings (currency symbol, decimal points, thousand separator, decimal separator)
        // from localized script.
        var currency_symbol = '<?php echo get_woocommerce_currency_symbol(); ?>';
        var price_decimals = <?php echo wc_get_price_decimals(); ?>;
        return currency_symbol + parseFloat(price).toFixed(price_decimals);
    };


    // Handle click on "Convert to WooCommerce Order" button
    $('.workrequest-create-order-btn').on('click', function() {
        var $button = $(this);
        var request_id = $button.data('request_id');
        var $statusDiv = $('#status-' + request_id);
        var $repairRequestRow = $('#repair-request-row-' + request_id);
        var $productsSubTable = $repairRequestRow.find('.products-sub-table');

        if (!confirm(workrequest_order_prep_ajax_object.confirm_text)) {
            return;
        }

        $button.prop('disabled', true).text('<?php esc_html_e( 'Creating Order...', 'workrequest' ); ?>');
        $statusDiv.html('<span class="spinner is-active"></span> <?php esc_html_e( 'Processing...', 'workrequest' ); ?>').show();

        var product_data = [];
        $productsSubTable.find('tbody tr').each(function() {
            var $row = $(this);
            var product_id = $row.data('product_id');
            var quantity = $row.find('.workrequest-product-qty').val();
            var note = $row.find('.workrequest-product-note').val();
            product_data.push({
                product_id: product_id,
                quantity: quantity,
                note: note
            });
        });

        $.ajax({
            url: workrequest_order_prep_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'workrequest_create_order',
                security: workrequest_order_prep_ajax_object.security,
                request_id: request_id,
                product_data: JSON.stringify(product_data) // Send as JSON string
            },
            success: function(response) {
                if (response.success) {
                    $statusDiv.html('<span class="dashicons dashicons-yes" style="color: green;"></span> ' + response.data.message + '<br><a href="' + response.data.edit_order_url + '" target="_blank"><?php esc_html_e( 'View Order', 'workrequest' ); ?> #' + response.data.order_id + '</a>').css('color', 'green');
                    // Hide the row after successful conversion
                    $repairRequestRow.fadeOut(500, function() {
                        $(this).remove();
                        // If no more requests, display message
                        if ($('.wp-list-table tbody tr').length === 0) {
                            $('.wp-list-table').replaceWith('<p><?php esc_html_e( 'No repair requests found with selected products awaiting order creation.', 'workrequest' ); ?></p>');
                        }
                    });
                } else {
                    $statusDiv.html('<span class="dashicons dashicons-no" style="color: red;"></span> ' + response.data.message).css('color', 'red');
                    $button.prop('disabled', false).text('<?php esc_html_e( 'Convert to WooCommerce Order', 'workrequest' ); ?>');
                }
            },
            error: function(xhr, status, error) {
                $statusDiv.html('<span class="dashicons dashicons-no" style="color: red;"></span> <?php esc_html_e( 'AJAX error:', 'workrequest' ); ?> ' + error).css('color', 'red');
                $button.prop('disabled', false).text('<?php esc_html_e( 'Convert to WooCommerce Order', 'workrequest' ); ?>');
            }
        });
    });

    // Initial calculation when page loads
    $('.total-amount-display').each(function() {
        updateGrandTotal($(this));
    });

    // Event listeners for quantity and note changes to update total dynamically
    $('.products-sub-table').on('input', '.workrequest-product-qty', function() {
        updateRowTotal(this);
    });
    // For note changes, no need to update total, but ensure data is correctly captured by button click handler.
});