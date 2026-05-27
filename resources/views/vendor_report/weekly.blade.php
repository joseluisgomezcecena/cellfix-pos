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
                        {!! Form::date('start_date', $start_date, ['class' => 'form-control', 'style' => 'width: 100%']) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('location_id', 'Sucursal:') !!}
                        {!! Form::select('location_id', $locations, $location_id, ['class' => 'form-control select2', 'placeholder' => 'Todas', 'style' => 'width: 100%']) !!}
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
    @endphp

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
    @endphp
    @foreach($vendors as $i => $vendor_data)
        @php
            $color = $card_colors[$i % count($card_colors)];
            $u = $vendor_data['user'];
            $rows = $vendor_data['rows'];
            $totals = $vendor_data['totals'];
        @endphp
        <div class="row" style="margin-top: 10px;">
            <div class="col-md-12">
                <div class="box box-solid" style="border: 2px solid {{ $color }};">
                    <div class="vendor-card-header" style="background-color: {{ $color }};">
                        {{ strtoupper(trim($u->first_name . ' ' . $u->last_name)) }}
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
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    @if(empty($vendors))
        <div class="alert alert-info">No hay vendedores con ventas en este periodo.</div>
    @endif
</section>

@stop
