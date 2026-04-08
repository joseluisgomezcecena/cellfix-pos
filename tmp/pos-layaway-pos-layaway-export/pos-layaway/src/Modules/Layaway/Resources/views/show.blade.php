@extends('layouts.app')

@section('title', __('layaway::lang.layaway_details'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('layaway::lang.layaway_details')
        <small>{{ $layaway->layaway_number }}</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                @slot('tool')
                    <div class="box-tools">
                        @can('layaway.update')
                            @if(in_array($layaway->status, ['pending', 'active']))
                                <a class="btn btn-warning btn-sm" href="{{ action('\\Modules\\Layaway\\Http\\Controllers\\LayawayController@edit', [$layaway->id]) }}">
                                    <i class="fa fa-edit"></i> @lang('messages.edit')
                                </a>
                            @endif
                        @endcan

                        @can('layaway.process_payment')
                            @if($layaway->balance_due > 0 && in_array($layaway->status, ['pending', 'active']))
                                <button type="button" class="btn btn-success btn-sm make-payment" data-href="{{ action('\\Modules\\Layaway\\Http\\Controllers\\LayawayPaymentController@create', [$layaway->id]) }}">
                                    <i class="fa fa-money"></i>
                                    @lang('layaway::lang.make_payment')
                                </button>
                            @endif
                        @endcan

                        @if(in_array($layaway->status, ['pending', 'active']))
                            <form method="POST" action="{{ action('\\Modules\\Layaway\\Http\\Controllers\\LayawayController@cancel', [$layaway->id]) }}" style="display: inline-block;" onsubmit="return confirm('@lang('messages.sure')')">
                                @csrf
                                <button type="submit" class="btn btn-default btn-sm">
                                    @lang('layaway::lang.cancel_layaway')
                                </button>
                            </form>
                        @endif

                        <a class="btn btn-primary btn-sm" href="{{ action('\\Modules\\Layaway\\Http\\Controllers\\LayawayController@printReceipt', [$layaway->id]) }}" target="_blank">
                            <i class="fa fa-print"></i> @lang('layaway::lang.print_receipt')
                        </a>
                    </div>
                @endslot

                <div class="row">
                    <div class="col-md-6">
                        <h4>@lang('layaway::lang.customer_details')</h4>
                        <address>
                            <strong>{{ $layaway->contact->name ?? '' }}</strong><br>
                            @if($layaway->contact->landmark)
                                {{ $layaway->contact->landmark }}<br>
                            @endif
                            {{ $layaway->contact->city ?? '' }}, {{ $layaway->contact->state ?? '' }}<br>
                            {{ $layaway->contact->country ?? '' }}<br>
                            @if($layaway->contact->mobile)
                                <abbr title="Phone">P:</abbr> {{ $layaway->contact->mobile }}
                            @endif
                            @if($layaway->contact->email)
                                <br><abbr title="Email">E:</abbr> {{ $layaway->contact->email }}
                            @endif
                        </address>
                    </div>

                    <div class="col-md-6">
                        <b>@lang('layaway::lang.layaway_number'):</b> {{ $layaway->layaway_number }}<br>
                        <b>@lang('layaway::lang.status'):</b>
                        @php
                            $status_colors = [
                                'pending' => 'label-warning',
                                'active' => 'label-info',
                                'completed' => 'label-success',
                                'cancelled' => 'label-danger',
                                'expired' => 'label-default'
                            ];
                            $color = isset($status_colors[$layaway->status]) ? $status_colors[$layaway->status] : 'label-default';
                        @endphp
                        <span class="label {{ $color }}">@lang('layaway::lang.status_' . $layaway->status)</span><br>

                        <b>@lang('layaway::lang.business_location'):</b> {{ $layaway->location->name ?? '' }}<br>
                        <b>@lang('layaway::lang.created_by'):</b> {{ $layaway->createdBy->first_name ?? '' }} {{ $layaway->createdBy->last_name ?? '' }}<br>
                        <b>@lang('layaway::lang.created_at'):</b> {{ $layaway->created_at->format('d/m/Y H:i') }}<br>
                        <b>@lang('layaway::lang.payment_deadline'):</b>
                        @if($layaway->isOverdue())
                            <span class="text-danger">{{ $layaway->payment_deadline->format('d/m/Y') }} <i class="fa fa-exclamation-triangle"></i></span>
                        @else
                            {{ $layaway->payment_deadline->format('d/m/Y') }}
                        @endif
                    </div>
                </div>

                @if($layaway->notes)
                    <div class="row">
                        <div class="col-md-12">
                            <h4>@lang('layaway::lang.notes'):</h4>
                            <p>{{ $layaway->notes }}</p>
                        </div>
                    </div>
                @endif
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('layaway::lang.items_on_layaway')])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>@lang('layaway::lang.product')</th>
                                <th>@lang('layaway::lang.quantity')</th>
                                <th>@lang('layaway::lang.unit_price')</th>
                                <th>@lang('layaway::lang.line_total')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($layaway->items as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item->full_name }}</td>
                                    <td>@format_quantity($item->quantity)</td>
                                    <td><span class="display_currency" data-currency_symbol="true">{{ $item->unit_price }}</span></td>
                                    <td><span class="display_currency" data-currency_symbol="true">{{ $item->total_price }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="row">
                    <div class="col-sm-6 col-sm-offset-6">
                        <div class="table-responsive">
                            <table class="table">
                                <tr class="success">
                                    <td><strong>@lang('layaway::lang.total_amount'):</strong></td>
                                    <td><span class="display_currency" data-currency_symbol="true">{{ $layaway->total_amount }}</span></td>
                                </tr>
                                <tr class="info">
                                    <td><strong>@lang('layaway::lang.down_payment_percentage'):</strong></td>
                                    <td>{{ $layaway->down_payment_percentage }}%</td>
                                </tr>
                                <tr class="info">
                                    <td><strong>@lang('layaway::lang.down_payment_amount'):</strong></td>
                                    <td><span class="display_currency" data-currency_symbol="true">{{ $layaway->down_payment_amount }}</span></td>
                                </tr>
                                <tr class="success">
                                    <td><strong>@lang('layaway::lang.total_paid'):</strong></td>
                                    <td><span class="display_currency" data-currency_symbol="true">{{ $layaway->total_paid }}</span></td>
                                </tr>
                                <tr class="warning">
                                    <td><strong>@lang('layaway::lang.balance_due'):</strong></td>
                                    <td><span class="display_currency" data-currency_symbol="true">{{ $layaway->balance_due }}</span></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('layaway::lang.payment_history')])
                @if($layaway->payments->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>@lang('layaway::lang.payment_date')</th>
                                    <th>@lang('layaway::lang.payment_amount')</th>
                                    <th>@lang('layaway::lang.payment_method')</th>
                                    <th>@lang('layaway::lang.processed_by')</th>
                                    <th>@lang('layaway::lang.payment_notes')</th>
                                    <th>@lang('messages.action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($layaway->payments as $payment)
                                    <tr>
                                        <td>{{ $payment->payment_date->format('d/m/Y H:i') }}</td>
                                        <td><span class="display_currency" data-currency_symbol="true">{{ $payment->amount }}</span></td>
                                        <td>{{ $payment->formatted_method }}</td>
                                        <td>{{ $payment->processedBy->first_name ?? '' }} {{ $payment->processedBy->last_name ?? '' }}</td>
                                        <td>{{ $payment->notes }}</td>
                                        <td>
                                            @can('layaway.view')
                                                <a href="{{ action('\\Modules\\Layaway\\Http\\Controllers\\LayawayPaymentController@printReceipt', [$payment->id]) }}" target="_blank" class="btn btn-xs btn-primary">
                                                    <i class="fa fa-print"></i> @lang('layaway::lang.print_receipt')
                                                </a>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted">@lang('layaway::lang.no_payments_found')</p>
                @endif
            @endcomponent
        </div>
    </div>

    <div class="modal fade layaway_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->
@stop

@section('javascript')
<script type="text/javascript">
$(document).ready(function(){
    // Make payment modal
    $(document).on('click', '.make-payment', function(e){
        e.preventDefault();
        var url = $(this).data('href');

        $('.layaway_modal').load(url, function(){
            $(this).modal('show');
            __currency_convert_recursively($('.layaway_modal'));
        });
    });

    // Initialize currency display
    __currency_convert_recursively($('.content'));
});
</script>
@endsection