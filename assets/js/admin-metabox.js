jQuery(document).ready(function($){
    // Log to confirm script loading and execution start new
    console.log('admin-metabox.js loaded and executing.');

    // Check if WorkRequestAdmin object and its properties exist
    if (typeof WorkRequestAdmin === 'undefined' || typeof WorkRequestAdmin.search_products_nonce === 'undefined' || typeof WorkRequestAdmin.search_placeholder_text === 'undefined') {
        console.error('WorkRequestAdmin object or its required properties are missing. Check wp_localize_script in PHP.');
        return; // Exit if essential data is not localized
    }

    // Initialize SelectWoo (enhanced select/autocomplete) for product search
    if (typeof $.fn.selectWoo !== 'undefined') {
        jQuery( '.workrequest-product-search' ).selectWoo({
            allowClear: true,
            placeholder: WorkRequestAdmin.search_placeholder_text,
            minimumInputLength: 3, // Start searching after 3 characters
            dropdownParent: jQuery('body'), // <--- این خط رو اضافه کنید
            ajax: {
                url: WorkRequestAdmin.ajax_url, // Use localized ajax_url
                dataType: 'json',
                delay: 250, // Delay in milliseconds before sending the request
                data: function (params) {
                    console.log('SelectWoo AJAX data function called with params:', params); // <--- این خط رو اضافه کنید
                    return {
                        term: params.term, // Search term from the input field
                        action: 'workrequest_search_products', // Explicitly define the AJAX action here
                        security: WorkRequestAdmin.search_products_nonce // Nonce for security
                    };
                },
                processResults: function (data) {
                    // Processes the JSON data returned from the server
                    // Expects an array of objects like [{id: '123', text: 'Product Name (SKU)'}]
                    return {
                        results: data
                    };
                },
                cache: true // Cache results for the same search terms
            }
        });
        console.log('SelectWoo initialization code executed for product search field.');
    } else {
        console.warn('SelectWoo (WooCommerce enhanced select) is not defined. Product search autocomplete may not work.');
        console.warn('Ensure woocommerce/assets/js/selectWoo/selectWoo.full.js and woocommerce/assets/js/admin/wc-enhanced-select.js are enqueued correctly.');
    }


    // --- Product List Management within the Metabox ---

    // Event listener for when a product is selected from the search results
    jQuery( '.workrequest-product-search' ).on( 'select2:select', function(e){
        var product_id = e.params.data.id;
        var product_text = e.params.data.text;
        console.log('Product selected from search:', product_id, product_text);

        // Check if the product is already in the list
        var existing_item = $( '#workrequest_selected_products_list li[data-product_id="' + product_id + '"]' );

        if ( existing_item.length > 0 ) {
            // If product exists, just increase its quantity by 1
            var qty_input = existing_item.find( '.product-qty' );
            qty_input.val( parseInt( qty_input.val() ) + 1 ).trigger( 'change' );
            console.log('Increased quantity for existing product.');
        } else {
            // If product is new, add it to the list with quantity 1
            var product_item = `
                <li data-product_id="${product_id}">
                    ${product_text}
                    <input type="hidden" name="workrequest_selected_products[${product_id}][id]" value="${product_id}" />
                    <span class="quantity-controls">
                        <button type="button" class="minus-qty button button-small">-</button>
                        <input type="number" name="workrequest_selected_products[${product_id}][qty]" class="product-qty small-text" value="1" min="1" />
                        <button type="button" class="plus-qty button button-small">+</button>
                    </span>
                    <button type="button" class="remove-product button button-small button-link-delete">×</button>
                </li>
            `;
            jQuery( '#workrequest_selected_products_list' ).append( product_item );
            console.log('Added new product to the list.');
        }

        // Clear the select2 input field after selection to allow for new searches
        jQuery(this).val(null).trigger('change');
    });

    // Handle quantity increase for products in the list
    jQuery( '#workrequest_selected_products_list' ).on( 'click', '.plus-qty', function(){
        var qty_input = $(this).siblings( '.product-qty' );
        qty_input.val( parseInt( qty_input.val() ) + 1 ).trigger( 'change' );
        console.log('Quantity increased.');
    });

    // Handle quantity decrease for products in the list (minimum 1)
    jQuery( '#workrequest_selected_products_list' ).on( 'click', '.minus-qty', function(){
        var qty_input = $(this).siblings( '.product-qty' );
        var current_qty = parseInt( qty_input.val() );
        if ( current_qty > 1 ) {
            qty_input.val( current_qty - 1 ).trigger( 'change' );
        }
        console.log('Quantity decreased (if > 1).');
    });

    // Handle removal of a product from the list
    jQuery( '#workrequest_selected_products_list' ).on( 'click', '.remove-product', function(){
        $(this).closest( 'li' ).remove();
        console.log('Product item removed from list.');
    });


    // --- Create WooCommerce Order Functionality ---

    // Event listener for the "Create WooCommerce Order" button
    jQuery( '#workrequest_create_order_button' ).on( 'click', function(e){
        e.preventDefault(); // Prevent default button action (form submission)

        var $button = $(this);
        // Find the spinner element. It's usually a sibling with a specific class or next sibling if structured well.
        // Given your HTML, you might need to add a spinner element next to the button.
        // For now, let's assume it might be a div with class 'spinner' after the button.
        var $spinner = $button.parent().find('.spinner'); // Adjust this selector if your spinner is positioned differently
                                                       // or you might need to add a spinner HTML element next to your button in admin-repair-request.php
        
        var post_id = WorkRequestAdmin.post_id; // Get the current post ID from localized script data
        var selected_products_data = []; // Array to store selected product IDs and quantities

        // Iterate through each selected product in the list to gather data
        jQuery( '#workrequest_selected_products_list li' ).each( function() {
            var product_id = $(this).data( 'product_id' );
            var qty = $(this).find( '.product-qty' ).val();
            selected_products_data.push({ id: product_id, qty: qty });
        });

        // Check if any products are selected
        if ( selected_products_data.length === 0 ) {
            alert( WorkRequestAdmin.no_products_selected_text ); // Show alert if no products
            return; // Stop execution
        }

        // Disable button and show spinner during AJAX request
        $button.prop( 'disabled', true );
        // $spinner.css( 'visibility', 'visible' ); // Uncomment if you have a spinner and its selector is correct
        // A better way to show spinner might be:
        $button.after('<span class="spinner is-active" style="float:none; margin: 0 5px;"></span>'); 
        // Find the newly added spinner
        $spinner = $button.next('.spinner');

        console.log('Attempting to create WooCommerce order...');

        // AJAX call to create the WooCommerce order on the server-side
        $.ajax({
            url: WorkRequestAdmin.ajax_url, // Use localized ajax_url
            type: 'POST',
            data: {
                action: WorkRequestAdmin.create_order_action, // Use the localized action name
                security: WorkRequestAdmin.create_order_nonce, // Nonce for security
                post_id: post_id, // Current Request Post ID
                products: JSON.stringify(selected_products_data) // Send as JSON string
            },
            success: function(response) {
                // Remove the spinner
                $spinner.remove();
                // Re-enable button on success or failure
                $button.prop( 'disabled', false );
                console.log('AJAX response received for order creation:', response);

                if (response.success) {
                    // Show success message with order ID
                    alert( WorkRequestAdmin.order_success_text.replace('%s', response.data.order_number) ); // Use order_number instead of id

                    // Update the metabox content to show the linked order details
                    var order_link = WorkRequestAdmin.edit_order_url.replace( '%d', response.data.order_id );
                    var metabox_content = `
                        <div class="workrequest-order-linked-info">
                            <p><strong>${WorkRequestAdmin.order_converted_text}</strong></p>
                            <p><strong>${WorkRequestAdmin.order_number_text}:</strong> <a href="${order_link}" target="_blank">#${response.data.order_number}</a></p>
                            <p><strong>${WorkRequestAdmin.order_status_text}:</strong> ${response.data.order_status_name}</p>
                            <p><strong>${WorkRequestAdmin.order_total_text}:</strong> ${response.data.order_total}</p>
                            <p class="description">${WorkRequestAdmin.order_edit_note}</p>
                        </div>
                    `;
                    // Replace the existing form with the new content
                    jQuery( '#workrequest-order-creation-form' ).replaceWith( metabox_content );
                    console.log('Metabox content updated with new order details.');

                } else {
                    // Show error message if order creation failed on server-side
                    alert( WorkRequestAdmin.order_error_text + ' ' + (response.data.message || 'Unknown error.') ); // Access 'message' from 'data' object
                    console.error('Error creating order:', response.data);
                }
            },
            error: function(xhr, status, error) {
                // Remove the spinner
                $spinner.remove();
                // Handle generic AJAX errors (e.g., network issues)
                $button.prop( 'disabled', false );
                alert( WorkRequestAdmin.order_ajax_error_text + ' ' + error );
                console.error('AJAX Error:', status, error, xhr);
            }
        });
    });
});