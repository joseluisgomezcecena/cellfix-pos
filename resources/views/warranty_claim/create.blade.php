@extends('layouts.app')
@section('title', 'Nueva garantía')

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">Registrar garantía</h1>
</section>

<section class="content">
    <form id="warranty_form">
        @csrf

        {{-- Paso 1: buscar la venta original --}}
        @component('components.widget', ['class' => 'box-primary', 'title' => '1. Venta original'])
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Folio de la venta (invoice_no):*</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="wc_invoice_no" placeholder="Ej. 72953">
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-primary" id="wc_lookup_sale"><i class="fa fa-search"></i> Buscar</button>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Sucursal donde se registra:*</label>
                        {!! Form::select('location_id', $locations, null, ['class' => 'form-control', 'id' => 'wc_location_id', 'required']) !!}
                    </div>
                </div>
                <div class="col-md-4" id="wc_sale_info_box" style="display:none;">
                    <label>Info de la venta:</label>
                    <div class="alert alert-info" style="padding:8px; margin-bottom:0; font-size:12px;">
                        <div><strong>Cliente:</strong> <span id="wc_customer_name"></span></div>
                        <div><strong>Fecha:</strong> <span id="wc_sale_date"></span></div>
                    </div>
                </div>
            </div>
            <div class="row" id="wc_original_line_row" style="display:none;">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Artículo(s) con problema (de la venta):*</label>
                        <div class="alert alert-info" style="padding:6px 12px; margin-bottom:8px; font-size:12px;">
                            Selecciona uno o varios artículos. Para <strong>Cambio</strong> (mismo/mayor/menor valor) solo se puede elegir uno.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-condensed" id="wc_items_table" style="margin-bottom:0;">
                                <thead style="background:#f5f5f5;">
                                    <tr>
                                        <th width="40" class="text-center"><input type="checkbox" id="wc_items_select_all" title="Seleccionar todos"></th>
                                        <th>Producto</th>
                                        <th width="150">SKU</th>
                                        <th width="120" class="text-right">Precio</th>
                                    </tr>
                                </thead>
                                <tbody id="wc_items_tbody"></tbody>
                            </table>
                        </div>
                        <div id="wc_items_count_hint" style="font-size:12px; color:#666; margin-top:6px;"></div>
                    </div>
                </div>
            </div>
            <input type="hidden" id="wc_original_sell_transaction_id" name="original_sell_transaction_id">
        @endcomponent

        {{-- Paso 2: motivo --}}
        @component('components.widget', ['class' => 'box-primary', 'title' => '2. Motivo / razón'])
            <div class="form-group">
                <label>¿Qué fue lo que falló con el equipo?*</label>
                <textarea class="form-control" name="motivo" id="wc_motivo" rows="3" required
                    placeholder="Describe la falla o razón del reclamo (mínimo 5 caracteres)"></textarea>
            </div>
        @endcomponent

        {{-- Paso 3: tipo de reclamo --}}
        @component('components.widget', ['class' => 'box-primary', 'title' => '3. Tipo de reclamo'])
            <div class="row">
                <div class="col-md-12">
                    <div class="btn-group" data-toggle="buttons" id="wc_type_group">
                        <label class="btn btn-default active">
                            <input type="radio" name="claim_type" value="refund" checked> Reembolso $
                        </label>
                        <label class="btn btn-default">
                            <input type="radio" name="claim_type" value="replacement_same"> Cambio (mismo equipo)
                        </label>
                        <label class="btn btn-default">
                            <input type="radio" name="claim_type" value="replacement_higher"> Cambio (mayor valor)
                        </label>
                        <label class="btn btn-default">
                            <input type="radio" name="claim_type" value="replacement_lower"> Cambio (menor valor)
                        </label>
                    </div>
                </div>
            </div>

            {{-- Reembolso --}}
            <div class="wc-type-section" id="wc_section_refund" style="margin-top:15px;">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Monto total a reembolsar:*</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" name="refund_amount" id="wc_refund_amount">
                            <small class="text-muted" id="wc_refund_split_hint">
                                Si hay varios artículos seleccionados, este monto se distribuye proporcionalmente por precio.
                            </small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Método:*</label>
                            <select class="form-control wc-method" name="refund_method" data-target="wc_refund_extra">
                                <option value="cash">Efectivo</option>
                                <option value="card">Tarjeta (reembolso)</option>
                                <option value="bank_transfer">Transferencia</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4" id="wc_refund_extra"></div>
                </div>
            </div>

            {{-- Replacement (los 3 variantes) --}}
            <div class="wc-type-section" id="wc_section_replacement" style="display:none; margin-top:15px;">
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label>Equipo de reemplazo:*</label>
                            <select class="form-control" id="wc_replacement_variation_id" name="replacement_variation_id"></select>
                            <small class="text-muted">Debe haber al menos 1 unidad en la sucursal seleccionada.</small>
                        </div>
                    </div>
                    <div class="col-md-4" id="wc_replacement_info" style="display:none;">
                        <label>Info:</label>
                        <div class="alert alert-info" style="padding:8px; margin-bottom:0; font-size:12px;">
                            <div>SKU: <code id="wc_repl_sku"></code></div>
                            <div>Stock: <strong id="wc_repl_stock"></strong></div>
                            <div>Precio: <strong id="wc_repl_price"></strong></div>
                        </div>
                    </div>
                </div>

                <div class="row wc-diff-row" style="display:none;">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Diferencia ($):*</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" name="price_difference" id="wc_price_difference">
                            <small class="text-muted" id="wc_diff_hint"></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Método:*</label>
                            <select class="form-control wc-method" name="price_difference_method" data-target="wc_diff_extra">
                                <option value="cash">Efectivo</option>
                                <option value="card">Tarjeta</option>
                                <option value="bank_transfer">Transferencia</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4" id="wc_diff_extra"></div>
                </div>
            </div>
        @endcomponent

        <div style="text-align:right;">
            <a href="{{ route('warranty-claims.index') }}" class="btn btn-default">Cancelar</a>
            <button type="submit" class="btn btn-primary" id="wc_submit"><i class="fa fa-save"></i> Registrar garantía</button>
        </div>
    </form>
</section>

{{-- Template: campos de tarjeta --}}
<script type="text/x-template" id="wc_tpl_card">
    <label>Terminal:*</label>
    <select class="form-control" name="__NAME__">
        <option value="">— Selecciona —</option>
        @foreach($card_terminals as $tid => $tname)
            <option value="{{ $tid }}">{{ $tname }}</option>
        @endforeach
    </select>
</script>

{{-- Template: desglose de billetes efectivo --}}
<script type="text/x-template" id="wc_tpl_cash">
    <label>Desglose de billetes: <a href="#" class="wc-open-cash" data-target="__PREFIX__">Capturar</a></label>
    <div class="wc-cash-summary" style="font-size:11px; color:#555;">Total capturado: <strong class="wc-cash-total">$0.00</strong></div>
    <input type="hidden" name="__PREFIX___denomination_breakdown" class="wc-cash-bd" value="">
</script>

{{-- Modal desglose --}}
<div class="modal fade" id="wc_cash_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#2196f3; color:#fff;">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
                <h4 class="modal-title">Desglose de billetes</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="wc_cash_target">
                <div class="row">
                    <div class="col-md-6">
                        <strong>PESOS (MXN)</strong>
                        <table class="table table-condensed table-bordered">
                            <thead><tr><th>Denominación</th><th class="text-center">Cantidad</th></tr></thead>
                            <tbody>
                                @foreach([1000, 500, 200, 100, 50, 20] as $face)
                                    <tr><td class="text-right"><strong>${{ $face }}</strong></td>
                                    <td><input type="number" min="0" step="1" class="form-control input-sm wc-mxn" data-denom="{{ $face }}" placeholder="0"></td></tr>
                                @endforeach
                                <tr><td class="text-right"><strong>Monedas</strong></td>
                                <td><input type="number" min="0" step="0.01" class="form-control input-sm" id="wc_coins_mxn" placeholder="0.00"></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <strong>DÓLARES (USD)</strong>
                        <label style="font-weight:normal;">TC: <input type="number" step="0.01" min="0" class="form-control input-sm" id="wc_rate" value="17.20" style="display:inline-block; width:70px;"></label>
                        <table class="table table-condensed table-bordered">
                            <thead><tr><th>Denominación</th><th class="text-center">Cantidad</th></tr></thead>
                            <tbody>
                                @foreach([100, 50, 20, 10, 5, 1] as $face)
                                    <tr><td class="text-right"><strong>${{ $face }}</strong></td>
                                    <td><input type="number" min="0" step="1" class="form-control input-sm wc-usd" data-denom="{{ $face }}" placeholder="0"></td></tr>
                                @endforeach
                                <tr><td class="text-right"><strong>Monedas USD</strong></td>
                                <td><input type="number" min="0" step="0.01" class="form-control input-sm" id="wc_coins_usd" placeholder="0.00"></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="alert alert-warning">Total capturado: <strong id="wc_cash_modal_total">$0.00</strong></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="wc_cash_save">Guardar desglose</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('javascript')
<script>
$(function () {

    // Buscar venta original
    $('#wc_lookup_sale').on('click', function () {
        var inv = $('#wc_invoice_no').val().trim();
        if (!inv) { toastr.warning('Escribe el folio de la venta.'); return; }
        $.getJSON('{{ route('warranty-claims.get-sell-products') }}', {invoice_no: inv})
            .done(function (r) {
                if (!r.success) { toastr.error(r.message); return; }
                $('#wc_original_sell_transaction_id').val(r.sale_id);
                $('#wc_customer_name').text(r.customer.name + (r.customer.mobile ? ' — ' + r.customer.mobile : ''));
                $('#wc_sale_date').text(r.transaction_date);
                $('#wc_sale_info_box').show();

                // Render de artículos como tabla con checkboxes
                var $tbody = $('#wc_items_tbody').empty();
                r.lines.forEach(function (l) {
                    var $row = $('<tr>');
                    $row.append('<td class="text-center"><input type="checkbox" class="wc-item-cb" '
                        + 'name="original_variation_ids[]" value="' + l.variation_id + '" '
                        + 'data-price="' + l.unit_price_inc_tax + '"></td>');
                    $row.append('<td>' + $('<div>').text(l.product_name).html() + '</td>');
                    $row.append('<td><small><code>' + $('<div>').text(l.sub_sku || '').html() + '</code></small></td>');
                    $row.append('<td class="text-right">$' + l.unit_price_inc_tax.toFixed(2) + '</td>');
                    $tbody.append($row);
                });
                $('#wc_items_select_all').prop('checked', false);
                $('#wc_original_line_row').show();
                updateItemsCountHint();
                recomputePriceDifference();
            })
            .fail(function () { toastr.error('Error consultando la venta.'); });
    });

    // "Seleccionar todos" del header
    $(document).on('change', '#wc_items_select_all', function () {
        $('.wc-item-cb').prop('checked', this.checked);
        validateItemsSelection(false);
        updateItemsCountHint();
        recomputePriceDifference();
    });

    // Cambio en cualquier checkbox de artículo
    $(document).on('change', '.wc-item-cb', function () {
        validateItemsSelection(true);
        // Sincroniza el "select all" con el estado actual
        var total = $('.wc-item-cb').length;
        var checked = $('.wc-item-cb:checked').length;
        $('#wc_items_select_all').prop('checked', total > 0 && checked === total);
        updateItemsCountHint();
        recomputePriceDifference();
    });

    // Para tipos de "Cambio" solo se puede elegir 1 artículo. Si el usuario intenta
    // marcar más, dejamos únicamente el último y toast de aviso.
    function validateItemsSelection(fromUserClick) {
        var t = $('input[name="claim_type"]:checked').val();
        if (t === 'refund') return;
        var $checked = $('.wc-item-cb:checked');
        if ($checked.length > 1) {
            if (fromUserClick) {
                toastr.warning('Para Cambio (mismo/mayor/menor valor) solo puedes elegir un artículo.');
            }
            // Deja solo el último marcado
            $checked.slice(0, -1).prop('checked', false);
            $('#wc_items_select_all').prop('checked', false);
        }
    }

    function updateItemsCountHint() {
        var n = $('.wc-item-cb:checked').length;
        var t = $('input[name="claim_type"]:checked').val();
        var msg = n === 0 ? 'Ningún artículo seleccionado' :
                  n === 1 ? '1 artículo seleccionado' :
                  n + ' artículos seleccionados';
        if (t === 'refund' && n > 1) {
            msg += ' — el monto total se distribuirá proporcionalmente.';
        }
        $('#wc_items_count_hint').text(msg);
    }

    // Tipo de reclamo → mostrar sección correspondiente
    $(document).on('change', 'input[name="claim_type"]', function () {
        var t = $(this).val();
        $('.wc-type-section').hide();
        if (t === 'refund') {
            $('#wc_section_refund').show();
        } else {
            $('#wc_section_replacement').show();
            if (t === 'replacement_same') {
                $('.wc-diff-row').hide();
            } else {
                $('.wc-diff-row').show();
                $('#wc_diff_hint').text(t === 'replacement_higher'
                    ? 'El cliente paga la diferencia.'
                    : 'El negocio devuelve al cliente.');
            }
        }
        // Al cambiar a Cambio con múltiples seleccionados, dejamos solo el primero
        validateItemsSelection(false);
        updateItemsCountHint();
        recomputePriceDifference();
    }).trigger('change');

    // Cálculo automático de la diferencia. Se dispara cuando cambia el equipo
    // original, el equipo de reemplazo o el tipo de reclamo. Solo prellena el
    // campo si el usuario aún no lo ha editado manualmente (data-user-edited).
    // Así respetamos ediciones manuales sin sobrescribirlas.
    function recomputePriceDifference() {
        var t = $('input[name="claim_type"]:checked').val();
        if (t !== 'replacement_higher' && t !== 'replacement_lower') return;

        // Ahora los items vienen de checkboxes; para Cambio solo hay 1 seleccionado.
        var $checked = $('.wc-item-cb:checked');
        var origPrice = ($checked.length === 1)
            ? (parseFloat($checked.first().data('price')) || 0)
            : 0;
        var replPrice = parseFloat($('#wc_replacement_variation_id').data('replacement-price')) || 0;
        if (!origPrice || !replPrice) return;

        var diff = replPrice - origPrice;
        var signOk = (t === 'replacement_higher' && diff > 0) || (t === 'replacement_lower' && diff < 0);
        var $diff = $('#wc_price_difference');
        var $hint = $('#wc_diff_hint');

        // Si el signo del cambio no coincide con el tipo elegido, se lo decimos
        // al usuario pero no forzamos el valor (puede que el usuario quiera cobrar
        // o devolver un monto distinto).
        if (!signOk) {
            $hint.html('<span style="color:#c62828;">⚠ El precio del reemplazo ' +
                (diff > 0 ? 'es MAYOR' : 'es MENOR') + ' que el original. Sugerido para "' +
                (diff > 0 ? 'Cambio (mayor valor)' : 'Cambio (menor valor)') + '".</span>');
            return;
        }

        // Refresca el hint con el cálculo
        $hint.html('Original <strong>$' + origPrice.toFixed(2) + '</strong> · Reemplazo <strong>$' +
            replPrice.toFixed(2) + '</strong> → Diferencia calculada: <strong>$' +
            Math.abs(diff).toFixed(2) + '</strong>' +
            (t === 'replacement_higher' ? ' (el cliente paga)' : ' (el negocio devuelve)'));

        // Solo autocompleta si el usuario no ha tecleado manualmente
        if (!$diff.data('user-edited')) {
            $diff.val(Math.abs(diff).toFixed(2));
        }
    }

    // Marca el campo como "user-edited" para no pisar su valor en recálculos.
    $(document).on('input', '#wc_price_difference', function () {
        $(this).data('user-edited', true);
    });

    // Cuando cambia el original, dispara recálculo.
    // Recomputo ya se dispara en el handler de los checkboxes de artículo.

    // Método de pago → agrega los extra fields (terminal para card, desglose para cash)
    $(document).on('change', '.wc-method', function () {
        var target = $(this).data('target');
        var m = $(this).val();
        var $c = $('#' + target).empty();
        var prefix = target === 'wc_refund_extra' ? 'refund' : 'price_difference';
        if (m === 'card') {
            var tpl = $('#wc_tpl_card').html().replace(/__NAME__/g, prefix + '_card_terminal_id');
            $c.html(tpl);
        } else if (m === 'cash') {
            var tpl = $('#wc_tpl_cash').html().replace(/__PREFIX__/g, prefix);
            $c.html(tpl);
        }
    });
    // Renderiza el estado inicial: dispara change en cada dropdown de método.
    $('.wc-method').trigger('change');

    // Search del reemplazo (select2 con AJAX)
    $('#wc_replacement_variation_id').select2({
        placeholder: 'Busca por nombre o SKU',
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
            url: '{{ route('warranty-claims.search-product') }}',
            dataType: 'json',
            delay: 300,
            data: function (p) { return {q: p.term, location_id: $('#wc_location_id').val()}; },
            processResults: function (data) {
                return {
                    results: data.map(function (x) {
                        return {
                            id: x.variation_id,
                            text: x.product_name + ' (SKU: ' + x.sub_sku + ') — Stock: ' + x.qty_available + ' — $' + parseFloat(x.sell_price_inc_tax).toFixed(2),
                            _raw: x,
                        };
                    }),
                };
            },
            cache: true,
        },
    });
    $('#wc_replacement_variation_id').on('select2:select', function (e) {
        var x = e.params.data._raw;
        $('#wc_repl_sku').text(x.sub_sku || '—');
        $('#wc_repl_stock').text(x.qty_available);
        $('#wc_repl_price').text('$' + parseFloat(x.sell_price_inc_tax).toFixed(2));
        $('#wc_replacement_info').show();
        // Guarda el precio del reemplazo para que recomputePriceDifference()
        // pueda calcular la diferencia automáticamente.
        $('#wc_replacement_variation_id').data('replacement-price', parseFloat(x.sell_price_inc_tax));
        // Reset del flag "user-edited" cuando cambia el equipo — así el auto-cálculo
        // vuelve a activarse en un equipo nuevo (si el usuario ya editó y luego
        // cambia de equipo, es razonable recalcular).
        $('#wc_price_difference').data('user-edited', false);
        recomputePriceDifference();
    });
    // Si el usuario limpia el select2, resetea el precio guardado.
    $('#wc_replacement_variation_id').on('select2:clear', function () {
        $('#wc_replacement_variation_id').removeData('replacement-price');
        $('#wc_price_difference').val('').data('user-edited', false);
    });

    // Modal desglose de billetes: reutilizable para refund y para diferencia
    var $target = null; // el prefix (refund | price_difference)
    $(document).on('click', '.wc-open-cash', function (e) {
        e.preventDefault();
        $target = $(this).data('target');
        $('#wc_cash_modal .wc-mxn, #wc_cash_modal .wc-usd, #wc_coins_mxn, #wc_coins_usd').val('');
        computeCashModal();
        $('#wc_cash_modal').modal('show');
    });
    $(document).on('input', '#wc_cash_modal .wc-mxn, #wc_cash_modal .wc-usd, #wc_coins_mxn, #wc_coins_usd, #wc_rate', computeCashModal);
    function computeCashModal() {
        var mxn = {}, usd = {}, sub_m = 0, sub_u = 0;
        $('.wc-mxn').each(function () { var d = $(this).data('denom'); var c = parseInt($(this).val()) || 0; if (c) { mxn[d] = c; sub_m += d * c; } });
        $('.wc-usd').each(function () { var d = $(this).data('denom'); var c = parseInt($(this).val()) || 0; if (c) { usd[d] = c; sub_u += d * c; } });
        var cm = parseFloat($('#wc_coins_mxn').val()) || 0;
        var cu = parseFloat($('#wc_coins_usd').val()) || 0;
        sub_m += cm; sub_u += cu;
        var rate = parseFloat($('#wc_rate').val()) || 0;
        var usd_in_mxn = parseFloat((sub_u * rate).toFixed(2));
        var total = parseFloat((sub_m + usd_in_mxn).toFixed(2));
        $('#wc_cash_modal_total').text('$' + total.toFixed(2));
        return {mxn: mxn, usd: usd, cm: cm, cu: cu, rate: rate, sub_m: sub_m, sub_u: sub_u, usd_in_mxn: usd_in_mxn, total: total};
    }
    $('#wc_cash_save').on('click', function () {
        var c = computeCashModal();
        if (c.total <= 0) { toastr.error('Captura al menos un billete o moneda.'); return; }
        var bd = {};
        if (Object.keys(c.mxn).length || c.cm > 0) {
            bd.mxn = $.extend({}, c.mxn);
            if (c.cm > 0) bd.mxn.coins = c.cm;
        }
        if (Object.keys(c.usd).length || c.cu > 0) {
            bd.usd = $.extend({}, c.usd);
            if (c.cu > 0) bd.usd.coins = c.cu;
            bd.exchange_rate = c.rate;
            bd.usd_in_mxn = c.usd_in_mxn;
        }
        // Escribir en el input hidden del target
        $('input[name="' + $target + '_denomination_breakdown"]').val(JSON.stringify(bd));
        $target === 'refund'
            ? $('#wc_refund_extra .wc-cash-total').text('$' + c.total.toFixed(2))
            : $('#wc_diff_extra .wc-cash-total').text('$' + c.total.toFixed(2));
        $('#wc_cash_modal').modal('hide');
    });

    // Submit
    $('#warranty_form').on('submit', function (e) {
        e.preventDefault();

        // Validación cliente-side: al menos 1 artículo seleccionado + regla de Cambio
        var $checked = $('.wc-item-cb:checked');
        if ($checked.length === 0) {
            toastr.error('Selecciona al menos un artículo con problema.');
            return;
        }
        var t = $('input[name="claim_type"]:checked').val();
        if (t !== 'refund' && $checked.length !== 1) {
            toastr.error('Para Cambio (mismo/mayor/menor valor) selecciona exactamente un artículo.');
            return;
        }

        var $btn = $('#wc_submit').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Registrando…');

        $.ajax({
            url: '{{ route('warranty-claims.store') }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function (r) {
                if (r.success) {
                    toastr.success(r.message);
                    if (r.print_url) window.open(r.print_url, '_blank');
                    setTimeout(function () { window.location = '{{ route('warranty-claims.index') }}'; }, 1000);
                } else {
                    toastr.error(r.message || 'Error');
                }
            },
            error: function (xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error al registrar.';
                toastr.error(msg);
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Registrar garantía');
            },
        });
    });
});
</script>
@endsection
