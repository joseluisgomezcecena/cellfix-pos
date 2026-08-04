@extends('layouts.app')
@section('title', __('lang_v1.denominations_report'))

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.denominations_report')
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">
            @lang('lang_v1.denominations_subtitle')
        </small>
    </h1>
</section>

<section class="content">
    @component('components.filters', ['title' => __('report.filters')])
        {!! Form::open(['url' => route('daily-cuts.denominations'), 'method' => 'get', 'class' => 'form-inline']) !!}
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('start_date', __('lang_v1.week_start') . ' (sábado):') !!}
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                            {!! Form::text('start_date', $start_date, ['class' => 'form-control sat-only-datepicker', 'id' => 'start_date', 'readonly', 'autocomplete' => 'off', 'style' => 'width: 100%; background-color: #fff;']) !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $locations, $location_id, ['class' => 'form-control select2', 'placeholder' => __('lang_v1.all') . ' (sumadas)', 'style' => 'width: 100%']) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group" style="margin-top: 25px;">
                        <button type="submit" class="btn btn-primary">@lang('messages.filter')</button>
                        <a href="{{ route('daily-cuts.weekly') }}" class="btn btn-default">
                            <i class="fas fa-table"></i> @lang('lang_v1.weekly_view')
                        </a>
                        @php
                            $week_end = \Carbon\Carbon::parse($start_date)->addDays(6)->toDateString();
                        @endphp
                        <a href="{{ route('daily-cuts.export', ['start_date' => $start_date, 'end_date' => $week_end, 'location_id' => $location_id]) }}" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> @lang('lang_v1.export_to_excel')
                        </a>
                        <a href="{{ route('daily-cuts.index') }}" class="btn btn-default">
                            <i class="fas fa-list"></i> @lang('lang_v1.daily_cuts_history')
                        </a>
                    </div>
                </div>
            </div>
        {!! Form::close() !!}
    @endcomponent

    <style>
        .denominations-table { font-size: 12px; }
        .denominations-table th, .denominations-table td { padding: 6px !important; vertical-align: middle !important; }
        .denominations-table thead th { text-align: center; background-color: #d1ecf1; font-weight: bold; }
        .denominations-table .group-mxn { background-color: #fff9c4; }
        .denominations-table .group-usd { background-color: #b3e5fc; }
        .denominations-table .group-terminal { background-color: #c8e6c9; }
        .denominations-table .day-cell { background-color: #fff700; font-weight: bold; }
        .denominations-table .total-cell { background-color: #fff59d; font-weight: bold; }
        .denominations-table .grand-total-row { background-color: #ffe082; font-weight: bold; }
    </style>

    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.denominations_breakdown')])
        <div class="table-responsive">
            <table class="table table-bordered denominations-table">
                <thead>
                    <tr>
                        <th rowspan="2">@lang('messages.date')</th>
                        <th colspan="{{ count($mxn_faces) + 2 }}" class="group-mxn">PESOS (MXN)</th>
                        <th colspan="{{ count($usd_faces) + 3 }}" class="group-usd">DÓLARES (USD)</th>
                        <th rowspan="2" style="background-color:#ffccbc; color:#bf360c;" title="Cambio dado como vuelto en efectivo — sale del cajón">CAMBIO EFECTIVO</th>
                        <th rowspan="2" class="total-cell">@lang('lang_v1.total_cash')</th>
                        <th rowspan="2" style="background-color: #bbdefb;">TARJETA</th>
                        @foreach($terminal_names as $name)
                            <th rowspan="2" class="group-terminal">{{ strtoupper($name) }}</th>
                        @endforeach
                        <th rowspan="2">TRANSFER.</th>
                        <th rowspan="2">CHEQUE</th>
                        <th rowspan="2" class="grand-total-row">TOTAL DINERO</th>
                    </tr>
                    <tr>
                        @foreach($mxn_faces as $face)
                            <th class="group-mxn">${{ $face }}</th>
                        @endforeach
                        <th class="group-mxn">@lang('lang_v1.coins')</th>
                        <th class="group-mxn">SUBTOTAL</th>
                        @foreach($usd_faces as $face)
                            <th class="group-usd">${{ $face }}</th>
                        @endforeach
                        <th class="group-usd">@lang('lang_v1.coins')</th>
                        <th class="group-usd">SUBTOTAL USD</th>
                        <th class="group-usd">CONV. MXN</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td class="day-cell">
                                {{ $row['day_name'] }}
                                <br><small>{{ $row['date']->format('d/m/Y') }}</small>
                            </td>
                            @foreach($mxn_faces as $face)
                                <td class="text-center">{{ $row['mxn_faces'][$face] ?: '' }}</td>
                            @endforeach
                            <td class="text-right">{{ $row['mxn_coins'] > 0 ? number_format($row['mxn_coins'], 2) : '' }}</td>
                            <td class="text-right group-mxn">
                                <span class="display_currency" data-currency_symbol="true">{{ $row['mxn_subtotal'] }}</span>
                            </td>
                            @foreach($usd_faces as $face)
                                <td class="text-center">{{ $row['usd_faces'][$face] ?: '' }}</td>
                            @endforeach
                            <td class="text-right">{{ $row['usd_coins'] > 0 ? '$' . number_format($row['usd_coins'], 2) : '' }}</td>
                            <td class="text-right group-usd">${{ number_format($row['usd_subtotal'], 2) }}</td>
                            <td class="text-right group-usd">
                                <span class="display_currency" data-currency_symbol="true">{{ $row['usd_in_mxn'] }}</span>
                            </td>
                            <td class="text-right" style="background-color:#ffe0b2; color:#bf360c;">
                                @if($row['cambio_cash'] > 0)
                                    −<span class="display_currency" data-currency_symbol="true">{{ $row['cambio_cash'] }}</span>
                                @endif
                            </td>
                            <td class="text-right total-cell">
                                <span class="display_currency" data-currency_symbol="true">{{ $row['total_cash'] }}</span>
                            </td>
                            <td class="text-right" style="background-color: #bbdefb; font-weight: bold;">
                                <span class="display_currency" data-currency_symbol="true">{{ $row['total_card'] }}</span>
                            </td>
                            @foreach($terminal_names as $name)
                                <td class="text-right">
                                    <small><span class="display_currency" data-currency_symbol="true">{{ $row['terminals'][$name] }}</span></small>
                                </td>
                            @endforeach
                            <td class="text-right">
                                <span class="display_currency" data-currency_symbol="true">{{ $row['transfer'] }}</span>
                            </td>
                            <td class="text-right">
                                <span class="display_currency" data-currency_symbol="true">{{ $row['cheque'] }}</span>
                            </td>
                            <td class="text-right grand-total-row">
                                <span class="display_currency" data-currency_symbol="true">{{ $row['total_dinero'] }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="grand-total-row">
                        <td>TOTALES</td>
                        @foreach($mxn_faces as $face)
                            <td class="text-center">{{ $totals['mxn_faces'][$face] ?: '' }}</td>
                        @endforeach
                        <td class="text-right">{{ $totals['mxn_coins'] > 0 ? number_format($totals['mxn_coins'], 2) : '' }}</td>
                        <td class="text-right group-mxn">
                            <span class="display_currency" data-currency_symbol="true">{{ $totals['mxn_subtotal'] }}</span>
                        </td>
                        @foreach($usd_faces as $face)
                            <td class="text-center">{{ $totals['usd_faces'][$face] ?: '' }}</td>
                        @endforeach
                        <td class="text-right">{{ $totals['usd_coins'] > 0 ? '$' . number_format($totals['usd_coins'], 2) : '' }}</td>
                        <td class="text-right group-usd">${{ number_format($totals['usd_subtotal'], 2) }}</td>
                        <td class="text-right group-usd">
                            <span class="display_currency" data-currency_symbol="true">{{ $totals['usd_in_mxn'] }}</span>
                        </td>
                        <td class="text-right" style="background-color:#ffab91; color:#bf360c;">
                            @if(($totals['cambio_cash'] ?? 0) > 0)
                                −<span class="display_currency" data-currency_symbol="true">{{ $totals['cambio_cash'] }}</span>
                            @endif
                        </td>
                        <td class="text-right total-cell">
                            <span class="display_currency" data-currency_symbol="true">{{ $totals['total_cash'] }}</span>
                        </td>
                        <td class="text-right" style="background-color: #bbdefb; font-weight: bold;">
                            <span class="display_currency" data-currency_symbol="true">{{ $totals['total_card'] }}</span>
                        </td>
                        @foreach($terminal_names as $name)
                            <td class="text-right">
                                <small><span class="display_currency" data-currency_symbol="true">{{ $totals['terminals'][$name] }}</span></small>
                            </td>
                        @endforeach
                        <td class="text-right">
                            <span class="display_currency" data-currency_symbol="true">{{ $totals['transfer'] }}</span>
                        </td>
                        <td class="text-right">
                            <span class="display_currency" data-currency_symbol="true">{{ $totals['cheque'] }}</span>
                        </td>
                        <td class="text-right">
                            <span class="display_currency" data-currency_symbol="true">{{ $totals['total_dinero'] }}</span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Celda solitaria: gastos de la semana con desglose por categoría.
             Se muestra como referencia informativa, fuera de la tabla principal.
             NO se resta del TOTAL DINERO. --}}
        <div class="row" style="margin-top:20px;">
            <div class="col-md-6 col-md-offset-3">
                <div class="box box-solid" style="border:2px solid #d9534f; text-align:center; padding:15px; background:#fff;">
                    <div style="font-size:11px; letter-spacing:0.14em; text-transform:uppercase; color:#6c757d; font-weight:700; margin-bottom:6px;">
                        Gastos de la semana
                    </div>
                    <div style="font-family:'Courier New',monospace; font-variant-numeric:tabular-nums; font-size:28px; font-weight:800; color:#c62828; letter-spacing:-0.02em;">
                        <span class="display_currency" data-currency_symbol="true">{{ $weekly_total_expenses ?? 0 }}</span>
                    </div>

                    @if(!empty($weekly_expenses_by_category) && count($weekly_expenses_by_category) > 0)
                        <div style="margin-top:14px; padding-top:12px; border-top:1px solid #f0d5d5;">
                            <div style="font-size:10px; letter-spacing:0.12em; text-transform:uppercase; color:#6c757d; font-weight:700; margin-bottom:8px;">
                                Desglose por categoría
                            </div>
                            <table style="width:100%; font-size:13px;">
                                <tbody>
                                @foreach($weekly_expenses_by_category as $cat)
                                    <tr>
                                        <td style="text-align:left; padding:4px 8px; color:#333;">
                                            {{ $cat->category }}
                                            <span style="color:#999; font-size:11px;">({{ $cat->tx_count }})</span>
                                        </td>
                                        <td style="text-align:right; padding:4px 8px; font-family:'Courier New',monospace; font-variant-numeric:tabular-nums; font-weight:700; color:#c62828;">
                                            <span class="display_currency" data-currency_symbol="true">{{ $cat->total }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if(($undesglosado_cash ?? 0) > 0.01)
        <div class="alert" style="background-color:#fff3cd; border:2px solid #f0ad4e; margin-top:15px; padding:15px;">
            <h4 style="margin-top:0; color:#8a6d3b;"><i class="fas fa-exclamation-triangle"></i> Aviso: hay efectivo sin desglosar</h4>
            <p style="margin-bottom:6px;">
                El <strong>TOTAL EFECTIVO</strong> del reporte (<span class="display_currency" data-currency_symbol="true">{{ $totals['total_cash'] }}</span>)
                es <strong>menor</strong> que el efectivo real registrado en la vista semanal
                (<span class="display_currency" data-currency_symbol="true">{{ $weekly_total_cash }}</span>).
            </p>
            <p style="margin-bottom:6px;">
                Diferencia:
                <strong style="color:#c62828;"><span class="display_currency" data-currency_symbol="true">{{ $undesglosado_cash }}</span></strong>
                — corresponde a pagos en efectivo donde <strong>la cajera no llenó el desglose de billetes</strong> en el modal del POS.
            </p>
            <p style="margin-bottom:0; font-size:12px; color:#8a6d3b;">
                <i class="fas fa-info-circle"></i>
                Este dinero sí entró al cajón y se contabilizó en el corte semanal; solo falta identificar en qué denominaciones vino.
                A partir de ahora el desglose es <strong>obligatorio</strong> en pagos con efectivo — si esto sigue subiendo, es un pago viejo (previo al cambio).
            </p>
        </div>
        @endif
    @endcomponent
</section>

@stop

@section('javascript')
<script type="text/javascript">
$(document).ready(function () {
    $('.sat-only-datepicker').datepicker({
        format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true,
        weekStart: 6, daysOfWeekDisabled: [0,1,2,3,4,5]
    });
});
</script>
@endsection
