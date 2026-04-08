/**
 * Promo Code POS Integration
 * Handles promo code application in the POS interface
 */

(function($) {
    'use strict';

    var PromoCodePOS = {
        currentPromoCode: null,

        init: function() {
            this.initSelect2();
            this.bindEvents();
        },

        initSelect2: function() {
            // Initialize Select2 for promo company dropdown
            if ($('#promo_company_name').length && typeof $.fn.select2 !== 'undefined') {
                $('#promo_company_name').select2({
                    placeholder: 'Seleccione una empresa',
                    allowClear: true,
                    dropdownParent: $('#promo_code_modal')
                });
            }
        },

        bindEvents: function() {
            var self = this;

            // Apply promo code button
            $('#promo_code_apply_btn').on('click', function(e) {
                e.preventDefault();
                self.applyPromoCode();
            });

            // Clear promo code button
            $('#promo_code_clear_btn').on('click', function(e) {
                e.preventDefault();
                self.clearPromoCode();
            });

            // Clear error when Select2 changes
            $('#promo_company_name').on('change', function() {
                $('#promo_code_error').hide();
            });
        },

        applyPromoCode: function() {
            var self = this;
            var companyName = $('#promo_company_name').val().trim();
            console.log('[PromoCode] applyPromoCode called with company:', companyName);

            if (!companyName) {
                this.showError('Por favor ingrese el nombre de la empresa');
                return;
            }

            // Get cart items
            var cartItems = this.getCartItems();
            console.log('[PromoCode] Cart items retrieved:', cartItems);

            if (cartItems.length === 0) {
                this.showError('No hay productos en el carrito');
                return;
            }

            // Show loading state
            $('#promo_code_apply_btn').prop('disabled', true).text('Verificando...');
            $('#promo_code_error').hide();

            console.log('[PromoCode] Making AJAX request to /promo-codes/calculate');

            // Make AJAX call to verify and calculate
            $.ajax({
                url: '/promo-codes/calculate',
                method: 'POST',
                data: {
                    company_name: companyName,
                    cart_items: cartItems,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    console.log('[PromoCode] AJAX success response:', response);
                    if (response.success) {
                        self.handlePromoCodeSuccess(response);
                    } else {
                        self.showError(response.message || 'Error al aplicar código promocional');
                    }
                },
                error: function(xhr) {
                    console.error('[PromoCode] AJAX error:', xhr);
                    var message = 'Error al verificar el código promocional';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    self.showError(message);
                },
                complete: function() {
                    $('#promo_code_apply_btn').prop('disabled', false).text($('#promo_code_apply_btn').data('original-text') || 'Aplicar Código Promocional');
                }
            });
        },

        getCartItems: function() {
            var items = [];

            // Loop through POS cart rows
            $('table#pos_table tbody tr.product_row').each(function() {
                var $row = $(this);
                var productId = $row.find('.product_id').val();
                var variationId = $row.find('.row_variation_id').val();
                var quantity = parseFloat($row.find('.pos_quantity').val()) || 1;
                var unitPrice = parseFloat($row.find('.pos_unit_price_inc_tax').val()) || 0;
                var rowIndex = $row.data('row_index');

                // Get product category from data attribute
                var category = $row.attr('data-category') || $row.data('category') || '';

                console.log('[PromoCode] Row ' + rowIndex + ': category=' + category + ', product_id=' + productId);

                // If category is not found, try to get from product data
                if (!category && window.pos_products) {
                    var product = window.pos_products.find(function(p) {
                        return p.product_id == productId;
                    });
                    if (product) {
                        category = product.category;
                    }
                }

                items.push({
                    product_id: productId,
                    variation_id: variationId,
                    quantity: quantity,
                    price: unitPrice,
                    category: category,
                    row_index: rowIndex
                });
            });

            return items;
        },

        handlePromoCodeSuccess: function(response) {
            var self = this;
            console.log('[PromoCode] Applying promo code:', response);

            this.currentPromoCode = response;

            // Store in hidden fields
            $('#promo_company_id').val(response.promo_company_id);
            $('#promo_total_discount').val(response.total_discount);

            // Apply line-level discounts to each product row
            console.log('[PromoCode] Applying line-level discounts to product rows');
            var discountsApplied = 0;

            // Loop through all product rows and apply discounts
            $('table#pos_table tbody tr.product_row').each(function() {
                var $row = $(this);
                var rowIndex = $row.data('row_index');
                var productId = $row.find('.product_id').val();
                var variationId = $row.find('.row_variation_id').val();

                // Find matching discount from response
                var matchingDiscount = null;
                $.each(response.item_discounts, function(index, itemDiscount) {
                    if (itemDiscount.product_id == productId && itemDiscount.variation_id == variationId) {
                        matchingDiscount = itemDiscount;
                        return false; // break loop
                    }
                });

                if (matchingDiscount) {
                    console.log('[PromoCode] Applying discount to row ' + rowIndex + ':', matchingDiscount);

                    // Get the discount type and amount
                    var discountType = matchingDiscount.discount_type; // 'fixed' or 'percentage'
                    var discountValue = matchingDiscount.discount_value; // the actual value (not amount)

                    // Set the discount type dropdown in modal
                    var $discountTypeSelect = $row.find('select.row_discount_type');
                    console.log('[PromoCode] Found discount type select:', $discountTypeSelect.length);
                    if ($discountTypeSelect.length) {
                        $discountTypeSelect.val(discountType);
                        console.log('[PromoCode] Set discount type to:', discountType);
                    }

                    // Set the discount amount input in modal
                    var $discountAmountInput = $row.find('input.row_discount_amount');
                    console.log('[PromoCode] Found discount amount input:', $discountAmountInput.length);
                    if ($discountAmountInput.length) {
                        __write_number($discountAmountInput, discountValue);
                        console.log('[PromoCode] Set discount amount to:', discountValue);
                    }

                    // Now manually calculate and apply the discount to the visible price
                    var $unitPriceField = $row.find('input.pos_unit_price');
                    var $unitPriceIncTaxField = $row.find('input.pos_unit_price_inc_tax');
                    var $quantityField = $row.find('input.pos_quantity');
                    var $lineTotalField = $row.find('input.pos_line_total');
                    var $lineTotalText = $row.find('span.pos_line_total_text');

                    console.log('[PromoCode] Fields found - unitPrice:', $unitPriceField.length,
                                'unitPriceIncTax:', $unitPriceIncTaxField.length,
                                'quantity:', $quantityField.length);

                    if ($unitPriceField.length && $unitPriceIncTaxField.length) {
                        // Get current unit price (before discount)
                        var unitPrice = __read_number($unitPriceField);
                        var quantity = __read_number($quantityField);

                        console.log('[PromoCode] Current unit price:', unitPrice, 'quantity:', quantity);

                        // Calculate discounted price
                        var discountedPrice = unitPrice;
                        if (discountType === 'fixed') {
                            discountedPrice = unitPrice - discountValue;
                        } else if (discountType === 'percentage') {
                            discountedPrice = unitPrice - (unitPrice * discountValue / 100);
                        }

                        console.log('[PromoCode] Discounted price:', discountedPrice);

                        // Get tax rate
                        var taxRate = 0;
                        var $taxSelect = $row.find('select.tax_id');
                        if ($taxSelect.length) {
                            var selectedOption = $taxSelect.find(':selected');
                            taxRate = selectedOption.data('rate') || 0;
                        }

                        console.log('[PromoCode] Tax rate:', taxRate);

                        // Calculate price including tax
                        var priceIncTax = discountedPrice;
                        if (taxRate > 0) {
                            priceIncTax = discountedPrice + (discountedPrice * taxRate / 100);
                        }

                        // Calculate line total
                        var lineTotal = priceIncTax * quantity;

                        console.log('[PromoCode] Price inc tax:', priceIncTax, 'Line total:', lineTotal);

                        // Update the visible fields
                        __write_number($unitPriceIncTaxField, priceIncTax);
                        __write_number($lineTotalField, lineTotal, false);

                        if ($lineTotalText.length) {
                            $lineTotalText.text(__currency_trans_from_en(lineTotal, true));
                        }

                        discountsApplied++;
                    }

                    // Add visual indicator on the product row
                    var $promoIndicator = $row.find('.promo-discount-indicator');
                    if ($promoIndicator.length) {
                        var discountText = discountType === 'percentage'
                            ? discountValue + '%'
                            : __currency_trans_from_en(discountValue, true);
                        $promoIndicator.find('.promo-discount-text').text(discountText + ' desc.');
                        $promoIndicator.removeClass('hide').show();
                    }
                }
            });

            console.log('[PromoCode] Applied ' + discountsApplied + ' discounts to product rows');

            // Show success message in modal
            $('#promo_company_name_display').text(response.company_name);
            $('#promo_total_discount_display').text(__currency_trans_from_en(response.total_discount, true));

            // Display item discounts breakdown
            var itemDiscountsHtml = '<ul class="list-unstyled">';
            $.each(response.item_discounts, function(index, item) {
                itemDiscountsHtml += '<li><small>' +
                    item.promo_category + ': ' +
                    __currency_trans_from_en(item.discount_amount, true) +
                    ' (' + item.discount_type + ': ' + item.discount_value + ')' +
                    '</small></li>';
            });
            itemDiscountsHtml += '</ul>';
            $('#promo_item_discounts_list').html(itemDiscountsHtml);

            $('#promo_code_details').show();
            $('#promo_code_clear_btn').show();

            // Show applied label in POS totals
            $('#promo_code_company_name').text(response.company_name);
            $('#promo_code_applied_label').show();

            // Show success toast
            toastr.success('Código promocional aplicado: ' + response.company_name + ' (' + discountsApplied + ' productos)');

            // Trigger POS recalculation
            console.log('[PromoCode] Calling pos_total_row()');
            if (typeof pos_total_row === 'function') {
                pos_total_row();
            } else {
                console.error('[PromoCode] pos_total_row() function not found');
            }
        },

        clearPromoCode: function() {
            var self = this;

            $.ajax({
                url: '/promo-codes/clear',
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    console.log('[PromoCode] Clearing promo code discounts');

                    // Clear line-level discounts from all product rows
                    var rowsCleared = 0;
                    $('table#pos_table tbody tr.product_row').each(function() {
                        var $row = $(this);

                        // Reset discount type to fixed (default)
                        var $discountTypeSelect = $row.find('select.row_discount_type');
                        if ($discountTypeSelect.length) {
                            $discountTypeSelect.val('fixed');
                        }

                        // Reset discount amount to 0
                        var $discountAmountInput = $row.find('input.row_discount_amount');
                        if ($discountAmountInput.length) {
                            __write_number($discountAmountInput, 0);
                        }

                        // Recalculate price from base price (without discount)
                        var $unitPriceField = $row.find('input.pos_unit_price');
                        var $unitPriceIncTaxField = $row.find('input.pos_unit_price_inc_tax');
                        var $quantityField = $row.find('input.pos_quantity');
                        var $lineTotalField = $row.find('input.pos_line_total');
                        var $lineTotalText = $row.find('span.pos_line_total_text');

                        if ($unitPriceField.length && $unitPriceIncTaxField.length) {
                            // Get base unit price (without discount)
                            var unitPrice = __read_number($unitPriceField);
                            var quantity = __read_number($quantityField);

                            // Get tax rate
                            var taxRate = 0;
                            var $taxSelect = $row.find('select.tax_id');
                            if ($taxSelect.length) {
                                var selectedOption = $taxSelect.find(':selected');
                                taxRate = selectedOption.data('rate') || 0;
                            }

                            // Calculate price including tax (no discount)
                            var priceIncTax = unitPrice;
                            if (taxRate > 0) {
                                priceIncTax = unitPrice + (unitPrice * taxRate / 100);
                            }

                            // Calculate line total
                            var lineTotal = priceIncTax * quantity;

                            // Update the visible fields
                            __write_number($unitPriceIncTaxField, priceIncTax);
                            __write_number($lineTotalField, lineTotal, false);

                            if ($lineTotalText.length) {
                                $lineTotalText.text(__currency_trans_from_en(lineTotal, true));
                            }

                            rowsCleared++;
                        }

                        // Hide promo indicator
                        var $promoIndicator = $row.find('.promo-discount-indicator');
                        if ($promoIndicator.length) {
                            $promoIndicator.addClass('hide').hide();
                        }
                    });

                    console.log('[PromoCode] Cleared discounts from ' + rowsCleared + ' product rows');

                    self.resetPromoCodeUI();
                    toastr.success('Código promocional removido');

                    // Recalculate POS totals
                    if (typeof pos_total_row === 'function') {
                        pos_total_row();
                    }
                },
                error: function() {
                    toastr.error('Error al remover código promocional');
                }
            });
        },

        resetPromoCodeUI: function() {
            this.currentPromoCode = null;

            // Clear Select2
            if ($('#promo_company_name').data('select2')) {
                $('#promo_company_name').val(null).trigger('change');
            } else {
                $('#promo_company_name').val('');
            }

            $('#promo_company_id').val('');
            $('#promo_total_discount').val('0');

            // Hide details
            $('#promo_code_details').hide();
            $('#promo_code_clear_btn').hide();
            $('#promo_code_error').hide();
            $('#promo_code_applied_label').hide();
        },

        showError: function(message) {
            $('#promo_code_error').html('<i class="fa fa-exclamation-triangle"></i> ' + message).show();
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        PromoCodePOS.init();
    });

    // Expose to global scope if needed
    window.PromoCodePOS = PromoCodePOS;

})(jQuery);
