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
