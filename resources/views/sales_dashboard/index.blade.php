@extends('layouts.app')
@section('title', __('lang_v1.sales_dashboard'))

@section('content')

@php
    $always_show = ['HIDROGEL', 'CORTOS'];
    $chart_day_labels = [];
    $chart_day_qty = [];
    $chart_day_amount = [];
    foreach ($days as $d) {
        $chart_day_labels[] = $d['label'] . ' ' . $d['short'];
        $chart_day_qty[] = (int) ($eq_by_day[$d['key']]['qty'] ?? 0);
        $chart_day_amount[] = round($eq_by_day[$d['key']]['amount'] ?? 0, 2);
    }
    $chart_vendor_labels = [];
    $chart_vendor_qty = [];
    foreach ($eq_vendors as $v) {
        $tot = 0;
        foreach ($days as $d) { $tot += $eq_matrix[$v][$d['key']] ?? 0; }
        $chart_vendor_labels[] = $v;
        $chart_vendor_qty[] = (int) $tot;
    }
    $cat_labels = []; $cat_amount = []; $cat_qty = [];
    foreach ($buckets as $bname => $bd) {
        if ($bd['qty'] > 0 || $bd['amount'] > 0) {
            $cat_labels[] = $bname; $cat_amount[] = round($bd['amount'], 2); $cat_qty[] = (int) $bd['qty'];
        }
    }
    $allCatVendors = [];
    foreach ($buckets as $bd) { foreach ($bd['vendors'] as $vn => $x) { $allCatVendors[$vn] = true; } }
    $allCatVendors = array_keys($allCatVendors);
@endphp

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.sales_dashboard')
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">
            {{ $start->format('d/m/Y') }} — {{ $end->format('d/m/Y') }}
        </small>
    </h1>
</section>

<section class="content">

    {{-- FILTROS --}}
    @component('components.widget', ['class' => 'box-solid'])
        {!! Form::open(['url' => route('sales-dashboard.index'), 'method' => 'get', 'class' => 'form-inline']) !!}
        <div class="form-group">
            {!! Form::label('start_date', 'Inicio de semana (sábado):') !!}
            <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                {!! Form::text('start_date', $start_date, ['class' => 'form-control sat-only-datepicker', 'id' => 'start_date', 'readonly', 'autocomplete' => 'off', 'style' => 'background-color:#fff;']) !!}
            </div>
        </div>
        <div class="form-group" style="margin-left:10px;">
            {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
            {!! Form::select('location_id', $locations, $location_id, ['class' => 'form-control select2', 'placeholder' => __('lang_v1.all'), 'style' => 'min-width:220px;']) !!}
        </div>
        <button type="submit" class="btn btn-primary" style="margin-left:10px;">@lang('messages.filter')</button>
        <a href="{{ route('sales-dashboard.export', ['start_date' => $start_date, 'location_id' => $location_id]) }}"
           class="btn btn-success" style="margin-left:6px;">
            <i class="fas fa-file-excel"></i> Exportar a Excel
        </a>
        {!! Form::close() !!}
    @endcomponent

    {{-- KPIs EQUIPOS --}}
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="info-box bg-aqua"><span class="info-box-icon"><i class="fas fa-mobile-alt"></i></span>
                <div class="info-box-content"><span class="info-box-text">@lang('lang_v1.equipos_sold')</span>
                    <span class="info-box-number">{{ (int) $eq_total_qty }}</span></div></div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box bg-green"><span class="info-box-icon"><i class="fas fa-dollar-sign"></i></span>
                <div class="info-box-content"><span class="info-box-text">@lang('lang_v1.equipos_amount')</span>
                    <span class="info-box-number">${{ number_format($eq_total_amount, 2) }}</span></div></div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box bg-blue"><span class="info-box-icon"><i class="fas fa-bullseye"></i></span>
                <div class="info-box-content"><span class="info-box-text">@lang('lang_v1.weekly_goal')</span>
                    <span class="info-box-number">{{ (int) $meta_qty }}</span></div></div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box {{ $faltan > 0 ? 'bg-red' : 'bg-green' }}"><span class="info-box-icon"><i class="fas fa-flag-checkered"></i></span>
                <div class="info-box-content"><span class="info-box-text">{{ $faltan > 0 ? __('lang_v1.missing') : __('lang_v1.goal_reached') }}</span>
                    <span class="info-box-number">{{ $faltan > 0 ? (int) $faltan : '✓' }}</span></div></div>
        </div>
    </div>

    {{-- Editar meta --}}
    @component('components.widget', ['class' => 'box-default'])
        {!! Form::open(['url' => route('sales-dashboard.save-goal'), 'method' => 'post', 'class' => 'form-inline']) !!}
        {!! Form::hidden('goal_location_id', $goal_loc) !!}
        <div class="form-group">
            {!! Form::label('target_qty', __('lang_v1.set_weekly_goal') . ' (' . ($goal_loc == 0 ? __('lang_v1.all') : __('purchase.business_location')) . '):') !!}
            {!! Form::number('target_qty', $meta_qty, ['class' => 'form-control', 'min' => 0, 'style' => 'width:120px;']) !!}
        </div>
        <button type="submit" class="btn btn-default" style="margin-left:8px;">@lang('messages.save')</button>
        {!! Form::close() !!}
    @endcomponent

    {{-- EQUIPOS POR DÍA --}}
    <div class="row">
        <div class="col-md-5">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.equipos_by_day')])
                <table class="table table-bordered table-striped" style="font-size:13px;">
                    <thead><tr class="bg-light-blue"><th>@lang('messages.date')</th><th class="text-center">@lang('lang_v1.equipos')</th><th class="text-right">TOTAL $</th></tr></thead>
                    <tbody>
                        @foreach($days as $d)
                            <tr><td>{{ $d['label'] }} {{ $d['short'] }}</td>
                                <td class="text-center">{{ (int) ($eq_by_day[$d['key']]['qty'] ?? 0) }}</td>
                                <td class="text-right">${{ number_format($eq_by_day[$d['key']]['amount'] ?? 0, 2) }}</td></tr>
                        @endforeach
                        <tr style="font-weight:bold; background:#d9edf7;"><td>TOTAL</td>
                            <td class="text-center">{{ (int) $eq_total_qty }}</td>
                            <td class="text-right">${{ number_format($eq_total_amount, 2) }}</td></tr>
                    </tbody>
                </table>
            @endcomponent
        </div>
        <div class="col-md-7">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.equipos_by_day')])
                <canvas id="chartDay" height="120"></canvas>
            @endcomponent
        </div>
    </div>

    {{-- EQUIPOS POR VENDEDOR --}}
    <div class="row">
        <div class="col-md-7">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.equipos_by_vendor')])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" style="font-size:12px;">
                        <thead><tr class="bg-purple"><th>@lang('messages.date')</th>
                            @foreach($eq_vendors as $v)<th class="text-center">{{ $v }}</th>@endforeach
                            <th class="text-center">TOTAL</th></tr></thead>
                        <tbody>
                            @foreach($days as $d)
                                @php $rowtot = 0; @endphp
                                <tr><td>{{ $d['label'] }}</td>
                                    @foreach($eq_vendors as $v)
                                        @php $c = (int) ($eq_matrix[$v][$d['key']] ?? 0); $rowtot += $c; @endphp
                                        <td class="text-center">{{ $c ?: '' }}</td>
                                    @endforeach
                                    <td class="text-center" style="font-weight:bold;">{{ $rowtot ?: '' }}</td></tr>
                            @endforeach
                            <tr style="font-weight:bold; background:#e1bee7;"><td>TOTALES</td>
                                @foreach($eq_vendors as $v)
                                    @php $ct = 0; foreach($days as $d){ $ct += $eq_matrix[$v][$d['key']] ?? 0; } @endphp
                                    <td class="text-center">{{ (int) $ct }}</td>
                                @endforeach
                                <td class="text-center">{{ (int) $eq_total_qty }}</td></tr>
                        </tbody>
                    </table>
                </div>
            @endcomponent
        </div>
        <div class="col-md-5">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.equipos_by_vendor')])
                <canvas id="chartVendor" height="160"></canvas>
            @endcomponent
        </div>
    </div>

    {{-- RESUMEN POR CATEGORÍA --}}
    <div class="row">
        <div class="col-md-5">
            @component('components.widget', ['class' => 'box-success', 'title' => __('lang_v1.summary_by_category')])
                <table class="table table-bordered table-striped" style="font-size:13px;">
                    <thead><tr class="bg-green"><th>@lang('lang_v1.category')</th><th class="text-center">@lang('sale.qty')</th><th class="text-right">TOTAL $</th></tr></thead>
                    <tbody>
                        @php $g_qty = 0; $g_amt = 0; @endphp
                        @foreach($buckets as $bname => $bd)
                            @if($bd['qty'] > 0 || $bd['amount'] > 0 || in_array($bname, $always_show))
                                @php $g_qty += $bd['qty']; $g_amt += $bd['amount']; @endphp
                                <tr><td><strong>{{ $bname }}</strong></td>
                                    <td class="text-center">{{ (int) $bd['qty'] }}</td>
                                    <td class="text-right">${{ number_format($bd['amount'], 2) }}</td></tr>
                            @endif
                        @endforeach
                        <tr style="font-weight:bold; background:#dff0d8;"><td>TOTAL</td>
                            <td class="text-center">{{ (int) $g_qty }}</td>
                            <td class="text-right">${{ number_format($g_amt, 2) }}</td></tr>
                    </tbody>
                </table>
            @endcomponent
        </div>
        <div class="col-md-7">
            @component('components.widget', ['class' => 'box-success', 'title' => __('lang_v1.summary_by_category')])
                <canvas id="chartCat" height="160"></canvas>
            @endcomponent
        </div>
    </div>

    {{-- CATEGORÍAS POR VENDEDOR --}}
    @component('components.widget', ['class' => 'box-warning', 'title' => __('lang_v1.categories_by_vendor')])
        <div class="table-responsive">
            <table class="table table-bordered table-striped" style="font-size:12px;">
                <thead><tr class="bg-yellow"><th>@lang('lang_v1.category')</th>
                    @foreach($allCatVendors as $v)<th class="text-center">{{ $v }}</th>@endforeach
                    <th class="text-center">TOTAL</th></tr></thead>
                <tbody>
                    @foreach($buckets as $bname => $bd)
                        @if($bd['qty'] > 0 || in_array($bname, $always_show))
                            <tr><td><strong>{{ $bname }}</strong></td>
                                @foreach($allCatVendors as $v)
                                    <td class="text-center">{{ isset($bd['vendors'][$v]) ? (int) $bd['vendors'][$v]['qty'] : '' }}</td>
                                @endforeach
                                <td class="text-center" style="font-weight:bold;">{{ (int) $bd['qty'] }}</td></tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
        <small class="text-muted">@lang('lang_v1.qty_units_note')</small>
    @endcomponent

    {{-- DETALLE DE EQUIPOS VENDIDOS --}}
    @component('components.widget', ['class' => 'box-info', 'title' => __('lang_v1.equipos_detail')])
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="equipos_detail_table" style="font-size:12px;">
                <thead><tr class="bg-aqua">
                    <th>#</th><th>@lang('user.name')</th><th>@lang('sale.product')</th><th>SKU/IMEI</th>
                    <th class="text-right">@lang('lang_v1.cost')</th><th>@lang('lang_v1.pay_type')</th>
                    <th>@lang('messages.date')</th><th class="text-right">TOTAL</th>
                </tr></thead>
                <tbody>
                    @forelse($detail as $row)
                        <tr><td>{{ $row['order'] }}</td><td>{{ $row['vendor'] }}</td>
                            <td>{{ $row['product'] }}</td><td>{{ $row['sku'] }}</td>
                            <td class="text-right">${{ number_format($row['cost'], 2) }}</td>
                            <td>{{ strtoupper(substr($row['pay_method'] ?? '', 0, 1)) }}</td>
                            <td>{{ $row['date'] }}</td>
                            <td class="text-right">${{ number_format($row['amount'], 2) }}</td></tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">@lang('lang_v1.no_equipos_in_period')</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endcomponent

</section>

@stop

@section('javascript')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        $('.sat-only-datepicker').datepicker({
            format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true,
            weekStart: 6, daysOfWeekDisabled: [0,1,2,3,4,5]
        });
        if ($.fn.DataTable) {
            $('#equipos_detail_table').DataTable({ pageLength: 25, order: [] });
        }

        @if(session('status'))
            @if(session('status')['success']) toastr.success("{{ session('status')['msg'] }}"); @endif
        @endif

        var palette = ['#36A2EB','#FF6384','#FFCE56','#4BC0C0','#9966FF','#FF9F40','#8BC34A','#E91E63','#00ACC1','#FF7043'];

        if (window.Chart) {
            new Chart(document.getElementById('chartDay'), {
                type: 'bar',
                data: { labels: @json($chart_day_labels),
                    datasets: [{ label: 'Equipos', data: @json($chart_day_qty), backgroundColor: '#36A2EB' }] },
                options: { responsive: true, plugins: { legend: { display: false } } }
            });

            new Chart(document.getElementById('chartVendor'), {
                type: 'bar',
                data: { labels: @json($chart_vendor_labels),
                    datasets: [{ label: 'Equipos', data: @json($chart_vendor_qty), backgroundColor: '#9966FF' }] },
                options: { indexAxis: 'y', responsive: true, plugins: { legend: { display: false } } }
            });

            new Chart(document.getElementById('chartCat'), {
                type: 'doughnut',
                data: { labels: @json($cat_labels),
                    datasets: [{ data: @json($cat_amount), backgroundColor: palette }] },
                options: { responsive: true, plugins: { legend: { position: 'right' } } }
            });
        }
    });
</script>
@endsection
