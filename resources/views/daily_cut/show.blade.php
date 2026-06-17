@extends('layouts.app')
@section('title', __('lang_v1.daily_cut') . ' - ' . $cut->cut_date->format('d/m/Y'))

@section('content')

{{-- Estilos de impresión: en print escondemos navegación/sidebar/botones y mostramos
     solo el contenido del corte. --}}
<style>
@media print {
    .navbar, .main-header, .main-sidebar, .control-sidebar, .left-side, .main-footer,
    .no-print, .btn, nav, header, footer, .sidebar-toggle, #sidebar-menu { display: none !important; }
    .content-wrapper { margin-left: 0 !important; }
    .content-header { padding-top: 0 !important; }
    body { background: #fff !important; font-size: 11px; }
    .info-box { box-shadow: none !important; border: 1px solid #ccc; }
    .box { box-shadow: none !important; border: 1px solid #ccc; }
    table { font-size: 10px; }
    .print-only { display: block !important; }
    a[href]:after { content: none !important; }
}
.print-only { display: none; }
</style>

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('lang_v1.daily_cut'): {{ $cut->cut_date->format('d/m/Y') }}
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">
            {{ $cut->location->name ?? '' }}
        </small>
    </h1>
</section>

{{-- Encabezado solo para impresión --}}
<div class="print-only" style="text-align:center; margin-bottom:15px;">
    <h2 style="margin:0;">CORTE DEL DÍA</h2>
    <div><strong>{{ $cut->location->name ?? '' }}</strong> — {{ $cut->cut_date->format('d/m/Y') }}</div>
    <div style="font-size:10px;">Impreso: {{ now()->format('d/m/Y H:i') }}</div>
</div>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.summary')])
                @slot('tool')
                    <button type="button" class="btn btn-success btn-sm no-print" onclick="window.print()">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                    <a href="{{ route('daily-cuts.index') }}" class="btn btn-default btn-sm no-print">
                        <i class="fas fa-arrow-left"></i> @lang('messages.back')
                    </a>
                @endslot

                @php
                    // BRUTO: lo que físicamente pasó por el cajón (lo que el cajero cuenta al cerrar).
                    // total_cash en BD es NETO (después de restar cambio entregado).
                    $cash_mxn_gross = (float) ($cut->summary['mxn']['subtotal'] ?? 0);
                    $usd_in_mxn_gross = (float) ($cut->summary['usd']['in_mxn'] ?? 0);
                    $cash_change = ($cash_mxn_gross + $usd_in_mxn_gross) - (float) $cut->total_cash;
                @endphp
                <div class="row">
                    <div class="col-md-3">
                        <div class="info-box bg-green">
                            <span class="info-box-icon"><i class="fas fa-cash-register"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">@lang('lang_v1.total_sales')</span>
                                <span class="info-box-number"><span class="display_currency" data-currency_symbol="true">{{ $cut->total_sales }}</span></span>
                                <small>{{ $cut->total_transactions }} @lang('lang_v1.transactions')</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="info-box bg-yellow">
                            <span class="info-box-icon"><i class="fas fa-money-bill-alt"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">@lang('lang_v1.cash') (MXN bruto)</span>
                                <span class="info-box-number"><span class="display_currency" data-currency_symbol="true">{{ $cash_mxn_gross }}</span></span>
                                <small>billetes MXN físicos recibidos</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="info-box" style="background-color:#0288d1; color:#fff;">
                            <span class="info-box-icon"><i class="fas fa-dollar-sign"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">USD (en MXN)</span>
                                <span class="info-box-number"><span class="display_currency" data-currency_symbol="true">{{ $usd_in_mxn_gross }}</span></span>
                                <small>USD ${{ number_format($cut->summary['usd']['subtotal'] ?? 0, 2) }}</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="info-box bg-blue">
                            <span class="info-box-icon"><i class="fas fa-credit-card"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">@lang('lang_v1.card')</span>
                                <span class="info-box-number"><span class="display_currency" data-currency_symbol="true">{{ $cut->total_card }}</span></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    @if($cash_change > 0.01)
                    <div class="col-md-3">
                        <div class="info-box" style="background-color:#9e9e9e; color:#fff;">
                            <span class="info-box-icon"><i class="fas fa-undo"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Cambio entregado</span>
                                <span class="info-box-number">-<span class="display_currency" data-currency_symbol="true">{{ $cash_change }}</span></span>
                                <small>billetes devueltos como vuelto</small>
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="col-md-3">
                        <div class="info-box bg-red">
                            <span class="info-box-icon"><i class="fas fa-receipt"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">@lang('expense.expenses')</span>
                                <span class="info-box-number"><span class="display_currency" data-currency_symbol="true">{{ $cut->total_expenses }}</span></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Reconciliación: confirma que bruto - cambio + tarjeta + transfer + cheque = total ventas --}}
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-info" style="margin-top:10px; padding:10px;">
                            <strong>Reconciliación:</strong>
                            Efectivo bruto <span class="display_currency" data-currency_symbol="true">{{ $cash_mxn_gross + $usd_in_mxn_gross }}</span>
                            − Cambio <span class="display_currency" data-currency_symbol="true">{{ $cash_change }}</span>
                            + Tarjeta <span class="display_currency" data-currency_symbol="true">{{ $cut->total_card }}</span>
                            + Transfer <span class="display_currency" data-currency_symbol="true">{{ $cut->total_transfer }}</span>
                            + Cheque <span class="display_currency" data-currency_symbol="true">{{ $cut->total_cheque }}</span>
                            = <strong><span class="display_currency" data-currency_symbol="true">{{ $cash_mxn_gross + $usd_in_mxn_gross - $cash_change + $cut->total_card + $cut->total_transfer + $cut->total_cheque }}</span></strong>
                            (Total ventas: <span class="display_currency" data-currency_symbol="true">{{ $cut->total_sales }}</span>)
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="info-box bg-gray">
                            <span class="info-box-icon"><i class="fas fa-exchange-alt"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">@lang('lang_v1.transfer')</span>
                                <span class="info-box-number"><span class="display_currency" data-currency_symbol="true">{{ $cut->total_transfer }}</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box bg-gray">
                            <span class="info-box-icon"><i class="fas fa-money-check"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">@lang('lang_v1.cheque')</span>
                                <span class="info-box-number"><span class="display_currency" data-currency_symbol="true">{{ $cut->total_cheque }}</span></span>
                            </div>
                        </div>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <div class="row">
        @php $summary = $cut->summary ?? []; @endphp

        {{-- Sales by brand (Reparaciones, Servicios, Equipos, Accesorios, Desbloqueos) --}}
        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.sales_by_category')])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr class="bg-green">
                                <th>@lang('product.brand')</th>
                                <th class="text-center">@lang('sale.qty')</th>
                                <th class="text-right">@lang('sale.subtotal')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($summary['sales_by_brand'] ?? [] as $row)
                                <tr>
                                    <td>{{ $row['brand'] }}</td>
                                    <td class="text-center">{{ number_format($row['quantity'], 2) }}</td>
                                    <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $row['subtotal'] }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center">@lang('lang_v1.no_data')</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endcomponent
        </div>

        {{-- Card by terminal --}}
        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.payments_by_terminal')])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr class="bg-blue">
                                <th>@lang('lang_v1.card_terminal')</th>
                                <th>@lang('lang_v1.bank')</th>
                                <th class="text-right">@lang('sale.amount')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($summary['card_by_terminal'] ?? [] as $row)
                                <tr>
                                    <td>{{ $row['name'] }}</td>
                                    <td>{{ $row['bank'] ?? '-' }}</td>
                                    <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $row['total'] }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center">@lang('lang_v1.no_data')</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <strong>@lang('lang_v1.card_by_type'):</strong>
                <ul class="list-unstyled" style="margin-top: 8px;">
                    <li>@lang('lang_v1.debit_card'): <strong><span class="display_currency" data-currency_symbol="true">{{ $summary['card_by_type']['debit'] ?? 0 }}</span></strong></li>
                    <li>@lang('lang_v1.credit_card'): <strong><span class="display_currency" data-currency_symbol="true">{{ $summary['card_by_type']['credit'] ?? 0 }}</span></strong></li>
                    <li>@lang('lang_v1.american_express'): <strong><span class="display_currency" data-currency_symbol="true">{{ $summary['card_by_type']['amex'] ?? 0 }}</span></strong></li>
                </ul>
            @endcomponent
        </div>
    </div>

    <div class="row">
        {{-- Cash denominations: MXN --}}
        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.cash_denominations') . ' — PESOS (MXN)'])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr class="bg-yellow">
                                <th class="text-right">@lang('lang_v1.denomination')</th>
                                <th class="text-center">@lang('lang_v1.count')</th>
                                <th class="text-right">@lang('sale.subtotal')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $mxn = $summary['mxn'] ?? ['denominations' => $summary['denominations'] ?? [], 'coins' => $summary['coins_total'] ?? 0];
                                $mxn_denoms = $mxn['denominations'] ?? [];
                                ksort($mxn_denoms, SORT_NUMERIC);
                                $mxn_total = 0;
                            @endphp
                            @foreach($mxn_denoms as $face => $count)
                                @php $sub = floatval($face) * intval($count); $mxn_total += $sub; @endphp
                                <tr>
                                    <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $face }}</span></td>
                                    <td class="text-center">{{ $count }}</td>
                                    <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $sub }}</span></td>
                                </tr>
                            @endforeach
                            @if(!empty($mxn['coins']))
                                @php $mxn_total += $mxn['coins']; @endphp
                                <tr><td class="text-right">@lang('lang_v1.coins')</td><td class="text-center">-</td><td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $mxn['coins'] }}</span></td></tr>
                            @endif
                            <tr class="bg-green"><th colspan="2" class="text-right">Subtotal MXN:</th><th class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $mxn_total }}</span></th></tr>
                            @if(empty($mxn_denoms) && empty($mxn['coins']))
                                <tr><td colspan="3" class="text-center">@lang('lang_v1.no_data')</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            @endcomponent
        </div>

        {{-- Cash denominations: USD --}}
        <div class="col-md-6">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.cash_denominations') . ' — DÓLARES (USD)'])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr class="bg-blue">
                                <th class="text-right">@lang('lang_v1.denomination')</th>
                                <th class="text-center">@lang('lang_v1.count')</th>
                                <th class="text-right">@lang('sale.subtotal')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $usd = $summary['usd'] ?? ['denominations' => [], 'coins' => 0, 'in_mxn' => 0];
                                $usd_denoms = $usd['denominations'] ?? [];
                                ksort($usd_denoms, SORT_NUMERIC);
                                $usd_total = 0;
                            @endphp
                            @foreach($usd_denoms as $face => $count)
                                @php $sub = floatval($face) * intval($count); $usd_total += $sub; @endphp
                                <tr>
                                    <td class="text-right">${{ $face }}</td>
                                    <td class="text-center">{{ $count }}</td>
                                    <td class="text-right">${{ number_format($sub, 2) }}</td>
                                </tr>
                            @endforeach
                            @if(!empty($usd['coins']))
                                @php $usd_total += $usd['coins']; @endphp
                                <tr><td class="text-right">@lang('lang_v1.coins')</td><td class="text-center">-</td><td class="text-right">${{ number_format($usd['coins'], 2) }}</td></tr>
                            @endif
                            <tr><th colspan="2" class="text-right">Subtotal USD:</th><th class="text-right">${{ number_format($usd_total, 2) }}</th></tr>
                            @if(!empty($usd['in_mxn']))
                                <tr class="bg-green"><th colspan="2" class="text-right">@lang('lang_v1.equivalent_in') MXN:</th><th class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $usd['in_mxn'] }}</span></th></tr>
                            @endif
                            @if(empty($usd_denoms) && empty($usd['coins']))
                                <tr><td colspan="3" class="text-center">@lang('lang_v1.no_data')</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>

    {{-- Lista detallada de todas las ventas del día --}}
    @if(isset($sales) && count($sales) > 0)
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => 'Listado de ventas del día (' . count($sales) . ')'])
                <div class="table-responsive">
                    <table class="table table-bordered table-condensed table-striped">
                        <thead>
                            <tr style="background-color:#f5f5f5;">
                                <th class="text-center" style="width:30px;">#</th>
                                <th>Hora</th>
                                <th>Factura</th>
                                <th>Cliente</th>
                                <th>Vendedor</th>
                                <th>Pago</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $running_total = 0;
                                $method_labels = [
                                    'cash' => 'Efectivo',
                                    'card' => 'Tarjeta',
                                    'bank_transfer' => 'Transferencia',
                                    'cheque' => 'Cheque',
                                    'other' => 'Otro',
                                ];
                            @endphp
                            @foreach($sales as $i => $sale)
                                @php
                                    $running_total += (float) $sale->final_total;
                                    $methods = $payments_by_tx[$sale->id] ?? [];
                                    $methods_labels = collect($methods)->unique()->map(function($m) use ($method_labels) {
                                        return $method_labels[$m] ?? ucfirst($m);
                                    })->implode(', ');
                                @endphp
                                <tr>
                                    <td class="text-center">{{ $i + 1 }}</td>
                                    <td><small>{{ \Carbon\Carbon::parse($sale->transaction_date)->format('H:i') }}</small></td>
                                    <td><a href="{{ url('/sells/' . $sale->id) }}" target="_blank" class="no-print">{{ $sale->invoice_no }}</a><span class="print-only">{{ $sale->invoice_no }}</span></td>
                                    <td>{{ $sale->customer_name }}</td>
                                    <td><small>{{ trim($sale->vendedor) ?: '-' }}</small></td>
                                    <td><small>{{ $methods_labels ?: '-' }}</small></td>
                                    <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $sale->final_total }}</span></td>
                                </tr>
                            @endforeach
                            <tr class="bg-green">
                                <th colspan="6" class="text-right">TOTAL DE VENTAS:</th>
                                <th class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $running_total }}</span></th>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-md-12">
            <p class="text-muted">
                <small>
                    <i class="fas fa-info-circle"></i>
                    @lang('lang_v1.generated_at'): {{ $cut->generated_at ? $cut->generated_at->format('d/m/Y H:i') : '-' }}
                    @if($cut->generatedBy)
                        — @lang('lang_v1.by'): {{ $cut->generatedBy->first_name }} {{ $cut->generatedBy->last_name }}
                    @endif
                </small>
            </p>
        </div>
    </div>
</section>

@stop
