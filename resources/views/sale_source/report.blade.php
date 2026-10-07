@extends('layouts.app')
@section('title', 'Orígenes de clientes — reporte')

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        Orígenes de clientes
        <small class="tw-text-sm tw-text-gray-700">De dónde se enteran de nosotros los clientes nuevos</small>
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr class="bg-light-blue">
                        <th style="width:40px;"></th>
                        <th>Origen</th>
                        <th class="text-center" style="width:120px;">Clientes</th>
                        <th class="text-center" style="width:120px;">Ventas</th>
                        <th class="text-right" style="width:160px;">Facturado</th>
                        <th class="text-center" style="width:100px;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sources as $s)
                        @php
                            $stats = $agg[$s->id] ?? null;
                            $contacts = $contacts_by_source[$s->id] ?? collect();
                            $hasData = $stats && (int)$stats->sales_count > 0;
                        @endphp
                        <tr @if(!$hasData) style="color:#999;" @endif>
                            <td class="text-center">
                                @if($hasData)
                                    <a href="#" class="toggle-source-rows" data-target="source_rows_{{ $s->id }}">
                                        <i class="fa fa-plus-square"></i>
                                    </a>
                                @endif
                            </td>
                            <td><strong>{{ $s->name }}</strong></td>
                            <td class="text-center">{{ $stats->unique_customers ?? 0 }}</td>
                            <td class="text-center">{{ $stats->sales_count ?? 0 }}</td>
                            <td class="text-right">${{ number_format((float)($stats->revenue ?? 0), 2) }}</td>
                            <td class="text-center">
                                @if($s->is_active)
                                    <span class="label label-success">Activo</span>
                                @else
                                    <span class="label label-default">Inactivo</span>
                                @endif
                            </td>
                        </tr>
                        @if($hasData)
                            <tr class="source-contacts" id="source_rows_{{ $s->id }}" style="display:none;">
                                <td colspan="6" style="background:#fafafa; padding:12px;">
                                    <div style="font-weight:bold; margin-bottom:8px;">
                                        <i class="fa fa-users"></i> Clientes que llegaron por "{{ $s->name }}"
                                        @if($contacts->count() >= 50)
                                            <small class="text-muted">(mostrando top 50 por facturación)</small>
                                        @endif
                                    </div>
                                    <table class="table table-condensed" style="margin-bottom:0; font-size:12px;">
                                        <thead>
                                            <tr>
                                                <th>Cliente</th>
                                                <th>Teléfono</th>
                                                <th>Primera compra</th>
                                                <th class="text-center">Ventas</th>
                                                <th class="text-right">Facturado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($contacts as $c)
                                                <tr>
                                                    <td>{{ $c->name }}</td>
                                                    <td>{{ $c->mobile ?: '—' }}</td>
                                                    <td>{{ $c->first_purchase ? \Carbon\Carbon::parse($c->first_purchase)->format('d/m/Y') : '—' }}</td>
                                                    <td class="text-center">{{ $c->sales_count }}</td>
                                                    <td class="text-right">${{ number_format((float)$c->revenue, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">Aún no hay orígenes configurados. @can('business_settings.access') Agrégalos en <a href="{{ route('sale-sources.index') }}">Configuraciones → Orígenes de clientes</a>. @endcan</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <small class="text-muted">
            Se cuenta cada venta cobrada en el POS donde el cajero registró el origen; las ventas con origen vacío no aparecen.
            @can('business_settings.access')
                Para agregar o editar opciones ve a <a href="{{ route('sale-sources.index') }}">Configuraciones → Orígenes de clientes</a>.
            @endcan
        </small>
    @endcomponent
</section>
@stop

@section('javascript')
<script>
$(document).on('click', '.toggle-source-rows', function (e) {
    e.preventDefault();
    var $icon = $(this).find('i');
    var target = '#' + $(this).data('target');
    $(target).toggle();
    if ($(target).is(':visible')) {
        $icon.removeClass('fa-plus-square').addClass('fa-minus-square');
    } else {
        $icon.removeClass('fa-minus-square').addClass('fa-plus-square');
    }
});
</script>
@endsection
