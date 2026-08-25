@extends('layouts.app')
@section('title', 'Nueva reparación de tienda')

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-2xl tw-font-bold tw-text-black">Nueva reparación de tienda</h1>
    <p class="tw-text-sm tw-text-gray-700">Escribe el IMEI y el sistema busca automáticamente el equipo, cliente y factura. Solo IMEI y técnico son obligatorios.</p>
</section>

<section class="content">
    {!! Form::open(['route' => 'store-repairs.store', 'method' => 'POST']) !!}

    @component('components.widget', ['class' => 'box-primary'])

        {{-- Paso 1: IMEI. Un select2 con AJAX que sugiere IMEIs conocidos.
             Al seleccionar uno (o pegar/escribir uno nuevo), se dispara lookup
             que pre-llena producto, cliente, sucursal y factura desde la venta original. --}}
        <div class="row">
            <div class="col-md-8">
                <div class="form-group">
                    <label style="font-size:15px;">IMEI del equipo *</label>
                    <select id="sr-imei-picker" style="width:100%;"></select>
                    <input type="hidden" name="imei" id="sr-imei-hidden" required>
                    <small class="text-muted">Escribe o pega el IMEI (mínimo 3 caracteres). Se autocompletan los datos si el equipo ya fue vendido antes.</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label style="font-size:15px;">Técnico *</label>
                    {!! Form::select('technician_id', $technicians, null, ['class' => 'form-control select2', 'placeholder' => '— Elige —', 'required', 'style' => 'width:100%']) !!}
                </div>
            </div>
        </div>

        {{-- Info del equipo detectado (solo se muestra si el lookup encontró la venta). --}}
        <div id="sr-equipment-info" class="alert alert-success" style="display:none; margin-top:10px;">
            <div class="row">
                <div class="col-md-6">
                    <strong><i class="fa fa-mobile-alt"></i> Equipo detectado:</strong>
                    <span id="sri-product">—</span><br>
                    <strong>Factura:</strong> <span id="sri-invoice">—</span>
                    &nbsp;•&nbsp;
                    <strong>Vendido:</strong> <span id="sri-date">—</span><br>
                    <strong>Sucursal:</strong> <span id="sri-location">—</span>
                </div>
                <div class="col-md-6">
                    <strong><i class="fa fa-user"></i> Cliente:</strong>
                    <span id="sri-customer">—</span><br>
                    <strong>Teléfono:</strong> <span id="sri-mobile">—</span>
                </div>
            </div>
            <div style="margin-top:8px; font-size:11px; color:#555;">
                <i class="fa fa-info-circle"></i>
                Los datos de cliente/sucursal se llenaron automáticamente desde la venta original. Puedes cambiarlos abajo si es necesario.
            </div>
        </div>

        {{-- Aviso cuando el IMEI NO se encuentra en ventas (equipo externo). --}}
        <div id="sr-equipment-notfound" class="alert alert-warning" style="display:none; margin-top:10px;">
            <i class="fa fa-exclamation-triangle"></i>
            <strong>IMEI no encontrado en ventas.</strong>
            Es un equipo que no está en tu sistema. Puedes seguir registrando la reparación llenando los datos manualmente abajo.
        </div>

        <hr>

        {{-- Sucursal + Estado --}}
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Sucursal</label>
                    {!! Form::select('location_id', $locations, null, ['class' => 'form-control select2', 'id' => 'sr-location', 'placeholder' => '— Elige —', 'style' => 'width:100%']) !!}
                    <small class="text-muted">Se autocompleta con la sucursal donde se vendió el equipo.</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Estado</label>
                    {!! Form::select('status', \App\StoreRepair::STATUSES, 'pending', ['class' => 'form-control']) !!}
                </div>
            </div>
        </div>

        {{-- Cliente: se llena solo si el IMEI viene con cliente. Si el user quiere editar
             o el equipo es externo, puede escribir manualmente. --}}
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Nombre del cliente</label>
                    <input type="text" name="customer_name" id="sr-customer-name" class="form-control" maxlength="191">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Teléfono del cliente</label>
                    <input type="text" name="customer_mobile" id="sr-customer-mobile" class="form-control" maxlength="50">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>Descripción del daño / notas</label>
                    {!! Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 3]) !!}
                </div>
            </div>
        </div>

        <div class="alert alert-info" style="margin-top:10px;">
            <i class="fa fa-info-circle"></i>
            La reparación se registra con <strong>comisión al técnico = $0</strong>. Un administrador o gerente puede actualizarla después desde el listado.
        </div>
    @endcomponent

    <div class="row">
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fa fa-save"></i> Guardar reparación
            </button>
            <a href="{{ route('store-repairs.index') }}" class="btn btn-default btn-lg">Cancelar</a>
        </div>
    </div>

    {!! Form::close() !!}
</section>

@endsection

@section('javascript')
<script>
$(function () {
    // IMEI picker: select2 con AJAX contra /store-repairs/search-imei.
    // tags:true permite escribir un IMEI que no está en las sugerencias.
    var $imei = $('#sr-imei-picker').select2({
        placeholder: 'Escribe o pega el IMEI (mín. 3 caracteres)…',
        minimumInputLength: 3,
        allowClear: true,
        tags: true,
        createTag: function (p) {
            var t = $.trim(p.term);
            if (t === '') return null;
            return { id: t, text: t + '  (usar este IMEI)', isNew: true };
        },
        ajax: {
            url: '{{ route('store-repairs.search-imei') }}',
            dataType: 'json',
            delay: 200,
            data: function (p) { return { term: p.term }; },
            processResults: function (data) {
                return { results: (data && data.results) || [] };
            },
        },
    });

    // Al elegir un IMEI, disparar lookup completo para pre-llenar el form.
    $imei.on('select2:select', function (e) {
        var imei = e.params.data.id;
        console.log('[store-repairs] select2:select →', imei, 'isNew=', e.params.data.isNew);
        $('#sr-imei-hidden').val(imei);
        lookupImei(imei);
    });

    $imei.on('select2:clear', function () {
        $('#sr-imei-hidden').val('');
        resetEquipmentInfo();
    });

    // Fallback: si el user escribe/pega y presiona Enter/tab sin que select2
    // dispare select (raro en algunos edge cases), leer el valor del input
    // interno de select2 y lookuparlo manualmente.
    $imei.on('select2:close', function () {
        var currentVal = $('#sr-imei-hidden').val();
        var pickerVal = $imei.val();
        if (pickerVal && pickerVal !== currentVal) {
            console.log('[store-repairs] select2:close fallback →', pickerVal);
            $('#sr-imei-hidden').val(pickerVal);
            lookupImei(pickerVal);
        }
    });

    function lookupImei(imei) {
        var url = '{{ route('store-repairs.lookup-imei') }}';
        console.log('[store-repairs] lookupImei GET', url, '?imei=' + imei);
        $.getJSON(url, { imei: imei })
            .done(function (r) {
                console.log('[store-repairs] lookup response:', r);
                if (!r || !r.found) {
                    $('#sr-equipment-info').hide();
                    $('#sr-equipment-notfound').show();
                    return;
                }
                $('#sr-equipment-notfound').hide();
                $('#sri-product').text(r.product_name || '—');
                $('#sri-invoice').text(r.invoice_no || '—');
                $('#sri-date').text(r.sale_date || '—');
                $('#sri-location').text(r.location_name || '—');
                $('#sri-customer').text(r.customer_name || '(sin cliente registrado)');
                $('#sri-mobile').text(r.customer_mobile || '—');
                $('#sr-equipment-info').show();

                // Pre-llenar sucursal (siempre — la venta original manda)
                if (r.location_id) {
                    $('#sr-location').val(r.location_id).trigger('change');
                }
                // Pre-llenar nombre y teléfono (siempre — el user puede editar después)
                if (r.customer_name) {
                    $('#sr-customer-name').val(r.customer_name);
                }
                if (r.customer_mobile) {
                    $('#sr-customer-mobile').val(r.customer_mobile);
                }
            })
            .fail(function (xhr, status, err) {
                console.error('[store-repairs] lookup FAIL:', status, err, xhr.responseText);
                if (typeof toastr !== 'undefined') {
                    toastr.error('No se pudo buscar el IMEI (' + xhr.status + '). Ver consola para detalle.');
                }
            });
    }

    function resetEquipmentInfo() {
        $('#sr-equipment-info').hide();
        $('#sr-equipment-notfound').hide();
    }
});
</script>
@endsection
