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
        @else
            <strong>Corte automático a las 18:00 hrs.</strong>
        @endif
        Si tu sucursal cierra antes (ej. sábados 15:00), elige tu sucursal en el dropdown de arriba
        y presiona <strong>Generar</strong> — el corte queda cerrado de inmediato y el heartbeat de
        las 18:00 lo respeta.
    </div>

    @component('components.filters', ['title' => __('report.filters')])
        {!! Form::open(['url' => route('daily-cuts.index'), 'method' => 'get', 'class' => 'form-inline']) !!}
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('start_date', __('messages.from') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                            {!! Form::text('start_date', $start_date, ['class' => 'form-control free-datepicker', 'id' => 'start_date', 'readonly', 'autocomplete' => 'off', 'style' => 'width: 100%; background-color: #fff;']) !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('end_date', __('messages.to') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                            {!! Form::text('end_date', $end_date, ['class' => 'form-control free-datepicker', 'id' => 'end_date', 'readonly', 'autocomplete' => 'off', 'style' => 'width: 100%; background-color: #fff;']) !!}
                        </div>
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
                {{-- Dropdown + botón Generar.
                     • Si eligen "Todas" → refresca todas (sin cerrar). Útil para ver datos en vivo.
                     • Si eligen una sucursal → genera Y CIERRA esa sucursal (acción del cajero al terminar). --}}
                {!! Form::open(['url' => route('daily-cuts.generate'), 'method' => 'post', 'style' => 'display:inline-block;', 'id' => 'generate_form',
                    'onsubmit' => "var sel=document.getElementById('generate_location_id'); if(sel.value && sel.value!=='all'){ return confirm('Esto VA A GENERAR Y CERRAR el corte de la sucursal seleccionada. Una vez cerrado el corte queda fijo (no se actualiza con ventas posteriores). ¿Continuar?'); } return true;"]) !!}
                    {!! Form::hidden('date', \Carbon\Carbon::now()->toDateString()) !!}
                    <select name="location_id" id="generate_location_id" class="form-control" style="display:inline-block; width:auto; margin-right:6px; vertical-align:middle; height:42px; font-size:14px;">
                        <option value="all">🏢 Todas (solo refrescar)</option>
                        @foreach($locations as $id => $name)
                            <option value="{{ $id }}">🔒 {{ $name }} (generar y cerrar)</option>
                        @endforeach
                    </select>
                    <button type="submit" id="generate_btn"
                        style="background: linear-gradient(to right, #16a34a, #22c55e); color: white; font-weight: bold; border: none; padding: 10px 24px; border-radius: 9999px; font-size: 14px; cursor: pointer; box-shadow: 0 2px 6px rgba(34,197,94,0.4);">
                        <i class="fas fa-sync"></i> Generar
                    </button>
                {!! Form::close() !!}
                @can('business_settings.access')
                    {!! Form::open(['url' => route('daily-cuts.regenerate-historical'), 'method' => 'post', 'style' => 'display:inline-block;', 'onsubmit' => "return confirm('Esto regenerará TODOS los cortes históricos del negocio con las reglas actuales (apartados activos ya NO se cuentan). Puede tardar varios minutos. ¿Continuar?');"]) !!}
                    <button type="submit" class="tw-dw-btn tw-bg-amber-600 hover:tw-bg-amber-700 tw-text-white tw-font-bold tw-rounded-full tw-border-none" title="Recalcular cortes históricos con las reglas actuales (apartados activos excluidos)">
                        <i class="fas fa-history"></i> Regenerar histórico
                    </button>
                    {!! Form::close() !!}
                @endcan
            </div>
        @endslot

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>@lang('messages.date')</th>
                        <th>@lang('purchase.business_location')</th>
                        <th>Estado</th>
                        <th class="text-right">@lang('lang_v1.total_sales')</th>
                        <th class="text-right">@lang('lang_v1.cash')</th>
                        <th class="text-right" title="USD convertido a MXN">USD (MXN)</th>
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
                        <tr style="{{ $cut->closed_at ? 'background-color:#f5f5f5;' : '' }}">
                            <td><strong>{{ $cut->cut_date->format('d/m/Y') }}</strong></td>
                            <td>{{ $cut->location->name ?? '-' }}</td>
                            <td>
                                @if($cut->closed_at)
                                    <span class="label label-default" title="Cerrado el {{ $cut->closed_at->format('d/m/Y H:i') }}">
                                        <i class="fas fa-lock"></i> CERRADO
                                    </span>
                                @else
                                    <span class="label label-success" title="El corte se sigue actualizando con cada acceso. Hasta que se cierre.">
                                        <i class="fas fa-clock"></i> EN CURSO
                                    </span>
                                @endif
                            </td>
                            @php
                                // BRUTO: cuántos billetes FÍSICAMENTE pasaron por el cajón.
                                // Es lo que el cajero cuenta al cerrar (sin restar cambio devuelto).
                                // total_cash en BD = neto (recibido - cambio); para mostrar bruto
                                // que coincida con conteo físico, sumamos los denominations.
                                $cash_mxn_gross = (float) ($cut->summary['mxn']['subtotal'] ?? 0);
                                $usd_in_mxn_gross = (float) ($cut->summary['usd']['in_mxn'] ?? 0);
                                // Cambio entregado = bruto - neto. Para reconciliar con Total ventas.
                                $cash_change = ($cash_mxn_gross + $usd_in_mxn_gross) - (float) $cut->total_cash;
                            @endphp
                            <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $cut->total_sales }}</span></td>
                            <td class="text-right" title="Billetes MXN recibidos (bruto, sin restar cambio)"><span class="display_currency" data-currency_symbol="true">{{ $cash_mxn_gross }}</span></td>
                            <td class="text-right" style="background-color:#e3f2fd;" title="Billetes USD recibidos convertidos a MXN"><span class="display_currency" data-currency_symbol="true">{{ $usd_in_mxn_gross }}</span></td>
                            <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $cut->total_card }}</span></td>
                            <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $cut->total_transfer }}</span></td>
                            <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $cut->total_cheque }}</span></td>
                            <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $cut->total_expenses }}</span></td>
                            <td><small>{{ $cut->generated_at ? $cut->generated_at->format('d/m/Y H:i') : '-' }}</small></td>
                            <td style="white-space:nowrap;">
                                <a href="{{ route('daily-cuts.show', $cut->id) }}" class="btn btn-xs btn-info">
                                    <i class="fas fa-eye"></i> @lang('messages.view')
                                </a>
                                {{-- Botones de Cerrar caja / Reabrir / Generar corte se consolidaron en el
                                     dropdown de arriba: el cajero elige su sucursal y presiona Generar →
                                     el sistema genera y cierra automáticamente. Sólo admin tiene acceso
                                     al endpoint reopen vía URL directa por si hay que reabrir un corte. --}}
                                @if($cut->closed_at)
                                    @can('business_settings.access')
                                        <form method="POST" action="{{ route('daily-cuts.reopen', $cut->id) }}" style="display:inline-block;"
                                            onsubmit="return confirm('¿Reabrir este corte? Volverá a estar mutable y podría cambiar con ventas posteriores.');">
                                            @csrf
                                            <button type="submit" class="btn btn-xs btn-default" title="Solo admin. Vuelve a hacer el corte mutable.">
                                                <i class="fas fa-lock-open"></i> Reabrir
                                            </button>
                                        </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center">@lang('lang_v1.no_cuts_found')</td>
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
                        <input type="text" class="form-control free-datepicker" id="export_start_date" name="start_date"
                            value="{{ $start_date }}" required readonly autocomplete="off" style="font-size: 16px; height: 42px; background-color:#fff;">
                        <small class="text-muted">@lang('lang_v1.export_week_help')</small>
                    </div>

                    <div class="form-group">
                        {!! Form::label('export_end_date', __('messages.to') . ':*') !!}
                        <input type="text" class="form-control free-datepicker" id="export_end_date" name="end_date"
                            value="{{ $end_date }}" required readonly autocomplete="off" style="font-size: 16px; height: 42px; background-color:#fff;">
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
    $(document).ready(function() {
        // Datepicker libre (semana inicia en sábado para visualización)
        $('.free-datepicker').datepicker({
            format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true, weekStart: 6
        });

        // When the user picks a start date, auto-compute end date = start + 6 days
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
