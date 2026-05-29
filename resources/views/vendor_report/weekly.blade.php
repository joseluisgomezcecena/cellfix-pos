@extends('layouts.app')
@section('title', 'Reporte semanal de vendedores')

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">Reporte semanal de vendedores
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">
            Unidades vendidas por categoría, día y vendedor
        </small>
    </h1>
</section>

<section class="content">
    @component('components.filters', ['title' => __('report.filters')])
        {!! Form::open(['url' => route('vendor-reports.weekly'), 'method' => 'get', 'class' => 'form-inline']) !!}
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('start_date', 'Inicio de semana (lunes):') !!}
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                            {!! Form::text('start_date', $start_date, ['class' => 'form-control', 'id' => 'start_date', 'readonly', 'autocomplete' => 'off', 'style' => 'width: 100%; background-color:#fff;']) !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('location_id', 'Sucursal:') !!}
                        {!! Form::select('location_id', $locations, $location_id, ['class' => 'form-control', 'placeholder' => 'Todas', 'style' => 'width: 100%']) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group" style="margin-top: 25px;">
                        <button type="submit" class="btn btn-primary">@lang('messages.filter')</button>
                        <a href="{{ route('vendor-reports.export-weekly', ['start_date' => $start_date, 'location_id' => $location_id]) }}" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Exportar a Excel
                        </a>
                    </div>
                </div>
            </div>
        {!! Form::close() !!}
    @endcomponent

    @php
        $day_short_map = [0 => 'DOMINGO', 1 => 'LUNES', 2 => 'MARTES', 3 => 'MIÉRCOLES', 4 => 'JUEVES', 5 => 'VIERNES', 6 => 'SÁBADO'];
        $brands = $data['brands'];
        $days = $data['days'];
        $vendors = $data['vendors'];
        $combined = $data['combined'];
        $combined_totals = $data['combined_totals'];
        $active_location_name = !empty($location_id) && isset($locations[$location_id]) ? $locations[$location_id] : 'TODAS';
    @endphp

    {{-- Indicador visible de filtros activos (para verificar que el filtro de sucursal se aplicó) --}}
    <div class="alert alert-info" style="margin-bottom:10px;">
        <strong>Filtros activos:</strong>
        Semana: {{ $start->format('d/m/Y') }} → {{ $end->format('d/m/Y') }}
        &nbsp;|&nbsp;
        <strong>Sucursal:</strong> <span style="background:#1abc9c;color:#fff;padding:2px 8px;border-radius:3px;">{{ $active_location_name }}</span>
    </div>

    <style>
        .vendor-report-table { font-size: 11px; margin-bottom: 0; }
        .vendor-report-table th, .vendor-report-table td { padding: 4px 6px !important; text-align: center; vertical-align: middle; }
        .vendor-report-table thead th { background-color: #ec407a; color: white; font-weight: bold; }
        .vendor-report-table tfoot td { background-color: #bbdefb; font-weight: bold; }
        .vendor-card-header { font-size: 16px; font-weight: bold; padding: 8px 12px; color: white; }
        .day-cell { background-color: #fafafa; font-weight: bold; }
    </style>

    {{-- COMBINED TOTALES TABLE FIRST --}}
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-warning', 'title' => 'TOTAL DE TODOS LOS VENDEDORES'])
                <div class="table-responsive">
                    <table class="table table-bordered vendor-report-table">
                        <thead>
                            <tr style="background-color: #ffc107;">
                                <th style="background-color:#ffc107; color:black;">DÍA</th>
                                @foreach($brands as $b)
                                    <th style="background-color:#ffc107; color:black;">{{ strtoupper($b->name) }}</th>
                                @endforeach
                                <th style="background-color:#ffc107; color:black;">N.DIA</th>
                                <th style="background-color:#ffc107; color:black;">TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($days as $d)
                                @php $key = $d->toDateString(); @endphp
                                <tr>
                                    <td class="day-cell">{{ $day_short_map[$d->dayOfWeek] ?? '' }} {{ $d->format('d/m') }}</td>
                                    @foreach($brands as $b)
                                        <td>{{ (int)($combined[$key][$b->id] ?? 0) }}</td>
                                    @endforeach
                                    <td>{{ (int)($combined[$key]['n_dia'] ?? 0) }}</td>
                                    <td><strong>{{ (int)($combined[$key]['total'] ?? 0) }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>TOTALES</td>
                                @foreach($brands as $b)
                                    <td>{{ (int)($combined_totals['brands'][$b->id] ?? 0) }}</td>
                                @endforeach
                                <td>{{ (int)$combined_totals['n_dia'] }}</td>
                                <td>{{ (int)$combined_totals['total'] }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>

    {{-- ONE TABLE PER VENDOR --}}
    @php
        $card_colors = ['#ec407a', '#42a5f5', '#66bb6a', '#ffa726', '#ab47bc', '#26a69a', '#7e57c2', '#ef5350', '#5c6bc0', '#26c6da'];
        // Si hay sucursal filtrada, mostrar solo vendedores con ventas (>0) en ese filtro.
        // Sin filtro, mostrar todos (aunque tengan 0) para listar a todos los vendedores activos.
        $vendors_visible = !empty($location_id)
            ? array_values(array_filter($vendors, fn($v) => $v['totals']['total'] > 0))
            : $vendors;
    @endphp
    @if(!empty($location_id))
        <p class="text-muted" style="margin: 6px 2px;">
            Mostrando <strong>{{ count($vendors_visible) }}</strong> vendedor(es) con ventas en esta sucursal (de {{ count($vendors) }} activos).
        </p>
    @endif
    @foreach($vendors_visible as $i => $vendor_data)
        @php
            $color = $card_colors[$i % count($card_colors)];
            $u = $vendor_data['user'];
            $rows = $vendor_data['rows'];
            $totals = $vendor_data['totals'];
            $commissions = $vendor_data['commissions'] ?? [];
            $commission_total = $totals['commission_total'] ?? 0;
        @endphp
        <div class="row" style="margin-top: 10px;">
            <div class="col-md-12">
                <div class="box box-solid" style="border: 2px solid {{ $color }};">
                    <div class="vendor-card-header" style="background-color: {{ $color }}; display:flex; justify-content:space-between; align-items:center;">
                        <span>{{ strtoupper(trim($u->first_name . ' ' . $u->last_name)) }}</span>
                        <span style="background:#fff; color:#222; padding:4px 12px; border-radius:4px; font-size:14px;">
                            COMISIÓN A PAGAR:
                            <strong style="color:#27ae60;">${{ number_format($commission_total, 2) }}</strong>
                        </span>
                    </div>
                    <div class="table-responsive" style="padding: 0;">
                        <table class="table table-bordered vendor-report-table" style="margin: 0;">
                            <thead>
                                <tr style="background-color: {{ $color }};">
                                    <th style="background-color:{{ $color }};">DÍA</th>
                                    @foreach($brands as $b)
                                        <th style="background-color:{{ $color }};">{{ strtoupper($b->name) }}</th>
                                    @endforeach
                                    <th style="background-color:{{ $color }};">N.DIA</th>
                                    <th style="background-color:{{ $color }};">TOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($days as $idx => $d)
                                    @php $row = $rows[$idx]; @endphp
                                    <tr>
                                        <td class="day-cell">{{ $day_short_map[$d->dayOfWeek] ?? '' }} {{ $d->format('d/m') }}</td>
                                        @foreach($brands as $b)
                                            <td>{{ (int)($row['brands'][$b->id] ?? 0) }}</td>
                                        @endforeach
                                        <td>{{ (int)$row['n_dia'] }}</td>
                                        <td><strong>{{ (int)$row['total'] }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td>TOTALES</td>
                                    @foreach($brands as $b)
                                        <td>{{ (int)($totals[$b->id] ?? 0) }}</td>
                                    @endforeach
                                    <td>{{ (int)$totals['n_dia'] }}</td>
                                    <td>{{ (int)$totals['total'] }}</td>
                                </tr>
                                <tr style="background:#fff9c4;">
                                    <td style="background:#fff9c4;">META (unidades)</td>
                                    @foreach($brands as $b)
                                        @php
                                            $c = $commissions[$b->id] ?? null;
                                            $meta_tip = ($c && $c['rate'] > 0) ? 'Comisiona $'.number_format($c['rate'], 2).'/unidad arriba de la meta' : '';
                                            $meta_val = $c ? (int) $c['meta'] : 0;
                                        @endphp
                                        <td style="background:#fff9c4; font-weight:normal;" title="{{ $meta_tip }}">{{ $meta_val }}</td>
                                    @endforeach
                                    <td style="background:#fff9c4;" colspan="2"></td>
                                </tr>
                                <tr style="background:#c8e6c9;">
                                    <td style="background:#c8e6c9;">COMISIÓN ($)</td>
                                    @foreach($brands as $b)
                                        @php
                                            $c = $commissions[$b->id] ?? null;
                                            $comm_tip = $c ? 'Vendió '.(int) $c['units'].' | Meta '.(int) $c['meta'].' | Sobre la meta '.(int) $c['over_meta'].' x $'.number_format($c['rate'], 2) : '';
                                            $comm_val = ($c && $c['commission'] > 0) ? '$'.number_format($c['commission'], 2) : '—';
                                            $comm_bold = ($c && $c['commission'] > 0);
                                        @endphp
                                        <td style="background:#c8e6c9;" title="{{ $comm_tip }}">
                                            @if($comm_bold)<strong>{{ $comm_val }}</strong>@else{{ $comm_val }}@endif
                                        </td>
                                    @endforeach
                                    <td style="background:#c8e6c9;"></td>
                                    <td style="background:#c8e6c9;"><strong>${{ number_format($commission_total, 2) }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    @if(empty($vendors_visible))
        <div class="alert alert-info">No hay vendedores con ventas en este periodo{{ !empty($location_id) ? ' en la sucursal seleccionada' : '' }}.</div>
    @endif
</section>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function () {
        // Date picker: solo permite seleccionar lunes (inicio de semana)
        $('#start_date').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true,
            weekStart: 1,
            daysOfWeekDisabled: [0, 2, 3, 4, 5, 6]
        });
    });
</script>
@endsection
