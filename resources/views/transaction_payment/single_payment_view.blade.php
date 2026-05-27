<div class="modal-dialog" role="document">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title no-print">
        @lang( 'lang_v1.view_payment' )
        @if(!empty($single_payment_line->payment_ref_no))
          ( @lang('purchase.ref_no'): {{ $single_payment_line->payment_ref_no }} )
        @endif
      </h4>
      <h4 class="modal-title visible-print-block">
        @if(!empty($single_payment_line->payment_ref_no))
          ( @lang('purchase.ref_no'): {{ $single_payment_line->payment_ref_no }} )
        @endif
      </h4>
    </div>
    <div class="modal-body">
      @if(!empty($transaction))
      <div class="row">
        @if(in_array($transaction->type, ['purchase', 'purchase_return']))
            <div class="col-xs-6">
              @lang('purchase.supplier'):
              <address>
                <strong>{{ $transaction->contact->supplier_business_name }}</strong>
                {{ $transaction->contact->name }}
                {!! $transaction->contact->contact_address !!}
                @if(!empty($transaction->contact->tax_number))
                  <br>@lang('contact.tax_no'): {{$transaction->contact->tax_number}}
                @endif
                @if(!empty($transaction->contact->mobile))
                  <br>@lang('contact.mobile'): {{$transaction->contact->mobile}}
                @endif
                @if(!empty($transaction->contact->email))
                  <br>@lang('business.email'): {{$transaction->contact->email}}
                @endif
              </address>
            </div>
            <div class="col-xs-6">
              @lang('business.business'):
              <address>
                <strong>{{ $transaction->business->name }}</strong>

                @if(!empty($transaction->location))
                  {{ $transaction->location->name }}
                  @if(!empty($transaction->location->landmark))
                    <br>{{$transaction->location->landmark}}
                  @endif
                  @if(!empty($transaction->location->city) || !empty($transaction->location->state) || !empty($transaction->location->country))
                    <br>{{implode(',', array_filter([$transaction->location->city, $transaction->location->state, $transaction->location->country]))}}
                  @endif
                @endif
                
                @if(!empty($transaction->business->tax_number_1))
                  <br>{{$transaction->business->tax_label_1}}: {{$transaction->business->tax_number_1}}
                @endif

                @if(!empty($transaction->business->tax_number_2))
                  <br>{{$transaction->business->tax_label_2}}: {{$transaction->business->tax_number_2}}
                @endif

                @if(!empty($transaction->location))
                  @if(!empty($transaction->location->mobile))
                    <br>@lang('contact.mobile'): {{$transaction->location->mobile}}
                  @endif
                  @if(!empty($transaction->location->email))
                    <br>@lang('business.email'): {{$transaction->location->email}}
                  @endif
                @endif
              </address>
            </div>
        @else
          <div class="col-xs-6">
            @if($transaction->type != 'payroll' && !empty($transaction->contact))
              @lang('contact.customer'):
              <address>
                <strong>{{ $transaction->contact->name ?? '' }}</strong>
               
                {!! $transaction->contact->contact_address !!}
                @if(!empty($transaction->contact->tax_number))
                  <br>@lang('contact.tax_no'): {{$transaction->contact->tax_number}}
                @endif
                @if(!empty($transaction->contact->mobile))
                  <br>@lang('contact.mobile'): {{$transaction->contact->mobile}}
                @endif
                @if(!empty($transaction->contact->email))
                  <br>@lang('business.email'): {{$transaction->contact->email}}
                @endif
              </address>
            @else
            @if(!empty($transaction->transaction_for))
              @lang('essentials::lang.payroll_for'):
              <address>
                  <strong>{{ $transaction->transaction_for->user_full_name }}</strong>
                  @if(!empty($transaction->transaction_for->address))
                      <br>{{$transaction->transaction_for->address}}
                  @endif
                  @if(!empty($transaction->transaction_for->contact_number))
                      <br>@lang('contact.mobile'): {{$transaction->transaction_for->contact_number}}
                  @endif
                  @if(!empty($transaction->transaction_for->email))
                      <br>@lang('business.email'): {{$transaction->transaction_for->email}}
                  @endif
              </address>
            @endif
            @endif
          </div>
          <div class="col-xs-6">
            @lang('business.business'):
            <address>
              <strong>{{ $transaction->business->name }}</strong>
              @if(!empty($transaction->location))
                {{ $transaction->location->name }}
                @if(!empty($transaction->location->landmark))
                  <br>{{$transaction->location->landmark}}
                @endif
                @if(!empty($transaction->location->city) || !empty($transaction->location->state) || !empty($transaction->location->country))
                  <br>{{implode(',', array_filter([$transaction->location->city, $transaction->location->state, $transaction->location->country]))}}
                @endif
              @endif
              
              @if(!empty($transaction->business->tax_number_1))
                <br>{{$transaction->business->tax_label_1}}: {{$transaction->business->tax_number_1}}
              @endif

              @if(!empty($transaction->business->tax_number_2))
                <br>{{$transaction->business->tax_label_2}}: {{$transaction->business->tax_number_2}}
              @endif

              @if(!empty($transaction->location))
                @if(!empty($transaction->location->mobile))
                  <br>@lang('contact.mobile'): {{$transaction->location->mobile}}
                @endif
                @if(!empty($transaction->location->email))
                  <br>@lang('business.email'): {{$transaction->location->email}}
                @endif
              @endif
            </address>
          </div>
        @endif
      </div>
      @endif
      <div class="row">
          <br>
          <div class="col-xs-6">
            <strong>@lang('purchase.amount') :</strong>
            @format_currency($single_payment_line->amount)<br>
            <strong>@lang('lang_v1.payment_method') :</strong>
            @php
                $sp_method_label = $payment_types[$single_payment_line->method] ?? '';
                if ($single_payment_line->method == 'card' && !empty($single_payment_line->card_type)) {
                    $sp_card_labels = [
                        'debit' => __('lang_v1.debit_card'),
                        'credit' => __('lang_v1.credit_card'),
                        'amex' => __('lang_v1.american_express'),
                    ];
                    $sp_method_label = $sp_card_labels[$single_payment_line->card_type] ?? $sp_method_label;
                }
                $sp_terminal_name = null;
                if ($single_payment_line->method == 'card' && !empty($single_payment_line->card_terminal_id)) {
                    $sp_terminal_name = \App\CardTerminal::where('id', $single_payment_line->card_terminal_id)->value('name');
                }
            @endphp
            {{ $sp_method_label }}<br>
            @if(!empty($sp_terminal_name))
                <strong>@lang('lang_v1.card_terminal') :</strong> {{ $sp_terminal_name }}<br>
            @endif
            @if($single_payment_line->method == "card")
              <strong>@lang('lang_v1.card_holder_name') :</strong>
              {{ $single_payment_line->card_holder_name }} <br>
              <strong>@lang('lang_v1.card_number') :</strong>
              {{ $single_payment_line->card_number }} <br>
              <strong>@lang('lang_v1.card_transaction_number') :</strong>
              {{ $single_payment_line->card_transaction_number }}
              
            @elseif($single_payment_line->method == "cheque")
              <strong>@lang('lang_v1.cheque_number') :</strong>
              {{ $single_payment_line->cheque_number }}
            @elseif($single_payment_line->method == "bank_transfer")

            @elseif($single_payment_line->method == "custom_pay_1")

              <strong>@lang('lang_v1.transaction_number') :</strong>
              {{ $single_payment_line->transaction_no }}
            @elseif($single_payment_line->method == "custom_pay_2")

              <strong>@lang('lang_v1.transaction_number') :</strong>
              {{ $single_payment_line->transaction_no }}
            @elseif($single_payment_line->method == "custom_pay_3")

              <strong> @lang('lang_v1.transaction_number'):</strong>
              {{ $single_payment_line->transaction_no }}
            @endif
            <strong>@lang('purchase.payment_note') :</strong>
              {{ $single_payment_line->note }}

            @php
              $denomination_breakdown = null;
              if (!empty($single_payment_line->denomination_breakdown)) {
                  $denomination_breakdown = is_array($single_payment_line->denomination_breakdown)
                      ? $single_payment_line->denomination_breakdown
                      : json_decode($single_payment_line->denomination_breakdown, true);
              }
            @endphp
            @if($single_payment_line->method == 'cash' && !empty($denomination_breakdown))
              @php
                $is_new_format = isset($denomination_breakdown['mxn']) || isset($denomination_breakdown['usd']);
                $mxn_bd = $is_new_format ? ($denomination_breakdown['mxn'] ?? []) : $denomination_breakdown;
                $usd_bd = $is_new_format ? ($denomination_breakdown['usd'] ?? []) : [];
                $exchange_rate = $denomination_breakdown['exchange_rate'] ?? null;
              @endphp
              <br><br>
              <strong>@lang('lang_v1.cash_denominations'):</strong>
              @if($exchange_rate)
                <small class="text-muted">— @lang('lang_v1.exchange_rate'): {{ $exchange_rate }}</small>
              @endif

              @if(!empty($mxn_bd))
                <p style="margin-top: 8px; margin-bottom: 4px;"><strong>PESOS (MXN)</strong></p>
                <table class="table table-condensed table-bordered" style="background-color: #e8f5e9;">
                  <thead><tr class="bg-gray"><th class="text-right">@lang('lang_v1.denomination')</th><th class="text-center">@lang('lang_v1.count')</th><th class="text-right">@lang('sale.subtotal')</th></tr></thead>
                  <tbody>
                    @php $mxn_total = 0; @endphp
                    @foreach($mxn_bd as $denom => $count)
                      @php $sub = $denom === 'coins' ? floatval($count) : floatval($denom) * intval($count); $mxn_total += $sub; @endphp
                      <tr>
                        <td class="text-right">@if($denom === 'coins') @lang('lang_v1.coins') @else <span class="display_currency" data-currency_symbol="true">{{ $denom }}</span>@endif</td>
                        <td class="text-center">@if($denom === 'coins') -- @else {{ $count }} @endif</td>
                        <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $sub }}</span></td>
                      </tr>
                    @endforeach
                    <tr class="bg-green"><th colspan="2" class="text-right">Subtotal MXN:</th><th class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $mxn_total }}</span></th></tr>
                  </tbody>
                </table>
              @endif

              @if(!empty($usd_bd))
                <p style="margin-top: 8px; margin-bottom: 4px;"><strong>DÓLARES (USD)</strong></p>
                <table class="table table-condensed table-bordered" style="background-color: #e3f2fd;">
                  <thead><tr class="bg-gray"><th class="text-right">@lang('lang_v1.denomination')</th><th class="text-center">@lang('lang_v1.count')</th><th class="text-right">@lang('sale.subtotal')</th></tr></thead>
                  <tbody>
                    @php $usd_total = 0; @endphp
                    @foreach($usd_bd as $denom => $count)
                      @php $sub = $denom === 'coins' ? floatval($count) : floatval($denom) * intval($count); $usd_total += $sub; @endphp
                      <tr>
                        <td class="text-right">@if($denom === 'coins') @lang('lang_v1.coins') @else ${{ $denom }} @endif</td>
                        <td class="text-center">@if($denom === 'coins') -- @else {{ $count }} @endif</td>
                        <td class="text-right">${{ number_format($sub, 2) }}</td>
                      </tr>
                    @endforeach
                    <tr><th colspan="2" class="text-right">Subtotal USD:</th><th class="text-right">${{ number_format($usd_total, 2) }}</th></tr>
                    @if($exchange_rate)
                      <tr><th colspan="2" class="text-right">@lang('lang_v1.equivalent_in') MXN:</th><th class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $usd_total * $exchange_rate }}</span></th></tr>
                    @endif
                  </tbody>
                </table>
              @endif
            @endif
          </div>
          <div class="col-xs-6">
            <b>@lang('purchase.ref_no'):</b> 
              @if(!empty($single_payment_line->payment_ref_no))
                {{ $single_payment_line->payment_ref_no }}
              @else
                --
              @endif
              <br/>
            <b>@lang('lang_v1.paid_on'):</b> {{ @format_datetime($single_payment_line->paid_on) }}<br/>
            <br>
            @if(!empty($single_payment_line->document_path))
              <a href="{{$single_payment_line->document_path}}" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-accent no-print" download="{{$single_payment_line->document_name}}"><i class="fa fa-download" data-toggle="tooltip" title="{{__('purchase.download_document')}}"></i> {{__('purchase.download_document')}}</a>
            @endif
          </div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white no-print" 
        aria-label="Print" 
          onclick="$(this).closest('div.modal').printThis();">
        <i class="fa fa-print"></i> @lang( 'messages.print' )
      </button>
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white no-print" data-dismiss="modal">@lang( 'messages.close' )
      </button>
    </div>
  </div>
</div>