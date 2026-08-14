@php
    $is_mobile = isMobile();
@endphp
<div class="row">
    <div
        class="pos-form-actions tw-rounded-tr-xl tw-rounded-tl-xl tw-shadow-[rgba(17,_17,_26,_0.1)_0px_0px_16px] tw-bg-white tw-cursor-pointer">
        <div
            class="tw-flex tw-items-center tw-justify-between tw-flex-col sm:tw-flex-row md:tw-flex-row lg:tw-flex-row xl:tw-flex-row tw-gap-3 tw-px-4 tw-py-3 tw-w-full">

            <!-- Lado izquierdo: borrar / cotizacion / suspend / total -->
            <div class="tw-flex tw-items-center tw-gap-3 tw-flex-row tw-flex-wrap">

                @if (!Gate::check('disable_draft') || auth()->user()->can('superadmin') || auth()->user()->can('admin'))
                    <button type="button"
                        class="tw-font-bold tw-text-gray-700 tw-text-xs md:tw-text-sm tw-flex tw-flex-col tw-items-center tw-justify-center tw-gap-1 @if ($pos_settings['disable_draft'] != 0) hide @endif"
                        id="pos-draft" @if (!empty($only_payment)) disabled @endif><i
                            class="fas fa-edit tw-text-[#009ce4]"></i> @lang('sale.draft')</button>
                @endif

                @if (!Gate::check('disable_quotation') || auth()->user()->can('superadmin') || auth()->user()->can('admin'))
                    <button type="button"
                        class="tw-font-bold tw-text-gray-700 tw-cursor-pointer tw-text-xs md:tw-text-sm tw-flex tw-flex-col tw-items-center tw-justify-center tw-gap-1"
                        id="pos-quotation" @if (!empty($only_payment)) disabled @endif><i
                            class="fas fa-edit tw-text-[#E7A500]"></i> @lang('lang_v1.quotation')</button>
                @endif

                @if (!Gate::check('disable_suspend_sale') || auth()->user()->can('superadmin') || auth()->user()->can('admin'))
                    @if (empty($pos_settings['disable_suspend']))
                        <button type="button"
                            class="tw-font-bold tw-text-gray-700 tw-cursor-pointer tw-text-xs md:tw-text-sm tw-flex tw-flex-col tw-items-center tw-justify-center tw-gap-1 pos-express-finalize"
                            data-pay_method="suspend" title="@lang('lang_v1.tooltip_suspend')"
                            @if (!empty($only_payment)) disabled @endif>
                            <i class="fas fa-pause tw-text-[#EF4B51]" aria-hidden="true"></i>
                            @lang('lang_v1.suspend')
                        </button>
                    @endif
                @endif

                @if (!Gate::check('disable_credit_sale') || auth()->user()->can('superadmin') || auth()->user()->can('admin'))
                    @if (empty($pos_settings['disable_credit_sale_button']))
                        <input type="hidden" name="is_credit_sale" value="0" id="is_credit_sale">
                        <button type="button"
                            class="tw-font-bold tw-text-gray-700 tw-cursor-pointer tw-text-xs md:tw-text-sm tw-flex tw-flex-col tw-items-center tw-justify-center tw-gap-1 pos-express-finalize"
                            data-pay_method="credit_sale" title="@lang('lang_v1.tooltip_credit_sale')"
                            @if (!empty($only_payment)) disabled @endif>
                            <i class="fas fa-check tw-text-[#5E5CA8]" aria-hidden="true"></i> @lang('lang_v1.credit_sale')
                        </button>
                    @endif
                @endif

                {{-- Indicador para el JS: quién puede cerrar una venta con saldo pendiente.
                     Los vendedores comunes NO pueden (bloqueo total contra fraude/olvido);
                     los gerentes con business_settings.access sí, con confirmación fuerte. --}}
                <input type="hidden" id="pos_can_credit_sale"
                    value="{{ auth()->user()->can('business_settings.access') || auth()->user()->can('superadmin') ? '1' : '0' }}">

                {{-- Recepción de reparación (orden en 2 pasos: recibir → entregar) --}}
                <input type="hidden" name="repair_status" id="repair_status" value="">
                @if (empty($edit))
                    <button type="button"
                        class="tw-font-bold tw-text-gray-700 tw-cursor-pointer tw-text-xs md:tw-text-sm tw-flex tw-flex-col tw-items-center tw-justify-center tw-gap-1"
                        id="pos-receive-repair" title="@lang('lang_v1.receive_repair_help')"
                        @if (!empty($only_payment)) disabled @endif>
                        <i class="fas fa-wrench tw-text-[#f39c12]"></i> @lang('lang_v1.receive_repair')
                    </button>
                    <button type="button"
                        class="tw-font-bold tw-text-gray-700 tw-cursor-pointer tw-text-xs md:tw-text-sm tw-flex tw-flex-col tw-items-center tw-justify-center tw-gap-1"
                        id="pos-deliver-repair" title="@lang('lang_v1.deliver_repair')">
                        <i class="fas fa-handshake tw-text-[#16a085]"></i> @lang('lang_v1.deliver_repair')
                    </button>
                @endif

                @if (empty($edit))
                    <button type="button"
                        class="tw-font-bold tw-text-white tw-cursor-pointer tw-text-xs md:tw-text-sm tw-bg-red-600 tw-p-2 tw-rounded-md"
                        id="pos-cancel"> <i class="fas fa-window-close"></i> @lang('sale.cancel')</button>
                @else
                    <button type="button"
                        class="tw-font-bold tw-text-white tw-cursor-pointer tw-text-xs md:tw-text-sm tw-bg-red-600 tw-p-2 tw-rounded-md hide"
                        id="pos-delete" @if (!empty($only_payment)) disabled @endif> <i
                            class="fas fa-trash-alt"></i> @lang('messages.delete')</button>
                @endif

                <div class="pos-total tw-flex tw-items-center tw-gap-2 tw-pl-3 tw-border-l">
                    <div
                        class="tw-text-black tw-font-bold tw-text-xs md:tw-text-base tw-flex tw-items-center tw-flex-col tw-leading-tight">
                        <div>@lang('sale.total')</div>
                        <div>@lang('lang_v1.payable'):</div>
                    </div>
                    <input type="hidden" name="final_total" id="final_total_input" value="0.00">
                    <span id="total_payable"
                        class="tw-text-green-900 tw-font-bold tw-text-base md:tw-text-2xl number">0.00</span>
                </div>
            </div>

            <!-- Lado derecho: 6 botones de método de pago + pago multiple -->
            <div class="tw-flex tw-flex-wrap tw-items-center tw-gap-2 tw-justify-end">
                <button type="button"
                    class="pos-payment-method-btn tw-font-bold tw-text-white tw-cursor-pointer tw-text-xs md:tw-text-sm tw-px-4 tw-py-2 tw-rounded-md tw-min-w-[110px]"
                    id="pos-pay-cash" data-pay_method="cash"
                    style="background-color: #28a745;"
                    @if (!empty($only_payment) || !array_key_exists('cash', $payment_types)) disabled @endif>
                    <i class="fas fa-money-bill-alt"></i> @lang('sale.cash')
                </button>

                <button type="button"
                    class="pos-payment-method-btn tw-font-bold tw-text-white tw-cursor-pointer tw-text-xs md:tw-text-sm tw-px-4 tw-py-2 tw-rounded-md tw-min-w-[110px]"
                    id="pos-pay-debit" data-pay_method="card" data-card_type="debit"
                    style="background-color: #2563eb;"
                    @if (!empty($only_payment) || !array_key_exists('card', $payment_types)) disabled @endif>
                    <i class="fas fa-credit-card"></i> @lang('lang_v1.debit_card')
                </button>

                <button type="button"
                    class="pos-payment-method-btn tw-font-bold tw-text-white tw-cursor-pointer tw-text-xs md:tw-text-sm tw-px-4 tw-py-2 tw-rounded-md tw-min-w-[110px]"
                    id="pos-pay-credit" data-pay_method="card" data-card_type="credit"
                    style="background-color: #1d4ed8;"
                    @if (!empty($only_payment) || !array_key_exists('card', $payment_types)) disabled @endif>
                    <i class="fas fa-credit-card"></i> @lang('lang_v1.credit_card')
                </button>

                <button type="button"
                    class="pos-payment-method-btn tw-font-bold tw-text-white tw-cursor-pointer tw-text-xs md:tw-text-sm tw-px-4 tw-py-2 tw-rounded-md tw-min-w-[110px]"
                    id="pos-pay-amex" data-pay_method="card" data-card_type="amex"
                    style="background-color: #1e40af;"
                    @if (!empty($only_payment) || !array_key_exists('card', $payment_types)) disabled @endif>
                    <i class="fab fa-cc-amex"></i> @lang('lang_v1.american_express')
                </button>

                @php
                    $rp_enabled = session('business.enable_rp') == 1;
                @endphp
                <button type="button"
                    class="pos-payment-method-btn tw-font-bold tw-text-white tw-cursor-pointer tw-text-xs md:tw-text-sm tw-px-4 tw-py-2 tw-rounded-md tw-min-w-[110px]"
                    id="pos-pay-points"
                    style="background-color: #f59e0b; @if(!$rp_enabled) opacity: 0.6; @endif"
                    @if(!$rp_enabled) disabled title="@lang('lang_v1.rp_not_enabled')" @else title="{{ session('business.rp_name', 'Puntos') }}" @endif>
                    <i class="fas fa-star"></i>
                    @if($rp_enabled)
                        {{ session('business.rp_name', __('lang_v1.points')) }}
                    @else
                        @lang('lang_v1.points')
                    @endif
                </button>

                <button type="button"
                    class="pos-payment-method-btn tw-font-bold tw-text-white tw-cursor-pointer tw-text-xs md:tw-text-sm tw-px-4 tw-py-2 tw-rounded-md tw-min-w-[110px]"
                    id="pos-pay-transfer" data-pay_method="bank_transfer"
                    style="background-color: #6b7280;"
                    @if (!empty($only_payment) || !array_key_exists('bank_transfer', $payment_types)) disabled @endif>
                    <i class="fas fa-exchange-alt"></i> @lang('lang_v1.transfer')
                </button>

                <button type="button"
                    class="pos-payment-method-btn tw-font-bold tw-text-white tw-cursor-pointer tw-text-xs md:tw-text-sm tw-px-4 tw-py-2 tw-rounded-md tw-min-w-[110px]"
                    id="pos-pay-cheque" data-pay_method="cheque"
                    style="background-color: #4b5563;"
                    @if (!empty($only_payment) || !array_key_exists('cheque', $payment_types)) disabled @endif>
                    <i class="fas fa-money-check"></i> @lang('lang_v1.cheque')
                </button>

                @if (!Gate::check('disable_pay_checkout') || auth()->user()->can('superadmin') || auth()->user()->can('admin'))
                    <button type="button"
                        class="tw-font-bold tw-text-white tw-cursor-pointer tw-text-xs md:tw-text-sm tw-bg-[#001F3E] tw-rounded-md tw-px-4 tw-py-2 tw-min-w-[140px]"
                        id="pos-finalize" title="@lang('lang_v1.tooltip_checkout_multi_pay')"
                        @if (!empty($only_payment)) disabled @endif>
                        <i class="fas fa-money-check-alt"></i> @lang('lang_v1.checkout_multi_pay')
                    </button>
                @endif
            </div>

            @if (!isset($pos_settings['hide_recent_trans']) || $pos_settings['hide_recent_trans'] == 0)
                <button type="button"
                    class="tw-font-bold tw-bg-[#646EE4] hover:tw-bg-[#414aac] tw-rounded-full tw-text-white tw-px-4 tw-h-9 tw-cursor-pointer tw-text-xs md:tw-text-sm"
                    data-toggle="modal" data-target="#recent_transactions_modal" id="recent-transactions">
                    <i class="fas fa-clock"></i> @lang('lang_v1.recent_transactions')
                </button>
            @endif
        </div>
    </div>
</div>

@include('sale_pos.partials.cash_payment_modal')
@include('sale_pos.partials.transfer_payment_modal')
@include('sale_pos.partials.cheque_payment_modal')
@include('sale_pos.partials.card_simple_modal')

{{-- La recepción de reparación usa el modal de pago real del POS (#modal_payment):
     efectivo en dólares/tipo de cambio y tarjeta con terminal, igual que una venta normal. --}}

@include('sale_pos.partials.repair_delivery_modal')

@if (isset($transaction))
    @include('sale_pos.partials.edit_discount_modal', [
        'sales_discount' => $transaction->discount_amount,
        'discount_type' => $transaction->discount_type,
        'rp_redeemed' => $transaction->rp_redeemed,
        'rp_redeemed_amount' => $transaction->rp_redeemed_amount,
        'max_available' => !empty($redeem_details['points']) ? $redeem_details['points'] : 0,
    ])
@else
    @include('sale_pos.partials.edit_discount_modal', [
        'sales_discount' => $business_details->default_sales_discount,
        'discount_type' => 'percentage',
        'rp_redeemed' => 0,
        'rp_redeemed_amount' => 0,
        'max_available' => 0,
    ])
@endif

@if (isset($transaction))
    @include('sale_pos.partials.edit_order_tax_modal', ['selected_tax' => $transaction->tax_id])
@else
    @include('sale_pos.partials.edit_order_tax_modal', [
        'selected_tax' => $business_details->default_sales_tax,
    ])
@endif

@include('sale_pos.partials.edit_shipping_modal')
