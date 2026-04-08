@extends('layouts.app')
@section('title', __('messages.add') . ' ' . __('inventorymultilocation::lang.transfer'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>{{ __('messages.add') }} {{ __('inventorymultilocation::lang.transfer') }}
        <small>{{ __('inventorymultilocation::lang.inventory_multi_location') }}</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ url('/home') }}"><i class="fa fa-dashboard"></i> {{ __('inventorymultilocation::lang.dashboard') }}</a></li>
        <li><a href="{{ route('inventory-multi.dashboard') }}">{{ __('inventorymultilocation::lang.dashboard') }}</a></li>
        <li><a href="{{ route('inventory-multi.transfers.index') }}">{{ __('inventorymultilocation::lang.stock_transfers') }}</a></li>
        <li class="active">{{ __('messages.add') }}</li>
    </ol>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])

        <div class="box-header with-border">
            <h3 class="box-title">{{ __('messages.add') }} {{ __('inventorymultilocation::lang.transfer') }}</h3>
        </div>

        <div class="box-body">
            <p class="text-info">
                <i class="fa fa-info-circle"></i> {{ __('inventorymultilocation::lang.create_transfer_info') }}
            </p>

            <div class="row">
                <div class="col-md-6">
                    <a href="{{ route('inventory-multi.inventory') }}" class="btn btn-block btn-primary btn-lg">
                        <i class="fa fa-list"></i><br>
                        {{ __('inventorymultilocation::lang.create_from_inventory') }}
                        <br><small>{{ __('inventorymultilocation::lang.browse_inventory_and_transfer') }}</small>
                    </a>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-info">
                        <h4><i class="icon fa fa-info"></i> {{ __('messages.how_it_works') }}</h4>
                        <ul>
                            <li>{{ __('inventorymultilocation::lang.go_to_inventory_view') }}</li>
                            <li>{{ __('inventorymultilocation::lang.select_products_to_transfer') }}</li>
                            <li>{{ __('inventorymultilocation::lang.use_bulk_transfer_or_individual') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    @endcomponent
</section>

@endsection
