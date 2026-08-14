@extends('layouts.app')
@section('title', __('lang_v1.daily_cut') . ' — ' . __('lang_v1.weekly_view'))

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.corte_por_dia')
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">
            @lang('lang_v1.weekly_view')
        </small>
    </h1>
</section>

<section class="content">
    @component('components.filters', ['title' => __('report.filters')])
        {!! Form::open(['url' => route('daily-cuts.weekly'), 'method' => 'get', 'class' => 'form-inline']) !!}
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
                        {!! Form::label('location_id', __('purchase.business_location') . ':*') !!}
                        @php
                            // Prepend "Todas las sucursales (sumadas)" como opción explícita
                            // con value="all". El default sigue siendo "ninguna seleccionada".
                            // Union (+) en vez de Collection::merge(): merge() llama array_merge()
                            // que REINDEXA las claves numéricas (id=6 → 0, id=7 → 1, ...) y rompía
                            // el filtro porque la URL terminaba con location_id=0,1,2 en vez de 6,7,8.
                            $locations_with_all = ['all' => '🏢 Todas las sucursales (sumadas)']
                                + (is_object($locations) ? $locations->toArray() : (array) $locations);
                        @endphp
                        {!! Form::select('location_id', $locations_with_all, $location_id, ['class' => 'form-control select2', 'placeholder' => '— Selecciona una sucursal —', 'required', 'style' => 'width: 100%']) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group" style="margin-top: 25px;">
                        <button type="submit" class="btn btn-primary">@lang('messages.filter')</button>
                        <a href="{{ route('daily-cuts.denominations') }}" class="btn btn-info">
                            <i class="fas fa-table"></i> @lang('lang_v1.denominations_report')
                        </a>
                        <a href="{{ route('daily-cuts.index') }}" class="btn btn-default">
                            <i class="fas fa-list"></i> @lang('lang_v1.daily_cuts_history')
                        </a>
                    </div>
                </div>
            </div>
        {!! Form::close() !!}
    @endcomponent

    @if(empty($location_id))
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-info" style="font-size:16px; padding:24px; text-align:center; border-left: 6px solid #2196f3;">
                    <i class="fas fa-info-circle fa-2x" style="vertical-align:middle; margin-right:12px; color:#2196f3;"></i>
                    <strong>Selecciona una sucursal en el filtro de arriba</strong> y presiona <em>Filtrar</em> para ver su resumen semanal.<br>
                    <small style="color:#555;">Cada sucursal se ve por separado para evitar mezclar totales entre cajas distintas.</small>
                </div>
            </div>
        </div>
    @else

    @php
        $week_end = \Carbon\Carbon::parse($start_date)->addDays(6)->toDateString();
    @endphp

    <div class="row" style="margin-bottom: 15px;">
        <div class="col-md-12 text-right">
            <a href="{{ route('daily-cuts.export-weekly', ['start_date' => $start_date, 'location_id' => $location_id]) }}"
               class="btn btn-success btn-lg" style="box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
                <i class="fas fa-file-excel"></i> @lang('lang_v1.export_this_week')
            </a>
            <a href="{{ route('daily-cuts.export', ['start_date' => $start_date, 'end_date' => $week_end, 'location_id' => $location_id]) }}"
               class="btn btn-default btn-lg" style="box-shadow: 0 2px 6px rgba(0,0,0,0.15);"
               title="{{ __('lang_v1.export_detailed') }}">
                <i class="fas fa-file-excel"></i> @lang('lang_v1.export_detailed')
            </a>
        </div>
    </div>

    <div class="row">
        @php
            $week_total_sales = 0;
            $week_total_cash = 0;
            $week_total_card = 0;
            $week_total_transfer = 0;
            $week_total_cheque = 0;
            $week_total_expenses = 0;
        @endphp
        @foreach($days as $key => $day)
            @php
                $total_dinero = $day['total_cash'] + $day['total_card'] + $day['total_transfer'] + $day['total_cheque'];
                $diff = $total_dinero - $day['total_sales'];
                $week_total_sales += $day['total_sales'];
                $week_total_cash += $day['total_cash'];
                $week_total_card += $day['total_card'];
                $week_total_transfer += $day['total_transfer'];
                $week_total_cheque += $day['total_cheque'];
                $week_total_expenses += $day['total_expenses'];
            @endphp
            <div class="col-md-4 col-sm-6">
                <div class="box box-solid" style="border: 2px solid #ddd;">
                    <div class="box-header" style="background-color: #fff700; color: #000; padding: 8px;">
                        <h4 style="margin: 0; font-weight: bold;">
                            {{ $day['day_name'] }}
                            <small class="pull-right">{{ $day['date']->format('d/m/Y') }}</small>
                        </h4>
                    </div>
                    <div class="box-body" style="padding: 0;">
                        <table class="table table-condensed table-bordered" style="margin: 0;">
                            <tbody>
                                {{-- Categorías --}}
                                @foreach($day['sales_by_brand'] as $row)
                                    <tr>
                                        <td>{{ strtoupper($row['brand']) }}</td>
                                        <td class="text-right">
                                            <span class="display_currency" data-currency_symbol="true">{{ $row['subtotal'] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                                <tr style="background-color: #f0f0f0; font-weight: bold;">
                                    <td>TOTAL</td>
                                    <td class="text-right">
                                        <span class="display_currency" data-currency_symbol="true">{{ $day['total_sales'] }}</span>
                                    </td>
                                </tr>

                                {{-- Métodos de pago --}}
                                <tr>
                                    <td>EFECTIVO</td>
                                    <td class="text-right">
                                        <span class="display_currency" data-currency_symbol="true">{{ $day['total_cash'] }}</span>
                                    </td>
                                </tr>
                                {{-- Conteo manual del cajero: suma total en MXN de billetes que él capturó
                                     en /daily-cuts/denominations. Aparece SIEMPRE (aunque sea $0) cuando
                                     hay datos capturados; el gerente lo cruza con EFECTIVO del sistema. --}}
                                @if($day['vendor_cash_has_data'] ?? false)
                                    <tr style="background-color:#fffbe6;">
                                        <td style="padding-left:18px;"><small><em>↳ Efectivo por el vendedor</em></small></td>
                                        <td class="text-right">
                                            <small>
                                                <span class="display_currency" data-currency_symbol="true">{{ $day['vendor_cash_count'] }}</span>
                                                @php $diff = $day['vendor_cash_count'] - $day['total_cash']; @endphp
                                                @if(abs($diff) >= 0.5)
                                                    <span style="color:{{ $diff > 0 ? '#2e7d32' : '#c62828' }}; font-weight:bold;">
                                                        ({{ $diff > 0 ? '+' : '' }}${{ number_format($diff, 2) }})
                                                    </span>
                                                @endif
                                            </small>
                                        </td>
                                    </tr>
                                @endif
                                <tr style="background-color: #e3f2fd; font-weight: bold;">
                                    <td>TARJETA</td>
                                    <td class="text-right">
                                        <span class="display_currency" data-currency_symbol="true">{{ $day['total_card'] }}</span>
                                    </td>
                                </tr>
                                @foreach($day['card_by_terminal'] as $t)
                                    <tr>
                                        <td style="padding-left: 20px;"><small>↳ {{ strtoupper($t['name']) }}</small></td>
                                        <td class="text-right">
                                            <small><span class="display_currency" data-currency_symbol="true">{{ $t['total'] }}</span></small>
                                        </td>
                                    </tr>
                                @endforeach
                                @php
                                    $terminals_sum = collect($day['card_by_terminal'])->sum('total');
                                    $card_no_terminal = $day['total_card'] - $terminals_sum;
                                @endphp
                                @if($card_no_terminal > 0.01)
                                    <tr>
                                        <td style="padding-left: 20px;"><small>↳ <em>Sin terminal asignada</em></small></td>
                                        <td class="text-right">
                                            <small><span class="display_currency" data-currency_symbol="true">{{ $card_no_terminal }}</span></small>
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <td>TRANSFERENCIAS</td>
                                    <td class="text-right">
                                        <span class="display_currency" data-currency_symbol="true">{{ $day['total_transfer'] }}</span>
                                    </td>
                                </tr>
                                @if($day['total_cheque'] > 0)
                                    <tr>
                                        <td>CHEQUES</td>
                                        <td class="text-right">
                                            <span class="display_currency" data-currency_symbol="true">{{ $day['total_cheque'] }}</span>
                                        </td>
                                    </tr>
                                @endif
                                <tr style="background-color: #f0c000; font-weight: bold;">
                                    <td>TOTAL DINERO</td>
                                    <td class="text-right">
                                        <span class="display_currency" data-currency_symbol="true">{{ $total_dinero }}</span>
                                    </td>
                                </tr>

                                {{-- Diferencia (verificación de caja) --}}
                                <tr style="background-color: {{ $diff < 0 ? '#fcc' : ($diff > 0 ? '#cfc' : '#eee') }};">
                                    <td>@lang('lang_v1.difference')</td>
                                    <td class="text-right">
                                        <span class="display_currency" data-currency_symbol="true">{{ $diff }}</span>
                                    </td>
                                </tr>

                                {{-- Gastos --}}
                                <tr style="background-color: #fcc;">
                                    <td>GASTOS</td>
                                    <td class="text-right">
                                        <span class="display_currency" data-currency_symbol="true">{{ $day['total_expenses'] }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Totales semanales --}}
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-success', 'title' => __('lang_v1.weekly_totals')])
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr class="bg-green">
                            <th>@lang('lang_v1.concept')</th>
                            <th class="text-right">@lang('sale.total')</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>@lang('lang_v1.total_sales')</strong></td>
                            <td class="text-right"><strong><span class="display_currency" data-currency_symbol="true">{{ $week_total_sales }}</span></strong></td>
                        </tr>
                        <tr>
                            <td>@lang('lang_v1.cash')</td>
                            <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $week_total_cash }}</span></td>
                        </tr>
                        <tr>
                            <td>@lang('lang_v1.card')</td>
                            <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $week_total_card }}</span></td>
                        </tr>
                        <tr>
                            <td>@lang('lang_v1.transfer')</td>
                            <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $week_total_transfer }}</span></td>
                        </tr>
                        <tr>
                            <td>@lang('lang_v1.cheque')</td>
                            <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $week_total_cheque }}</span></td>
                        </tr>
                        <tr style="background-color: #fcc;">
                            <td>@lang('expense.expenses')</td>
                            <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $week_total_expenses }}</span></td>
                        </tr>
                        <tr class="bg-yellow">
                            <td><strong>@lang('lang_v1.net_profit')</strong></td>
                            <td class="text-right"><strong><span class="display_currency" data-currency_symbol="true">{{ $week_total_sales - $week_total_expenses }}</span></strong></td>
                        </tr>
                    </tbody>
                </table>
            @endcomponent
        </div>
    </div>
    @endif
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
