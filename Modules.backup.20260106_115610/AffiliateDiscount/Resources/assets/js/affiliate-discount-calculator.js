/**
 * Affiliate Discount Calculator - JavaScript
 * Handles automatic discount calculation for POS cart
 */

(function($) {
    'use strict';

    var AffiliateDiscountCalculator = {
        isActive: false,
        lastCalculation: null,

        init: function() {
            this.bindEvents();
            this.checkActiveSession();
        },

        bindEvents: function() {
            var self = this;

            // Listen for cart updates (adjust selectors based on your POS implementation)
            $(document).on('product:added product:updated product:removed cart:updated', function() {
                if (self.isActive) {
                    self.recalculate();
                }
            });

            // Hook into existing POS cart update functions if available
            if (typeof pos_product_row !== 'undefined') {
                var originalPosProductRow = pos_product_row;
                window.pos_product_row = function(product) {
                    var result = originalPosProductRow.apply(this, arguments);
                    if (self.isActive) {
                        setTimeout(function() { self.recalculate(); }, 500);
                    }
                    return result;
                };
            }
        },

        checkActiveSession: function() {
            var self = this;

            $.ajax({
                url: '/api/affiliate-discount/active-session',
                method: 'GET',
                success: function(response) {
                    if (response.success && response.data) {
                        self.isActive = true;
                        self.recalculate();
                    }
                }
            });
        },

        recalculate: function() {
            var self = this;

            // Get cart items (adjust based on your POS structure)
            var cartItems = this.extractCartItems();

            console.log('[Affiliate Discount] Recalculate called, cart items:', cartItems);

            if (cartItems.length === 0) {
                console.log('[Affiliate Discount] No cart items found, skipping calculation');
                return;
            }

            console.log('[Affiliate Discount] Sending calculation request for', cartItems.length, 'items');

            $.ajax({
                url: '/api/affiliate-discount/calculate',
                method: 'POST',
                data: {
                    cart_items: cartItems,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    console.log('[Affiliate Discount] Calculation response:', response);
                    if (response.success) {
                        self.lastCalculation = response.data;
                        self.applyDiscounts(response.data);
                    }
                },
                error: function(xhr) {
                    console.error('[Affiliate Discount] Calculation error:', xhr.status, xhr.responseText);
                    if (xhr.status === 400) {
                        // No active session
                        self.isActive = false;
                    }
                }
            });
        },

        extractCartItems: function() {
            var items = [];

            // Extract from POS table
            $('#pos_table tbody tr.product_row').each(function() {
                var $row = $(this);

                // Get product_id - try multiple selectors
                var productId = $row.find('input.product_id').val() ||
                                $row.find('input[name*="[product_id]"]').val();

                // Skip if no product_id
                if (!productId) {
                    console.log('[Affiliate Discount] Skipping row - no product_id found');
                    return;
                }

                var quantity = parseFloat($row.find('input.pos_quantity').val()) ||
                               parseFloat($row.find('input[name*="[quantity]"]').val()) || 1;

                var unitPrice = parseFloat($row.find('input.pos_unit_price_inc_tax').val()) ||
                                parseFloat($row.find('input[name*="[unit_price_inc_tax]"]').val()) || 0;

                console.log('[Affiliate Discount] Extracted item:', {
                    product_id: productId,
                    quantity: quantity,
                    unit_price: unitPrice,
                    subtotal: quantity * unitPrice
                });

                items.push({
                    product_id: productId,
                    quantity: quantity,
                    unit_price: unitPrice
                    // Category will be fetched by backend from database
                });
            });

            console.log('[Affiliate Discount] Total items extracted:', items.length);
            return items;
        },

        applyDiscounts: function(calculationData) {
            var totalDiscount = calculationData.total_discount || 0;

            console.log('[Affiliate Discount] Applying discount:', totalDiscount);

            // Update the discount field in POS
            if ($('#discount_amount').length) {
                // Set discount type to fixed amount
                if ($('#discount_type').length) {
                    $('#discount_type').val('fixed');
                }

                // Set discount amount
                var discountFormatted = totalDiscount.toFixed(2);
                $('#discount_amount').val(discountFormatted);
                console.log('[Affiliate Discount] Set discount_amount to:', discountFormatted);

                // Trigger change event
                $('#discount_amount').trigger('change');
            } else {
                console.warn('[Affiliate Discount] discount_amount field not found!');
            }

            // Update visual indicators
            this.updateDiscountDisplay(calculationData);

            // Trigger POS recalculation - try multiple methods
            if (typeof pos_total_row !== 'undefined') {
                console.log('[Affiliate Discount] Calling pos_total_row()');
                pos_total_row();
            } else {
                console.warn('[Affiliate Discount] pos_total_row() not found');
            }

            // Also try triggering change on cart total field
            $('.price_total').trigger('update');
        },

        updateDiscountDisplay: function(calculationData) {
            // Create/update discount breakdown display
            var $displayArea = $('#affiliate-discount-breakdown');

            if ($displayArea.length === 0) {
                // Create display area if it doesn't exist
                $displayArea = $('<div id="affiliate-discount-breakdown" class="well well-sm" style="margin-top:10px;"></div>');
                $('.pos-form').prepend($displayArea);
            }

            var html = '<h5><i class="fa fa-percent"></i> Descuentos Afiliados Activos:</h5>';
            html += '<table class="table table-condensed table-sm">';

            if (calculationData.calculations && calculationData.calculations.length > 0) {
                calculationData.calculations.forEach(function(calc) {
                    if (calc.discount > 0) {
                        html += '<tr>';
                        html += '<td>' + calc.category.toUpperCase() + '</td>';
                        html += '<td class="text-right">-$' + calc.discount.toFixed(2) + '</td>';
                        html += '</tr>';
                    }
                });

                html += '<tr class="info">';
                html += '<td><strong>Total Descuento:</strong></td>';
                html += '<td class="text-right"><strong>-$' + calculationData.total_discount.toFixed(2) + '</strong></td>';
                html += '</tr>';
            } else {
                html += '<tr><td colspan="2" class="text-center text-muted">Sin descuentos aplicados</td></tr>';
            }

            html += '</table>';

            $displayArea.html(html).show();
        },

        clearDiscounts: function() {
            this.isActive = false;
            this.lastCalculation = null;
            $('#affiliate-discount-breakdown').remove();

            // Clear discount field
            if ($('#discount_amount').length) {
                $('#discount_amount').val(0).trigger('change');
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        if ($('#pos_table').length > 0 || $('.pos-form').length > 0) {
            AffiliateDiscountCalculator.init();
        }
    });

    // Expose to global scope
    window.AffiliateDiscountCalculator = AffiliateDiscountCalculator;

})(jQuery);
