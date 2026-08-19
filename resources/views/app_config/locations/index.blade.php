@extends('layouts.app')
@section('title', 'App Config — Sucursales')

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        Sucursales (App)
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">
            Datos que consume la app Celfix — teléfono, horarios, ubicación, visibilidad
        </small>
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr class="bg-light-blue">
                        <th>Sucursal</th>
                        <th>Tel (App)</th>
                        <th>Horarios</th>
                        <th class="text-center">GPS</th>
                        <th class="text-center">Visible en App</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($locations as $loc)
                        @php
                            $hours = is_string($loc->hours_json) ? json_decode($loc->hours_json, true) : ($loc->hours_json ?? []);
                            $hours = is_array($hours) ? $hours : [];
                            $days_captured = 0;
                            foreach (['mon','tue','wed','thu','fri','sat','sun'] as $d) {
                                if (!empty($hours[$d]) && (empty($hours[$d]['closed']) || $hours[$d]['closed'] === false)) $days_captured++;
                            }
                        @endphp
                        <tr>
                            <td><strong>{{ $loc->name }}</strong>
                                @if($loc->landmark)<br><small class="text-muted">{{ $loc->landmark }}</small>@endif
                            </td>
                            <td>{{ $loc->phone_app ?: '—' }}</td>
                            <td class="text-center">
                                @if($days_captured > 0)
                                    <span class="label label-success">{{ $days_captured }}/7 días</span>
                                @else
                                    <span class="label label-default">Sin capturar</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($loc->latitude && $loc->longitude)
                                    <i class="fas fa-check text-success"></i>
                                    <br><small>{{ number_format($loc->latitude, 4) }}, {{ number_format($loc->longitude, 4) }}</small>
                                @else
                                    <i class="fas fa-times text-muted"></i>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($loc->is_public_in_app)
                                    <span class="label label-success">Visible</span>
                                @else
                                    <span class="label label-default">Oculta</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('app-config.locations.edit', $loc->id) }}" class="btn btn-primary btn-xs">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endcomponent
</section>
@stop
