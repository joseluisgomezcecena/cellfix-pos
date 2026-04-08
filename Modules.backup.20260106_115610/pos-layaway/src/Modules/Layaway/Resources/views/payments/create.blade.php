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
                            {!! Form::number('amount', null, ['class' => 'form-control', 'placeholder' => __('layaway::lang.payment_amount'), 'required', 'min' => '0.01', 'max' => $layaway->balance_due, 'step' => '0.01']) !!}
                        </div>
                        <small class="text-muted">@lang('layaway::lang.maximum_amount'): <span class="display_currency" data-currency_symbol="true">{{ $layaway->balance_due }}</span></small>
                    </div>

                    <div class="form-group">
                        {!! Form::label('payment_method', __('layaway::lang.payment_method') . ':*') !!}
                        {!! Form::select('payment_method', $payment_methods, 'cash', ['class' => 'form-control select2', 'placeholder' => __('layaway::lang.select_payment_method'), 'required', 'style' => 'width: 100%;']) !!}
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

            <!-- Payment method specific fields -->
            <div class="row payment_method_fields" id="card_fields" style="display: none;">
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('card_number', __('lang_v1.card_number') . ':') !!}
                        {!! Form::text('card_number', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.card_number')]) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('card_holder_name', __('lang_v1.card_holder_name') . ':') !!}
                        {!! Form::text('card_holder_name', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.card_holder_name')]) !!}
                    </div>
                </div>
            </div>

            <div class="row payment_method_fields" id="cheque_fields" style="display: none;">
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('cheque_number', __('lang_v1.cheque_number') . ':') !!}
                        {!! Form::text('cheque_number', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.cheque_number')]) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('bank_account_number', __('lang_v1.bank_account_number') . ':') !!}
                        {!! Form::text('bank_account_number', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.bank_account_number')]) !!}
                    </div>
                </div>
            </div>

            <div class="row payment_method_fields" id="bank_transfer_fields" style="display: none;">
                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('bank_account_number', __('lang_v1.bank_account_number') . ':') !!}
                        {!! Form::text('bank_account_number', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.bank_account_number')]) !!}
                    </div>
                </div>
            </div>

            <div class="row payment_method_fields" id="other_fields" style="display: none;">
                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('transaction_no', __('lang_v1.transaction_no') . ':') !!}
                        {!! Form::text('transaction_no', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.transaction_no')]) !!}
                    </div>
                </div>
            </div>
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

    // Payment method change
    $('select[name="payment_method"]').change(function(){
        var method = $(this).val();

        // Hide all payment method fields
        $('.payment_method_fields').hide();

        // Show relevant fields based on payment method
        if(method == 'card') {
            $('#card_fields').show();
        } else if(method == 'cheque') {
            $('#cheque_fields').show();
        } else if(method == 'bank_transfer') {
            $('#bank_transfer_fields').show();
        } else if(method == 'other') {
            $('#other_fields').show();
        }
    });

    // Set maximum amount
    var max_amount = {{ $layaway->balance_due }};
    $('input[name="amount"]').attr('max', max_amount);

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

                    // Reload the page to update payment history
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
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