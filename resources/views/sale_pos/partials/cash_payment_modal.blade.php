<div class="modal fade" tabindex="-1" role="dialog" id="cash_payment_modal">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #28a745; color: white;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fas fa-money-bill-alt"></i> @lang('lang_v1.cash')</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8 text-center">
                        <h3 style="margin: 0;">@lang('sale.total_payable'):
                            <strong id="cash_modal_total_payable" class="text-success">$0.00</strong>
                        </h3>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-weight: bold;">@lang('lang_v1.exchange_rate') (USD→MXN):</label>
                            {{-- IMPORTANTE: leemos directo de BD (no de sesión) para que el cajero
                                 SIEMPRE vea el valor más reciente, sin importar cuándo se logueó.
                                 La sesión solo se carga al login y queda stale tras /exchange-rate. --}}
                            @php
                                $live_exchange_rate = \DB::table('business')
                                    ->where('id', session('user.business_id'))
                                    ->value('cash_exchange_rate') ?: 18.00;
                            @endphp
                            <input type="number" min="0" step="0.01" class="form-control" id="cash_exchange_rate"
                                value="{{ number_format($live_exchange_rate, 2, '.', '') }}"
                                style="height: 42px;">
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row">
                    {{-- PESOS section --}}
                    <div class="col-md-6">
                        <div style="background: #e8f5e9; padding: 10px; border-radius: 6px;">
                            <h4 style="margin: 0 0 12px 0; text-align: center;"><i class="fas fa-money-bill"></i> PESOS (MXN)</h4>
                            @php
                                $mxn_denoms = [20, 50, 100, 200, 500, 1000];
                            @endphp
                            @foreach($mxn_denoms as $dnm)
                                <div class="form-group" style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                    <label style="width: 70px; margin: 0; font-size: 18px; font-weight: bold;">${{ $dnm }}</label>
                                    <input type="number" min="0" step="1" class="form-control cash-denom-count"
                                        data-currency="mxn" data-denom="{{ $dnm }}" placeholder="0"
                                        style="font-size: 16px; height: 38px;">
                                </div>
                            @endforeach
                            <div class="form-group" style="display: flex; align-items: center; gap: 10px;">
                                <label style="width: 70px; margin: 0; font-size: 14px; font-weight: bold;">@lang('lang_v1.coins')</label>
                                <input type="number" min="0" step="0.01" class="form-control" id="cash_coins_mxn"
                                    placeholder="0.00" style="font-size: 16px; height: 38px;">
                            </div>
                            <div class="text-right" style="margin-top: 12px; font-weight: bold; font-size: 16px;">
                                @lang('lang_v1.subtotal'): <span id="cash_subtotal_mxn">$0.00</span>
                            </div>
                        </div>
                    </div>

                    {{-- DÓLARES section --}}
                    <div class="col-md-6">
                        <div style="background: #e3f2fd; padding: 10px; border-radius: 6px;">
                            <h4 style="margin: 0 0 12px 0; text-align: center;"><i class="fas fa-dollar-sign"></i> DÓLARES (USD)</h4>
                            @php
                                $usd_denoms = [1, 5, 10, 20, 50, 100];
                            @endphp
                            @foreach($usd_denoms as $dnm)
                                <div class="form-group" style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                    <label style="width: 70px; margin: 0; font-size: 18px; font-weight: bold;">${{ $dnm }}</label>
                                    <input type="number" min="0" step="1" class="form-control cash-denom-count"
                                        data-currency="usd" data-denom="{{ $dnm }}" placeholder="0"
                                        style="font-size: 16px; height: 38px;">
                                </div>
                            @endforeach
                            <div class="form-group" style="display: flex; align-items: center; gap: 10px;">
                                <label style="width: 70px; margin: 0; font-size: 14px; font-weight: bold;">@lang('lang_v1.coins')</label>
                                <input type="number" min="0" step="0.01" class="form-control" id="cash_coins_usd"
                                    placeholder="0.00" style="font-size: 16px; height: 38px;">
                            </div>
                            <div class="text-right" style="margin-top: 12px; font-weight: bold; font-size: 16px;">
                                @lang('lang_v1.subtotal'): <span id="cash_subtotal_usd">$0.00</span>
                                <br>
                                <small class="text-muted">@lang('lang_v1.equivalent_in') MXN:
                                    <strong id="cash_usd_to_mxn">$0.00</strong>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6 text-right">
                        <h3>@lang('lang_v1.total_paying'):
                            <strong id="cash_modal_total_received" class="text-primary">$0.00</strong>
                        </h3>
                    </div>
                    <div class="col-md-6 text-right">
                        <h3>@lang('lang_v1.change_return'):
                            <strong id="cash_modal_change" class="text-danger">$0.00</strong>
                        </h3>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
                <button type="button" class="tw-dw-btn tw-text-white" id="cash_modal_pay_btn"
                    style="background-color: #28a745;"><i class="fas fa-check"></i> @lang('lang_v1.pay')</button>
            </div>
        </div>
    </div>
</div>
