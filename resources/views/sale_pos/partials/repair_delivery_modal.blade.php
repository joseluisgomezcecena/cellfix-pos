{{-- Modal de ENTREGA de reparación: busca orden pendiente, asigna técnico y cobra el saldo --}}
<div class="modal fade" id="repair_delivery_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color:#16a085;color:#fff;">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
                <h4 class="modal-title"><i class="fas fa-handshake"></i> @lang('lang_v1.deliver_repair')</h4>
            </div>
            <div class="modal-body">
                <div class="input-group" style="margin-bottom:10px;">
                    <input type="text" id="repair_delivery_search" class="form-control"
                        placeholder="@lang('lang_v1.search_by_customer')">
                    <span class="input-group-btn">
                        <button class="btn btn-primary" type="button" id="repair_delivery_search_btn">
                            <i class="fas fa-search"></i></button>
                    </span>
                </div>
                <div id="repair_delivery_results"></div>

                <div id="repair_delivery_panel" style="display:none; border-top:2px solid #16a085; margin-top:12px; padding-top:12px;">
                    <h4 id="rd_order_title" style="margin-top:0;"></h4>
                    <p id="rd_order_products" class="text-muted"></p>
                    <div class="row">
                        <div class="col-sm-4"><strong>Total:</strong> <span id="rd_total"></span></div>
                        <div class="col-sm-4"><strong>@lang('lang_v1.anticipo'):</strong> <span id="rd_paid"></span></div>
                        <div class="col-sm-4"><strong>DEBE:</strong> <span id="rd_balance" style="color:#c0392b;font-weight:bold;"></span></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-sm-4"><div class="form-group">
                            <label>@lang('lang_v1.technician'):</label>
                            <select id="rd_technician" class="form-control"></select>
                        </div></div>
                        <div class="col-sm-4"><div class="form-group">
                            <label>@lang('lang_v1.payment_method'):</label>
                            <select id="rd_method" class="form-control">
                                @if(array_key_exists('cash', $payment_types))
                                    <option value="cash">@lang('sale.cash')</option>
                                @endif
                                @if(array_key_exists('card', $payment_types))
                                    <option value="card:debit">@lang('lang_v1.debit_card')</option>
                                    <option value="card:credit">@lang('lang_v1.credit_card')</option>
                                    <option value="card:amex">@lang('lang_v1.american_express')</option>
                                @endif
                                @if(array_key_exists('bank_transfer', $payment_types))
                                    <option value="bank_transfer">@lang('lang_v1.transfer')</option>
                                @endif
                                @if(array_key_exists('cheque', $payment_types))
                                    <option value="cheque">@lang('lang_v1.cheque')</option>
                                @endif
                            </select>
                        </div></div>
                        <div class="col-sm-4"><div class="form-group">
                            <label>@lang('lang_v1.amount_to_collect') (MXN $):</label>
                            <input type="number" min="0" step="0.01" id="rd_payment" class="form-control">
                        </div></div>
                    </div>
                    <div class="row" id="rd_card_row" style="display:none;">
                        <div class="col-sm-4"><div class="form-group">
                            <label>@lang('lang_v1.card_terminal'):</label>
                            <select id="rd_terminal" class="form-control"></select>
                        </div></div>
                    </div>
                    <div class="row" style="background:#fffde7; padding:8px; border-radius:4px; margin:0 0 10px 0;">
                        <div class="col-sm-4"><div class="form-group" style="margin-bottom:0;">
                            <label>@lang('lang_v1.pay_in_usd') (USD $):</label>
                            <input type="number" min="0" step="0.01" id="rd_usd" class="form-control" placeholder="0">
                        </div></div>
                        <div class="col-sm-4"><div class="form-group" style="margin-bottom:0;">
                            <label>@lang('lang_v1.exchange_rate'):</label>
                            <input type="number" min="0" step="0.01" id="rd_rate" class="form-control">
                        </div></div>
                        <div class="col-sm-4" style="padding-top:22px;"><small class="text-muted">@lang('lang_v1.usd_note')</small></div>
                    </div>
                    <input type="hidden" id="rd_order_id" value="">
                    <button type="button" class="btn btn-success btn-lg" id="repair_deliver_btn">
                        <i class="fas fa-check"></i> @lang('lang_v1.collect_and_deliver')
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
(function () {
    function boot() {
        if (!window.jQuery) { return setTimeout(boot, 50); }
        jQuery(function ($) {
    var rdTechnicians = [];
    var rdTerminals = [];
    var rdRate = 18;

    function rdSearch() {
        var term = $('#repair_delivery_search').val();
        $.get('{{ route('repair-orders.pending') }}', { term: term }, function (data) {
            rdTechnicians = data.technicians || [];
            rdTerminals = data.terminals || [];
            rdRate = data.exchange_rate || 18;
            var html = '';
            if (!data.orders || data.orders.length === 0) {
                html = '<p class="text-muted text-center">@lang('lang_v1.no_pending_repairs')</p>';
            } else {
                html = '<table class="table table-bordered table-hover" style="font-size:13px;"><thead><tr>'
                    + '<th>@lang('messages.date')</th><th>@lang('contact.customer')</th><th>Tel</th><th>@lang('sale.product')</th>'
                    + '<th class="text-right">Total</th><th class="text-right">DEBE</th><th></th></tr></thead><tbody>';
                $.each(data.orders, function (i, o) {
                    html += '<tr><td>' + o.date + '</td><td>' + o.customer + '</td><td>' + (o.mobile || '') + '</td>'
                        + '<td><small>' + (o.products || '') + '</small></td>'
                        + '<td class="text-right">$' + o.total.toFixed(2) + '</td>'
                        + '<td class="text-right" style="color:#c0392b;font-weight:bold;">$' + o.balance.toFixed(2) + '</td>'
                        + '<td><button type="button" class="btn btn-xs btn-success rd-select" '
                        + 'data-id="' + o.id + '" data-customer="' + $('<div>').text(o.customer).html() + '" '
                        + 'data-products="' + $('<div>').text(o.products || '').html() + '" '
                        + 'data-total="' + o.total + '" data-paid="' + o.paid + '" data-balance="' + o.balance + '">'
                        + '@lang('lang_v1.deliver')</button></td></tr>';
                });
                html += '</tbody></table>';
            }
            $('#repair_delivery_results').html(html);
            $('#repair_delivery_panel').hide();
        }, 'json');
    }

    $('#pos-deliver-repair').click(function () {
        $('#repair_delivery_search').val('');
        $('#repair_delivery_results').html('');
        $('#repair_delivery_panel').hide();
        $('#repair_delivery_modal').modal('show');
    });

    $('#repair_delivery_search_btn').click(rdSearch);
    $('#repair_delivery_search').on('keypress', function (e) {
        if (e.which === 13) { e.preventDefault(); rdSearch(); }
    });

    $(document).on('click', '.rd-select', function () {
        var b = $(this);
        $('#rd_order_id').val(b.data('id'));
        $('#rd_order_title').text(b.data('customer'));
        $('#rd_order_products').text(b.data('products'));
        $('#rd_total').text('$' + parseFloat(b.data('total')).toFixed(2));
        $('#rd_paid').text('$' + parseFloat(b.data('paid')).toFixed(2));
        $('#rd_balance').text('$' + parseFloat(b.data('balance')).toFixed(2));
        $('#rd_payment').val(parseFloat(b.data('balance')).toFixed(2));
        var opts = '<option value="">-- @lang('lang_v1.technician') --</option>';
        $.each(rdTechnicians, function (i, t) { opts += '<option value="' + t.id + '">' + t.name + '</option>'; });
        $('#rd_technician').html(opts);
        // terminales
        var topts = '<option value="">-- @lang('lang_v1.card_terminal') --</option>';
        $.each(rdTerminals, function (i, t) { topts += '<option value="' + t.id + '">' + t.name + '</option>'; });
        $('#rd_terminal').html(topts);
        // dólares
        $('#rd_usd').val('');
        $('#rd_rate').val(parseFloat(rdRate).toFixed(2));
        $('#rd_method').trigger('change');
        $('#repair_delivery_panel').show();
    });

    // Mostrar terminal solo si el método es tarjeta
    $('#rd_method').on('change', function () {
        var isCard = (($(this).val() || '').indexOf('card') === 0);
        $('#rd_card_row').toggle(isCard);
    });

    // Si capturan monto en dólares, calcular el equivalente en MXN
    $('#rd_usd, #rd_rate').on('input', function () {
        var usd = parseFloat($('#rd_usd').val()) || 0;
        var rate = parseFloat($('#rd_rate').val()) || 0;
        if (usd > 0 && rate > 0) {
            $('#rd_payment').val((usd * rate).toFixed(2));
        }
    });

    $('#repair_deliver_btn').click(function () {
        var id = $('#rd_order_id').val();
        if (!id) { return; }
        var btn = $(this);
        btn.prop('disabled', true);
        var rawMethod = ($('#rd_method').val() || 'cash').split(':');
        $.ajax({
            method: 'POST',
            url: '/repair-orders/' + id + '/deliver',
            data: {
                technician_id: $('#rd_technician').val(),
                payment_amount: $('#rd_payment').val(),
                payment_method: rawMethod[0],
                card_type: rawMethod[1] || '',
                card_terminal_id: $('#rd_terminal').val(),
                usd_amount: $('#rd_usd').val(),
                exchange_rate: $('#rd_rate').val(),
                _token: '{{ csrf_token() }}'
            },
            dataType: 'json',
            success: function (res) {
                btn.prop('disabled', false);
                if (res.success) {
                    toastr.success(res.msg);
                    $('#repair_delivery_modal').modal('hide');
                    // Imprimir el recibo de la entrega (reusa el helper del POS)
                    if (res.transaction_id) {
                        $.ajax({
                            url: '/sells/' + res.transaction_id + '/print',
                            method: 'GET',
                            dataType: 'json',
                            success: function (pr) {
                                if (pr.success && typeof pos_print === 'function') {
                                    pos_print(pr.receipt);
                                }
                            }
                        });
                    }
                } else {
                    toastr.error(res.msg);
                }
            },
            error: function () { btn.prop('disabled', false); toastr.error('Error'); }
        });
    });
        });
    }
    boot();
})();
</script>
