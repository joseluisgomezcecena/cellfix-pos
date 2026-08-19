@extends('layouts.app')
@section('title', 'Beneficios — App Config')

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        Beneficios
        <small class="tw-text-sm tw-text-gray-700">Beneficios permanentes de socios que aparecen en la app Celfix</small>
    </h1>
</section>

<section class="content">
    <div style="margin-bottom: 15px;">
        <a href="{{ route('app-config.benefits.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuevo beneficio
        </a>
    </div>

    @component('components.widget', ['class' => 'box-primary'])
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr class="bg-light-blue">
                        <th style="width:80px;">Valor</th>
                        <th>Título</th>
                        <th>Compra mínima</th>
                        <th>Sucursal</th>
                        <th style="width:60px;">Orden</th>
                        <th class="text-center">Activo</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($benefits as $b)
                        <tr>
                            <td><span style="font-size:20px; font-weight:bold; color:#2196f3;">{{ $b->displayValue() }}</span></td>
                            <td><strong>{{ $b->title }}</strong>
                                @if($b->description)<br><small class="text-muted">{{ \Str::limit($b->description, 80) }}</small>@endif
                                @if($b->conditions)<br><small class="text-warning"><em>{{ $b->conditions }}</em></small>@endif
                            </td>
                            <td>{{ $b->min_purchase ? '$' . number_format($b->min_purchase, 2) : '—' }}</td>
                            <td>{{ $b->location->name ?? 'Todas' }}</td>
                            <td class="text-center">{{ $b->sort_order }}</td>
                            <td class="text-center">
                                @if($b->is_active)
                                    <span class="label label-success">Sí</span>
                                @else
                                    <span class="label label-default">No</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('app-config.benefits.edit', $b->id) }}" class="btn btn-primary btn-xs">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-danger btn-xs benefit-delete" data-id="{{ $b->id }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">Aún no hay beneficios. Crea el primero.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endcomponent
</section>
@stop

@section('javascript')
<script>
$(document).on('click', '.benefit-delete', function () {
    if (!confirm('¿Eliminar este beneficio?')) return;
    var id = $(this).data('id');
    var $btn = $(this);
    $.ajax({
        url: '/app-config/benefits/' + id,
        method: 'DELETE',
        data: { _token: '{{ csrf_token() }}' },
        success: function (r) {
            if (r.success == 1) { toastr.success(r.msg); $btn.closest('tr').fadeOut(); }
            else toastr.error(r.msg);
        },
        error: function () { toastr.error('Error al eliminar.'); }
    });
});
</script>
@endsection
