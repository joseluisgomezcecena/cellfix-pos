@extends('layouts.app')
@section('title', 'Cursos — App Config')

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        Cursos
        <small class="tw-text-sm tw-text-gray-700">Programa cursos y talleres para los socios Celfix</small>
    </h1>
</section>

<section class="content">
    <div style="margin-bottom: 15px;">
        <a href="{{ route('app-config.courses.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuevo curso
        </a>
    </div>

    @component('components.widget', ['class' => 'box-primary'])
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr class="bg-light-blue">
                        <th style="width:70px;">Imagen</th>
                        <th>Título</th>
                        <th>Sucursal</th>
                        <th>Fecha y hora</th>
                        <th class="text-center">Inscritos</th>
                        <th class="text-center">Activo</th>
                        <th class="text-center" style="width:210px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($courses as $c)
                        @php
                            $isPast = $c->ends_at && $c->ends_at->isPast();
                            $isFull = (int)$c->capacity > 0 && $c->enrollments_count >= (int)$c->capacity;
                        @endphp
                        <tr @if($isPast) style="opacity:0.6;" @endif>
                            <td>
                                @if($c->image_path)
                                    <img src="{{ asset('storage/' . $c->image_path) }}" style="max-width:60px; max-height:45px;">
                                @endif
                            </td>
                            <td>
                                <strong>{{ $c->title }}</strong>
                                @if($isPast)<span class="label label-default" style="margin-left:6px;">Finalizado</span>@endif
                                @if($c->instructor_name)<br><small class="text-muted"><i class="fa fa-user"></i> {{ $c->instructor_name }}</small>@endif
                                @if($c->description)<br><small class="text-muted">{{ \Str::limit($c->description, 90) }}</small>@endif
                            </td>
                            <td>{{ $c->location->name ?? 'Todas' }}</td>
                            <td>
                                {{ $c->starts_at ? $c->starts_at->format('d/m/Y H:i') : '—' }}
                                <br><small class="text-muted">hasta {{ $c->ends_at ? $c->ends_at->format('H:i') : '—' }}</small>
                            </td>
                            <td class="text-center">
                                @if((int)$c->capacity === 0)
                                    <span class="label label-info">{{ $c->enrollments_count }} <small>/ ∞</small></span>
                                @elseif($isFull)
                                    <span class="label label-warning">{{ $c->enrollments_count }} / {{ $c->capacity }} · Lleno</span>
                                @else
                                    <span class="label label-success">{{ $c->enrollments_count }} / {{ $c->capacity }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($c->is_active)
                                    <span class="label label-success">Sí</span>
                                @else
                                    <span class="label label-default">No</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('app-config.courses.enrollments', $c->id) }}" class="btn btn-info btn-xs" title="Ver inscritos">
                                    <i class="fas fa-users"></i>
                                </a>
                                <a href="{{ route('app-config.courses.edit', $c->id) }}" class="btn btn-primary btn-xs" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-danger btn-xs course-delete" data-id="{{ $c->id }}" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">Aún no hay cursos programados. Crea el primero.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endcomponent
</section>
@stop

@section('javascript')
<script>
$(document).on('click', '.course-delete', function () {
    if (!confirm('¿Eliminar este curso? Se cancelarán todas las inscripciones.')) return;
    var id = $(this).data('id');
    var $btn = $(this);
    $.ajax({
        url: '/app-config/courses/' + id,
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
