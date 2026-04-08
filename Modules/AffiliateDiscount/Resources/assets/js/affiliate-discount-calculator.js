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

            // DISABLED: Auto-recalculation has been disabled
            // Discounts are now applied server-side via core discount system
            // when products are added to cart

            console.log('[AffiliateDiscount] Calculator auto-recalculation disabled - using server-side discounts');

            // Keep the event listeners for potential manual recalculation
            // but don't automatically trigger on every cart update
        },

        checkActiveSession: function() {
            // COMPLETELY DISABLED - Server-side discount system handles everything
            // No client-side recalculation needed
            console.log('[AffiliateDiscount] checkActiveSession disabled - using server-side discounts only');
            return;
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
            console.log('[Affiliate Discount] Applying discounts to product rows');

            // Apply line-level discounts to each product row
            if (calculationData.calculations && calculationData.calculations.length > 0) {
                calculationData.calculations.forEach(function(calc) {
                    if (calc.discount > 0 && calc.product_id) {
                        // Find the product row
                        var $row = $('#pos_table tbody tr.product_row').filter(function() {
                            var rowProductId = $(this).find('input.product_id').val() ||
                                             $(this).find('input[name*="[product_id]"]').val();
                            return rowProductId == calc.product_id;
                        });

                        if ($row.length > 0) {
                            console.log('[Affiliate Discount] Applying discount to product', calc.product_id, ':', calc.discount);

                            // Calculate per-unit discount
                            var quantity = parseFloat($row.find('input.pos_quantity').val()) || 1;
                            var perUnitDiscount = calc.discount / quantity;

                            // Set discount type to fixed
                            var $discountType = $row.find('select.row_discount_type');
                            if ($discountType.length) {
                                $discountType.val('fixed');
                            }

                            // Set discount amount (per unit)
                            var $discountAmount = $row.find('input.row_discount_amount');
                            if ($discountAmount.length) {
                                $discountAmount.val(perUnitDiscount.toFixed(2));
                                $discountAmount.trigger('change');
                                console.log('[Affiliate Discount] Set row discount:', perUnitDiscount.toFixed(2));
                            }
                        }
                    }
                });
            }

            // Update visual indicators
            this.updateDiscountDisplay(calculationData);

            // Trigger POS recalculation
            if (typeof pos_total_row !== 'undefined') {
                console.log('[Affiliate Discount] Calling pos_total_row()');
                pos_total_row();
            }
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
