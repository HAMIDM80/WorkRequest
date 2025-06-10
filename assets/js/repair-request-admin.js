// assets/js/repair-request-admin.js

jQuery(document).ready(function($) {
    // AJAX handler for product search (for Select2)
    $.fn.select2.amd.define('select2/data/ajax-custom', ['select2/data/ajax', 'select2/utils'], function (AjaxAdapter, Utils) {
        function CustomData(options) {
            CustomData.__super__.constructor.call(this, options);
        }

        Utils.Extend(CustomData, AjaxAdapter);

        CustomData.prototype.query = function (params, callback) {
            var data = {
                action: this.options.ajax.action || 'workrequest_search_products',
                security: this.options.ajax.security,
                q: params.term || '',
                page: params.page || 1
            };

            $.ajax({
                url: this.options.ajax.url,
                dataType: this.options.ajax.dataType,
                data: data,
                processResults: this.options.ajax.processResults,
                cache: this.options.ajax.cache
            })
            .done(function (data) {
                callback(data);
            });
        };

        return CustomData;
    });

    // Initialize Select2 for product search (if not already initialized in render_repair_products_metabox)
    // This script will be loaded *after* the PHP-generated script, so we put this here
    // as a fallback or for re-initialization if the page loads dynamically.
    if ( ! $('.wc-product-search').data('select2') ) {
        $('.wc-product-search').select2({
            ajax: {
                url: workrequest_ajax_object.ajax_url,
                dataType: 'json',
                delay: 250,
                action: 'workrequest_search_products', // Custom action for AJAX
                security: workrequest_ajax_object.security,
                data: function (params) {
                    return {
                        q: params.term, // search term
                        action: 'workrequest_search_products',
                        security: workrequest_ajax_object.security
                    };
                },
                processResults: function (data) {
                    // Map results to Select2 format {id: value, text: text}
                    return {
                        results: $.map(data, function (item) {
                            return {
                                id: item.id,
                                text: item.text // Assuming 'text' is the product name
                            };
                        })
                    };
                },
                cache: true
            },
            minimumInputLength: 3,
            placeholder: 'Search for a product...',
            allowClear: true
        });
    }

    // Add product to the list when selected
    $('#workrequest_product_search').on('select2:select', function (e) {
        var product_id = e.params.data.id;
        var product_name = e.params.data.text;
        var $productsList = $('#workrequest-selected-products-list');

        // Check if product already exists
        if ($productsList.find('tr[data-product_id="' + product_id + '"]').length > 0) {
            alert('This product is already added.');
            return;
        }

        // Remove "No products added yet." row if it exists
        $productsList.find('.no-products-row').remove();

        // Append new product row
        var newRow = `
            <tr data-product_id="${product_id}">
                <td>${product_name}</td>
                <td><input type="number" name="workrequest_products[${product_id}][quantity]" value="1" min="1" step="1" class="small-text workrequest-product-qty" /></td>
                <td><input type="text" name="workrequest_products[${product_id}][note]" value="" class="large-text" placeholder="Add note (optional)" /></td>
                <td><button type="button" class="button button-small remove-product-row">Remove</button></td>
            </tr>
        `;
        $productsList.append(newRow);

        // Clear the select2 field after selection
        $(this).val(null).trigger('change');
    });

    // Remove product row
    $('#workrequest-selected-products-list').on('click', '.remove-product-row', function() {
        $(this).closest('tr').remove();
        if ($('#workrequest-selected-products-list').find('tr').length === 0) {
            $('#workrequest-selected-products-list').append('<tr class="no-products-row"><td colspan="4">No products added yet.</td></tr>');
        }
    });

});