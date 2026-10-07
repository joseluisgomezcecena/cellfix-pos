@extends('layouts.app')
@section('title', 'Orígenes de clientes')

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        Orígenes de clientes
        <small class="tw-text-sm tw-text-gray-700">De dónde se enteran los clientes nuevos de nosotros</small>
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-warning', 'title' => 'Modal al cobrar'])
        <p style="margin:0 0 10px; font-size:13px;">
            Si está activado, al cobrar una venta en el POS a un cliente <strong>nuevo</strong>
            (que nunca había comprado) aparece un modal preguntando cómo se enteró de Celfix antes
            de imprimir el ticket. El cajero puede cerrarlo sin elegir.
        </p>
        <div class="row">
            <div class="col-sm-6">
                <label style="font-size:14px;">
                    <input type="checkbox" id="sale_source_modal_switch"
                        @if(!empty($business->sale_source_modal_enabled)) checked @endif>
                    <strong>Mostrar modal al cobrar</strong>
                    <span id="sale_source_switch_label" class="label @if(!empty($business->sale_source_modal_enabled)) label-success @else label-default @endif" style="margin-left:8px;">
                        @if(!empty($business->sale_source_modal_enabled)) ACTIVADO @else DESACTIVADO @endif
                    </span>
                </label>
            </div>
        </div>
    @endcomponent

    <div style="margin-bottom: 15px;">
        <a href="{{ route('sale-sources.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuevo origen
        </a>
    </div>

    @component('components.widget', ['class' => 'box-primary'])
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr class="bg-light-blue">
                        <th style="width:60px;">Orden</th>
                        <th>Nombre</th>
                        <th class="text-center" style="width:100px;">Activo</th>
                        <th class="text-center" style="width:150px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sources as $s)
                        <tr>
                            <td>{{ $s->sort_order }}</td>
                            <td><strong>{{ $s->name }}</strong></td>
                            <td class="text-center">
                                @if($s->is_active)
                                    <span class="label label-success">Sí</span>
                                @else
                                    <span class="label label-default">No</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('sale-sources.edit', $s->id) }}" class="btn btn-primary btn-xs">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-danger btn-xs source-delete" data-id="{{ $s->id }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">Aún no hay orígenes configurados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endcomponent
</section>
@stop

@section('javascript')
<script>
$(document).on('change', '#sale_source_modal_switch', function () {
    var enabled = $(this).is(':checked') ? 1 : 0;
    var $label = $('#sale_source_switch_label');
    $.ajax({
        url: '{{ route('sale-sources.toggle-modal') }}',
        method: 'POST',
        data: { _token: '{{ csrf_token() }}', enabled: enabled },
        success: function (r) {
            if (r.success == 1) {
                toastr.success(r.msg);
                if (r.enabled) {
                    $label.removeClass('label-default').addClass('label-success').text('ACTIVADO');
                } else {
                    $label.removeClass('label-success').addClass('label-default').text('DESACTIVADO');
                }
            } else toastr.error(r.msg || 'Error.');
        },
        error: function () { toastr.error('Error al actualizar el switch.'); }
    });
});

$(document).on('click', '.source-delete', function () {
    if (!confirm('¿Eliminar este origen? Si tiene ventas asociadas se desactivará en lugar de borrarse.')) return;
    var id = $(this).data('id');
    var $btn = $(this);
    $.ajax({
        url: '/sale-sources/' + id,
        method: 'DELETE',
        data: { _token: '{{ csrf_token() }}' },
        success: function (r) {
            if (r.success == 1) {
                toastr.success(r.msg);
                $btn.closest('tr').fadeOut();
            } else toastr.error(r.msg);
        },
        error: function () { toastr.error('Error al eliminar.'); }
    });
});
</script>
@endsection
