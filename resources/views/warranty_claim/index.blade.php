@extends('layouts.app')
@section('title', 'Garantías')

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">Historial de garantías</h1>
</section>

<section class="content">
    @component('components.filters', ['title' => __('report.filters')])
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Ubicación:</label>
                    {!! Form::select('location_id', $locations, null, ['class' => 'form-control select2', 'placeholder' => 'Todas', 'id' => 'wc_filter_location', 'style' => 'width:100%']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Tipo:</label>
                    <select class="form-control" id="wc_filter_type">
                        <option value="">Todos</option>
                        <option value="refund">Reembolso en efectivo/tarjeta</option>
                        <option value="replacement_same">Cambio por mismo equipo</option>
                        <option value="replacement_higher">Cambio por mayor valor</option>
                        <option value="replacement_lower">Cambio por menor valor</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Rango de fechas:</label>
                    <input type="text" class="form-control" id="wc_filter_dates" placeholder="Todas las fechas">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group" style="margin-top:25px;">
                    <a href="{{ route('warranty-claims.create') }}" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Nueva garantía
                    </a>
                </div>
            </div>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary'])
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="warranty_claims_table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Folio</th>
                        <th>Venta original</th>
                        <th>Cliente</th>
                        <th>Sucursal</th>
                        <th>Tipo</th>
                        <th>Equipo devuelto</th>
                        <th>Equipo entregado</th>
                        <th class="text-right">Monto</th>
                        <th>Estado</th>
                        <th>Registrado por</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</section>

@endsection

@section('javascript')
<script>
$(function () {
    var start_date = null, end_date = null;
    $('#wc_filter_dates').daterangepicker({
        autoUpdateInput: false,
        locale: { cancelLabel: 'Limpiar', applyLabel: 'Aplicar', format: 'DD/MM/YYYY' },
    }).on('apply.daterangepicker', function (ev, p) {
        start_date = p.startDate.format('YYYY-MM-DD');
        end_date = p.endDate.format('YYYY-MM-DD');
        $(this).val(p.startDate.format('DD/MM/YYYY') + ' - ' + p.endDate.format('DD/MM/YYYY'));
        table.ajax.reload();
    }).on('cancel.daterangepicker', function () {
        start_date = null; end_date = null; $(this).val(''); table.ajax.reload();
    });

    var table = $('#warranty_claims_table').DataTable({
        processing: true, serverSide: true,
        ajax: {
            url: '{{ route('warranty-claims.index') }}',
            data: function (d) {
                d.location_id = $('#wc_filter_location').val();
                d.claim_type = $('#wc_filter_type').val();
                d.start_date = start_date;
                d.end_date = end_date;
            },
        },
        columns: [
            {data: 'claim_date', name: 'claim_date'},
            {data: 'ref_no', name: 'ref_no'},
            {data: 'original_invoice', name: 'original_invoice'},
            {data: 'customer_name', name: 'customer_name'},
            {data: 'location_name', name: 'location_name'},
            {data: 'claim_type', name: 'claim_type'},
            {data: 'original_product_name', name: 'original_product_name'},
            {data: 'replacement_product_name', name: 'replacement_product_name'},
            {data: 'amount', name: 'amount', className: 'text-right'},
            {data: 'status', name: 'status'},
            {data: 'created_by_name', name: 'created_by_name'},
            {data: 'action', orderable: false, searchable: false},
        ],
        order: [[0, 'desc']],
    });

    $('#wc_filter_location, #wc_filter_type').on('change', function () { table.ajax.reload(); });

    // Cancelar
    $(document).on('click', '.cancel-warranty', function (e) {
        e.preventDefault();
        if (!confirm('¿Cancelar esta garantía? Se revierte el inventario y las transacciones asociadas.')) return;
        var url = $(this).data('href');
        $.post(url, {_token: '{{ csrf_token() }}'}, function (r) {
            if (r.success) {
                toastr.success(r.message);
                table.ajax.reload();
            } else {
                toastr.error(r.message || 'Error');
            }
        });
    });
});
</script>
@endsection
