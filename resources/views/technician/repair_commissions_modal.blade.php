<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title"><i class="fa fa-wrench"></i> @lang('lang_v1.repair_commissions')</h4>
        </div>
        <div class="modal-body">
            <p class="text-muted">@lang('lang_v1.repair_commissions_help')</p>
            <input type="text" id="repair_comm_search" class="form-control"
                placeholder="Buscar reparación, SKU o categoría..." style="margin-bottom:10px;">
            <div style="max-height:55vh; overflow:auto;">
                <table class="table table-condensed table-bordered table-striped" id="repair_comm_table">
                    <thead>
                        <tr class="bg-light-blue">
                            <th>Marca</th>
                            <th>@lang('lang_v1.category')</th>
                            <th>@lang('sale.product')</th>
                            <th>SKU</th>
                            <th style="width:150px;">@lang('lang_v1.commission') $</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $p)
                            <tr class="rc-row" data-search="{{ strtolower(($p->brand ?? '') . ' ' . $p->category . ' ' . $p->name . ' ' . $p->sku) }}">
                                <td><small><strong>{{ $p->brand ?? '—' }}</strong></small></td>
                                <td><small>{{ $p->category }}</small></td>
                                <td>{{ $p->name }}</td>
                                <td><small>{{ $p->sku }}</small></td>
                                <td>
                                    <input type="number" min="0" step="0.01"
                                        class="form-control input-sm rc-input"
                                        data-product_id="{{ $p->id }}"
                                        value="{{ isset($existing[$p->id]) && $existing[$p->id] > 0 ? $existing[$p->id] : '' }}"
                                        placeholder="0">
                                </td>
                            </tr>
                        @endforeach
                        @if($products->isEmpty())
                            <tr><td colspan="5" class="text-center text-muted">@lang('lang_v1.no_repair_products')</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <span class="text-muted pull-left" style="margin-top:8px;">{{ count($products) }} productos</span>
            <button type="button" class="btn btn-primary save_repair_commissions">
                <i class="fa fa-save"></i> @lang('messages.save')
            </button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>
