<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => route('card-terminals.update', [$terminal->id]), 'method' => 'put', 'id' => 'card_terminal_form']) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('lang_v1.edit_card_terminal')</h4>
        </div>

        <div class="modal-body">
            <div class="form-group">
                {!! Form::label('name', __('lang_v1.terminal_name') . ':*') !!}
                {!! Form::text('name', $terminal->name, ['class' => 'form-control', 'required']) !!}
            </div>

            <div class="form-group">
                {!! Form::label('bank', __('lang_v1.bank') . ':') !!}
                {!! Form::text('bank', $terminal->bank, ['class' => 'form-control']) !!}
            </div>

            <div class="form-group">
                {!! Form::label('account_number', __('lang_v1.account_number') . ':') !!}
                {!! Form::text('account_number', $terminal->account_number, ['class' => 'form-control']) !!}
            </div>

            <div class="form-group">
                <label>
                    {!! Form::checkbox('is_active', 1, $terminal->is_active, ['class' => 'input-icheck']) !!}
                    <strong>@lang('lang_v1.is_active')</strong>
                </label>
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang('messages.update')</button>
            <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}
    </div>
</div>
