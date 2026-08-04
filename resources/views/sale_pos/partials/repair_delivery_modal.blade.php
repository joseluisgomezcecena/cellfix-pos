{{-- Modal de ENTREGA de reparación: busca orden pendiente, asigna técnico y cobra el saldo.
     Soporta pago múltiple (cash + tarjeta + transferencia). En efectivo, cada fila puede
     capturar billetes MXN y/o USD combinados; el total debe cuadrar con el monto de la fila. --}}
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
                    <div class="form-group">
                        <label>@lang('lang_v1.technician'):</label>
                        <select id="rd_technician" class="form-control" style="max-width:400px;"></select>
                    </div>

                    <hr style="border-color:#16a085;">

                    <label style="font-size:14px; font-weight:700; color:#16a085;">
                        <i class="fas fa-money-check-alt"></i> Formas de pago
                    </label>
                    <div id="rd_payment_rows"></div>

                    <button type="button" class="btn btn-info btn-sm" id="rd_add_payment_row">
                        <i class="fas fa-plus"></i> Agregar forma de pago
                    </button>

                    <div style="margin-top:14px; padding:10px; background:#f5f5f5; border-radius:4px;">
                        <div class="row">
                            <div class="col-sm-6"><strong>Total pagado:</strong> <span id="rd_grand_total" style="font-weight:700;">$0.00</span></div>
                            <div class="col-sm-6"><strong>Faltante:</strong> <span id="rd_remaining" style="color:#c62828; font-weight:700;">$0.00</span></div>
                        </div>
                    </div>

                    <input type="hidden" id="rd_order_id" value="">
                    <button type="button" class="btn btn-success btn-lg" id="repair_deliver_btn" style="margin-top:10px;">
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

{{-- Template de una fila de pago. __IDX__ se reemplaza al insertar. --}}
<script type="text/x-template" id="rd_payment_row_tpl">
    <div class="rd-payment-row" data-row-idx="__IDX__" style="border:1px solid #ddd; padding:10px; border-radius:4px; margin-bottom:10px; background:#fff;">
        <div class="row">
            <div class="col-sm-3"><div class="form-group" style="margin-bottom:6px;">
                <label style="font-size:12px;">@lang('lang_v1.payment_method'):</label>
                <select class="form-control input-sm rd-method">
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
            <div class="col-sm-3"><div class="form-group" style="margin-bottom:6px;">
                <label style="font-size:12px;">Monto MXN:</label>
                <input type="number" min="0" step="0.01" class="form-control input-sm rd-amount-mxn" placeholder="0">
            </div></div>
            <div class="col-sm-2 rd-usd-col"><div class="form-group" style="margin-bottom:6px;">
                <label style="font-size:12px;">Monto USD:</label>
                <input type="number" min="0" step="0.01" class="form-control input-sm rd-amount-usd" placeholder="0">
            </div></div>
            <div class="col-sm-2 rd-usd-col"><div class="form-group" style="margin-bottom:6px;">
                <label style="font-size:12px;">TC:</label>
                <input type="number" min="0" step="0.01" class="form-control input-sm rd-rate">
            </div></div>
            <div class="col-sm-2 rd-terminal-col" style="display:none;"><div class="form-group" style="margin-bottom:6px;">
                <label style="font-size:12px;">Terminal:</label>
                <select class="form-control input-sm rd-terminal"></select>
            </div></div>
            <div class="col-sm-1" style="padding-top:22px;">
                <button type="button" class="btn btn-xs btn-danger rd-remove-row" title="Quitar" style="display:none;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        {{-- Desglose de billetes (MXN + USD) — solo visible cuando el método es Efectivo --}}
        <div class="rd-denom-panel" style="display:none; margin-top:8px; padding:8px; background:#eaf6ff; border-radius:4px; border:1px dashed #1e88e5;">
            <div class="row">
                <div class="col-sm-6">
                    <label style="font-size:12px; font-weight:700; color:#0d47a1;">Billetes MXN:</label>
                    <div class="row">
                        @foreach([1000, 500, 200, 100, 50, 20] as $face)
                            <div class="col-xs-4" style="margin-bottom:4px;">
                                <label style="font-size:11px; margin:0;">${{ $face }}</label>
                                <input type="number" min="0" step="1" class="form-control input-sm rd-mxn-denom" data-denom="{{ $face }}" placeholder="0">
                            </div>
                        @endforeach
                        <div class="col-xs-4" style="margin-bottom:4px;">
                            <label style="font-size:11px; margin:0;">Monedas</label>
                            <input type="number" min="0" step="0.01" class="form-control input-sm rd-mxn-coins" placeholder="0.00">
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <label style="font-size:12px; font-weight:700; color:#c65500;">Billetes USD:</label>
                    <div class="row">
                        @foreach([100, 50, 20, 10, 5, 1] as $face)
                            <div class="col-xs-4" style="margin-bottom:4px;">
                                <label style="font-size:11px; margin:0;">${{ $face }}</label>
                                <input type="number" min="0" step="1" class="form-control input-sm rd-usd-denom" data-denom="{{ $face }}" placeholder="0">
                            </div>
                        @endforeach
                        <div class="col-xs-4" style="margin-bottom:4px;">
                            <label style="font-size:11px; margin:0;">Monedas</label>
                            <input type="number" min="0" step="0.01" class="form-control input-sm rd-usd-coins" placeholder="0.00">
                        </div>
                    </div>
                </div>
            </div>
            <div style="margin-top:6px; padding:4px 8px; background:#fff; border-radius:3px; font-size:12px;">
                MXN capturado: <strong class="rd-mxn-captured">$0.00</strong>
                &nbsp;|&nbsp;
                USD capturado: <strong class="rd-usd-captured">$0.00</strong>
                &nbsp;|&nbsp;
                USD en MXN: <strong class="rd-usd-in-mxn">$0.00</strong>
                &nbsp;|&nbsp;
                <span style="color:#0d47a1;">Total desglose: <strong class="rd-total-captured">$0.00</strong></span>
            </div>
        </div>
    </div>
</script>

<script type="text/javascript">
(function () {
    function boot() {
        if (!window.jQuery) { return setTimeout(boot, 50); }
        jQuery(function ($) {
    var rdTechnicians = [];
    var rdTerminals = [];
    var rdRate = 18;
    var rdRowCounter = 0;

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
                        + 'data-total="' + o.total + '" data-paid="' + o.paid + '" data-balance="' + o.balance + '" '
                        + 'data-technician-id="' + (o.assigned_technician_id || '') + '">'
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

    // Crea una fila de pago desde el template. `initialAmount` prellena Monto MXN.
    function rdCreatePaymentRow(initialAmount) {
        var idx = ++rdRowCounter;
        var tpl = $('#rd_payment_row_tpl').html().replace(/__IDX__/g, idx);
        var $row = $(tpl);
        // Preinicializa terminales, TC, monto
        var topts = '<option value="">-- @lang('lang_v1.card_terminal') --</option>';
        $.each(rdTerminals, function (i, t) { topts += '<option value="' + t.id + '">' + t.name + '</option>'; });
        $row.find('.rd-terminal').html(topts);
        $row.find('.rd-rate').val(parseFloat(rdRate).toFixed(2));
        if (initialAmount != null && !isNaN(initialAmount)) {
            $row.find('.rd-amount-mxn').val(parseFloat(initialAmount).toFixed(2));
        }
        // El botón quitar solo aparece si no es la primera fila
        if ($('#rd_payment_rows .rd-payment-row').length > 0) {
            $row.find('.rd-remove-row').show();
        }
        $('#rd_payment_rows').append($row);
        // Dispara change para inicializar visibilidad de columnas
        $row.find('.rd-method').trigger('change');
        rdRecalcAll();
    }

    // Recalcula el total de una fila y la etiqueta de "Total desglose"
    function rdRecalcRow($row) {
        var mxnBills = 0;
        $row.find('.rd-mxn-denom').each(function () {
            var d = parseFloat($(this).data('denom')) || 0;
            var c = parseInt($(this).val()) || 0;
            mxnBills += d * c;
        });
        var mxnCoins = parseFloat($row.find('.rd-mxn-coins').val()) || 0;
        var mxnTotal = mxnBills + mxnCoins;

        var usdBills = 0;
        $row.find('.rd-usd-denom').each(function () {
            var d = parseFloat($(this).data('denom')) || 0;
            var c = parseInt($(this).val()) || 0;
            usdBills += d * c;
        });
        var usdCoins = parseFloat($row.find('.rd-usd-coins').val()) || 0;
        var usdTotal = usdBills + usdCoins;

        var rate = parseFloat($row.find('.rd-rate').val()) || 0;
        var usdInMxn = usdTotal * rate;
        var totalCaptured = mxnTotal + usdInMxn;

        $row.find('.rd-mxn-captured').text('$' + mxnTotal.toFixed(2));
        $row.find('.rd-usd-captured').text('$' + usdTotal.toFixed(2));
        $row.find('.rd-usd-in-mxn').text('$' + usdInMxn.toFixed(2));
        $row.find('.rd-total-captured').text('$' + totalCaptured.toFixed(2));

        // Auto-sync: si el método es cash, el monto de la fila = total capturado
        // (así el usuario no tiene que teclearlo). También rellena Monto USD.
        if ($row.find('.rd-method').val() === 'cash') {
            $row.find('.rd-amount-mxn').val(mxnTotal.toFixed(2));
            $row.find('.rd-amount-usd').val(usdTotal.toFixed(2));
        }
    }

    // Recalcula el gran total y muestra faltante
    function rdRecalcAll() {
        var grand = 0;
        $('#rd_payment_rows .rd-payment-row').each(function () {
            var $r = $(this);
            var mxn = parseFloat($r.find('.rd-amount-mxn').val()) || 0;
            var usd = parseFloat($r.find('.rd-amount-usd').val()) || 0;
            var rate = parseFloat($r.find('.rd-rate').val()) || 0;
            grand += mxn + (usd * rate);
        });
        var balance = parseFloat($('#rd_balance').text().replace(/[^0-9.-]/g, '')) || 0;
        var remaining = balance - grand;
        $('#rd_grand_total').text('$' + grand.toFixed(2));
        var remColor = Math.abs(remaining) < 0.01 ? '#2e7d32' : (remaining > 0 ? '#c62828' : '#f57c00');
        $('#rd_remaining')
            .text('$' + remaining.toFixed(2) + (remaining < -0.01 ? ' (sobra)' : ''))
            .css('color', remColor);
    }

    $(document).on('click', '.rd-select', function () {
        var b = $(this);
        $('#rd_order_id').val(b.data('id'));
        $('#rd_order_title').text(b.data('customer'));
        $('#rd_order_products').text(b.data('products'));
        $('#rd_total').text('$' + parseFloat(b.data('total')).toFixed(2));
        $('#rd_paid').text('$' + parseFloat(b.data('paid')).toFixed(2));
        var balance = parseFloat(b.data('balance'));
        $('#rd_balance').text('$' + balance.toFixed(2));

        // Técnico ya asignado — se preselecciona
        var assignedTechId = String(b.data('technician-id') || '');
        var opts = '<option value="">-- @lang('lang_v1.technician') --</option>';
        $.each(rdTechnicians, function (i, t) {
            var sel = (String(t.id) === assignedTechId) ? ' selected' : '';
            opts += '<option value="' + t.id + '"' + sel + '>' + t.name + '</option>';
        });
        $('#rd_technician').html(opts);

        // Limpia filas de pago previas y crea una fila inicial con el balance
        $('#rd_payment_rows').empty();
        rdRowCounter = 0;
        rdCreatePaymentRow(balance);
        $('#repair_delivery_panel').show();
    });

    // Agregar fila de pago
    $('#rd_add_payment_row').on('click', function () {
        rdCreatePaymentRow(0);
    });

    // Quitar fila
    $(document).on('click', '.rd-remove-row', function () {
        $(this).closest('.rd-payment-row').remove();
        rdRecalcAll();
    });

    // Método cambia → mostrar/ocultar columnas USD, terminal, panel de desglose
    $(document).on('change', '.rd-method', function () {
        var $row = $(this).closest('.rd-payment-row');
        var v = $(this).val() || '';
        var isCash = (v === 'cash');
        var isCard = (v.indexOf('card') === 0);
        $row.find('.rd-usd-col').toggle(isCash);
        $row.find('.rd-terminal-col').toggle(isCard);
        $row.find('.rd-denom-panel').toggle(isCash);
        if (!isCash) {
            // Al cambiar a no-cash, limpia el desglose y desactiva auto-sync del monto
            $row.find('.rd-mxn-denom, .rd-usd-denom').val('');
            $row.find('.rd-mxn-coins, .rd-usd-coins').val('');
            $row.find('.rd-amount-usd').val('');
            rdRecalcRow($row);
        }
        rdRecalcAll();
    });

    // Cambios en el desglose → recalcula la fila (y auto-fill del monto si es cash)
    $(document).on('input', '.rd-mxn-denom, .rd-usd-denom, .rd-mxn-coins, .rd-usd-coins, .rd-rate', function () {
        var $row = $(this).closest('.rd-payment-row');
        rdRecalcRow($row);
        rdRecalcAll();
    });

    // Cambios manuales al monto MXN o USD (para no-cash o override) → solo recalcula gran total
    $(document).on('input', '.rd-amount-mxn, .rd-amount-usd', function () {
        rdRecalcAll();
    });

    // Submit
    $('#repair_deliver_btn').click(function () {
        var id = $('#rd_order_id').val();
        if (!id) { return; }

        // Recolecta las filas de pago
        var payments = [];
        var problem = null;
        $('#rd_payment_rows .rd-payment-row').each(function (i) {
            var $r = $(this);
            var raw = ($r.find('.rd-method').val() || 'cash').split(':');
            var method = raw[0];
            var cardType = raw[1] || '';
            var mxn = parseFloat($r.find('.rd-amount-mxn').val()) || 0;
            var usd = parseFloat($r.find('.rd-amount-usd').val()) || 0;
            var rate = parseFloat($r.find('.rd-rate').val()) || 0;

            // Fila vacía → omitir (permite al usuario dejar filas sobrantes sin borrarlas)
            if (mxn <= 0 && usd <= 0) { return; }

            var pay = {
                method: method,
                amount_mxn: mxn.toFixed(2),
                amount_usd: usd > 0 ? usd.toFixed(2) : '0',
                exchange_rate: rate.toFixed(4),
                card_type: cardType,
                card_terminal_id: $r.find('.rd-terminal').val() || '',
                mxn_breakdown: '',
                mxn_coins: '0',
                usd_breakdown: '',
                usd_coins: '0'
            };

            if (method === 'cash') {
                var mxnMap = {};
                $r.find('.rd-mxn-denom').each(function () {
                    var d = $(this).data('denom');
                    var c = parseInt($(this).val()) || 0;
                    if (c > 0) mxnMap[d] = c;
                });
                var usdMap = {};
                $r.find('.rd-usd-denom').each(function () {
                    var d = $(this).data('denom');
                    var c = parseInt($(this).val()) || 0;
                    if (c > 0) usdMap[d] = c;
                });
                var mxnCoins = parseFloat($r.find('.rd-mxn-coins').val()) || 0;
                var usdCoins = parseFloat($r.find('.rd-usd-coins').val()) || 0;

                // Si tiene MXN pero no capturó desglose → error
                if (mxn > 0 && Object.keys(mxnMap).length === 0 && mxnCoins <= 0) {
                    problem = 'Falta el desglose de billetes MXN en la fila ' + (i + 1) + '.';
                    return false;
                }
                if (usd > 0 && Object.keys(usdMap).length === 0 && usdCoins <= 0) {
                    problem = 'Falta el desglose de billetes USD en la fila ' + (i + 1) + '.';
                    return false;
                }
                pay.mxn_breakdown = JSON.stringify(mxnMap);
                pay.mxn_coins = mxnCoins.toFixed(2);
                pay.usd_breakdown = JSON.stringify(usdMap);
                pay.usd_coins = usdCoins.toFixed(2);
            }

            if (method === 'card' && !pay.card_terminal_id) {
                problem = 'Selecciona la terminal en la fila ' + (i + 1) + '.';
                return false;
            }

            payments.push(pay);
        });

        if (problem) { toastr.error(problem); return; }

        if (payments.length === 0) {
            // No hay ningún pago — ok si el balance ya está en 0 (todo se cubrió con anticipo)
            var bal = parseFloat($('#rd_balance').text().replace(/[^0-9.-]/g, '')) || 0;
            if (bal > 0.01) {
                toastr.error('Falta capturar el pago.');
                return;
            }
        }

        var btn = $(this);
        btn.prop('disabled', true);
        $.ajax({
            method: 'POST',
            url: '/repair-orders/' + id + '/deliver',
            data: {
                technician_id: $('#rd_technician').val(),
                payments: payments,
                _token: '{{ csrf_token() }}'
            },
            dataType: 'json',
            success: function (res) {
                btn.prop('disabled', false);
                if (res.success) {
                    toastr.success(res.msg);
                    $('#repair_delivery_modal').modal('hide');
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
