<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@lang('layaway::lang.layaway') #{{ $layaway->layaway_number }} - @lang('layaway::lang.receipt')</title>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">

    <style type="text/css">
        body {
            font-family: Arial, sans-serif;
            color: #000000;
        }
        .receipt-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
        }
        .table-slim {
            font-size: 13px;
        }
        .table-slim th,
        .table-slim td {
            padding: 5px;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-left {
            text-align: left;
        }
        .pull-left {
            float: left;
        }
        .pull-right {
            float: right;
        }
        .word-wrap {
            word-wrap: break-word;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending { background-color: #f0ad4e; color: white; }
        .status-active { background-color: #5cb85c; color: white; }
        .status-completed { background-color: #0275d8; color: white; }
        .status-cancelled { background-color: #d9534f; color: white; }
        .status-expired { background-color: #636c72; color: white; }

        @media print {
            .receipt-container {
                margin: 0;
                padding: 10px;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Business Information -->
        <div class="row" style="color: #000000 !important;">
            <div class="col-xs-12 text-center">
                <h2 class="text-center">
                    {{ $business->name }}
                </h2>

                @if(!empty($business->city) || !empty($business->state) || !empty($business->country))
                    <p>
                        <small class="text-center">
                            @if(!empty($business->landmark))
                                {{ $business->landmark }}<br>
                            @endif
                            @if(!empty($business->city))
                                {{ $business->city }}@if(!empty($business->state)),@endif
                            @endif
                            @if(!empty($business->state))
                                {{ $business->state }}
                            @endif
                            @if(!empty($business->zip_code))
                                {{ $business->zip_code }}
                            @endif
                            @if(!empty($business->country))
                                <br>{{ $business->country }}
                            @endif
                        </small>
                    </p>
                @endif

                @if(!empty($business->mobile) || !empty($business->alternate_number))
                    <p>
                        @if(!empty($business->mobile))
                            {{ $business->mobile }}
                        @endif
                        @if(!empty($business->mobile) && !empty($business->alternate_number))
                            ,
                        @endif
                        @if(!empty($business->alternate_number))
                            {{ $business->alternate_number }}
                        @endif
                    </p>
                @endif

                @if(!empty($business->email))
                    <p>{{ $business->email }}</p>
                @endif

                <!-- Receipt Title -->
                <h3 class="text-center">
                    @lang('layaway::lang.layaway') @lang('layaway::lang.receipt')
                </h3>
            </div>
        </div>

        <!-- Layaway Details Header -->
        <div class="row" style="color: #000000 !important;">
            <div class="col-xs-12 text-center">
                <p style="width: 100% !important" class="word-wrap">
                    <span class="pull-left text-left word-wrap">
                        <b>@lang('layaway::lang.layaway_number'):</b> {{ $layaway->layaway_number }}
                        <br>
                        <b>@lang('layaway::lang.status'):</b>
                        <span class="status-badge status-{{ $layaway->status }}">
                            {{ ucfirst($layaway->status) }}
                        </span>

                        @if(!empty($layaway->contact))
                            <br><br>
                            <b>@lang('contact.customer'):</b><br>
                            {{ $layaway->contact->name ?? '' }}
                            @if(!empty($layaway->contact->mobile))
                                <br>{{ $layaway->contact->mobile }}
                            @endif
                            @if(!empty($layaway->contact->email))
                                <br>{{ $layaway->contact->email }}
                            @endif
                        @endif
                    </span>

                    <span class="pull-right text-left">
                        <b>@lang('layaway::lang.created_at'):</b> {{ @format_date($layaway->created_at) }}
                        <br>
                        <b>@lang('layaway::lang.payment_deadline'):</b> {{ @format_date($layaway->payment_deadline) }}

                        @if(!empty($layaway->location))
                            <br><br>
                            <b>@lang('business.location'):</b><br>
                            {{ $layaway->location->name ?? '' }}
                            @if(!empty($layaway->location->landmark))
                                <br>{{ $layaway->location->landmark }}
                            @endif
                        @endif

                        @if(!empty($layaway->createdBy))
                            <br><br>
                            <b>@lang('layaway::lang.created_by'):</b><br>
                            {{ $layaway->createdBy->first_name ?? '' }} {{ $layaway->createdBy->last_name ?? '' }}
                        @endif
                    </span>
                </p>
            </div>
        </div>

        <!-- Line Items -->
        <div class="row" style="color: #000000 !important;">
            <div class="col-xs-12">
                <br/>
                <table class="table table-responsive table-slim table-bordered">
                    <thead>
                        <tr>
                            <th width="45%">@lang('sale.product')</th>
                            <th class="text-center" width="15%">@lang('sale.qty')</th>
                            <th class="text-right" width="20%">@lang('sale.unit_price')</th>
                            <th class="text-right" width="20%">@lang('sale.subtotal')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($layaway->items && $layaway->items->count() > 0)
                            @foreach($layaway->items as $item)
                                <tr>
                                    <td>
                                        {{ $item->product->name ?? 'N/A' }}
                                        @if(!empty($item->variation) && !empty($item->variation->name) && $item->variation->name != 'DUMMY' && $item->variation->is_dummy != 1)
                                            <br><small>{{ $item->variation->name }}</small>
                                        @endif
                                        @if(!empty($item->product->sku))
                                            <br><small>SKU: {{ $item->product->sku }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        {{ $item->quantity }}
                                    </td>
                                    <td class="text-right">
                                        {{ number_format($item->unit_price, 2) }}
                                    </td>
                                    <td class="text-right">
                                        {{ number_format($item->total_price, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="4" class="text-center">@lang('lang_v1.no_products')</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payment Summary -->
        <div class="row" style="color: #000000 !important;">
            <div class="col-md-12"><hr/></div>

            <!-- Payment History (Left Column) -->
            <div class="col-xs-6">
                <h4>@lang('layaway::lang.payment_history')</h4>
                <table class="table table-slim table-bordered">
                    <thead>
                        <tr>
                            <th>@lang('messages.date')</th>
                            <th>@lang('sale.payment_method')</th>
                            <th class="text-right">@lang('sale.amount')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($layaway->payments && $layaway->payments->count() > 0)
                            @foreach($layaway->payments as $payment)
                                <tr>
                                    <td>{{ @format_date($payment->payment_date) }}</td>
                                    <td>{{ $payment->payment_method ?? 'N/A' }}</td>
                                    <td class="text-right">{{ number_format($payment->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="3" class="text-center">@lang('layaway::lang.no_payments_yet')</td>
                            </tr>
                        @endif
                    </tbody>
                    @if($layaway->payments && $layaway->payments->count() > 0)
                        <tfoot>
                            <tr>
                                <th colspan="2">@lang('sale.total_paid'):</th>
                                <th class="text-right">{{ number_format($layaway->total_paid, 2) }}</th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <!-- Financial Summary (Right Column) -->
            <div class="col-xs-6">
                <div class="table-responsive">
                    <table class="table table-slim">
                        <tbody>
                            <tr>
                                <th style="width:60%">@lang('layaway::lang.total_amount'):</th>
                                <td class="text-right">{{ number_format($layaway->total_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <th>@lang('layaway::lang.down_payment'):</th>
                                <td class="text-right">
                                    {{ number_format($layaway->down_payment_amount, 2) }}
                                    @if($layaway->down_payment_percentage)
                                        <small>({{ number_format($layaway->down_payment_percentage, 0) }}%)</small>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>@lang('sale.total_paid'):</th>
                                <td class="text-right">{{ number_format($layaway->total_paid, 2) }}</td>
                            </tr>
                            <tr class="h4">
                                <th>@lang('layaway::lang.balance_due'):</th>
                                <td class="text-right">
                                    <strong>{{ number_format($layaway->balance_due, 2) }}</strong>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Notes -->
        @if(!empty($layaway->notes))
            <div class="row" style="color: #000000 !important;">
                <div class="col-xs-12">
                    <hr/>
                    <h4>@lang('lang_v1.notes'):</h4>
                    <p>{!! nl2br(e($layaway->notes)) !!}</p>
                </div>
            </div>
        @endif

        <!-- Print Button -->
        <div class="row no-print" style="margin-top: 20px;">
            <div class="col-xs-12 text-center">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fa fa-print"></i> @lang('messages.print')
                </button>
                <button onclick="window.close()" class="btn btn-default">
                    @lang('messages.close')
                </button>
            </div>
        </div>
    </div>
</body>
</html>
