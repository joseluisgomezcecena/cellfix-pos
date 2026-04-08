{{-- Affiliate Discount Modal - Based on Screenshot Design --}}
@if(isset($show_affiliate_discount) && $show_affiliate_discount && isset($affiliate_modal_enabled) && $affiliate_modal_enabled)

{{-- Button to open modal - Place in POS sidebar --}}
<div class="affiliate-discount-trigger mb-3" style="margin-top: 10px;">
    <button type="button" class="btn btn-block btn-info" id="openAffiliateDiscountModal">
        <i class="fa fa-percent"></i> Descuento Afiliados
        <span class="badge" id="activeDiscountBadge" style="display:none;">0</span>
    </button>
</div>

{{-- Modal --}}
<div class="modal fade" id="affiliateDiscountModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #f4f4f4;">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title" style="color: #333; font-weight: bold;">DESCUENTO AFILIADOS</h4>
            </div>

            <div class="modal-body" style="padding: 20px;">
                {{-- Business Selector --}}
                <div class="form-group">
                    <label style="color: #e74c3c; font-weight: bold; text-transform: uppercase;">Empresa Afiliada</label>
                    <select class="form-control" id="affiliateBusinessSelect" style="padding: 10px;">
                        <option value="">Ninguna</option>
                        @foreach($affiliated_businesses as $business)
                            <option value="{{ $business->id }}">{{ $business->contact->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Discount Options Container --}}
                <div id="discountOptionsContainer" style="display: none; margin-top: 20px;">
                    {{-- Dynamic content will be loaded here by JavaScript --}}
                </div>

                {{-- Instructions --}}
                <div class="alert alert-info" style="margin-top: 20px; text-align: center;">
                    <small style="color: #e74c3c;">
                        SELECCIONAR LOS DESCUENTOS QUE SE REQUIEREN Y SE DESCUENTAN AUTOMÁTICAMENTE
                    </small>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="saveAffiliateDiscounts" style="background-color: #9b59b6; border-color: #9b59b6; padding: 10px 30px;">
                    Guardar
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal" style="padding: 10px 30px;">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Include CSS --}}
<link rel="stylesheet" href="{{ asset('modules/affiliatediscount/css/affiliate-discount.css') }}">

@endif
