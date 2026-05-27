<div class="payment_details_div @if( $payment_line['method'] !== 'card' ) {{ 'hide' }} @endif" data-type="card">
	<div class="col-md-6">
		<div class="form-group">
			{!! Form::label("card_type_$row_index", __('lang_v1.card_type') . ':*') !!}
			{!! Form::select("payment[$row_index][card_type]", [
				'debit' => __('lang_v1.debit_card'),
				'credit' => __('lang_v1.credit_card'),
				'amex' => __('lang_v1.american_express'),
			], $payment_line['card_type'] ?? 'debit', [
				'class' => 'form-control card_type_hidden',
				'id' => "card_type_$row_index",
				'style' => 'width:100%;',
			]) !!}
		</div>
	</div>
	@php
		$card_terminals_options = \App\CardTerminal::forDropdown(session('user.business_id'));
	@endphp
	@if(count($card_terminals_options) > 0)
	<div class="col-md-6">
		<div class="form-group">
			{!! Form::label("card_terminal_id_$row_index", __('lang_v1.card_terminal') . ':') !!}
			{!! Form::select("payment[$row_index][card_terminal_id]", $card_terminals_options, $payment_line['card_terminal_id'] ?? null, [
				'class' => 'form-control',
				'id' => "card_terminal_id_$row_index",
				'placeholder' => __('lang_v1.select_terminal'),
				'style' => 'width:100%;',
			]) !!}
		</div>
	</div>
	@endif
</div>
<div class="payment_details_div @if( $payment_line['method'] !== 'cheque' ) {{ 'hide' }} @endif" data-type="cheque" >
	<div class="col-md-12">
		<div class="form-group">
			{!! Form::label("cheque_number_$row_index",__('lang_v1.cheque_no')) !!}
			{!! Form::text("payment[$row_index][cheque_number]", $payment_line['cheque_number'], ['class' => 'form-control', 'placeholder' => __('lang_v1.cheque_no'), 'id' => "cheque_number_$row_index"]); !!}
		</div>
	</div>
</div>
<div class="payment_details_div @if( $payment_line['method'] !== 'bank_transfer' ) {{ 'hide' }} @endif" data-type="bank_transfer" >
	<div class="col-md-12">
		<div class="form-group">
			{!! Form::label("bank_account_number_$row_index",__('lang_v1.bank_account_number')) !!}
			{!! Form::text( "payment[$row_index][bank_account_number]", $payment_line['bank_account_number'], ['class' => 'form-control', 'placeholder' => __('lang_v1.bank_account_number'), 'id' => "bank_account_number_$row_index"]); !!}
		</div>
	</div>
</div>

@for ($i = 1; $i < 8; $i++)
<div class="payment_details_div @if( $payment_line['method'] !== 'custom_pay_' . $i ) {{ 'hide' }} @endif" data-type="custom_pay_{{$i}}" >
	<div class="col-md-12">
		<div class="form-group">
			{!! Form::label("transaction_no_{$i}_{$row_index}", __('lang_v1.transaction_no')) !!}
			{!! Form::text("payment[$row_index][transaction_no_{$i}]", $payment_line['transaction_no'], ['class' => 'form-control', 'placeholder' => __('lang_v1.transaction_no'), 'id' => "transaction_no_{$i}_{$row_index}"]); !!}
		</div>
	</div>
</div>
@endfor