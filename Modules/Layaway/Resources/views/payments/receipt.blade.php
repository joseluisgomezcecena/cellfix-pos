<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@lang('layaway::lang.payment_receipt') #{{ $payment->id }}</title>

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
        .payment-amount {
            font-size: 24px;
            font-weight: bold;
            color: #5cb85c;
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
                    @lang('layaway::lang.payment_receipt')
                </h3>
            </div>
        </div>

        <!-- Payment Details Header -->
        <div class="row" style="color: #000000 !important;">
            <div class="col-xs-12 text-center">
                <p style="width: 100% !important" class="word-wrap">
                    <span class="pull-left text-left word-wrap">
                        <b>@lang('layaway::lang.receipt_number'):</b> #{{ $payment->id }}
                        <br>
                        <b>@lang('layaway::lang.layaway_number'):</b> {{ $payment->layaway->layaway_number ?? 'N/A' }}
                        <br>
                        <b>@lang('layaway::lang.status'):</b>
                        @if(!empty($payment->layaway))
                            <span class="status-badge status-{{ $payment->layaway->status }}">
                                {{ ucfirst($payment->layaway->status) }}
                            </span>
                        @endif

                        @if(!empty($payment->layaway->contact))
                            <br><br>
                            <b>@lang('contact.customer'):</b><br>
                            {{ $payment->layaway->contact->name ?? '' }}
                            @if(!empty($payment->layaway->contact->mobile))
                                <br>{{ $payment->layaway->contact->mobile }}
                            @endif
                            @if(!empty($payment->layaway->contact->email))
                                <br>{{ $payment->layaway->contact->email }}
                            @endif
                        @endif
                    </span>

                    <span class="pull-right text-left">
                        <b>@lang('layaway::lang.payment_date'):</b> {{ @format_datetime($payment->payment_date) }}

                        @if(!empty($payment->layaway->location))
                            <br><br>
                            <b>@lang('business.location'):</b><br>
                            {{ $payment->layaway->location->name ?? '' }}
                            @if(!empty($payment->layaway->location->landmark))
                                <br>{{ $payment->layaway->location->landmark }}
                            @endif
                        @endif

                        @if(!empty($payment->processedBy))
                            <br><br>
                            <b>@lang('layaway::lang.processed_by'):</b><br>
                            {{ $payment->processedBy->first_name ?? '' }} {{ $payment->processedBy->last_name ?? '' }}
                        @endif
                    </span>
                </p>
            </div>
        </div>

        <!-- Payment Transaction Details -->
        <div class="row" style="color: #000000 !important;">
            <div class="col-xs-12">
                <br/>
                <h4 class="text-center">@lang('layaway::lang.payment_details')</h4>
                <table class="table table-bordered table-slim">
                    <tbody>
                        <tr>
                            <th style="width:50%">@lang('sale.payment_method'):</th>
                            <td>{{ $payment->payment_method ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>@lang('sale.payment_date'):</th>
                            <td>{{ @format_datetime($payment->payment_date) }}</td>
                        </tr>
                        @if(!empty($payment->payment_reference))
                            <tr>
                                <th>@lang('layaway::lang.reference_number'):</th>
                                <td>{{ $payment->payment_reference }}</td>
                            </tr>
                        @endif
                        <tr class="success">
                            <th>@lang('sale.amount'):</th>
                            <td>
                                <span class="payment-amount">{{ number_format($payment->amount, 2) }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>

                @if(!empty($payment->notes))
                    <div style="margin-top: 15px;">
                        <h5>@lang('lang_v1.notes'):</h5>
                        <p>{!! nl2br(e($payment->notes)) !!}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Layaway Summary -->
        @if(!empty($payment->layaway))
            <div class="row" style="color: #000000 !important;">
                <div class="col-xs-12">
                    <hr/>
                    <h4 class="text-center">@lang('layaway::lang.layaway_summary')</h4>
                    <div class="col-xs-6 col-xs-offset-3">
                        <table class="table table-slim">
                            <tbody>
                                <tr>
                                    <th style="width:60%">@lang('layaway::lang.total_amount'):</th>
                                    <td class="text-right">{{ number_format($payment->layaway->total_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <th>@lang('layaway::lang.down_payment'):</th>
                                    <td class="text-right">
                                        {{ number_format($payment->layaway->down_payment_amount, 2) }}
                                        @if($payment->layaway->down_payment_percentage)
                                            <small>({{ number_format($payment->layaway->down_payment_percentage, 0) }}%)</small>
                                        @endif
                                    </td>
                                </tr>
                                <tr class="active">
                                    <th>@lang('sale.total_paid'):</th>
                                    <td class="text-right">
                                        <strong>{{ number_format($payment->layaway->total_paid, 2) }}</strong>
                                    </td>
                                </tr>
                                <tr class="h4 warning">
                                    <th>@lang('layaway::lang.remaining_balance'):</th>
                                    <td class="text-right">
                                        <strong>{{ number_format($payment->layaway->balance_due, 2) }}</strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <!-- Thank You Message -->
        <div class="row" style="color: #000000 !important; margin-top: 30px;">
            <div class="col-xs-12 text-center">
                <h4>@lang('layaway::lang.thank_you_for_your_payment')</h4>
                @if(!empty($payment->layaway) && $payment->layaway->balance_due > 0)
                    <p>
                        <strong>@lang('layaway::lang.next_payment_due'):</strong> {{ @format_date($payment->layaway->payment_deadline) }}
                    </p>
                @elseif(!empty($payment->layaway) && $payment->layaway->balance_due == 0)
                    <p class="text-success">
                        <strong>@lang('layaway::lang.layaway_paid_in_full')</strong>
                    </p>
                @endif
            </div>
        </div>

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
