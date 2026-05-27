<div class="modal fade" tabindex="-1" role="dialog" id="card_simple_modal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" id="card_simple_modal_header" style="background-color: #2563eb; color: white;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fas fa-credit-card"></i> <span id="card_simple_modal_title">@lang('lang_v1.card')</span></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 text-center" style="margin-bottom: 15px;">
                        <h3 style="margin: 0;">@lang('sale.total_payable'):
                            <strong id="card_simple_modal_total_payable" class="text-success">$0.00</strong>
                        </h3>
                    </div>
                </div>

                <div class="form-group">
                    {!! Form::label('card_simple_amount', __('sale.amount') . ':*') !!}
                    <input type="number" class="form-control" id="card_simple_amount" min="0.01" step="0.01"
                        placeholder="0.00" style="font-size: 22px; height: 48px; text-align: center;" required>
                </div>

                @php
                    $card_terminals = \App\CardTerminal::forDropdown(session('user.business_id'));
                @endphp
                @if(count($card_terminals) > 0)
                <div class="form-group">
                    {!! Form::label('card_simple_terminal', __('lang_v1.card_terminal') . ':') !!}
                    {!! Form::select('card_simple_terminal', $card_terminals, null, [
                        'class' => 'form-control',
                        'id' => 'card_simple_terminal',
                        'placeholder' => __('lang_v1.select_terminal'),
                        'style' => 'width:100%;',
                    ]) !!}
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
                <button type="button" class="tw-dw-btn tw-text-white" id="card_simple_modal_pay_btn"
                    style="background-color: #2563eb;"><i class="fas fa-check"></i> @lang('lang_v1.pay')</button>
            </div>
        </div>
    </div>
</div>
