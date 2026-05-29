@extends('layouts.app')
@section('title', __('lang_v1.daily_cuts'))

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.daily_cuts')
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">
            @lang('lang_v1.daily_cuts_subtitle')
        </small>
    </h1>
</section>

<section class="content">
    {{-- Estado del corte automático a las 18:00 --}}
    <div class="alert" style="background:{{ !empty($auto_cut_today) ? '#c8e6c9' : '#fff3cd' }}; color:#222; margin-bottom:10px;">
        <i class="fas fa-clock"></i>
        @if(!empty($auto_cut_today))
            <strong>Corte automático generado hoy</strong> a las {{ $auto_cut_today->generated_at->format('H:i') }}.
            Se ejecuta solo el primer acceso al sistema después de las 18:00.
        @else
            <strong>Corte automático programado para las 18:00 hrs.</strong>
            Se generará automáticamente con el primer acceso al sistema a partir de esa hora (sin necesidad de presionar "Generar corte").
        @endif
    </div>

    @component('components.filters', ['title' => __('report.filters')])
        {!! Form::open(['url' => route('daily-cuts.index'), 'method' => 'get', 'class' => 'form-inline']) !!}
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('start_date', __('messages.from') . ':') !!}
                        {!! Form::date('start_date', $start_date, ['class' => 'form-control', 'style' => 'width: 100%']) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('end_date', __('messages.to') . ':') !!}
                        {!! Form::date('end_date', $end_date, ['class' => 'form-control', 'style' => 'width: 100%']) !!}
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
                    </div>
                </div>
            </div>
        {!! Form::close() !!}
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.daily_cuts_history')])
        @slot('tool')
            <div class="box-tools">
                <a href="{{ route('daily-cuts.weekly') }}" class="tw-dw-btn tw-bg-gradient-to-r tw-from-blue-600 tw-to-blue-500 tw-text-white tw-font-bold tw-rounded-full tw-border-none" style="margin-right: 8px;">
                    <i class="fas fa-calendar-week"></i> @lang('lang_v1.weekly_view')
                </a>
                <a href="{{ route('daily-cuts.denominations') }}" class="tw-dw-btn tw-bg-gradient-to-r tw-from-purple-600 tw-to-purple-500 tw-text-white tw-font-bold tw-rounded-full tw-border-none" style="margin-right: 8px;">
                    <i class="fas fa-table"></i> @lang('lang_v1.denominations_report')
                </a>
                <a href="{{ route('daily-cuts.export-weekly', ['start_date' => $start_date, 'location_id' => $location_id]) }}" class="tw-dw-btn tw-bg-gradient-to-r tw-from-emerald-600 tw-to-teal-500 tw-text-white tw-font-bold tw-rounded-full tw-border-none" style="margin-right: 8px;" title="{{ __('lang_v1.export_weekly_by_location') }}">
                    <i class="fas fa-file-excel"></i> @lang('lang_v1.export_weekly_by_location')
                </a>
                <button type="button"
                    class="tw-dw-btn tw-bg-gradient-to-r tw-from-gray-500 tw-to-gray-400 tw-text-white tw-font-bold tw-rounded-full tw-border-none"
                    style="margin-right: 8px; border: none; cursor: pointer;"
                    data-toggle="modal" data-target="#export_detailed_modal"
                    title="{{ __('lang_v1.export_detailed') }}">
                    <i class="fas fa-file-excel"></i> @lang('lang_v1.export_detailed')
                </button>
                {!! Form::open(['url' => route('daily-cuts.generate'), 'method' => 'post', 'style' => 'display:inline-block;']) !!}
                    {!! Form::hidden('date', \Carbon\Carbon::now()->toDateString()) !!}
                    <button type="submit" class="tw-dw-btn tw-bg-gradient-to-r tw-from-green-600 tw-to-green-500 tw-text-white tw-font-bold tw-rounded-full tw-border-none">
                        <i class="fas fa-sync"></i> @lang('lang_v1.generate_now')
                    </button>
                {!! Form::close() !!}
            </div>
        @endslot

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>@lang('messages.date')</th>
                        <th>@lang('purchase.business_location')</th>
                        <th class="text-right">@lang('lang_v1.total_sales')</th>
                        <th class="text-right">@lang('lang_v1.cash')</th>
                        <th class="text-right">@lang('lang_v1.card')</th>
                        <th class="text-right">@lang('lang_v1.transfer')</th>
                        <th class="text-right">@lang('lang_v1.cheque')</th>
                        <th class="text-right">@lang('expense.expenses')</th>
                        <th>@lang('lang_v1.generated_at')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cuts as $cut)
                        <tr>
                            <td><strong>{{ $cut->cut_date->format('d/m/Y') }}</strong></td>
                            <td>{{ $cut->location->name ?? '-' }}</td>
                            <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $cut->total_sales }}</span></td>
                            <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $cut->total_cash }}</span></td>
                            <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $cut->total_card }}</span></td>
                            <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $cut->total_transfer }}</span></td>
                            <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $cut->total_cheque }}</span></td>
                            <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $cut->total_expenses }}</span></td>
                            <td><small>{{ $cut->generated_at ? $cut->generated_at->format('d/m/Y H:i') : '-' }}</small></td>
                            <td>
                                <a href="{{ route('daily-cuts.show', $cut->id) }}" class="btn btn-xs btn-info">
                                    <i class="fas fa-eye"></i> @lang('messages.view')
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center">@lang('lang_v1.no_cuts_found')</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endcomponent
</section>

<!-- Detailed Export Modal -->
<div class="modal fade" id="export_detailed_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #6b7280; color: white;">
                <button type="button" class="close" data-dismiss="modal" style="color: white;">&times;</button>
                <h4 class="modal-title"><i class="fas fa-file-excel"></i> @lang('lang_v1.export_detailed')</h4>
            </div>
            {!! Form::open(['url' => route('daily-cuts.export'), 'method' => 'get', 'id' => 'export_detailed_form']) !!}
                <div class="modal-body">
                    <div class="form-group">
                        {!! Form::label('export_start_date', __('lang_v1.week_start') . ':*') !!}
                        <input type="date" class="form-control" id="export_start_date" name="start_date"
                            value="{{ $start_date }}" required style="font-size: 16px; height: 42px;">
                        <small class="text-muted">@lang('lang_v1.export_week_help')</small>
                    </div>

                    <div class="form-group">
                        {!! Form::label('export_end_date', __('messages.to') . ':*') !!}
                        <input type="date" class="form-control" id="export_end_date" name="end_date"
                            value="{{ $end_date }}" required readonly style="font-size: 16px; height: 42px;">
                        <small class="text-muted">@lang('lang_v1.auto_calculated_end_date')</small>
                    </div>

                    <div class="form-group">
                        {!! Form::label('export_location_id', __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $locations, $location_id, [
                            'class' => 'form-control',
                            'id' => 'export_location_id',
                            'placeholder' => __('lang_v1.all') . ' (todas las sucursales)',
                            'style' => 'font-size: 16px; height: 42px;',
                        ]) !!}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.cancel')</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-download"></i> @lang('lang_v1.download_excel')
                    </button>
                </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>

@stop

@section('javascript')
<script>
    // When the user picks a start date, auto-compute end date = start + 6 days
    $(document).ready(function() {
        $('#export_start_date').on('change', function() {
            var start = new Date($(this).val());
            if (!isNaN(start.getTime())) {
                start.setDate(start.getDate() + 6);
                var yyyy = start.getFullYear();
                var mm = String(start.getMonth() + 1).padStart(2, '0');
                var dd = String(start.getDate()).padStart(2, '0');
                $('#export_end_date').val(yyyy + '-' + mm + '-' + dd);
            }
        });
    });
</script>
@endsection
