<div class="modal-dialog modal-xl no-print" role="document">
  <div class="modal-content">
    <div class="modal-header">
    <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title" id="modalTitle"> @lang('sale.sell_details') (<b>@if($sell->type == 'sales_order') @lang('restaurant.order_no') @else @lang('sale.invoice_no') @endif :</b> {{ $sell->invoice_no }})
    </h4>
</div>
<div class="modal-body">
    <div class="row">
      <div class="col-xs-12">
          <p class="pull-right"><b>@lang('messages.date'):</b> {{ @format_date($sell->transaction_date) }}</p>
      </div>
    </div>
    <div class="row">
      @php
        $custom_labels = json_decode(session('business.custom_labels'), true);
        $export_custom_fields = [];
        if (!empty($sell->is_export) && !empty($sell->export_custom_fields_info)) {
            $export_custom_fields = $sell->export_custom_fields_info;
        }
      @endphp
      <div class="@if(!empty($export_custom_fields)) col-sm-3 @else col-sm-4 @endif">
        <b>@if($sell->type == 'sales_order') {{ __('restaurant.order_no') }} @else {{ __('sale.invoice_no') }} @endif:</b> #{{ $sell->invoice_no }}<br>
        <b>{{ __('sale.status') }}:</b> 
          @if($sell->status == 'draft' && $sell->is_quotation == 1)
            {{ __('lang_v1.quotation') }}
          @else
            {{ $statuses[$sell->status] ?? __('sale.' . $sell->status) }}
          @endif
        <br>
        @if($sell->type != 'sales_order')
          <b>{{ __('sale.payment_status') }}:</b> @if(!empty($sell->payment_status)){{ __('lang_v1.' . $sell->payment_status) }}
          @endif
        @endif
        @if(!empty($custom_labels['sell']['custom_field_1']))
          <br><strong>{{$custom_labels['sell']['custom_field_1'] ?? ''}}: </strong> {{$sell->custom_field_1}}
        @endif
        @if(!empty($custom_labels['sell']['custom_field_2']))
          <br><strong>{{$custom_labels['sell']['custom_field_2'] ?? ''}}: </strong> {{$sell->custom_field_2}}
        @endif
        @if(!empty($custom_labels['sell']['custom_field_3']))
          <br><strong>{{$custom_labels['sell']['custom_field_3'] ?? ''}}: </strong> {{$sell->custom_field_3}}
        @endif
        @if(!empty($custom_labels['sell']['custom_field_4']))
          <br><strong>{{$custom_labels['sell']['custom_field_4'] ?? ''}}: </strong> {{$sell->custom_field_4}}
        @endif

        @if(!empty($sales_orders))
              <br><br><strong>@lang('lang_v1.sales_orders'):</strong>
             <table class="table table-slim no-border">
               <tr>
                 <th>@lang('lang_v1.sales_order')</th>
                 <th>@lang('lang_v1.date')</th>
               </tr>
               @foreach($sales_orders as $so)
                <tr>
                  <td>{{$so->invoice_no}}</td>
                  <td>{{@format_datetime($so->transaction_date)}}</td>
                </tr>
               @endforeach
             </table>
          @endif
        @if($sell->document_path)
          <br>
          <br>
          <a href="{{$sell->document_path}}" 
          download="{{$sell->document_name}}" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-accent pull-left no-print">
            <i class="fa fa-download"></i> 
              &nbsp;{{ __('purchase.download_document') }}
          </a>
        @endif
      </div>
      <div class="@if(!empty($export_custom_fields)) col-sm-3 @else col-sm-4 @endif">
        @if(!empty($sell->contact->supplier_business_name))
          {{ $sell->contact->supplier_business_name }}<br>
        @endif
        <b>{{ __('sale.customer_name') }}:</b> {{ $sell->contact->name }}<br>
        <b>{{ __('business.address') }}:</b><br>
        @if(!empty($sell->billing_address()))
          {{$sell->billing_address()}}
        @else
          {!! $sell->contact->contact_address !!}
          @if($sell->contact->mobile)
          <br>
              {{__('contact.mobile')}}: {{ $sell->contact->mobile }}
          @endif
          @if($sell->contact->alternate_number)
          <br>
              {{__('contact.alternate_contact_number')}}: {{ $sell->contact->alternate_number }}
          @endif
          @if($sell->contact->landline)
            <br>
              {{__('contact.landline')}}: {{ $sell->contact->landline }}
          @endif
          @if($sell->contact->email)
            <br>
              {{__('business.email')}}: {{ $sell->contact->email }}
          @endif
        @endif
        
      </div>
      <div class="@if(!empty($export_custom_fields)) col-sm-3 @else col-sm-4 @endif">
      @if(in_array('tables' ,$enabled_modules))
         <strong>@lang('restaurant.table'):</strong>
          {{$sell->table->name ?? ''}}<br>
      @endif
      @if(in_array('service_staff' ,$enabled_modules))
          <strong>@lang('restaurant.service_staff'):</strong>
          {{$sell->service_staff->user_full_name ?? ''}}<br>
      @endif

      <strong>@lang('sale.shipping'):</strong>
      <span class="label @if(!empty($shipping_status_colors[$sell->shipping_status])) {{$shipping_status_colors[$sell->shipping_status]}} @else {{'bg-gray'}} @endif">{{$shipping_statuses[$sell->shipping_status] ?? '' }}</span><br>
      @if(!empty($sell->shipping_address()))
        {{$sell->shipping_address()}}
      @else
        {{$sell->shipping_address ?? '--'}}
      @endif
      @if(!empty($sell->delivered_to))
        <br><strong>@lang('lang_v1.delivered_to'): </strong> {{$sell->delivered_to}}
      @endif

      @if(!empty($sell->delivery_person_user->first_name))
        <br><strong>@lang('lang_v1.delivery_person'): </strong> {{$sell->delivery_person_user->surname}} {{$sell->delivery_person_user->first_name}}     {{$sell->delivery_person_user->last_name}}
      @endif

      
      @if(!empty($sell->shipping_custom_field_1))
        <br><strong>{{$custom_labels['shipping']['custom_field_1'] ?? ''}}: </strong> {{$sell->shipping_custom_field_1}}
      @endif
      @if(!empty($sell->shipping_custom_field_2))
        <br><strong>{{$custom_labels['shipping']['custom_field_2'] ?? ''}}: </strong> {{$sell->shipping_custom_field_2}}
      @endif
      @if(!empty($sell->shipping_custom_field_3))
        <br><strong>{{$custom_labels['shipping']['custom_field_3'] ?? ''}}: </strong> {{$sell->shipping_custom_field_3}}
      @endif
      @if(!empty($sell->shipping_custom_field_4))
        <br><strong>{{$custom_labels['shipping']['custom_field_4'] ?? ''}}: </strong> {{$sell->shipping_custom_field_4}}
      @endif
      @if(!empty($sell->shipping_custom_field_5))
        <br><strong>{{$custom_labels['shipping']['custom_field_5'] ?? ''}}: </strong> {{$sell->shipping_custom_field_5}}
      @endif
      @php
        $medias = $sell->media->where('model_media_type', 'shipping_document')->all();
      @endphp
      @if(count($medias))
        @include('sell.partials.media_table', ['medias' => $medias])
      @endif

      @if(in_array('types_of_service' ,$enabled_modules))
        @if(!empty($sell->types_of_service))
          <strong>@lang('lang_v1.types_of_service'):</strong>
          {{$sell->types_of_service->name}}<br>
        @endif
        @if(!empty($sell->types_of_service->enable_custom_fields))
          <strong>{{ $custom_labels['types_of_service']['custom_field_1'] ?? __('lang_v1.service_custom_field_1' )}}:</strong>
          {{$sell->service_custom_field_1}}<br>
          <strong>{{ $custom_labels['types_of_service']['custom_field_2'] ?? __('lang_v1.service_custom_field_2' )}}:</strong>
          {{$sell->service_custom_field_2}}<br>
          <strong>{{ $custom_labels['types_of_service']['custom_field_3'] ?? __('lang_v1.service_custom_field_3' )}}:</strong>
          {{$sell->service_custom_field_3}}<br>
          <strong>{{ $custom_labels['types_of_service']['custom_field_4'] ?? __('lang_v1.service_custom_field_4' )}}:</strong>
          {{$sell->service_custom_field_4}}<br>
          <strong>{{ $custom_labels['types_of_service']['custom_field_5'] ?? __('lang_v1.custom_field', ['number' => 5])}}:</strong>
          {{$sell->service_custom_field_5}}<br>
          <strong>{{ $custom_labels['types_of_service']['custom_field_6'] ?? __('lang_v1.custom_field', ['number' => 6])}}:</strong>
          {{$sell->service_custom_field_6}}
        @endif
      @endif
      </div>
      @if(!empty($export_custom_fields))
          <div class="col-sm-3">
                @foreach($export_custom_fields as $label => $value)
                    <strong>
                        @php
                            $export_label = __('lang_v1.export_custom_field1');
                            if ($label == 'export_custom_field_1') {
                                $export_label =__('lang_v1.export_custom_field1');
                            } elseif ($label == 'export_custom_field_2') {
                                $export_label = __('lang_v1.export_custom_field2');
                            } elseif ($label == 'export_custom_field_3') {
                                $export_label = __('lang_v1.export_custom_field3');
                            } elseif ($label == 'export_custom_field_4') {
                                $export_label = __('lang_v1.export_custom_field4');
                            } elseif ($label == 'export_custom_field_5') {
                                $export_label = __('lang_v1.export_custom_field5');
                            } elseif ($label == 'export_custom_field_6') {
                                $export_label = __('lang_v1.export_custom_field6');
                            }
                        @endphp

                        {{$export_label}}
                        :
                    </strong> {{$value ?? ''}} <br>
                @endforeach
          </div>
      @endif
    </div>
    <br>
    <div class="row">
      <div class="col-sm-12 col-xs-12">
        <h4>{{ __('sale.products') }}:</h4>
      </div>

      <div class="col-sm-12 col-xs-12">
        <div class="table-responsive">
          @include('sale_pos.partials.sale_line_details')
        </div>
      </div>
    </div>
    <div class="row">
      @php
        $total_paid = 0;
      @endphp
      @if($sell->type != 'sales_order')
      <div class="col-sm-12 col-xs-12">
        <h4>{{ __('sale.payment_info') }}:</h4>
      </div>
      <div class="col-md-6 col-sm-12 col-xs-12">
        <div class="table-responsive">
          <table class="table bg-gray">
            <tr class="bg-green">
              <th>#</th>
              <th>{{ __('messages.date') }}</th>
              <th>{{ __('purchase.ref_no') }}</th>
              <th>{{ __('sale.amount') }}</th>
              <th>{{ __('sale.payment_mode') }}</th>
              <th>{{ __('sale.payment_note') }}</th>
            </tr>
            @foreach($sell->payment_lines as $payment_line)
              @php
                if($payment_line->is_return == 1){
                  $total_paid -= $payment_line->amount;
                } else {
                  $total_paid += $payment_line->amount;
                }
                $denomination_breakdown = null;
                if (!empty($payment_line->denomination_breakdown)) {
                    $denomination_breakdown = is_array($payment_line->denomination_breakdown)
                        ? $payment_line->denomination_breakdown
                        : json_decode($payment_line->denomination_breakdown, true);
                }
              @endphp
              <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ @format_date($payment_line->paid_on) }}</td>
                <td>{{ $payment_line->payment_ref_no }}</td>
                <td><span class="display_currency" data-currency_symbol="true">{{ $payment_line->amount }}</span></td>
                <td>
                  @php
                    $method_label = $payment_types[$payment_line->method] ?? $payment_line->method;
                    if ($payment_line->method == 'card' && !empty($payment_line->card_type)) {
                        $card_type_labels = [
                            'debit' => __('lang_v1.debit_card'),
                            'credit' => __('lang_v1.credit_card'),
                            'amex' => __('lang_v1.american_express'),
                        ];
                        $method_label = $card_type_labels[$payment_line->card_type] ?? $method_label;
                    }
                    $terminal_name = null;
                    if ($payment_line->method == 'card' && !empty($payment_line->card_terminal_id)) {
                        $terminal_name = \App\CardTerminal::where('id', $payment_line->card_terminal_id)->value('name');
                    }
                  @endphp
                  {{ $method_label }}
                  @if(!empty($terminal_name))
                    <br/>
                    <small class="text-muted"><i class="fas fa-cash-register"></i> {{ $terminal_name }}</small>
                  @endif
                  @if($payment_line->is_return == 1)
                    <br/>
                    ( {{ __('lang_v1.change_return') }} )
                  @endif
                </td>
                <td>@if($payment_line->note)
                  {{ ucfirst($payment_line->note) }}
                  @else
                  --
                  @endif
                </td>
              </tr>
              @if($payment_line->method == 'cash' && !empty($denomination_breakdown))
                @php
                  // Detect format: new (nested mxn/usd) vs old (flat keys)
                  $is_new_format = isset($denomination_breakdown['mxn']) || isset($denomination_breakdown['usd']);
                  $mxn_bd = $is_new_format ? ($denomination_breakdown['mxn'] ?? []) : $denomination_breakdown;
                  $usd_bd = $is_new_format ? ($denomination_breakdown['usd'] ?? []) : [];
                  $exchange_rate = $denomination_breakdown['exchange_rate'] ?? null;
                @endphp
                <tr>
                  <td colspan="6" style="background-color: #f9f9f9; padding-left: 30px;">
                    <strong>{{ __('lang_v1.cash_denominations') }}:</strong>
                    @if($exchange_rate)
                      <small class="text-muted">— @lang('lang_v1.exchange_rate'): {{ $exchange_rate }}</small>
                    @endif
                    <div class="row" style="margin-top: 8px;">
                      @if(!empty($mxn_bd))
                        <div class="col-md-6">
                          <strong>PESOS (MXN)</strong>
                          <table class="table table-condensed table-bordered" style="background-color: #e8f5e9;">
                            <thead><tr><th class="text-right">@lang('lang_v1.denomination')</th><th class="text-center">@lang('lang_v1.count')</th><th class="text-right">@lang('sale.subtotal')</th></tr></thead>
                            <tbody>
                              @php $mxn_total = 0; @endphp
                              @foreach($mxn_bd as $denom => $count)
                                @php
                                  $sub = $denom === 'coins' ? floatval($count) : floatval($denom) * intval($count);
                                  $mxn_total += $sub;
                                @endphp
                                <tr>
                                  <td class="text-right">
                                    @if($denom === 'coins') @lang('lang_v1.coins')
                                    @else <span class="display_currency" data-currency_symbol="true">{{ $denom }}</span>
                                    @endif
                                  </td>
                                  <td class="text-center">@if($denom === 'coins')--@else {{ $count }}@endif</td>
                                  <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $sub }}</span></td>
                                </tr>
                              @endforeach
                              <tr class="bg-green"><th colspan="2" class="text-right">Subtotal MXN:</th><th class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $mxn_total }}</span></th></tr>
                            </tbody>
                          </table>
                        </div>
                      @endif
                      @if(!empty($usd_bd))
                        <div class="col-md-6">
                          <strong>DÓLARES (USD)</strong>
                          <table class="table table-condensed table-bordered" style="background-color: #e3f2fd;">
                            <thead><tr><th class="text-right">@lang('lang_v1.denomination')</th><th class="text-center">@lang('lang_v1.count')</th><th class="text-right">@lang('sale.subtotal')</th></tr></thead>
                            <tbody>
                              @php $usd_total = 0; @endphp
                              @foreach($usd_bd as $denom => $count)
                                @php
                                  $sub = $denom === 'coins' ? floatval($count) : floatval($denom) * intval($count);
                                  $usd_total += $sub;
                                @endphp
                                <tr>
                                  <td class="text-right">
                                    @if($denom === 'coins') @lang('lang_v1.coins')
                                    @else ${{ $denom }}
                                    @endif
                                  </td>
                                  <td class="text-center">@if($denom === 'coins')--@else {{ $count }}@endif</td>
                                  <td class="text-right">${{ number_format($sub, 2) }}</td>
                                </tr>
                              @endforeach
                              <tr class="bg-blue"><th colspan="2" class="text-right">Subtotal USD:</th><th class="text-right">${{ number_format($usd_total, 2) }}</th></tr>
                              @if($exchange_rate)
                                <tr><th colspan="2" class="text-right">@lang('lang_v1.equivalent_in') MXN:</th><th class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $usd_total * $exchange_rate }}</span></th></tr>
                              @endif
                            </tbody>
                          </table>
                        </div>
                      @endif
                    </div>
                  </td>
                </tr>
              @endif
            @endforeach
          </table>
        </div>
      </div>
      @endif
      <div class="col-md-6 col-sm-12 col-xs-12 @if($sell->type == 'sales_order') col-md-offset-6 @endif">
        <div class="table-responsive">
          <table class="table bg-gray">
            <tr>
              <th>{{ __('sale.total') }}: </th>
              <td></td>
              <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $sell->total_before_tax }}</span></td>
            </tr>
            <tr>
              <th>{{ __('sale.discount') }}:</th>
              <td><b>(-)</b></td>
              <td><div class="pull-right"><span class="display_currency" @if( $sell->discount_type == 'fixed') data-currency_symbol="true" @endif>{{ $sell->discount_amount }}</span> @if( $sell->discount_type == 'percentage') {{ '%'}} @endif</span></div></td>
            </tr>
            @if(in_array('types_of_service' ,$enabled_modules) && !empty($sell->packing_charge))
              <tr>
                <th>{{ __('lang_v1.packing_charge') }}:</th>
                <td><b>(+)</b></td>
                <td><div class="pull-right"><span class="display_currency" @if( $sell->packing_charge_type == 'fixed') data-currency_symbol="true" @endif>{{ $sell->packing_charge }}</span> @if( $sell->packing_charge_type == 'percent') {{ '%'}} @endif </div></td>
              </tr>
            @endif
            @if(session('business.enable_rp') == 1 && !empty($sell->rp_redeemed) )
              <tr>
                <th>{{session('business.rp_name')}}:</th>
                <td><b>(-)</b></td>
                <td> <span class="display_currency pull-right" data-currency_symbol="true">{{ $sell->rp_redeemed_amount }}</span></td>
              </tr>
            @endif
            <tr>
              <th>{{ __('sale.order_tax') }}:</th>
              <td><b>(+)</b></td>
              <td class="text-right">
                @if(!empty($order_taxes))
                  @foreach($order_taxes as $k => $v)
                    <strong><small>{{$k}}</small></strong> - <span class="display_currency pull-right" data-currency_symbol="true">{{ $v }}</span><br>
                  @endforeach
                @else
                0.00
                @endif
              </td>
            </tr>
            @if(!empty($line_taxes))
            <tr>
              <th>{{ __('lang_v1.line_taxes') }}:</th>
              <td></td>
              <td class="text-right">
                @if(!empty($line_taxes))
                  @foreach($line_taxes as $k => $v)
                    <strong><small>{{$k}}</small></strong> - <span class="display_currency pull-right" data-currency_symbol="true">{{ $v }}</span><br>
                  @endforeach
                @else
                0.00
                @endif
              </td>
            </tr>
            @endif
            <tr>
              <th>{{ __('sale.shipping') }}: @if($sell->shipping_details)({{$sell->shipping_details}}) @endif</th>
              <td><b>(+)</b></td>
              <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $sell->shipping_charges }}</span></td>
            </tr>

            @if( !empty( $sell->additional_expense_value_1 )  && !empty( $sell->additional_expense_key_1 ))
              <tr>
                <th>{{ $sell->additional_expense_key_1 }}:</th>
                <td><b>(+)</b></td>
                <td><span class="display_currency pull-right" >{{ $sell->additional_expense_value_1 }}</span></td>
              </tr>
            @endif
            @if( !empty( $sell->additional_expense_value_2 )  && !empty( $sell->additional_expense_key_2 ))
              <tr>
                <th>{{ $sell->additional_expense_key_2 }}:</th>
                <td><b>(+)</b></td>
                <td><span class="display_currency pull-right" >{{ $sell->additional_expense_value_2 }}</span></td>
              </tr>
            @endif
            @if( !empty( $sell->additional_expense_value_3 )  && !empty( $sell->additional_expense_key_3 ))
              <tr>
                <th>{{ $sell->additional_expense_key_3 }}:</th>
                <td><b>(+)</b></td>
                <td><span class="display_currency pull-right" >{{ $sell->additional_expense_value_3 }}</span></td>
              </tr>
            @endif
            @if( !empty( $sell->additional_expense_value_4 ) && !empty( $sell->additional_expense_key_4 ))
              <tr>
                <th>{{ $sell->additional_expense_key_4 }}:</th>
                <td><b>(+)</b></td>
                <td><span class="display_currency pull-right" >{{ $sell->additional_expense_value_4 }}</span></td>
              </tr>
            @endif
            <tr>
              <th>{{ __('lang_v1.round_off') }}: </th>
              <td></td>
              <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $sell->round_off_amount }}</span></td>
            </tr>
            <tr>
              <th>{{ __('sale.total_payable') }}: </th>
              <td></td>
              <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $sell->final_total }}</span></td>
            </tr>
            @if($sell->type != 'sales_order')
            <tr>
              <th>{{ __('sale.total_paid') }}:</th>
              <td></td>
              <td><span class="display_currency pull-right" data-currency_symbol="true" >{{ $total_paid }}</span></td>
            </tr>
            <tr>
              <th>{{ __('sale.total_remaining') }}:</th>
              <td></td>
              <td>
                <!-- Converting total paid to string for floating point substraction issue -->
                @php
                  $total_paid = (string) $total_paid;
                @endphp
                <span class="display_currency pull-right" data-currency_symbol="true" >{{ $sell->final_total - $total_paid }}</span></td>
            </tr>
            @endif
          </table>
        </div>
      </div>
    </div>

    {{-- Sección de devoluciones: si esta venta tiene una devolución asociada,
         muestra el total devuelto, cuánto se reembolsó al cliente y cuánto queda
         pendiente. Antes el modal solo mostraba el total pagado / restante de la
         venta, sin ningún indicador de que existiera una devolución. --}}
    @php
        $sell_return_tx = $sell->return_parent; // hasOne desde la venta padre → la devolución
    @endphp
    @if(! empty($sell_return_tx))
    @php
        $ret_refund_payments = $sell_return_tx->payment_lines()->where('is_return', 0)->get();
        $ret_refunded_total = $ret_refund_payments->sum('amount');
        $ret_total = (float) $sell_return_tx->final_total;
        $ret_pending = max(0, $ret_total - $ret_refunded_total);
        $ret_status = $sell_return_tx->payment_status;
        $ret_status_labels = [
            'paid' => ['Pagado', '#2e7d32'],
            'partial' => ['Parcial', '#f57c00'],
            'due' => ['Debido', '#c62828'],
        ];
        $ret_si = $ret_status_labels[$ret_status] ?? ['—', '#757575'];
        $ret_method_labels = [
            'cash' => ['Efectivo', '#fbc02d', '#000'],
            'card' => ['Tarjeta', '#1976d2', '#fff'],
            'bank_transfer' => ['Transferencia', '#607d8b', '#fff'],
            'cheque' => ['Cheque', '#455a64', '#fff'],
            'other' => ['Otro / crédito', '#9e9e9e', '#fff'],
        ];
    @endphp
    <div class="row" style="margin-top:10px;">
      <div class="col-sm-12">
        <div style="padding:12px; background:#fff3e0; border-left:4px solid #e65100; border-radius:4px;">
          <strong style="font-size:14px;"><i class="fas fa-undo"></i> Devolución aplicada a esta venta</strong>
          <span class="label" style="background:{{ $ret_si[1] }}; color:#fff; padding:3px 8px; margin-left:8px;">{{ $ret_si[0] }}</span>
          <a href="{{ action([\App\Http\Controllers\SellReturnController::class, 'show'], [$sell->id]) }}"
             class="btn-modal" data-container=".view_modal" style="margin-left:8px; font-size:12px;">
             (ver detalle)
          </a>
          <table class="table table-condensed" style="margin-top:8px; margin-bottom:0;">
            <tr>
              <td><strong>Total devuelto:</strong></td>
              <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $ret_total }}</span></td>
            </tr>
            @foreach($ret_refund_payments as $rp)
              @php $mlbl = $ret_method_labels[$rp->method] ?? $ret_method_labels['other']; @endphp
              <tr>
                <td>
                  Reembolsado al cliente — <span class="label" style="background:{{ $mlbl[1] }}; color:{{ $mlbl[2] }};">{{ $mlbl[0] }}</span>
                  <small class="text-muted">{{ \Carbon\Carbon::parse($rp->paid_on)->format('d/m/Y H:i') }}</small>
                </td>
                <td class="text-right"><span style="color:#2e7d32;"><strong><span class="display_currency" data-currency_symbol="true">{{ $rp->amount }}</span></strong></span></td>
              </tr>
            @endforeach
            <tr style="border-top:2px solid #ddd;">
              <td><strong>Pendiente de entregar al cliente:</strong></td>
              <td class="text-right"><strong style="color:{{ $ret_pending > 0.01 ? '#c62828' : '#2e7d32' }};"><span class="display_currency" data-currency_symbol="true">{{ $ret_pending }}</span></strong></td>
            </tr>
          </table>
        </div>
      </div>
    </div>
    @endif
    <div class="row">
      <div class="col-sm-6">
        <strong>{{ __( 'sale.sell_note')}}:</strong><br>
        <p class="well well-sm no-shadow bg-gray">
          @if($sell->additional_notes)
            {!! nl2br($sell->additional_notes) !!}
          @else
            --
          @endif
        </p>
      </div>
      <div class="col-sm-6">
        <strong>{{ __( 'sale.staff_note')}}:</strong><br>
        <p class="well well-sm no-shadow bg-gray">
          @if($sell->staff_note)
            {!! nl2br($sell->staff_note) !!}
          @else
            --
          @endif
        </p>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12">
            <strong>{{ __('lang_v1.activities') }}:</strong><br>
            @includeIf('activity_log.activities', ['activity_type' => 'sell'])
        </div>
    </div>
  </div>
  <div class="modal-footer">
    @if($sell->type != 'sales_order')
    <a href="#" class="print-invoice tw-dw-btn tw-dw-btn-success tw-text-white" data-href="{{route('sell.printInvoice', [$sell->id])}}?package_slip=true"><i class="fas fa-file-alt" aria-hidden="true"></i> @lang("lang_v1.packing_slip")</a>
    @endif
    @can('print_invoice')
      <a href="#" class="print-invoice tw-dw-btn tw-dw-btn-primary tw-text-white" data-href="{{route('sell.printInvoice', [$sell->id])}}"><i class="fa fa-print" aria-hidden="true"></i> @lang("lang_v1.print_invoice")</a>
    @endcan
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white no-print" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>
  </div>
</div>

<script type="text/javascript">
  $(document).ready(function(){
    var element = $('div.modal-xl');
    __currency_convert_recursively(element);
  });
</script>
