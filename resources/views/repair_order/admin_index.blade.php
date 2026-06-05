@extends('layouts.app')
@section('title', 'Administrar Reparaciones')

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        Administrar Reparaciones
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">
            Buscar y reasignar el técnico cuando se haya capturado por error
        </small>
    </h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" id="rep_admin_term" class="form-control"
                                placeholder="Buscar por cliente, teléfono, folio o producto…">
                            <span class="input-group-btn">
                                <button class="btn btn-primary" type="button" id="rep_admin_search_btn">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select id="rep_admin_status" class="form-control">
                            <option value="">Todos los estados</option>
                            <option value="pending">Pendientes</option>
                            <option value="delivered">Entregadas</option>
                        </select>
                    </div>
                </div>

                <div style="margin-top: 18px;">
                    <div id="rep_admin_results"></div>
                </div>
            @endcomponent
        </div>
    </div>
</section>

{{-- Modal para cambiar técnico --}}
<div class="modal fade" id="change_technician_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #2196f3; color: #fff;">
                <button type="button" class="close" data-dismiss="modal" style="color: #fff;">&times;</button>
                <h4 class="modal-title"><i class="fas fa-user-cog"></i> Cambiar técnico asignado</h4>
            </div>
            <div class="modal-body">
                <p>
                    <strong>Reparación:</strong> <span id="ct_invoice"></span><br>
                    <strong>Cliente:</strong> <span id="ct_customer"></span><br>
                    <strong>Producto(s):</strong> <span id="ct_products" class="text-muted"></span><br>
                    <strong>Técnico actual:</strong> <span id="ct_current_technician" style="color: #c0392b;"></span>
                </p>
                <hr>
                <div class="form-group">
                    <label>Nuevo técnico:</label>
                    <select id="ct_technician" class="form-control">
                        <option value="">— Sin asignar —</option>
                        @foreach($technicians as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                <input type="hidden" id="ct_transaction_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="ct_save_btn">
                    <i class="fas fa-save"></i> Guardar cambio
                </button>
            </div>
        </div>
    </div>
</div>

@stop

@section('javascript')
<script type="text/javascript">
$(document).ready(function () {
    function repAdminSearch() {
        var term = $('#rep_admin_term').val();
        var status = $('#rep_admin_status').val();
        $('#rep_admin_results').html('<p class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Buscando…</p>');
        $.get('{{ route('repair-orders.admin-search') }}', { term: term, status: status }, function (data) {
            var html = '';
            if (!data.orders || data.orders.length === 0) {
                html = '<p class="text-muted text-center" style="padding:20px;">No se encontraron reparaciones.</p>';
            } else {
                html = '<table class="table table-bordered table-hover" style="font-size:13px;"><thead><tr>'
                    + '<th>Fecha</th><th>Folio</th><th>Cliente</th><th>Tel</th>'
                    + '<th>Producto(s)</th><th>Técnico actual</th><th>Estado</th><th>Acción</th></tr></thead><tbody>';
                $.each(data.orders, function (i, o) {
                    var statusLabel = o.repair_status === 'pending'
                        ? '<span class="label label-warning">Pendiente</span>'
                        : '<span class="label label-success">Entregada</span>';
                    var techStyle = o.current_technician_id ? '' : 'color:#c0392b;font-style:italic;';
                    html += '<tr>'
                        + '<td>' + o.date + '</td>'
                        + '<td>' + (o.invoice_no || '—') + '</td>'
                        + '<td>' + $('<div>').text(o.customer).html() + '</td>'
                        + '<td>' + (o.mobile || '—') + '</td>'
                        + '<td><small>' + $('<div>').text(o.products).html() + '</small></td>'
                        + '<td style="' + techStyle + '">' + $('<div>').text(o.technician).html() + '</td>'
                        + '<td>' + statusLabel + '</td>'
                        + '<td><button type="button" class="btn btn-xs btn-primary rep-admin-change" '
                        + 'data-id="' + o.id + '" '
                        + 'data-invoice="' + (o.invoice_no || '') + '" '
                        + 'data-customer="' + $('<div>').text(o.customer).html() + '" '
                        + 'data-products="' + $('<div>').text(o.products).html() + '" '
                        + 'data-current-technician="' + $('<div>').text(o.technician).html() + '" '
                        + 'data-current-technician-id="' + (o.current_technician_id || '') + '">'
                        + '<i class="fas fa-user-cog"></i> Cambiar técnico</button></td>'
                        + '</tr>';
                });
                html += '</tbody></table>';
            }
            $('#rep_admin_results').html(html);
        }, 'json').fail(function () {
            $('#rep_admin_results').html('<div class="alert alert-danger">Error al buscar reparaciones.</div>');
        });
    }

    $('#rep_admin_search_btn').click(repAdminSearch);
    $('#rep_admin_term').on('keypress', function (e) {
        if (e.which === 13) { e.preventDefault(); repAdminSearch(); }
    });
    $('#rep_admin_status').on('change', repAdminSearch);

    // Carga inicial: todas las reparaciones
    repAdminSearch();

    // Abrir modal de cambiar técnico
    $(document).on('click', '.rep-admin-change', function () {
        var b = $(this);
        $('#ct_transaction_id').val(b.data('id'));
        $('#ct_invoice').text(b.data('invoice') || '—');
        $('#ct_customer').text(b.data('customer'));
        $('#ct_products').text(b.data('products'));
        $('#ct_current_technician').text(b.data('current-technician'));
        $('#ct_technician').val(b.data('current-technician-id') || '');
        $('#change_technician_modal').modal('show');
    });

    // Guardar el cambio
    $('#ct_save_btn').click(function () {
        var id = $('#ct_transaction_id').val();
        var technician_id = $('#ct_technician').val();
        if (!id) return;
        var btn = $(this);
        btn.prop('disabled', true);
        $.ajax({
            method: 'POST',
            url: '/repair-orders/' + id + '/change-technician',
            data: {
                technician_id: technician_id,
                _token: '{{ csrf_token() }}'
            },
            dataType: 'json',
            success: function (res) {
                btn.prop('disabled', false);
                if (res.success) {
                    toastr.success(res.msg);
                    $('#change_technician_modal').modal('hide');
                    repAdminSearch();
                } else {
                    toastr.error(res.msg);
                }
            },
            error: function () {
                btn.prop('disabled', false);
                toastr.error('Error al actualizar técnico');
            }
        });
    });
});
</script>
@endsection
