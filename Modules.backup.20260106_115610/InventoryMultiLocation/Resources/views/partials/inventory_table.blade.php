<div class="table-responsive">
    <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr>
                <th style="width: 40px;">
                    <input type="checkbox" id="select-all-items">
                </th>
                <th>{{ __('inventorymultilocation::lang.product_name') }}</th>
                <th>{{ __('inventorymultilocation::lang.sku') }}</th>
                @if(request()->get('location_id') == 'all')
                    <th>{{ __('business.business_location') }}</th>
                @endif
                <th>{{ __('inventorymultilocation::lang.current_stock') }}</th>
                <th>{{ __('inventorymultilocation::lang.unit_cost') }}</th>
                <th>{{ __('sale.selling_price') }}</th>
                <th>{{ __('inventorymultilocation::lang.total_value') }}</th>
                <th>{{ __('inventorymultilocation::lang.stock_status') }}</th>
                <th>{{ __('inventorymultilocation::lang.last_updated') }}</th>
                <th>{{ __('messages.action') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($inventory as $item)
                <tr data-product-id="{{ $item->product_id }}"
                    data-variation-id="{{ $item->variation_id }}"
                    data-location-id="{{ $item->location_id }}">
                    <td>
                        <input type="checkbox" class="inventory-checkbox">
                    </td>
                    <td>
                        <div class="product-name">{{ $item->product_name }}</div>
                        @if($item->variation_name && $item->variation_name != 'DUMMY')
                            <small class="text-muted variation-name">{{ $item->variation_name }}</small>
                        @endif
                        @if($item->category_name)
                            <br><span class="label label-default">{{ $item->category_name }}</span>
                        @endif
                        @if($item->brand_name)
                            <span class="label label-info">{{ $item->brand_name }}</span>
                        @endif
                    </td>
                    <td>
                        <code>{{ $item->sku ?: $item->sub_sku }}</code>
                    </td>
                    @if(request()->get('location_id') == 'all')
                        <td>
                            <span class="label label-primary">{{ $item->location_name ?: 'N/A' }}</span>
                        </td>
                    @endif
                    <td class="text-center">
                        <span class="current-stock">{{ number_format($item->qty_available ?: 0, 2) }}</span>
                        @if($item->unit_name)
                            <small class="text-muted">{{ $item->unit_name }}</small>
                        @endif
                    </td>
                    <td class="text-right">
                        @if($item->default_purchase_price)
                            <span class="display_currency" data-currency_symbol="true">{{ $item->default_purchase_price }}</span>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </td>
                    <td class="text-right">
                        @if($item->default_sell_price)
                            <span class="display_currency" data-currency_symbol="true">{{ $item->default_sell_price }}</span>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </td>
                    <td class="text-right">
                        @if($item->default_purchase_price && $item->qty_available)
                            <span class="display_currency" data-currency_symbol="true">{{ $item->default_purchase_price * $item->qty_available }}</span>
                        @else
                            <span class="text-muted">0.00</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="stock-indicator"
                              data-stock="{{ $item->qty_available ?: 0 }}"
                              data-alert-qty="{{ $item->alert_quantity ?: 0 }}">
                        </span>
                    </td>
                    <td class="text-center">
                        @if($item->last_updated)
                            <small class="text-muted">{{ \Carbon\Carbon::parse($item->last_updated)->diffForHumans() }}</small>
                        @else
                            <small class="text-muted">Never</small>
                        @endif
                    </td>
                    <td>
                        <div class="btn-group">
                            <button type="button" class="btn btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">
                                {{ __('messages.actions') }} <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu" role="menu">
                                @if($item->qty_available > 0)
                                    <li>
                                        <a href="#" class="quick-transfer"
                                           data-product-id="{{ $item->product_id }}"
                                           data-variation-id="{{ $item->variation_id }}"
                                           data-location-id="{{ $item->location_id }}"
                                           data-product-name="{{ $item->product_name }}"
                                           data-current-stock="{{ $item->qty_available }}">
                                            <i class="fa fa-exchange"></i> {{ __('inventorymultilocation::lang.transfer') }}
                                        </a>
                                    </li>
                                @else
                                    <li class="disabled">
                                        <a href="#" class="text-muted">
                                            <i class="fa fa-info-circle"></i> {{ __('inventorymultilocation::lang.no_stock_to_transfer') }}
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ request()->get('location_id') == 'all' ? '11' : '10' }}" class="text-center">
                        <div class="alert alert-info" style="margin: 20px 0;">
                            <i class="fa fa-info-circle"></i> {{ __('messages.no_data') }}
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($inventory->hasPages())
    <div class="text-center">
        {!! $inventory->appends(request()->query())->links() !!}
    </div>
@endif

<script>
$(document).ready(function() {
    // Select all checkbox
    $('#select-all-items').on('change', function() {
        $('.inventory-checkbox').prop('checked', $(this).is(':checked')).trigger('change');
    });

    // Initialize display currency
    __currency_convert_recursively($('.table-responsive'));
});
</script>