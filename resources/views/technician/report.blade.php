@extends('layouts.app')
@section('title', __('lang_v1.technicians_report'))

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.technicians_report')
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">
            @lang('lang_v1.technicians_report_subtitle')
        </small>
    </h1>
</section>

<section class="content">
    @component('components.filters', ['title' => __('report.filters')])
        {!! Form::open(['url' => route('technicians.report'), 'method' => 'get', 'class' => 'form-inline']) !!}
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('start_date', __('lang_v1.week_start') . ':') !!}
                        {!! Form::date('start_date', $start_date, ['class' => 'form-control', 'style' => 'width: 100%']) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $locations, $location_id, ['class' => 'form-control select2', 'placeholder' => __('lang_v1.all'), 'style' => 'width: 100%']) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group" style="margin-top: 25px;">
                        <button type="submit" class="btn btn-primary">@lang('messages.filter')</button>
                        <a href="{{ route('technicians.export-report', ['start_date' => $start_date, 'location_id' => $location_id]) }}" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> @lang('lang_v1.export_to_excel')
                        </a>
                        <a href="{{ route('technicians.index') }}" class="btn btn-default">
                            <i class="fas fa-cog"></i> @lang('lang_v1.manage_technicians')
                        </a>
                    </div>
                </div>
            </div>
        {!! Form::close() !!}
    @endcomponent

    @php
        $day_abbr = [0 => 'DO', 1 => 'LU', 2 => 'MA', 3 => 'MI', 4 => 'JU', 5 => 'VI', 6 => 'SA'];
    @endphp

    @forelse($data as $tech_data)
        @php
            $tech = $tech_data['technician'];
            $week_count = $tech_data['week_count'];
        @endphp
        <div class="row">
            <div class="col-md-12">
                @component('components.widget', [
                    'class' => 'box-primary',
                    'title' => strtoupper($tech->name) . ' — REPARACIONES'
                ])
                    @slot('tool')
                        <div style="font-weight: bold; color: #fff;">
                            <span class="label bg-blue" style="font-size: 14px;">
                                {{ $week_count }} reparaciones
                            </span>
                            <span class="label bg-green" style="font-size: 14px; margin-left: 4px;">
                                Total: <span class="display_currency" data-currency_symbol="true">{{ $tech_data['week_total'] }}</span>
                            </span>
                            @if($tech_data['commission_due'] > 0)
                                <span class="label bg-yellow" style="font-size: 14px; margin-left: 4px;">
                                    Comisión: <span class="display_currency" data-currency_symbol="true">{{ $tech_data['commission_due'] }}</span>
                                </span>
                            @endif
                        </div>
                    @endslot

                    @if($week_count == 0)
                        <p class="text-muted text-center">@lang('lang_v1.no_repairs_this_week')</p>
                    @else
                        @php $show_commission = true; $extra_cols = $show_commission ? 1 : 0; @endphp
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-condensed" style="font-size: 11px;">
                                <thead>
                                    <tr style="background-color: #2196f3; color: white;">
                                        <th>ORDEN</th>
                                        <th>DÍA</th>
                                        <th>NOTA</th>
                                        <th>CLIENTE</th>
                                        <th>TIPO DE REPARACIÓN</th>
                                        <th class="text-right">TOTAL</th>
                                        <th class="text-right">ANTICIPO</th>
                                        <th class="text-right">DEBE</th>
                                        <th class="text-center">TIPO DE PAGO</th>
                                        <th>FECHA ENTRADA</th>
                                        <th>FECHA SALIDA</th>
                                        <th class="text-right">TIPO DE CAMBIO</th>
                                        <th class="text-right">TOTAL EN PESOS</th>
                                        <th>SUCURSAL</th>
                                        <th>VENDEDOR</th>
                                        @if($show_commission)
                                            <th class="text-right">PAGO {{ strtoupper($tech->name) }}</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $order = 1; @endphp
                                    @foreach($tech_data['by_day'] as $day_key => $day_info)
                                        @foreach($day_info['lines'] as $line)
                                            <tr>
                                                <td>{{ $order++ }}</td>
                                                <td style="background-color: #ffeb3b; font-weight: bold; text-align: center;">{{ $day_abbr[$day_info['date']->dayOfWeek] }}</td>
                                                <td>{{ $line['invoice_no'] }}</td>
                                                <td>{{ $line['customer'] }}</td>
                                                <td>{{ $line['product'] }}</td>
                                                <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $line['total'] }}</span></td>
                                                <td class="text-right">
                                                    @if($line['anticipo'] > 0)
                                                        <span class="display_currency" data-currency_symbol="true">{{ $line['anticipo'] }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-right">
                                                    @if($line['debe'] > 0)
                                                        <span class="display_currency" data-currency_symbol="true" style="color:#c0392b;font-weight:bold;">{{ $line['debe'] }}</span>
                                                    @else
                                                        <span class="display_currency" data-currency_symbol="true" style="color:#27ae60;">0</span>
                                                    @endif
                                                </td>
                                                <td class="text-center" style="{{ $line['tipo_pago'] === 'D' ? 'background-color: #c8e6c9; font-weight: bold;' : '' }}">{{ $line['tipo_pago'] }}</td>
                                                <td>{{ !empty($line['entry_date']) ? \Carbon\Carbon::parse($line['entry_date'])->format('d/m/Y') : '—' }}</td>
                                                <td>{{ \Carbon\Carbon::parse($line['transaction_date'])->format('d/m/Y') }}</td>
                                                <td class="text-right">{{ $line['tipo_cambio'] ? number_format($line['tipo_cambio'], 2) : '' }}</td>
                                                <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $line['total_en_pesos'] }}</span></td>
                                                <td>{{ $line['location'] }}</td>
                                                <td>{{ $line['vendor'] ?: '—' }}</td>
                                                @if($show_commission)
                                                    <td class="text-right">{{ number_format($line['commission'] ?? 0, 2) }}</td>
                                                @endif
                                            </tr>
                                        @endforeach
                                        <tr style="background-color: #fff9c4; font-weight: bold;">
                                            <td colspan="5" class="text-right">SUBTOTAL {{ $day_abbr[$day_info['date']->dayOfWeek] }} ({{ $day_info['count'] }} rep.):</td>
                                            <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $day_info['subtotal'] }}</span></td>
                                            <td colspan="{{ 8 + $extra_cols }}"></td>
                                        </tr>
                                    @endforeach
                                    <tr style="background-color: #4caf50; color: white; font-weight: bold;">
                                        <td colspan="5" class="text-right">TOTAL SEMANA ({{ $week_count }} reparaciones):</td>
                                        <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $tech_data['week_total'] }}</span></td>
                                        <td colspan="6"></td>
                                        <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $tech_data['week_total'] }}</span></td>
                                        <td colspan="2"></td>
                                        @if($show_commission)
                                            <td class="text-right">{{ number_format($tech_data['commission_due'], 2) }}</td>
                                        @endif
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endcomponent
            </div>
        </div>
    @empty
        <div class="alert alert-info">
            @lang('lang_v1.no_technicians_yet')
            <a href="{{ route('technicians.index') }}" class="btn btn-sm btn-primary">@lang('lang_v1.manage_technicians')</a>
        </div>
    @endforelse
</section>

@stop
