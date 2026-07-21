<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('layaway::lang.make_payment')</h4>
        </div>

        {!! Form::open(['url' => action('\\Modules\\Layaway\\Http\\Controllers\\LayawayPaymentController@store', $layaway->id), 'method' => 'post', 'id' => 'payment_form']) !!}

        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <h4>@lang('layaway::lang.layaway_details')</h4>
                    <p><strong>@lang('layaway::lang.layaway_number'):</strong> {{ $layaway->layaway_number }}</p>
                    <p><strong>@lang('layaway::lang.customer'):</strong> {{ $layaway->contact->name ?? '' }}</p>
                    <p><strong>@lang('layaway::lang.total_amount'):</strong> <span class="display_currency" data-currency_symbol="true">{{ $layaway->total_amount }}</span></p>
                    <p><strong>@lang('layaway::lang.total_paid'):</strong> <span class="display_currency" data-currency_symbol="true">{{ $layaway->total_paid }}</span></p>
                    <p><strong>@lang('layaway::lang.balance_due'):</strong> <span class="display_currency text-danger" data-currency_symbol="true">{{ $layaway->balance_due }}</span></p>

                    @if($layaway->status == 'pending')
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            @lang('layaway::lang.down_payment_required', ['amount' => number_format($layaway->down_payment_amount, 2)])
                        </div>
                    @endif
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('amount', __('layaway::lang.payment_amount') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fas fa-rupee-sign"></i>
                            </span>
                            {!! Form::number('amount', $layaway->balance_due, ['class' => 'form-control', 'placeholder' => __('layaway::lang.payment_amount'), 'required', 'min' => '0.01', 'max' => $layaway->balance_due, 'step' => '0.01']) !!}
                        </div>
                        <small class="text-muted">@lang('layaway::lang.maximum_amount'): <span class="display_currency" data-currency_symbol="true">{{ $layaway->balance_due }}</span></small>
                    </div>

                    <div class="form-group">
                        {!! Form::label('payment_date', __('layaway::lang.payment_date') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-calendar"></i>
                            </span>
                            {!! Form::text('payment_date', null, ['class' => 'form-control', 'placeholder' => __('layaway::lang.payment_date'), 'required']) !!}
                        </div>
                    </div>

                    @if(count($registers) > 0)
                        <div class="form-group">
                            {!! Form::label('cash_register_id', __('layaway::lang.cash_register') . ':') !!}
                            {!! Form::select('cash_register_id', $registers, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'style' => 'width: 100%;']) !!}
                        </div>
                    @endif

                    <div class="form-group">
                        {!! Form::label('notes', __('layaway::lang.payment_notes') . ':') !!}
                        {!! Form::textarea('notes', null, ['class' => 'form-control', 'placeholder' => __('layaway::lang.payment_notes'), 'rows' => 3]) !!}
                    </div>
                </div>
            </div>

            <!-- Multi-pago: lista de renglones. Cada renglón es un método + monto +
                 campos específicos. Se agregan/quitan con los botones y sumados
                 deben igualar el "Monto a pagar" de arriba. -->
            @php
                $card_terminals = \App\CardTerminal::forDropdown(session('user.business_id'));
            @endphp
            <hr>
            <h4 style="margin-top:0;">Métodos de pago</h4>
            <p class="text-muted" style="margin-bottom:12px;">
                Divide el monto entre uno o más métodos. La suma debe igualar el monto a pagar.
                Para efectivo se captura el desglose de billetes debajo del renglón.
            </p>

            <div id="layaway-payment-rows-container"></div>

            <button type="button" id="layaway-add-payment-row" class="btn btn-info btn-sm">
                <i class="fa fa-plus"></i> Agregar otro método de pago
            </button>

            <div class="alert" style="background:#fff3cd; border:1px solid #ffc107; margin-top:15px;">
                <div class="row">
                    <div class="col-md-4">
                        <strong>Monto a pagar:</strong>
                        <span id="layaway-target-amount" style="font-size:16px;">$0.00</span>
                    </div>
                    <div class="col-md-4">
                        <strong>Suma renglones:</strong>
                        <span id="layaway-rows-sum" style="font-size:16px;">$0.00</span>
                    </div>
                    <div class="col-md-4">
                        <strong>Diferencia:</strong>
                        <span id="layaway-rows-diff" style="font-size:16px; color:#c62828;">$0.00</span>
                    </div>
                </div>
            </div>

            {{-- Template de renglón (no se renderiza directo; el JS lo clona). --}}
            <script type="text/x-template" id="layaway-payment-row-template">
                <div class="layaway-payment-row panel panel-default" data-idx="__IDX__" style="margin-bottom:12px;">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label>Método:</label>
                                <select name="payments[__IDX__][method]" class="form-control layaway-row-method">
                                    <option value="cash">Efectivo</option>
                                    <option value="card">Tarjeta</option>
                                    <option value="bank_transfer">Transferencia</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="other">Otro</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Monto:</label>
                                <input type="number" name="payments[__IDX__][amount]" class="form-control layaway-row-amount" step="0.01" min="0.01" placeholder="0.00">
                            </div>
                            <div class="col-md-5 layaway-row-fields">
                                {{-- Campos por método (poblado por JS). --}}
                            </div>
                            <div class="col-md-1 text-right">
                                <label>&nbsp;</label>
                                <button type="button" class="btn btn-danger btn-sm layaway-remove-row" title="Quitar renglón">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="layaway-cash-details" style="display:none; margin-top:12px; border-top:1px dashed #ddd; padding-top:10px;">
                            {{-- Desglose de billetes para renglones de efectivo (poblado por JS). --}}
                        </div>
                    </div>
                </div>
            </script>

            {{-- Templates de campos específicos por método --}}
            <script type="text/x-template" id="layaway-row-fields-card">
                <div class="row">
                    <div class="col-md-6">
                        <label>Tipo tarjeta:</label>
                        <select name="payments[__IDX__][card_type]" class="form-control input-sm">
                            <option value="debit">{{ __('lang_v1.debit_card') }}</option>
                            <option value="credit">{{ __('lang_v1.credit_card') }}</option>
                            <option value="amex">{{ __('lang_v1.american_express') }}</option>
                        </select>
                    </div>
                    @if(count($card_terminals) > 0)
                    <div class="col-md-6">
                        <label>Terminal:</label>
                        <select name="payments[__IDX__][card_terminal_id]" class="form-control input-sm layaway-card-terminal">
                            <option value="">— Selecciona —</option>
                            @foreach($card_terminals as $tid => $tname)
                                <option value="{{ $tid }}">{{ $tname }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </div>
            </script>

            <script type="text/x-template" id="layaway-row-fields-bank_transfer">
                <label>Cuenta bancaria:</label>
                <input type="text" name="payments[__IDX__][bank_account_number]" class="form-control input-sm" placeholder="Ref / cuenta">
            </script>

            <script type="text/x-template" id="layaway-row-fields-cheque">
                <div class="row">
                    <div class="col-md-6">
                        <label>Nº cheque:</label>
                        <input type="text" name="payments[__IDX__][cheque_number]" class="form-control input-sm">
                    </div>
                    <div class="col-md-6">
                        <label>Cuenta:</label>
                        <input type="text" name="payments[__IDX__][bank_account_number]" class="form-control input-sm">
                    </div>
                </div>
            </script>

            <script type="text/x-template" id="layaway-row-fields-other">
                <label>Nº transacción:</label>
                <input type="text" name="payments[__IDX__][transaction_no]" class="form-control input-sm">
            </script>

            {{-- Template de desglose de billetes por renglón de cash --}}
            <script type="text/x-template" id="layaway-row-cash-desglose">
                <div class="alert alert-info" style="padding:8px; margin-bottom:8px;">
                    <strong>Desglose de billetes recibidos (renglón #<span class="cash-row-num">__IDX__</span>):</strong>
                    Total capturado: <strong class="layaway-cash-total-r">$0.00</strong>
                    | Cambio: <strong class="layaway-cash-change-r" style="color:#c62828;">$0.00</strong>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <strong>PESOS (MXN)</strong>
                        <table class="table table-condensed table-bordered" style="margin-bottom:6px;">
                            <thead><tr><th class="text-right">Denom</th><th class="text-center">Cant</th></tr></thead>
                            <tbody>
                                @foreach([1000, 500, 200, 100, 50, 20] as $face)
                                    <tr>
                                        <td class="text-right"><strong>${{ $face }}</strong></td>
                                        <td><input type="number" min="0" step="1" class="form-control input-sm layaway-r-mxn" data-denom="{{ $face }}" placeholder="0"></td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td class="text-right"><strong>Monedas</strong></td>
                                    <td><input type="number" min="0" step="0.01" class="form-control input-sm layaway-r-coins-mxn" placeholder="0.00"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <strong>DÓLARES (USD)</strong>
                        <label style="font-weight:normal; font-size:11px;">Tipo cambio USD→MXN:
                            <input type="number" step="0.01" min="0" class="form-control input-sm layaway-r-exchange" value="17.20" style="display:inline-block; width:70px;">
                        </label>
                        <table class="table table-condensed table-bordered" style="margin-bottom:6px;">
                            <thead><tr><th class="text-right">Denom</th><th class="text-center">Cant</th></tr></thead>
                            <tbody>
                                @foreach([100, 50, 20, 10, 5, 1] as $face)
                                    <tr>
                                        <td class="text-right"><strong>${{ $face }}</strong></td>
                                        <td><input type="number" min="0" step="1" class="form-control input-sm layaway-r-usd" data-denom="{{ $face }}" placeholder="0"></td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td class="text-right"><strong>Monedas USD</strong></td>
                                    <td><input type="number" min="0" step="0.01" class="form-control input-sm layaway-r-coins-usd" placeholder="0.00"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <input type="hidden" name="payments[__IDX__][denomination_breakdown]" class="layaway-r-breakdown" value="">
                <input type="hidden" name="payments[__IDX__][change_return_amount]" class="layaway-r-change" value="0">
            </script>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
            <button type="submit" class="btn btn-primary">@lang('layaway::lang.process_payment')</button>
        </div>

        {!! Form::close() !!}
    </div>
</div>

<script type="text/javascript">
$(document).ready(function(){
    // Initialize datepicker with current date/time
    var currentDateTime = moment().format(moment_date_format + ' ' + moment_time_format);
    $('input[name="payment_date"]').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
        defaultDate: moment()
    }).val(currentDateTime);

    // Initialize select2
    $('.select2').select2();

    // ==================== MULTI-PAGO ====================
    var rowIdx = 0;
    var max_amount = {{ $layaway->balance_due }};
    $('input[name="amount"]').attr('max', max_amount);

    function renderRowTemplate(tpl, idx) {
        return tpl.replace(/__IDX__/g, idx);
    }

    // Añade un renglón nuevo con método por default 'cash'.
    function addPaymentRow(defaultMethod, defaultAmount) {
        var idx = rowIdx++;
        var tpl = $('#layaway-payment-row-template').html();
        var $row = $(renderRowTemplate(tpl, idx));
        $('#layaway-payment-rows-container').append($row);
        if (typeof defaultAmount === 'number') {
            $row.find('.layaway-row-amount').val(defaultAmount.toFixed(2));
        }
        $row.find('.layaway-row-method').val(defaultMethod || 'cash').trigger('change');
        recomputeRowsSum();
    }

    // Renderiza los campos específicos según método (card, transfer, cheque, other)
    // y muestra/oculta el desglose de billetes para efectivo.
    $(document).on('change', '.layaway-row-method', function () {
        var $row = $(this).closest('.layaway-payment-row');
        var idx = $row.data('idx');
        var method = $(this).val();
        var $fields = $row.find('.layaway-row-fields');
        var $cashDetails = $row.find('.layaway-cash-details');

        $fields.empty();
        $cashDetails.hide().empty();

        var tplId = null;
        if (method === 'card') tplId = 'layaway-row-fields-card';
        else if (method === 'bank_transfer') tplId = 'layaway-row-fields-bank_transfer';
        else if (method === 'cheque') tplId = 'layaway-row-fields-cheque';
        else if (method === 'other') tplId = 'layaway-row-fields-other';

        if (tplId) {
            var tpl = $('#' + tplId).html();
            $fields.html(renderRowTemplate(tpl, idx));
        }

        if (method === 'cash') {
            var cashTpl = $('#layaway-row-cash-desglose').html();
            $cashDetails.html(renderRowTemplate(cashTpl, idx)).show();
            $cashDetails.find('.cash-row-num').text(idx + 1);
            recomputeCashRow($row);
        }
    });

    // Botón "Agregar otro método de pago"
    $('#layaway-add-payment-row').on('click', function () {
        // Calcula lo que aún falta para llegar al monto total
        var target = parseFloat($('input[name="amount"]').val()) || 0;
        var sum = 0;
        $('.layaway-row-amount').each(function () {
            sum += parseFloat($(this).val()) || 0;
        });
        var diff = Math.max(0, parseFloat((target - sum).toFixed(2)));
        addPaymentRow('cash', diff > 0 ? diff : undefined);
    });

    // Quitar un renglón
    $(document).on('click', '.layaway-remove-row', function () {
        if ($('.layaway-payment-row').length <= 1) {
            toastr.info('Debe haber al menos un método de pago.');
            return;
        }
        $(this).closest('.layaway-payment-row').remove();
        recomputeRowsSum();
    });

    // Recalcular desglose cash del renglón dado
    function recomputeCashRow($row) {
        var $details = $row.find('.layaway-cash-details');
        if (!$details.length || $details.is(':hidden')) return;

        var mxn = {}, usd = {};
        var mxn_sub = 0, usd_sub = 0;
        $details.find('.layaway-r-mxn').each(function () {
            var d = $(this).data('denom');
            var c = parseInt($(this).val()) || 0;
            if (c > 0) { mxn[d] = c; mxn_sub += d * c; }
        });
        $details.find('.layaway-r-usd').each(function () {
            var d = $(this).data('denom');
            var c = parseInt($(this).val()) || 0;
            if (c > 0) { usd[d] = c; usd_sub += d * c; }
        });
        var coins_mxn = parseFloat($details.find('.layaway-r-coins-mxn').val()) || 0;
        var coins_usd = parseFloat($details.find('.layaway-r-coins-usd').val()) || 0;
        mxn_sub += coins_mxn;
        usd_sub += coins_usd;

        var rate = parseFloat($details.find('.layaway-r-exchange').val()) || 0;
        var usd_in_mxn = parseFloat((usd_sub * rate).toFixed(2));
        var total = parseFloat((mxn_sub + usd_in_mxn).toFixed(2));

        var rowAmount = parseFloat($row.find('.layaway-row-amount').val()) || 0;
        var change = Math.max(0, parseFloat((total - rowAmount).toFixed(2)));

        $details.find('.layaway-cash-total-r').text('$' + total.toFixed(2));
        $details.find('.layaway-cash-change-r').text('$' + change.toFixed(2));

        var bd = {};
        if (Object.keys(mxn).length || coins_mxn > 0) {
            bd.mxn = $.extend({}, mxn);
            if (coins_mxn > 0) bd.mxn.coins = coins_mxn;
        }
        if (Object.keys(usd).length || coins_usd > 0) {
            bd.usd = $.extend({}, usd);
            if (coins_usd > 0) bd.usd.coins = coins_usd;
            bd.exchange_rate = rate;
            bd.usd_in_mxn = usd_in_mxn;
        }
        $details.find('.layaway-r-breakdown').val(Object.keys(bd).length ? JSON.stringify(bd) : '');
        $details.find('.layaway-r-change').val(change);
    }

    // Recalcular el indicador de suma total
    function recomputeRowsSum() {
        var target = parseFloat($('input[name="amount"]').val()) || 0;
        var sum = 0;
        $('.layaway-row-amount').each(function () {
            sum += parseFloat($(this).val()) || 0;
        });
        var diff = parseFloat((target - sum).toFixed(2));
        $('#layaway-target-amount').text('$' + target.toFixed(2));
        $('#layaway-rows-sum').text('$' + sum.toFixed(2));
        $('#layaway-rows-diff').text('$' + diff.toFixed(2));
        $('#layaway-rows-diff').css('color', Math.abs(diff) < 0.01 ? '#2e7d32' : '#c62828');
    }

    // Delegación: input en cualquier input relacionado recalcula suma/desglose
    $(document).on('input', 'input[name="amount"]', function () {
        recomputeRowsSum();
        $('.layaway-payment-row').each(function () {
            if ($(this).find('.layaway-row-method').val() === 'cash') {
                recomputeCashRow($(this));
            }
        });
    });
    $(document).on('input', '.layaway-row-amount', function () {
        recomputeRowsSum();
        var $row = $(this).closest('.layaway-payment-row');
        if ($row.find('.layaway-row-method').val() === 'cash') {
            recomputeCashRow($row);
        }
    });
    $(document).on('input', '.layaway-r-mxn, .layaway-r-usd, .layaway-r-coins-mxn, .layaway-r-coins-usd, .layaway-r-exchange', function () {
        recomputeCashRow($(this).closest('.layaway-payment-row'));
    });

    // Renglón inicial: efectivo, monto = balance_due
    addPaymentRow('cash', max_amount);

    // Form validation and AJAX submission
    $('#payment_form').submit(function(e) {
        e.preventDefault();

        var amount = parseFloat($('input[name="amount"]').val());

        if(amount <= 0) {
            toastr.error("@lang('layaway::lang.amount_required')");
            return false;
        }

        if(amount > max_amount) {
            toastr.error("@lang('layaway::lang.payment_exceeds_balance')");
            return false;
        }

        // Suma de renglones debe igualar el monto a pagar
        var sum = 0;
        $('.layaway-row-amount').each(function () {
            sum += parseFloat($(this).val()) || 0;
        });
        if (Math.abs(sum - amount) > 0.01) {
            toastr.error('La suma de los métodos de pago (' + sum.toFixed(2) + ') no coincide con el monto a pagar (' + amount.toFixed(2) + ').');
            return false;
        }

        // Validaciones por renglón
        var rowError = null;
        $('.layaway-payment-row').each(function () {
            var $row = $(this);
            var method = $row.find('.layaway-row-method').val();
            var rowAmount = parseFloat($row.find('.layaway-row-amount').val()) || 0;
            if (rowAmount <= 0) {
                rowError = 'Cada renglón de pago debe tener un monto mayor a 0.';
                return false;
            }
            if (method === 'card') {
                var $term = $row.find('.layaway-card-terminal');
                if ($term.length && !$term.val()) {
                    rowError = 'Debes seleccionar una terminal para el pago con tarjeta.';
                    $term.focus();
                    return false;
                }
            } else if (method === 'cash') {
                var bd = ($row.find('.layaway-r-breakdown').val() || '').trim();
                if (!bd) {
                    rowError = 'Captura el desglose de billetes en el renglón de efectivo.';
                    return false;
                }
            }
        });
        if (rowError) {
            toastr.error(rowError);
            return false;
        }

        // Disable submit button to prevent double submission
        var $submitBtn = $(this).find('button[type="submit"]');
        var originalText = $submitBtn.text();
        $submitBtn.prop('disabled', true).text('@lang("messages.please_wait")');

        // Submit via AJAX
        var formData = $(this).serializeArray();

        // Convert payment_date to Laravel-compatible format
        var paymentDateField = formData.find(field => field.name === 'payment_date');
        if (paymentDateField) {
            var momentDate = moment(paymentDateField.value, moment_date_format + ' ' + moment_time_format);
            if (momentDate.isValid()) {
                paymentDateField.value = momentDate.format('YYYY-MM-DD HH:mm:ss');
            }
        }

        $.ajax({
            method: 'POST',
            url: $(this).attr('action'),
            dataType: 'json',
            data: formData,
            success: function(result) {
                if (result.success == true) {
                    toastr.success(result.msg);
                    $('.layaway_modal').modal('hide');

                    // Abre el recibo en una pestaña nueva si el backend regresó el id
                    // del pago. Antes solo se recargaba la página y la cajera nunca
                    // veía el recibo. La ventana emergente puede ser bloqueada por el
                    // navegador — si eso pasa, el link queda en la lista de pagos.
                    if (result.payment_id) {
                        try {
                            window.open(
                                '{{ url('/layaway/payments/receipt') }}/' + result.payment_id,
                                '_blank'
                            );
                        } catch (e) {}
                    }

                    // Reload the page to update payment history
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    toastr.error(result.msg);
                }
            },
            error: function(xhr) {
                if (xhr.status == 422) {
                    // Validation errors
                    var errors = xhr.responseJSON.errors;
                    var errorMsg = '';
                    $.each(errors, function(key, value) {
                        errorMsg += value[0] + '<br>';
                    });
                    toastr.error(errorMsg);
                } else {
                    toastr.error('@lang("messages.something_went_wrong")');
                }
            },
            complete: function() {
                // Re-enable submit button
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });

    // Quick amount buttons
    @if($layaway->status == 'pending')
        // Add quick button for down payment amount
        var down_payment = {{ $layaway->down_payment_amount }};
        $('<button type="button" class="btn btn-info btn-sm" style="margin-top: 5px;">Down Payment (' + __currency_trans_from_en(down_payment, true) + ')</button>')
            .insertAfter('input[name="amount"]')
            .click(function() {
                $('input[name="amount"]').val(down_payment);
            });
    @endif

    // Add quick button for full payment
    $('<button type="button" class="btn btn-success btn-sm" style="margin-top: 5px; margin-left: 5px;">Full Payment</button>')
        .insertAfter('input[name="amount"]')
        .click(function() {
            $('input[name="amount"]').val(max_amount);
        });

    // Initialize currency display
    __currency_convert_recursively($('.modal-content'));
});
</script>