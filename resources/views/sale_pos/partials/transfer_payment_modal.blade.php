<div class="modal fade" tabindex="-1" role="dialog" id="transfer_payment_modal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #6c757d; color: white;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fas fa-exchange-alt"></i> @lang('lang_v1.bank_transfer')</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 text-center" style="margin-bottom: 15px;">
                        <h3 style="margin: 0;">@lang('sale.total_payable'):
                            <strong id="transfer_modal_total_payable" class="text-success">$0.00</strong>
                        </h3>
                    </div>
                </div>

                <div class="form-group">
                    {!! Form::label('transfer_reference', __('lang_v1.transaction_no') . ':*') !!}
                    <input type="text" class="form-control" id="transfer_reference"
                        placeholder="@lang('lang_v1.transaction_no')" required>
                </div>

                <div class="form-group">
                    {!! Form::label('transfer_sale_note', __('sale.sell_note') . ':') !!}
                    <textarea class="form-control" id="transfer_sale_note" rows="3"
                        placeholder="@lang('sale.sell_note')"></textarea>
                </div>

                <div class="form-group">
                    {!! Form::label('transfer_staff_note', __('sale.staff_note') . ':') !!}
                    <textarea class="form-control" id="transfer_staff_note" rows="3"
                        placeholder="@lang('sale.staff_note')"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
                <button type="button" class="tw-dw-btn tw-text-white" id="transfer_modal_pay_btn"
                    style="background-color: #6c757d;"><i class="fas fa-check"></i> @lang('lang_v1.pay')</button>
            </div>
        </div>
    </div>
</div>
