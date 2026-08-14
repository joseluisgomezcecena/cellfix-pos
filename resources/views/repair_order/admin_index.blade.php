@extends('layouts.app')
@section('title', 'Administrar Reparaciones')

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        Administrar Reparaciones
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">
            Buscar, filtrar y reasignar el técnico de cada reparación
        </small>
    </h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => 'Filtros'])
                <div class="row">
                    <div class="col-md-4 col-sm-6">
                        <div class="form-group">
                            <label>Rango de fechas:</label>
                            <input type="text" id="rep_admin_date_range" class="form-control"
                                readonly placeholder="Selecciona un rango" style="cursor:pointer;">
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label>Sucursal:</label>
                            <select id="rep_admin_location" class="form-control select2" style="width:100%;">
                                <option value="">Todas</option>
                                @foreach($locations as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label>Técnico:</label>
                            <select id="rep_admin_technician" class="form-control select2" style="width:100%;">
                                <option value="">Todos</option>
                                @foreach($technicians as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <div class="form-group">
                            <label>Estado:</label>
                            <select id="rep_admin_status" class="form-control">
                                <option value="">Todos</option>
                                <option value="pending">Pendientes</option>
                                <option value="delivered">Entregadas</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="input-group">
                            <input type="text" id="rep_admin_term" class="form-control"
                                placeholder="Buscar por cliente, teléfono, folio o producto…">
                            <span class="input-group-btn">
                                <button class="btn btn-primary" type="button" id="rep_admin_search_btn">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                                <button class="btn btn-default" type="button" id="rep_admin_reset_btn">
                                    <i class="fas fa-times"></i> Limpiar
                                </button>
                            </span>
                        </div>
                    </div>
                </div>
            @endcomponent

            @component('components.widget', ['class' => 'box-primary'])
                <div class="table-responsive">
                    <table id="rep_admin_table" class="table table-bordered table-striped" style="font-size:13px; width:100%;">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Folio</th>
                                <th>Sucursal</th>
                                <th>Cliente</th>
                                <th>Tel</th>
                                <th>Producto(s)</th>
                                <th>Técnico actual</th>
                                <th>Estado</th>
                                <th>Total</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>

{{-- Modal para cambiar técnico (por línea, soporta 2+ técnicos en la misma reparación) --}}
<div class="modal fade" id="change_technician_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #2196f3; color: #fff;">
                <button type="button" class="close" data-dismiss="modal" style="color: #fff;">&times;</button>
                <h4 class="modal-title"><i class="fas fa-user-cog"></i> Cambiar técnico asignado</h4>
            </div>
            <div class="modal-body">
                <p>
                    <strong>Reparación:</strong> <span id="ct_invoice"></span> &nbsp;·&nbsp;
                    <strong>Cliente:</strong> <span id="ct_customer"></span>
                </p>

                {{-- Aplicar mismo técnico a todas las líneas — atajo para casos simples --}}
                <div class="well well-sm" style="background: #e3f2fd; padding: 10px;">
                    <div class="form-inline">
                        <label style="margin-right: 8px;"><i class="fas fa-users"></i> Aplicar a TODAS las líneas:</label>
                        <select id="ct_apply_all" class="form-control" style="min-width: 220px;">
                            <option value="">— Elige un técnico —</option>
                            @foreach($technicians as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-sm btn-info" id="ct_apply_all_btn" style="margin-left: 6px;">
                            <i class="fas fa-arrow-down"></i> Aplicar
                        </button>
                        <small class="text-muted" style="display: block; margin-top: 4px;">
                            Opcional. Si necesitas técnicos distintos por producto, edítalos directamente en la tabla.
                        </small>
                    </div>
                </div>

                <table class="table table-bordered table-condensed" id="ct_lines_table" style="margin-top: 12px;">
                    <thead>
                        <tr class="bg-purple">
                            <th style="width: 55%;">Producto</th>
                            <th>Técnico</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
                <div id="ct_lines_loading" class="text-center text-muted" style="padding: 20px;">
                    <i class="fas fa-spinner fa-spin"></i> Cargando líneas…
                </div>

                <input type="hidden" id="ct_transaction_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="ct_save_btn">
                    <i class="fas fa-save"></i> Guardar cambios
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Options list reutilizable para los selects por línea --}}
<script type="text/template" id="ct_tech_options_tpl">
    <option value="">— Sin asignar —</option>
    @foreach($technicians as $t)
        <option value="{{ $t->id }}">{{ $t->name }}</option>
    @endforeach
</script>

@stop

@section('javascript')
<script type="text/javascript">
$(document).ready(function () {
    // Selects2 para dropdowns
    $('#rep_admin_location, #rep_admin_technician').select2({ width: '100%' });

    // Date range picker (últimos 30 días por defecto)
    var today = moment();
    var thirtyDaysAgo = moment().subtract(30, 'days');
    $('#rep_admin_date_range').daterangepicker({
        startDate: thirtyDaysAgo,
        endDate: today,
        ranges: {
            'Hoy': [moment(), moment()],
            'Ayer': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Últimos 7 días': [moment().subtract(6, 'days'), moment()],
            'Últimos 30 días': [moment().subtract(29, 'days'), moment()],
            'Este mes': [moment().startOf('month'), moment().endOf('month')],
            'Mes anterior': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        locale: {
            format: 'DD/MM/YYYY',
            applyLabel: 'Aplicar',
            cancelLabel: 'Cancelar',
            fromLabel: 'Desde',
            toLabel: 'Hasta',
            customRangeLabel: 'Rango personalizado',
            daysOfWeek: ['Do','Lu','Ma','Mi','Ju','Vi','Sa'],
            monthNames: ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
            firstDay: 1
        }
    }, function (start, end) {
        $('#rep_admin_date_range').val(start.format('DD/MM/YYYY') + ' — ' + end.format('DD/MM/YYYY'));
        table.ajax.reload();
    });
    $('#rep_admin_date_range').val(thirtyDaysAgo.format('DD/MM/YYYY') + ' — ' + today.format('DD/MM/YYYY'));

    // DataTables server-side
    var table = $('#rep_admin_table').DataTable({
        processing: true,
        serverSide: true,
        searching: false, // usamos nuestro propio input
        ordering: false,  // orden fijo por transaction_date desc
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
        language: {
            emptyTable: 'No se encontraron reparaciones',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ reparaciones',
            infoEmpty: 'Sin reparaciones',
            infoFiltered: '(filtradas de _MAX_ total)',
            loadingRecords: 'Cargando…',
            processing: 'Procesando…',
            paginate: { first: 'Primera', last: 'Última', next: 'Siguiente', previous: 'Anterior' },
            lengthMenu: 'Mostrar _MENU_ reparaciones',
            zeroRecords: 'Sin coincidencias'
        },
        ajax: {
            url: '{{ route('repair-orders.admin-search') }}',
            data: function (d) {
                var dr = $('#rep_admin_date_range').val();
                if (dr && dr.indexOf('—') > -1) {
                    var parts = dr.split('—');
                    d.start_date = moment(parts[0].trim(), 'DD/MM/YYYY').format('YYYY-MM-DD');
                    d.end_date = moment(parts[1].trim(), 'DD/MM/YYYY').format('YYYY-MM-DD');
                }
                d.location_id = $('#rep_admin_location').val();
                d.technician_id = $('#rep_admin_technician').val();
                d.status = $('#rep_admin_status').val();
                d.term = $('#rep_admin_term').val();
            }
        },
        columns: [
            { data: 'date' },
            { data: 'invoice_no', render: function (d) { return d || '—'; } },
            { data: 'location', render: function (d) { return d || '—'; } },
            { data: 'customer' },
            { data: 'mobile', render: function (d) { return d || '—'; } },
            { data: 'products', render: function (d) { return '<small>' + $('<div>').text(d).html() + '</small>'; } },
            { data: 'technician', render: function (d, t, row) {
                var style = row.current_technician_id ? '' : 'color:#c0392b;font-style:italic;';
                return '<span style="' + style + '">' + $('<div>').text(d).html() + '</span>';
            }},
            { data: 'repair_status', render: function (d) {
                if (d === 'pending') return '<span class="label label-warning">Pendiente</span>';
                // NULL o 'delivered' → Entregada. Las transacciones que aparecen aquí
                // ya son status='final', así que si no hay repair_status explícito
                // asumimos que la reparación se completó.
                return '<span class="label label-success">Entregada</span>';
            }},
            { data: 'total', render: function (d, t, row) {
                var fmt = function (n) {
                    return '$' + parseFloat(n).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                };
                var html = '<div style="font-weight:700;">' + fmt(d) + '</div>';
                // Anticipo (si hubo)
                if (parseFloat(row.anticipo_amount || 0) > 0.001) {
                    html += '<div style="font-size:11px; color:#2e7d32; margin-top:2px;">'
                        + '<i class="fas fa-hand-holding-usd"></i> Anticipo: <strong>' + fmt(row.anticipo_amount) + '</strong>'
                        + (row.anticipo_date ? ' <span style="color:#666;">(' + row.anticipo_date + ')</span>' : '')
                        + '</div>';
                } else {
                    html += '<div style="font-size:11px; color:#999; margin-top:2px;">'
                        + '<i class="far fa-circle"></i> Sin anticipo</div>';
                }
                // Fecha de entrega (si ya entregada)
                if (row.delivered_at) {
                    html += '<div style="font-size:11px; color:#1565c0; margin-top:2px;">'
                        + '<i class="fas fa-box-open"></i> Entregada: <strong>' + row.delivered_at + '</strong>'
                        + '</div>';
                } else if (row.repair_status === 'pending') {
                    html += '<div style="font-size:11px; color:#c62828; margin-top:2px;">'
                        + '<i class="fas fa-hourglass-half"></i> Pendiente de entrega</div>';
                }
                return html;
            }},
            { data: null, render: function (d, t, row) {
                return '<button type="button" class="btn btn-xs btn-primary rep-admin-change" '
                    + 'data-id="' + row.id + '" '
                    + 'data-invoice="' + (row.invoice_no || '') + '" '
                    + 'data-customer="' + $('<div>').text(row.customer).html() + '" '
                    + 'data-products="' + $('<div>').text(row.products).html() + '" '
                    + 'data-current-technician="' + $('<div>').text(row.technician).html() + '" '
                    + 'data-current-technician-id="' + (row.current_technician_id || '') + '">'
                    + '<i class="fas fa-user-cog"></i> Cambiar</button>';
            }}
        ]
    });

    // Handlers para filtros → recargar tabla
    $('#rep_admin_search_btn').click(function () { table.ajax.reload(); });
    $('#rep_admin_term').on('keypress', function (e) {
        if (e.which === 13) { e.preventDefault(); table.ajax.reload(); }
    });
    $('#rep_admin_location, #rep_admin_technician, #rep_admin_status').on('change', function () {
        table.ajax.reload();
    });

    // Botón limpiar filtros
    $('#rep_admin_reset_btn').click(function () {
        $('#rep_admin_term').val('');
        $('#rep_admin_location').val('').trigger('change');
        $('#rep_admin_technician').val('').trigger('change');
        $('#rep_admin_status').val('');
        $('#rep_admin_date_range').data('daterangepicker').setStartDate(thirtyDaysAgo);
        $('#rep_admin_date_range').data('daterangepicker').setEndDate(today);
        $('#rep_admin_date_range').val(thirtyDaysAgo.format('DD/MM/YYYY') + ' — ' + today.format('DD/MM/YYYY'));
        table.ajax.reload();
    });

    // Abrir modal de cambiar técnico — carga las líneas dinámicamente.
    // Cada producto/línea tiene su propio select para permitir 2+ técnicos por reparación.
    $(document).on('click', '.rep-admin-change', function () {
        var b = $(this);
        var id = b.data('id');
        $('#ct_transaction_id').val(id);
        $('#ct_invoice').text(b.data('invoice') || '—');
        $('#ct_customer').text(b.data('customer'));
        $('#ct_apply_all').val('');
        $('#ct_lines_table').hide();
        $('#ct_lines_table tbody').empty();
        $('#ct_lines_loading').show();
        $('#change_technician_modal').modal('show');

        var tplOptions = $('#ct_tech_options_tpl').html();
        $.getJSON('/repair-orders/' + id + '/lines', function (resp) {
            $('#ct_lines_loading').hide();
            if (!resp || resp.success !== 1) {
                toastr.error('No se pudieron cargar las líneas de la reparación.');
                return;
            }
            var $tbody = $('#ct_lines_table tbody');
            resp.lines.forEach(function (line) {
                var $sel = $('<select class="form-control input-sm ct-line-tech" '
                    + 'data-tsl-id="' + line.tsl_id + '">' + tplOptions + '</select>');
                $sel.val(line.technician_id || '');
                var $tr = $('<tr></tr>');
                $tr.append($('<td></td>').text(line.product_name));
                $tr.append($('<td></td>').append($sel));
                $tbody.append($tr);
            });
            $('#ct_lines_table').show();
        }).fail(function () {
            $('#ct_lines_loading').hide();
            toastr.error('Error al cargar las líneas.');
        });
    });

    // Aplicar mismo técnico a todas las líneas del modal
    $('#ct_apply_all_btn').click(function () {
        var val = $('#ct_apply_all').val();
        $('.ct-line-tech').val(val);
    });

    // Guardar cambio de técnico — envía por línea (assignments)
    $('#ct_save_btn').click(function () {
        var id = $('#ct_transaction_id').val();
        if (!id) return;
        var assignments = {};
        $('.ct-line-tech').each(function () {
            var $s = $(this);
            assignments[$s.data('tsl-id')] = $s.val() || '';
        });
        var btn = $(this);
        btn.prop('disabled', true);
        $.ajax({
            method: 'POST',
            url: '/repair-orders/' + id + '/change-technician',
            data: {
                assignments: assignments,
                _token: '{{ csrf_token() }}'
            },
            dataType: 'json',
            success: function (res) {
                btn.prop('disabled', false);
                if (res.success) {
                    toastr.success(res.msg);
                    $('#change_technician_modal').modal('hide');
                    table.ajax.reload(null, false); // false = mantener página actual
                } else {
                    toastr.error(res.msg);
                }
            },
            error: function () {
                btn.prop('disabled', false);
                toastr.error('Error al guardar el cambio');
            }
        });
    });
});
</script>
@endsection
