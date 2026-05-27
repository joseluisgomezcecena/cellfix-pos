@extends('layouts.app')
@section('title', __('lang_v1.stock_correction'))

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.stock_correction')
        <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">
            @lang('lang_v1.stock_correction_subtitle')
        </small>
    </h1>
</section>

<section class="content">
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        @lang('lang_v1.stock_correction_help')
    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.stock_corrections')])
        @slot('tool')
            <div class="box-tools">
                <a class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full pull-right"
                    href="{{ route('stock-corrections.create') }}">
                    <i class="fa fa-plus"></i> @lang('lang_v1.add_stock_correction')
                </a>
            </div>
        @endslot

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="stock_corrections_table">
                <thead>
                    <tr>
                        <th>@lang('messages.date')</th>
                        <th>@lang('purchase.business_location')</th>
                        <th>@lang('sale.product')</th>
                        <th>@lang('lang_v1.correction_type')</th>
                        <th>@lang('sale.qty')</th>
                        <th>@lang('lang_v1.qty_before')</th>
                        <th>@lang('lang_v1.qty_after')</th>
                        <th>@lang('lang_v1.reason')</th>
                        <th>@lang('user.name')</th>
                        <th>@lang('lang_v1.notes')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</section>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        $('#stock_corrections_table').DataTable({
            processing: true,
            serverSide: true,
            order: [[0, 'desc']],
            ajax: '{{ route('stock-corrections.index') }}',
            columns: [
                { data: 'created_at', name: 'stock_corrections.created_at' },
                { data: 'location_name', name: 'bl.name' },
                { data: 'product_full', name: 'p.name' },
                { data: 'type', name: 'stock_corrections.type' },
                { data: 'quantity', name: 'stock_corrections.quantity' },
                { data: 'qty_before', name: 'stock_corrections.qty_before' },
                { data: 'qty_after', name: 'stock_corrections.qty_after' },
                { data: 'reason', name: 'stock_corrections.reason', orderable: false },
                { data: 'user_name', name: 'u.first_name' },
                { data: 'note', name: 'stock_corrections.note' }
            ]
        });

        @if(session('status'))
            @if(session('status')['success'])
                toastr.success("{{ session('status')['msg'] }}");
            @else
                toastr.error("{{ session('status')['msg'] }}");
            @endif
        @endif
    });
</script>
@endsection
