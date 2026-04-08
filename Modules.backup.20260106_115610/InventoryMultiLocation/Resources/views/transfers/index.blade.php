@extends('layouts.app')
@section('title', __('inventorymultilocation::lang.stock_transfers'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>{{ __('inventorymultilocation::lang.stock_transfers') }}
        <small>{{ __('inventorymultilocation::lang.inventory_multi_location') }}</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ url('/home') }}"><i class="fa fa-dashboard"></i> {{ __('inventorymultilocation::lang.dashboard') }}</a></li>
        <li><a href="{{ route('inventory-multi.dashboard') }}">{{ __('inventorymultilocation::lang.dashboard') }}</a></li>
        <li class="active">{{ __('inventorymultilocation::lang.stock_transfers') }}</li>
    </ol>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])

        <!-- Header -->
        <div class="box-header with-border">
            <div class="row">
                <div class="col-md-6">
                    <h3 class="box-title">
                        <i class="fa fa-exchange"></i> {{ __('inventorymultilocation::lang.stock_transfers') }}
                    </h3>
                </div>
                <div class="col-md-6 text-right">
                    <div class="btn-group" role="group">
                        <a href="{{ route('inventory-multi.dashboard') }}" class="btn btn-default btn-sm">
                            <i class="fa fa-dashboard"></i> {{ __('inventorymultilocation::lang.dashboard') }}
                        </a>
                        <a href="{{ route('inventory-multi.inventory') }}" class="btn btn-default btn-sm">
                            <i class="fa fa-list"></i> {{ __('inventorymultilocation::lang.view_inventory') }}
                        </a>
                        <a href="{{ route('inventory-multi.transfers.create') }}" class="btn btn-primary btn-sm">
                            <i class="fa fa-plus"></i> {{ __('messages.add') }} {{ __('inventorymultilocation::lang.transfer') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="box-header">
            <form method="GET" id="transfer-filters">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>{{ __('sale.status') }}</label>
                            <select name="status" class="form-control select2">
                                <option value="">{{ __('messages.all') }}</option>
                                <option value="pending" {{ request()->get('status') == 'pending' ? 'selected' : '' }}>{{ __('lang_v1.pending') }}</option>
                                <option value="in_transit" {{ request()->get('status') == 'in_transit' ? 'selected' : '' }}>{{ __('inventorymultilocation::lang.in_transit') }}</option>
                                <option value="completed" {{ request()->get('status') == 'completed' ? 'selected' : '' }}>{{ __('lang_v1.completed') }}</option>
                                <option value="cancelled" {{ request()->get('status') == 'cancelled' ? 'selected' : '' }}>{{ __('messages.cancelled') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>{{ __('lang_v1.from_date') }}</label>
                            <input type="date" name="from_date" class="form-control" value="{{ request()->get('from_date') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>{{ __('lang_v1.to_date') }}</label>
                            <input type="date" name="to_date" class="form-control" value="{{ request()->get('to_date') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label><br>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-search"></i> {{ __('messages.filter') }}
                            </button>
                            <a href="{{ route('inventory-multi.transfers.index') }}" class="btn btn-default">
                                <i class="fa fa-refresh"></i> {{ __('messages.reset') }}
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Transfers Table -->
        <div class="box-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('sale.ref_no') }}</th>
                            <th>{{ __('inventorymultilocation::lang.from_location') }}</th>
                            <th>{{ __('inventorymultilocation::lang.to_location') }}</th>
                            <th>{{ __('sale.status') }}</th>
                            <th>{{ __('sale.total_amount') }}</th>
                            <th>{{ __('messages.created_by') }}</th>
                            <th>{{ __('messages.date') }}</th>
                            <th>{{ __('messages.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transfers as $transfer)
                            <tr>
                                <td>
                                    <a href="{{ route('inventory-multi.transfers.show', $transfer->id) }}">
                                        {{ $transfer->reference_no }}
                                    </a>
                                </td>
                                <td>
                                    <span class="label label-primary">{{ $transfer->fromLocation->name ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <span class="label label-info">{{ $transfer->toLocation->name ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    @if($transfer->status == 'pending')
                                        <span class="label label-warning">{{ __('lang_v1.pending') }}</span>
                                    @elseif($transfer->status == 'in_transit')
                                        <span class="label label-info">{{ __('inventorymultilocation::lang.in_transit') }}</span>
                                    @elseif($transfer->status == 'completed')
                                        <span class="label label-success">{{ __('lang_v1.completed') }}</span>
                                    @else
                                        <span class="label label-danger">{{ __('messages.cancelled') }}</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <span class="display_currency" data-currency_symbol="true">{{ $transfer->total_amount }}</span>
                                </td>
                                <td>{{ $transfer->createdBy->first_name ?? 'N/A' }} {{ $transfer->createdBy->last_name ?? '' }}</td>
                                <td>
                                    {{ \Carbon\Carbon::parse($transfer->created_at)->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">
                                            {{ __('messages.actions') }} <span class="caret"></span>
                                        </button>
                                        <ul class="dropdown-menu" role="menu">
                                            <li>
                                                <a href="{{ route('inventory-multi.transfers.show', $transfer->id) }}">
                                                    <i class="fa fa-eye"></i> {{ __('messages.view') }}
                                                </a>
                                            </li>
                                            @if($transfer->status == 'pending')
                                                <li>
                                                    <a href="#" class="complete-transfer" data-id="{{ $transfer->id }}">
                                                        <i class="fa fa-check"></i> {{ __('messages.complete') }}
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="#" class="cancel-transfer" data-id="{{ $transfer->id }}">
                                                        <i class="fa fa-times"></i> {{ __('messages.cancel') }}
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">
                                    <div class="alert alert-info" style="margin: 20px 0;">
                                        <i class="fa fa-info-circle"></i> {{ __('messages.no_data') }}
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($transfers->hasPages())
                <div class="text-center">
                    {!! $transfers->appends(request()->query())->links() !!}
                </div>
            @endif
        </div>

    @endcomponent
</section>

@endsection

@section('javascript')
<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2();

    // Initialize currency display
    __currency_convert_recursively($('.table-responsive'));

    // Complete transfer
    $(document).on('click', '.complete-transfer', function(e) {
        e.preventDefault();
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
    $(document).on('click', '.cancel-transfer', function(e) {
        e.preventDefault();
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
                            location.reload();
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
