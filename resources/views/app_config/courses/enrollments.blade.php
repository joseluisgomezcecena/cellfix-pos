@extends('layouts.app')
@section('title', 'Inscritos — ' . $course->title)

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        Inscritos: {{ $course->title }}
        <small class="tw-text-sm tw-text-gray-700">
            {{ $course->starts_at->format('d/m/Y H:i') }} — {{ $course->ends_at->format('H:i') }}
            @if($course->location) · {{ $course->location->name }} @endif
        </small>
    </h1>
</section>

<section class="content">
    <div style="margin-bottom:15px;">
        <a href="{{ route('app-config.courses.index') }}" class="btn btn-default">
            <i class="fa fa-arrow-left"></i> Volver a cursos
        </a>
        <a href="{{ route('app-config.courses.edit', $course->id) }}" class="btn btn-primary">
            <i class="fa fa-edit"></i> Editar curso
        </a>
    </div>

    @component('components.widget', ['class' => 'box-primary'])
        <p style="margin-bottom:10px;">
            <strong>Inscritos:</strong> {{ $enrollments->count() }}
            @if((int)$course->capacity > 0)
                / {{ $course->capacity }}
                @if($enrollments->count() >= (int)$course->capacity)
                    <span class="label label-warning">Cupo lleno</span>
                @endif
            @else
                <span class="text-muted">(sin límite)</span>
            @endif
        </p>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr class="bg-light-blue">
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>Membresía</th>
                        <th>Se inscribió</th>
                        <th class="text-center" style="width:100px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($enrollments as $i => $e)
                        @php $c = $e->contact; @endphp
                        <tr data-enrollment="{{ $e->id }}">
                            <td>{{ $i + 1 }}</td>
                            <td>
                                @if($c)
                                    <strong>{{ trim($c->name ?: (($c->first_name ?? '') . ' ' . ($c->last_name ?? ''))) }}</strong>
                                @else
                                    <em class="text-muted">Cliente eliminado</em>
                                @endif
                            </td>
                            <td>{{ $c->mobile ?? '—' }}</td>
                            <td>{{ $c->membership_no ?? '—' }}</td>
                            <td>{{ $e->enrolled_at ? $e->enrolled_at->format('d/m/Y H:i') : '—' }}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-danger btn-xs enrollment-remove"
                                        data-course="{{ $course->id }}" data-id="{{ $e->id }}" title="Quitar inscrito">
                                    <i class="fas fa-times"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">Nadie se ha inscrito todavía.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endcomponent
</section>
@stop

@section('javascript')
<script>
$(document).on('click', '.enrollment-remove', function () {
    if (!confirm('¿Quitar a este inscrito? El cupo queda libre para otro cliente.')) return;
    var courseId = $(this).data('course');
    var id = $(this).data('id');
    var $btn = $(this);
    $.ajax({
        url: '/app-config/courses/' + courseId + '/enrollments/' + id,
        method: 'DELETE',
        data: { _token: '{{ csrf_token() }}' },
        success: function (r) {
            if (r.success == 1) { toastr.success(r.msg); $btn.closest('tr').fadeOut(); }
            else toastr.error(r.msg);
        },
        error: function () { toastr.error('Error al remover.'); }
    });
});
</script>
@endsection
