@extends('layouts.app')
@section('title', __('messages.view') . ' ' . __('inventorymultilocation::lang.transfer'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>{{ __('messages.view') }} {{ __('inventorymultilocation::lang.transfer') }}
        <small>#{{ $transfer->reference_no }}</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ url('/home') }}"><i class="fa fa-dashboard"></i> {{ __('inventorymultilocation::lang.dashboard') }}</a></li>
        <li><a href="{{ route('inventory-multi.dashboard') }}">{{ __('inventorymultilocation::lang.dashboard') }}</a></li>
        <li><a href="{{ route('inventory-multi.transfers.index') }}">{{ __('inventorymultilocation::lang.stock_transfers') }}</a></li>
        <li class="active">{{ $transfer->reference_no }}</li>
    </ol>
</section>

<!-- Print Styles -->
<style>
@media print {
    .no-print, .main-header, .main-sidebar, .content-header, .main-footer, .breadcrumb { display: none !important; }
    .content-wrapper { margin-left: 0 !important; }
    .box { border: none !important; box-shadow: none !important; }
    body { font-size: 12pt; }
    .table { width: 100%; border-collapse: collapse; }
    .table th, .table td { border: 1px solid #000 !important; padding: 8px !important; }
    .label { border: 1px solid #000; padding: 2px 5px; }
}
</style>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])

        <!-- Header -->
        <div class="box-header with-border">
            <h3 class="box-title">
                <i class="fa fa-exchange"></i> {{ __('inventorymultilocation::lang.transfer') }} #{{ $transfer->reference_no }}
            </h3>
            <div class="box-tools pull-right">
                @if($transfer->status == 'pending')
                    <span class="label label-warning">{{ __('inventorymultilocation::lang.pending') }}</span>
                @elseif($transfer->status == 'in_transit')
                    <span class="label label-info">{{ __('inventorymultilocation::lang.in_transit') }}</span>
                @elseif($transfer->status == 'completed')
                    <span class="label label-success">{{ __('inventorymultilocation::lang.completed') }}</span>
                @else
                    <span class="label label-danger">{{ __('inventorymultilocation::lang.cancelled') }}</span>
                @endif
            </div>
        </div>

        <!-- Transfer Details -->
        <div class="box-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table">
                        <tr>
                            <th style="width: 40%;">{{ __('sale.ref_no') }}:</th>
                            <td>{{ $transfer->reference_no }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('inventorymultilocation::lang.from_location') }}:</th>
                            <td><span class="label label-primary">{{ $transfer->fromLocation->name }}</span></td>
                        </tr>
                        <tr>
                            <th>{{ __('inventorymultilocation::lang.to_location') }}:</th>
                            <td><span class="label label-info">{{ $transfer->toLocation->name }}</span></td>
                        </tr>
                        <tr>
                            <th>{{ __('sale.status') }}:</th>
                            <td>
                                @if($transfer->status == 'pending')
                                    <span class="label label-warning">{{ __('inventorymultilocation::lang.pending') }}</span>
                                @elseif($transfer->status == 'in_transit')
                                    <span class="label label-info">{{ __('inventorymultilocation::lang.in_transit') }}</span>
                                @elseif($transfer->status == 'completed')
                                    <span class="label label-success">{{ __('inventorymultilocation::lang.completed') }}</span>
                                @else
                                    <span class="label label-danger">{{ __('inventorymultilocation::lang.cancelled') }}</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table">
                        <tr>
                            <th style="width: 40%;">{{ __('messages.created_by') }}:</th>
                            <td>{{ $transfer->createdBy->first_name ?? '' }} {{ $transfer->createdBy->last_name ?? '' }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('messages.created_at') }}:</th>
                            <td>{{ \Carbon\Carbon::parse($transfer->created_at)->format('d/m/Y H:i') }}</td>
                        </tr>
                        @if($transfer->transferred_at)
                        <tr>
                            <th>{{ __('inventorymultilocation::lang.transferred_at') }}:</th>
                            <td>{{ \Carbon\Carbon::parse($transfer->transferred_at)->format('d/m/Y H:i') }}</td>
                        </tr>
                        @endif
                        @if($transfer->received_at)
                        <tr>
                            <th>{{ __('inventorymultilocation::lang.received_at') }}:</th>
                            <td>{{ \Carbon\Carbon::parse($transfer->received_at)->format('d/m/Y H:i') }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            @if($transfer->notes)
            <div class="row">
                <div class="col-md-12">
                    <strong>{{ __('inventorymultilocation::lang.notes') }}:</strong>
                    <p>{{ $transfer->notes }}</p>
                </div>
            </div>
            @endif

            <hr>

            <!-- Transfer Items -->
            <h4>{{ __('inventorymultilocation::lang.transfer_items') }}</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>{{ __('sale.product') }}</th>
                            <th>{{ __('inventorymultilocation::lang.imei_sku') }}</th>
                            <th>{{ __('sale.qty') }}</th>
                            <th class="text-right">{{ __('sale.unit_price') }}</th>
                            <th class="text-right">{{ __('sale.subtotal') }}</th>
                            @if($transfer->status == 'completed')
                                <th class="text-right">{{ __('inventorymultilocation::lang.qty_received') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transfer->items as $item)
                            <tr>
                                <td>
                                    <strong>{{ $item->product->name ?? 'N/A' }}</strong>
                                    @if($item->variation && $item->variation->name != 'DUMMY')
                                        <br><small class="text-muted">{{ $item->variation->name }}</small>
                                    @endif
                                </td>
                                <td>{{ $item->variation->sub_sku ?? $item->product->sku ?? 'N/A' }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td class="text-right">
                                    <span class="display_currency" data-currency_symbol="true">{{ $item->unit_cost }}</span>
                                </td>
                                <td class="text-right">
                                    <span class="display_currency" data-currency_symbol="true">{{ $item->total_cost }}</span>
                                </td>
                                @if($transfer->status == 'completed')
                                    <td class="text-right">{{ $item->quantity_received ?? $item->quantity }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="{{ $transfer->status == 'completed' ? '5' : '4' }}" class="text-right">
                                {{ __('sale.total') }}:
                            </th>
                            <th class="text-right">
                                <span class="display_currency" data-currency_symbol="true">{{ $transfer->total_amount }}</span>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="row no-print" style="margin-top: 20px;">
                <div class="col-md-12">
                    <a href="{{ route('inventory-multi.transfers.index') }}" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> {{ __('messages.back') }}
                    </a>

                    <button type="button" class="btn btn-info" onclick="window.print()">
                        <i class="fa fa-print"></i> {{ __('inventorymultilocation::lang.print') }}
                    </button>

                    @if($transfer->status == 'pending')
                        <button type="button" class="btn btn-success complete-transfer" data-id="{{ $transfer->id }}">
                            <i class="fa fa-check"></i> {{ __('messages.complete') }}
                        </button>
                        <button type="button" class="btn btn-danger cancel-transfer" data-id="{{ $transfer->id }}">
                            <i class="fa fa-times"></i> {{ __('messages.cancel') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>

    @endcomponent
</section>

@endsection

@section('javascript')
<script>
$(document).ready(function() {
    // Initialize currency display
    __currency_convert_recursively($('.box-body'));

    // Complete transfer
    $('.complete-transfer').on('click', function() {
        const transferId = $(this).data('id');

        swal({
            title: "{{ __('messages.sure') }}",
            text: "{{ __('inventorymultilocation::lang.complete_transfer_confirm') }}",
            icon: "warning",
            buttons: true,
            dangerMode: false,
        }).then((willComplete) => {
            if (willComplete) {
                $.post('{{ url('inventory-multi/transfers') }}/' + transferId + '/complete')
                    .done(function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            location.reload();
                        } else {
                            toastr.error(response.message);
                        }
                    })
                    .fail(function() {
                        toastr.error('{{ __('messages.something_went_wrong') }}');
                    });
            }
        });
    });

    // Cancel transfer
    $('.cancel-transfer').on('click', function() {
        const transferId = $(this).data('id');

        swal({
            title: "{{ __('messages.sure') }}",
            text: "{{ __('inventorymultilocation::lang.cancel_transfer_confirm') }}",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        }).then((willCancel) => {
            if (willCancel) {
                $.ajax({
                    url: '{{ url('inventory-multi/transfers') }}/' + transferId,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            window.location.href = '{{ route('inventory-multi.transfers.index') }}';
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function() {
                        toastr.error('{{ __('messages.something_went_wrong') }}');
                    }
                });
            }
        });
    });
});
</script>
@endsection
