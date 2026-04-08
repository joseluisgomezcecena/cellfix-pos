/**
 * Affiliate Discount Modal - JavaScript
 * Handles modal interactions and discount selection
 */

(function($) {
    'use strict';

    var AffiliateDiscountModal = {
        selectedBusiness: null,
        selectedDiscounts: {},

        init: function() {
            this.bindEvents();
            this.loadActiveSession();
        },

        bindEvents: function() {
            var self = this;

            // Open modal
            $('#openAffiliateDiscountModal').on('click', function() {
                $('#affiliateDiscountModal').modal('show');
            });

            // Business selection changed
            $('#affiliateBusinessSelect').on('change', function() {
                var businessId = $(this).val();
                if (businessId) {
                    self.selectedBusiness = businessId;
                    self.loadDiscountOptions(businessId);
                } else {
                    self.selectedBusiness = null;
                    $('#discountOptionsContainer').hide().html('');
                }
            });

            // Save discounts
            $('#saveAffiliateDiscounts').on('click', function() {
                self.saveSelections();
            });

            // Delegate event for radio buttons
            $(document).on('change', '.discount-option-radio', function() {
                var category = $(this).data('category');
                var discountId = $(this).val();
                self.selectedDiscounts[category] = discountId;
            });

            // Add new option button (if permission granted)
            $(document).on('click', '.add-discount-option-btn', function() {
                var category = $(this).data('category');
                self.showAddOptionForm(category);
            });
        },

        loadDiscountOptions: function(businessId) {
            var self = this;

            $.ajax({
                url: '/api/affiliate-discount/options/' + businessId,
                method: 'GET',
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        self.renderDiscountOptions(response.data);
                    } else {
                        $('#discountOptionsContainer').html(
                            '<div class="alert alert-warning">No discount options available for this business.</div>'
                        ).show();
                    }
                },
                error: function() {
                    toastr.error('Failed to load discount options');
                }
            });
        },

        renderDiscountOptions: function(categoriesData) {
            var html = '';

            categoriesData.forEach(function(categoryData) {
                html += '<div class="discount-category-section" style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">';
                html += '<h5 style="color: #e74c3c; font-weight: bold; margin-bottom: 10px;">' + categoryData.category_label + ':</h5>';

                // Add button (if user has permission - we'll always show it for now)
                html += '<button type="button" class="btn btn-success btn-xs add-discount-option-btn" data-category="' + categoryData.category + '" style="margin-bottom: 10px;">';
                html += '<i class="fa fa-plus"></i></button>';

                // Radio buttons for options
                categoryData.options.forEach(function(option) {
                    html += '<div class="radio" style="margin-left: 20px;">';
                    html += '<label>';
                    html += '<input type="radio" name="discount_' + categoryData.category + '" ';
                    html += 'class="discount-option-radio" ';
                    html += 'data-category="' + categoryData.category + '" ';
                    html += 'value="' + option.id + '"> ';
                    html += option.label;
                    html += '</label>';
                    html += '</div>';
                });

                html += '</div>';
            });

            $('#discountOptionsContainer').html(html).show();
        },

        saveSelections: function() {
            var self = this;

            if (!this.selectedBusiness) {
                toastr.warning('Please select a business first');
                return;
            }

            var selections = Object.values(this.selectedDiscounts).filter(function(v) { return v; });

            $.ajax({
                url: '/api/affiliate-discount/save-selection',
                method: 'POST',
                data: {
                    business_id: this.selectedBusiness,
                    selections: selections,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.msg);
                        $('#affiliateDiscountModal').modal('hide');

                        // Update badge
                        $('#activeDiscountBadge').text(selections.length).show();

                        // Activate calculator and trigger recalculation
                        if (typeof AffiliateDiscountCalculator !== 'undefined') {
                            AffiliateDiscountCalculator.isActive = true;
                            AffiliateDiscountCalculator.recalculate();
                        }
                    }
                },
                error: function() {
                    toastr.error('Failed to save selections');
                }
            });
        },

        loadActiveSession: function() {
            var self = this;

            $.ajax({
                url: '/api/affiliate-discount/active-session',
                method: 'GET',
                success: function(response) {
                    if (response.success && response.data) {
                        var count = response.data.selections ? response.data.selections.length : 0;
                        if (count > 0) {
                            $('#activeDiscountBadge').text(count).show();
                        }
                    }
                }
            });
        },

        showAddOptionForm: function(category) {
            // Simple prompt for quick add (can be enhanced with a modal)
            var label = prompt('Enter discount label (e.g., $50 PESOS or 5% DESC.):');
            if (!label) return;

            var type = confirm('Click OK for Fixed Amount, Cancel for Percentage') ? 'fixed' : 'percentage';
            var value = prompt('Enter discount value:');
            if (!value) return;

            $.ajax({
                url: '/api/affiliate-discount/add-option',
                method: 'POST',
                data: {
                    business_id: this.selectedBusiness,
                    category: category,
                    discount_type: type,
                    discount_value: value,
                    discount_label: label,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.msg);
                        // Reload options
                        AffiliateDiscountModal.loadDiscountOptions(AffiliateDiscountModal.selectedBusiness);
                    }
                },
                error: function() {
                    toastr.error('Failed to add option');
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        if ($('#affiliateDiscountModal').length > 0) {
            AffiliateDiscountModal.init();
        }
    });

    // Expose to global scope if needed
    window.AffiliateDiscountModal = AffiliateDiscountModal;

})(jQuery);
