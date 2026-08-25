@extends('layouts.app')
@section('title', 'Reparación de Tienda')

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">Reparación de Tienda
        <small class="tw-text-sm tw-text-gray-700">Reparaciones gratis al cliente — sin cobro, con comisión al técnico</small>
    </h1>
</section>

<section class="content">
    @component('components.filters', ['title' => __('report.filters')])
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Sucursal:</label>
                    {!! Form::select('location_id', $locations, null, ['class' => 'form-control select2', 'placeholder' => 'Todas', 'id' => 'sr_filter_location', 'style' => 'width:100%']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Técnico:</label>
                    {!! Form::select('technician_id', $technicians, null, ['class' => 'form-control select2', 'placeholder' => 'Todos', 'id' => 'sr_filter_technician', 'style' => 'width:100%']) !!}
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Estado:</label>
                    <select class="form-control" id="sr_filter_status">
                        <option value="">Todos</option>
                        <option value="pending">Pendiente</option>
                        <option value="in_progress">En proceso</option>
                        <option value="delivered">Entregada</option>
                        <option value="cancelled">Cancelada</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Fechas:</label>
                    <input type="text" class="form-control" id="sr_filter_dates" placeholder="Todas">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group" style="margin-top:25px;">
                    @if(auth()->user()->can('business_settings.access') || auth()->user()->can('superadmin'))
                        <a href="{{ route('store-repairs.create') }}" class="btn btn-primary">
                            <i class="fa fa-plus"></i> Nueva
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary'])
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="store_repairs_table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>IMEI</th>
                        <th>Sucursal</th>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>Técnico</th>
                        <th class="text-right">Comisión</th>
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
    $('#sr_filter_dates').daterangepicker({
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

    var table = $('#store_repairs_table').DataTable({
        processing: true, serverSide: true,
        ajax: {
            url: '{{ route('store-repairs.index') }}',
            data: function (d) {
                d.location_id = $('#sr_filter_location').val();
                d.technician_id = $('#sr_filter_technician').val();
                d.status = $('#sr_filter_status').val();
                d.start_date = start_date;
                d.end_date = end_date;
            },
        },
        columns: [
            {data: 'created_at', name: 'sr.created_at'},
            {data: 'imei', name: 'sr.imei'},
            {data: 'location_name', name: 'bl.name'},
            {data: 'customer_name', name: 'sr.customer_name'},
            {data: 'customer_mobile', name: 'sr.customer_mobile'},
            {data: 'technician_name', name: 't.name'},
            {data: 'commission', name: 'sr.commission', className: 'text-right'},
            {data: 'status', name: 'sr.status'},
            {data: 'created_by_name', name: 'created_by_name'},
            {data: 'action', orderable: false, searchable: false},
        ],
        order: [[0, 'desc']],
    });

    $('#sr_filter_location, #sr_filter_technician, #sr_filter_status').on('change', function () { table.ajax.reload(); });
});
</script>
@endsection
