@extends('layouts.app')
@section('title', 'Promos — App Config')

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        Promos
        <small class="tw-text-sm tw-text-gray-700">Promociones temporales que aparecen en la app Celfix</small>
    </h1>
</section>

<section class="content">
    <div style="margin-bottom: 15px;">
        <a href="{{ route('app-config.promos.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nueva promo
        </a>
    </div>

    @component('components.widget', ['class' => 'box-primary'])
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr class="bg-light-blue">
                        <th style="width:80px;">Imagen</th>
                        <th>Título</th>
                        <th>Categoría</th>
                        <th>Vigencia</th>
                        <th>Sucursal</th>
                        <th class="text-center">Activa</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($promos as $p)
                        <tr>
                            <td>
                                @if($p->image_path)
                                    <img src="{{ asset('storage/' . $p->image_path) }}" style="max-width:70px; max-height:50px;">
                                @endif
                            </td>
                            <td><strong>{{ $p->title }}</strong>
                                @if($p->description)<br><small class="text-muted">{{ \Str::limit($p->description, 80) }}</small>@endif
                            </td>
                            <td>{{ $p->category ?: '—' }}</td>
                            <td>
                                @if($p->starts_at || $p->ends_at)
                                    {{ $p->starts_at ? $p->starts_at->format('d/m/Y') : '—' }}
                                    →
                                    {{ $p->ends_at ? $p->ends_at->format('d/m/Y') : '—' }}
                                @else
                                    <span class="text-muted">Sin vigencia</span>
                                @endif
                            </td>
                            <td>{{ $p->location->name ?? 'Todas' }}</td>
                            <td class="text-center">
                                @if($p->isCurrentlyActive())
                                    <span class="label label-success">Sí</span>
                                @else
                                    <span class="label label-default">No</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('app-config.promos.edit', $p->id) }}" class="btn btn-primary btn-xs">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-danger btn-xs promo-delete" data-id="{{ $p->id }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">Aún no hay promos. Crea la primera.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endcomponent
</section>
@stop

@section('javascript')
<script>
$(document).on('click', '.promo-delete', function () {
    if (!confirm('¿Eliminar esta promo?')) return;
    var id = $(this).data('id');
    var $btn = $(this);
    $.ajax({
        url: '/app-config/promos/' + id,
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
