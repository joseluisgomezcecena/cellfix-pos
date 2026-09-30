@extends('layouts.app')
@section('title', 'Membresías Premium — App Config')

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        Membresías Premium
        <small class="tw-text-sm tw-text-gray-700">Administra las suscripciones anuales de socios Celfix</small>
    </h1>
</section>

<section class="content">
    <div class="alert alert-info" style="padding:12px;">
        <i class="fa fa-info-circle"></i>
        <strong>Cómo funciona:</strong>
        Cada membresía premium dura <strong>1 año</strong> desde la fecha de activación.
        Los socios premium ven promos y beneficios marcados con <i class="fa fa-star" style="color:#f0ad4e;"></i>
        sin restricción; los usuarios registrados no premium los ven grises con el texto
        "Paga tu suscripción para acceder".
    </div>

    @component('components.widget', ['class' => 'box-primary'])
        <h4 style="margin-top:0;"><i class="fa fa-search"></i> Buscar cliente</h4>
        <div class="form-group">
            <input type="text" id="membership-search" class="form-control input-lg"
                   placeholder="Nombre, teléfono o número de membresía…" autocomplete="off">
            <small class="text-muted">Escribe al menos 2 caracteres.</small>
        </div>
        <div id="membership-search-results" style="margin-top:10px;"></div>
    @endcomponent

    @component('components.widget', ['class' => 'box-success'])
        <h4 style="margin-top:0;"><i class="fa fa-star" style="color:#f0ad4e;"></i> Membresías activas ({{ $active->count() }})</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr class="bg-light-blue">
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>Núm. Membresía</th>
                        <th>Expira</th>
                        <th>Días restantes</th>
                        <th class="text-center" style="width:180px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($active as $c)
                        @php
                            $expires = \Carbon\Carbon::parse($c->membership_expires_at);
                            $daysLeft = (int) now()->startOfDay()->diffInDays($expires->startOfDay(), false);
                        @endphp
                        <tr data-contact="{{ $c->id }}">
                            <td><strong>{{ trim(($c->name ?: (($c->first_name ?? '') . ' ' . ($c->last_name ?? '')))) }}</strong></td>
                            <td>{{ $c->mobile }}</td>
                            <td>{{ $c->membership_no ?? '—' }}</td>
                            <td>{{ $expires->format('Y-m-d') }}</td>
                            <td>
                                @if($daysLeft <= 30)
                                    <span class="label label-warning">{{ $daysLeft }} días</span>
                                @else
                                    <span class="label label-success">{{ $daysLeft }} días</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-primary btn-xs membership-action" data-action="renew" data-id="{{ $c->id }}">
                                    <i class="fa fa-refresh"></i> Renovar
                                </button>
                                <button type="button" class="btn btn-danger btn-xs membership-action" data-action="cancel" data-id="{{ $c->id }}">
                                    <i class="fa fa-times"></i> Cancelar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">No hay membresías activas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endcomponent

    @if($expired->count() > 0)
    @component('components.widget', ['class' => 'box-default'])
        <h4 style="margin-top:0;"><i class="fa fa-history"></i> Membresías expiradas (últimas 50)</h4>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Teléfono</th>
                        <th>Expiró el</th>
                        <th class="text-center" style="width:180px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expired as $c)
                        <tr data-contact="{{ $c->id }}">
                            <td>{{ trim(($c->name ?: (($c->first_name ?? '') . ' ' . ($c->last_name ?? '')))) }}</td>
                            <td>{{ $c->mobile }}</td>
                            <td>{{ \Carbon\Carbon::parse($c->membership_expires_at)->format('Y-m-d') }}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-success btn-xs membership-action" data-action="activate" data-id="{{ $c->id }}">
                                    <i class="fa fa-star"></i> Reactivar
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endcomponent
    @endif
</section>
@stop

@section('javascript')
<script>
$(function () {
    var $input = $('#membership-search');
    var $results = $('#membership-search-results');
    var searchTimer = null;

    $input.on('input', function () {
        var term = $(this).val().trim();
        clearTimeout(searchTimer);
        if (term.length < 2) { $results.empty(); return; }
        searchTimer = setTimeout(function () { runSearch(term); }, 250);
    });

    function runSearch(term) {
        $.getJSON('{{ route('app-config.memberships.search') }}', { q: term })
            .done(function (r) { renderResults(r.data); })
            .fail(function () { $results.html('<div class="alert alert-danger">Error al buscar.</div>'); });
    }

    function renderResults(rows) {
        if (!rows || rows.length === 0) {
            $results.html('<div class="text-muted"><em>Sin resultados.</em></div>');
            return;
        }
        var html = '<table class="table table-condensed table-hover"><thead><tr>' +
                   '<th>Cliente</th><th>Teléfono</th><th>Membresía</th><th>Estado</th><th></th>' +
                   '</tr></thead><tbody>';
        rows.forEach(function (c) {
            var status = c.is_premium
                ? '<span class="label label-success">Premium hasta ' + c.membership_expires_at + '</span>'
                : (c.membership_expires_at
                    ? '<span class="label label-default">Expiró ' + c.membership_expires_at + '</span>'
                    : '<span class="label label-default">No premium</span>');
            var action = c.is_premium
                ? '<button type="button" class="btn btn-primary btn-xs membership-action" data-action="renew" data-id="' + c.id + '"><i class="fa fa-refresh"></i> Renovar</button> ' +
                  '<button type="button" class="btn btn-danger btn-xs membership-action" data-action="cancel" data-id="' + c.id + '"><i class="fa fa-times"></i> Cancelar</button>'
                : '<button type="button" class="btn btn-success btn-xs membership-action" data-action="activate" data-id="' + c.id + '"><i class="fa fa-star"></i> Activar Premium</button>';
            html += '<tr>' +
                    '<td><strong>' + $('<i>').text(c.name).html() + '</strong></td>' +
                    '<td>' + $('<i>').text(c.mobile || '').html() + '</td>' +
                    '<td>' + $('<i>').text(c.membership_no || '—').html() + '</td>' +
                    '<td>' + status + '</td>' +
                    '<td>' + action + '</td>' +
                    '</tr>';
        });
        html += '</tbody></table>';
        $results.html(html);
    }

    var actionRoutes = {
        activate: '{{ url('/app-config/memberships') }}/{id}/activate',
        renew:    '{{ url('/app-config/memberships') }}/{id}/renew',
        cancel:   '{{ url('/app-config/memberships') }}/{id}/cancel',
    };
    var actionMessages = {
        activate: '¿Activar membresía premium por 1 año?',
        renew:    '¿Renovar la membresía 1 año más?',
        cancel:   '¿Cancelar la membresía? El cliente perderá acceso premium al instante.',
    };

    $(document).on('click', '.membership-action', function () {
        var $btn = $(this);
        var action = $btn.data('action');
        var id = $btn.data('id');
        if (!confirm(actionMessages[action])) return;
        var url = actionRoutes[action].replace('{id}', id);
        $btn.prop('disabled', true);
        $.ajax({
            url: url,
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function (r) {
                if (r.success == 1) {
                    toastr.success(r.msg);
                    setTimeout(function () { location.reload(); }, 800);
                } else {
                    toastr.error(r.msg || 'Error.');
                    $btn.prop('disabled', false);
                }
            },
            error: function () { toastr.error('Error de red.'); $btn.prop('disabled', false); }
        });
    });
});
</script>
@endsection
