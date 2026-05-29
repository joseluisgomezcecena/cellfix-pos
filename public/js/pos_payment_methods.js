/**
 * POS Payment Method Modals
 *
 * Wires the EFECTIVO / TARJETA DEBITO / TARJETA CREDITO / TRANSFERENCIA / CHEQUE
 * buttons added to the POS form actions bar, and submits the sale through the
 * existing payment_row hidden inputs so the backend flow is unchanged.
 */
$(document).ready(function () {

    // Bootstrap 3 doesn't auto-stack modals. When a 2nd modal opens on top of
    // another (e.g. cash modal opening from inside the multi-payment modal),
    // it gets a lower z-index and ends up behind. This bumps it above.
    $(document).on('show.bs.modal', '.modal', function () {
        var zIndex = 1040 + (10 * $('.modal:visible').length);
        $(this).css('z-index', zIndex);
        setTimeout(function() {
            $('.modal-backdrop').not('.modal-stack')
                .css('z-index', zIndex - 1)
                .addClass('modal-stack');
        }, 0);
    });
    // Restore body class when stacked modals close so scrollbar/padding work
    $(document).on('hidden.bs.modal', '.modal', function () {
        if ($('.modal:visible').length > 0) {
            $('body').addClass('modal-open');
        }
    });

    // Helper: read total payable from POS
    function getTotalPayable() {
        return __read_number($('input#final_total_input')) || 0;
    }

    // Helper: format as currency
    function formatCurrency(num) {
        return __currency_trans_from_en(num, true);
    }

    // Helper: validates POS state before opening any payment modal
    function preCheck() {
        // Cobro normal (métodos rápidos): NO marcar como recepción de reparación
        if ($('#repair_status').length) { $('#repair_status').val(''); }
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }
        if ($('#reward_point_enabled').length) {
            var validate_rp = isValidatRewardPoint();
            if (!validate_rp['is_valid']) {
                toastr.error(validate_rp['msg']);
                return false;
            }
        }
        // Repair items must have a technician selected
        var missing_tech = false;
        $('table#pos_table tbody tr.product_row[data-is_repair="1"]').each(function () {
            var $select = $(this).find('.technician_select');
            if (!$select.val()) {
                $select.css('border', '2px solid #d9534f').focus();
                missing_tech = true;
                return false;
            } else {
                $select.css('border', '');
            }
        });
        if (missing_tech) {
            toastr.error('Selecciona el técnico para cada producto de reparación antes de cobrar');
            return false;
        }
        return true;
    }

    // Helper: write the amount, method, and extra fields into the first payment row
    function writeFirstPaymentRow(method, amount, fields) {
        var firstRow = $('#payment_rows_div .payment_row').first();

        // Method dropdown
        firstRow.find('.payment_types_dropdown').val(method).trigger('change');

        // Amount
        var amountInput = firstRow.find('.payment-amount').first();
        __write_number(amountInput, amount);
        amountInput.trigger('change');

        // Optional extra fields (transaction_no, note, card_type, card_terminal_id, etc.)
        // Includes <select> in the selector so dropdowns like card_type and card_terminal_id work too.
        if (fields && typeof fields === 'object') {
            $.each(fields, function (key, value) {
                var input = firstRow.find(
                    'input[name$="[' + key + ']"], textarea[name$="[' + key + ']"], select[name$="[' + key + ']"]'
                ).first();
                if (input.length) {
                    input.val(value).trigger('change');
                }
            });
        }
    }

    // Helper: write denomination breakdown into the first payment row
    function writeDenominations(denominations) {
        var firstRow = $('#payment_rows_div .payment_row').first();
        $.each(denominations, function (denomValue, count) {
            var input = firstRow.find('input[name$="[denominations][' + denomValue + ']"]');
            if (input.length) {
                input.val(count);
            }
        });
    }

    // Tracks the multi-payment row that opened the cash modal (or null if main flow)
    var cashModalTargetRow = null;

    // ===========================
    //   EFECTIVO (botón principal)
    // ===========================
    $('#pos-pay-cash').on('click', function () {
        if (!preCheck()) return;
        cashModalTargetRow = null;

        var total = getTotalPayable();
        $('#cash_modal_total_payable').text(formatCurrency(total));
        resetCashModal();

        $('#cash_payment_modal').modal('show');
    });

    function resetCashModal() {
        $('#cash_payment_modal').find('input.cash-denom-count, #cash_coins_mxn, #cash_coins_usd').val('');
        $('#cash_subtotal_mxn').text(formatCurrency(0));
        $('#cash_subtotal_usd').text('$0.00');
        $('#cash_usd_to_mxn').text(formatCurrency(0));
        $('#cash_modal_total_received').text(formatCurrency(0));
        $('#cash_modal_change').text(formatCurrency(0));
    }

    // ===========================
    //   EFECTIVO (botón por fila en pago múltiple)
    // ===========================
    $(document).on('click', '.open-denominations-popup', function () {
        var row = $(this).closest('.payment_row');
        cashModalTargetRow = row;

        // Use the row's existing amount as the "to pay" hint, or fall back to total
        var rowAmount = __read_number(row.find('.payment-amount').first()) || getTotalPayable();
        $('#cash_modal_total_payable').text(formatCurrency(rowAmount));
        resetCashModal();

        // Prefill from any existing breakdown (handles both old flat and new nested format)
        var existingBreakdown = row.find('.denomination_breakdown_input').val();
        if (existingBreakdown) {
            try {
                var bd = JSON.parse(existingBreakdown);
                fillCashModalFromBreakdown(bd);
                recomputeCashTotals();
            } catch (e) {}
        }

        $('#cash_payment_modal').modal('show');
    });

    function fillCashModalFromBreakdown(bd) {
        if (!bd) return;

        // New nested format: { mxn: {...}, usd: {...}, exchange_rate: X }
        if (bd.mxn || bd.usd) {
            if (bd.mxn) {
                $.each(bd.mxn, function (key, value) {
                    if (key === 'coins') {
                        $('#cash_coins_mxn').val(value);
                    } else {
                        $('#cash_payment_modal .cash-denom-count[data-currency="mxn"][data-denom="' + key + '"]').val(value);
                    }
                });
            }
            if (bd.usd) {
                $.each(bd.usd, function (key, value) {
                    if (key === 'coins') {
                        $('#cash_coins_usd').val(value);
                    } else {
                        $('#cash_payment_modal .cash-denom-count[data-currency="usd"][data-denom="' + key + '"]').val(value);
                    }
                });
            }
            if (bd.exchange_rate) {
                $('#cash_exchange_rate').val(parseFloat(bd.exchange_rate).toFixed(2));
            }
            return;
        }

        // Old flat format: { "20": 5, "50": 3, "coins": 12.50 } — assumed MXN
        $.each(bd, function (key, value) {
            if (key === 'coins') {
                $('#cash_coins_mxn').val(value);
            } else {
                $('#cash_payment_modal .cash-denom-count[data-currency="mxn"][data-denom="' + key + '"]').val(value);
            }
        });
    }

    // Show/hide the per-row denominations button based on method
    $(document).on('change', '.payment_types_dropdown', function () {
        var btn = $(this).closest('.payment_row').find('.open-denominations-popup');
        if ($(this).val() === 'cash') {
            btn.removeClass('hide');
        } else {
            btn.addClass('hide');
            // Clear breakdown if switching away from cash
            $(this).closest('.payment_row').find('.denomination_breakdown_input').val('');
        }
    });

    // Recalc total when typing in any denom, coin, or exchange rate
    $('#cash_payment_modal').on('input', '.cash-denom-count, #cash_coins_mxn, #cash_coins_usd, #cash_exchange_rate', function () {
        recomputeCashTotals();
    });

    function recomputeCashTotals() {
        var subtotal_mxn = 0;
        var subtotal_usd = 0;

        $('#cash_payment_modal .cash-denom-count').each(function () {
            var currency = $(this).data('currency');
            var denom = parseFloat($(this).data('denom')) || 0;
            var count = parseFloat($(this).val()) || 0;
            if (currency === 'usd') {
                subtotal_usd += denom * count;
            } else {
                subtotal_mxn += denom * count;
            }
        });

        subtotal_mxn += parseFloat($('#cash_coins_mxn').val()) || 0;
        subtotal_usd += parseFloat($('#cash_coins_usd').val()) || 0;

        var rate = parseFloat(parseFloat($('#cash_exchange_rate').val()).toFixed(2)) || 0;
        var usd_in_mxn = parseFloat((subtotal_usd * rate).toFixed(2));
        var total_received = parseFloat((subtotal_mxn + usd_in_mxn).toFixed(2));

        var payable = getTotalPayable();
        var change = total_received - payable;
        if (change < 0) change = 0;

        $('#cash_subtotal_mxn').text(formatCurrency(subtotal_mxn));
        $('#cash_subtotal_usd').text('$' + subtotal_usd.toFixed(2));
        $('#cash_usd_to_mxn').text(formatCurrency(usd_in_mxn));
        $('#cash_modal_total_received').text(formatCurrency(total_received));
        $('#cash_modal_change').text(formatCurrency(change));
    }

    $('#cash_modal_pay_btn').on('click', function () {
        var mxn = {};
        var usd = {};
        var subtotal_mxn = 0;
        var subtotal_usd = 0;

        $('#cash_payment_modal .cash-denom-count').each(function () {
            var currency = $(this).data('currency');
            var denom = parseFloat($(this).data('denom')) || 0;
            var count = parseInt($(this).val()) || 0;
            if (count > 0) {
                if (currency === 'usd') {
                    usd[denom] = count;
                    subtotal_usd += denom * count;
                } else {
                    mxn[denom] = count;
                    subtotal_mxn += denom * count;
                }
            }
        });

        var coins_mxn = parseFloat($('#cash_coins_mxn').val()) || 0;
        var coins_usd = parseFloat($('#cash_coins_usd').val()) || 0;
        subtotal_mxn += coins_mxn;
        subtotal_usd += coins_usd;

        var rate = parseFloat(parseFloat($('#cash_exchange_rate').val()).toFixed(2)) || 0;
        var usd_in_mxn = parseFloat((subtotal_usd * rate).toFixed(2));
        var total_received = parseFloat((subtotal_mxn + usd_in_mxn).toFixed(2));

        // Build the new breakdown structure: { mxn: {...}, usd: {...}, exchange_rate: X }
        var breakdown = {};
        if (Object.keys(mxn).length || coins_mxn > 0) {
            breakdown.mxn = $.extend({}, mxn);
            if (coins_mxn > 0) breakdown.mxn.coins = coins_mxn;
        }
        if (Object.keys(usd).length || coins_usd > 0) {
            breakdown.usd = $.extend({}, usd);
            if (coins_usd > 0) breakdown.usd.coins = coins_usd;
            breakdown.exchange_rate = rate;
            breakdown.usd_in_mxn = usd_in_mxn;
        }

        // Combined denominations (legacy/flat) for the per-denom inputs, only MXN
        var denominations = mxn;
        var coins = coins_mxn;

        // -------- Multi-payment row mode: just fill the row and close --------
        if (cashModalTargetRow && cashModalTargetRow.length) {
            __write_number(cashModalTargetRow.find('.payment-amount').first(), total_received);
            cashModalTargetRow.find('.payment-amount').first().trigger('change');

            // Save breakdown to the row's hidden input
            cashModalTargetRow.find('.denomination_breakdown_input').val(
                Object.keys(breakdown).length ? JSON.stringify(breakdown) : ''
            );

            // Sync to existing per-denom inputs if the legacy table is shown
            $.each(denominations, function (denom, count) {
                cashModalTargetRow.find('input[name$="[denominations][' + denom + ']"]').val(count);
            });

            cashModalTargetRow = null;
            $('#cash_payment_modal').modal('hide');
            return;
        }

        // -------- Main flow: one-click checkout --------
        var payable = getTotalPayable();

        if (total_received < payable) {
            toastr.error(LANG.amount_lower_than_total || 'El monto recibido es menor al total a pagar');
            return;
        }

        // Write to first payment row using the FULL amount received (cash the
        // customer handed over). The system balances overpayment with the
        // change_return entry below, so net paid = total_received - change.
        writeFirstPaymentRow('cash', total_received);
        writeDenominations(denominations);

        // Save denomination breakdown as JSON in a hidden input that the
        // backend reads to persist into transaction_payments.denomination_breakdown
        var firstRow = $('#payment_rows_div .payment_row').first();
        var rowIndex = firstRow.find('.payment-amount').first().attr('name');
        var indexMatch = rowIndex ? rowIndex.match(/payment\[(\d+|[a-z_]+)\]/) : null;
        var idx = indexMatch ? indexMatch[1] : 0;
        var breakdownInputName = 'payment[' + idx + '][denomination_breakdown]';
        var existing = firstRow.find('input[name="' + breakdownInputName + '"]');
        if (existing.length) {
            existing.val(JSON.stringify(breakdown));
        } else {
            firstRow.append('<input type="hidden" name="' + breakdownInputName + '" value=\'' + JSON.stringify(breakdown) + '\'>');
        }

        // Set the change return amount
        var change = total_received - payable;
        if (change > 0) {
            $('#change_return').val(change);
            __write_number($('input#change_return'), change);
        }

        // Save coins as part of additional notes for the payment line for
        // human readability in the payment row note field
        if (coins > 0) {
            var noteInput = firstRow.find('textarea[name$="[note]"]').first();
            var existingNote = noteInput.val() || '';
            var coinsNote = 'Monedas: ' + formatCurrency(coins);
            noteInput.val(existingNote ? existingNote + ' | ' + coinsNote : coinsNote);
        }

        $('#cash_payment_modal').modal('hide');

        // Submit
        if (typeof pos_form_obj !== 'undefined') {
            pos_form_obj.submit();
        } else {
            $('form#add_pos_sell_form').submit();
        }
    });

    // ===========================
    //   TARJETA DEBITO / CREDITO / AMEX
    //   Uses simple card modal (only amount, no PAN/holder/etc.)
    // ===========================
    var pendingCardType = null;
    var cardTypeLabels = {
        debit: 'Tarjeta Débito',
        credit: 'Tarjeta Crédito',
        amex: 'American Express'
    };
    var cardTypeColors = {
        debit: '#2563eb',
        credit: '#1d4ed8',
        amex: '#1e40af'
    };

    $('#pos-pay-debit, #pos-pay-credit, #pos-pay-amex').on('click', function () {
        if (!preCheck()) return;

        pendingCardType = $(this).data('card_type');
        var payable = getTotalPayable();

        $('#card_simple_modal_title').text(cardTypeLabels[pendingCardType] || 'Tarjeta');
        $('#card_simple_modal_header').css('background-color', cardTypeColors[pendingCardType] || '#2563eb');
        $('#card_simple_modal_pay_btn').css('background-color', cardTypeColors[pendingCardType] || '#2563eb');
        $('#card_simple_modal_total_payable').text(formatCurrency(payable));
        $('#card_simple_amount').val(payable.toFixed(2));

        $('#card_simple_modal').modal('show');
    });

    $('#card_simple_modal').on('shown.bs.modal', function () {
        $('#card_simple_amount').select();
    });

    $('#card_simple_modal_pay_btn').on('click', function () {
        var amount = parseFloat($('#card_simple_amount').val()) || 0;
        if (amount <= 0) {
            toastr.error('El monto debe ser mayor a 0');
            return;
        }

        var terminalId = $('#card_simple_terminal').val() || '';

        // Set first payment row: method=card, amount, card_type, and terminal
        writeFirstPaymentRow('card', amount, {
            card_type: pendingCardType,
            card_terminal_id: terminalId
        });

        // Also set the hidden card_type input directly in case the
        // payment_row form has it under a different selector
        var firstRow = $('#payment_rows_div .payment_row').first();
        firstRow.find('.card_type_hidden').val(pendingCardType);

        $('#card_simple_modal').modal('hide');

        if (typeof pos_form_obj !== 'undefined') {
            pos_form_obj.submit();
        } else {
            $('form#add_pos_sell_form').submit();
        }
    });

    // ===========================
    //   PUNTOS — opens existing discount/redemption modal in points-only mode
    // ===========================
    $('#pos-pay-points').on('click', function () {
        if ($(this).is(':disabled')) return;
        if (!preCheck()) return;

        var available = parseInt($('#available_rp').text()) || 0;
        if (available <= 0) {
            toastr.warning('Este cliente no tiene puntos disponibles para canjear');
            return;
        }

        // Mark modal as opened in points-only mode and hide the discount section
        var $modal = $('#posEditDiscountModal');
        $modal.data('points_only', true);
        $modal.find('.discount-section').addClass('hide');
        $modal.find('.modal-title-discount').addClass('hide');
        // Make sure the rp section + title are visible regardless of original state
        $modal.find('.rp-section').removeClass('hide');
        $modal.find('.modal-title-rp').removeClass('hide');

        $modal.modal('show');
    });

    // Restore the modal sections when closed so the pencil-icon entry point keeps working
    $('#posEditDiscountModal').on('hidden.bs.modal', function () {
        var $modal = $(this);
        if ($modal.data('points_only')) {
            $modal.removeData('points_only');
            $modal.find('.discount-section').removeClass('hide');
            $modal.find('.modal-title-discount').removeClass('hide');
        }
    });

    // ===========================
    //   TRANSFERENCIA
    // ===========================
    $('#pos-pay-transfer').on('click', function () {
        if (!preCheck()) return;

        $('#transfer_modal_total_payable').text(formatCurrency(getTotalPayable()));
        $('#transfer_reference').val('');
        $('#transfer_sale_note').val('');
        $('#transfer_staff_note').val('');

        $('#transfer_payment_modal').modal('show');
    });

    $('#transfer_modal_pay_btn').on('click', function () {
        var ref = $.trim($('#transfer_reference').val());
        if (!ref) {
            toastr.error('Por favor ingresa el número de transferencia');
            $('#transfer_reference').focus();
            return;
        }

        var payable = getTotalPayable();
        var saleNote = $('#transfer_sale_note').val();
        var staffNote = $('#transfer_staff_note').val();

        writeFirstPaymentRow('bank_transfer', payable, {
            transaction_no: ref,
            note: saleNote
        });

        // Sale note + staff note go to top-level fields
        if (saleNote) $('textarea[name="sale_note"]').val(saleNote);
        if (staffNote) $('textarea[name="staff_note"]').val(staffNote);

        $('#transfer_payment_modal').modal('hide');

        if (typeof pos_form_obj !== 'undefined') {
            pos_form_obj.submit();
        } else {
            $('form#add_pos_sell_form').submit();
        }
    });

    // ===========================
    //   CHEQUE
    // ===========================
    $('#pos-pay-cheque').on('click', function () {
        if (!preCheck()) return;

        $('#cheque_modal_total_payable').text(formatCurrency(getTotalPayable()));
        $('#cheque_number').val('');
        $('#cheque_sale_note').val('');
        $('#cheque_staff_note').val('');

        $('#cheque_payment_modal').modal('show');
    });

    $('#cheque_modal_pay_btn').on('click', function () {
        var chequeNo = $.trim($('#cheque_number').val());
        if (!chequeNo) {
            toastr.error('Por favor ingresa el número de cheque');
            $('#cheque_number').focus();
            return;
        }

        var payable = getTotalPayable();
        var saleNote = $('#cheque_sale_note').val();
        var staffNote = $('#cheque_staff_note').val();

        writeFirstPaymentRow('cheque', payable, {
            cheque_number: chequeNo,
            note: saleNote
        });

        if (saleNote) $('textarea[name="sale_note"]').val(saleNote);
        if (staffNote) $('textarea[name="staff_note"]').val(staffNote);

        $('#cheque_payment_modal').modal('hide');

        if (typeof pos_form_obj !== 'undefined') {
            pos_form_obj.submit();
        } else {
            $('form#add_pos_sell_form').submit();
        }
    });
});
