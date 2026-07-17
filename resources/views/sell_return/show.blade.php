<div class="modal-dialog modal-xl no-print" role="document">
  <div class="modal-content">
    <div class="modal-header">
    <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title" id="modalTitle"> @lang('lang_v1.sell_return') (<b>@lang('sale.invoice_no'):</b> {{ $sell->return_parent->invoice_no }})
    </h4>
</div>
<div class="modal-body">
   <div class="row">
      <div class="col-sm-6 col-xs-6">
        <h4>@lang('lang_v1.sell_return_details'):</h4>
        <strong>@lang('lang_v1.return_date'):</strong> {{@format_date($sell->return_parent->transaction_date)}}<br>
        <strong>@lang('contact.customer'):</strong> {{ $sell->contact->name }} <br>
        <strong>@lang('purchase.business_location'):</strong> {{ $sell->location->name }}
      </div>
      <div class="col-sm-6 col-xs-6">
        <h4>@lang('lang_v1.sell_details'):</h4>
        <strong>@lang('sale.invoice_no'):</strong> {{ $sell->invoice_no }} <br>
        <strong>@lang('messages.date'):</strong> {{@format_date($sell->transaction_date)}}
      </div>
    </div>
    <br>
    <div class="row">
      <div class="col-sm-12">
        <br>
        <table class="table bg-gray">
          <thead>
            <tr class="bg-green">
                <th>#</th>
                <th>@lang('product.product_name')</th>
                <th>@lang('sale.unit_price')</th>
                <th>@lang('lang_v1.return_quantity')</th>
                <th>@lang('lang_v1.return_subtotal')</th>
            </tr>
        </thead>
        <tbody>
            @php
              $total_before_tax = 0;
            @endphp
            @foreach($sell->sell_lines as $sell_line)

            @if($sell_line->quantity_returned == 0)
                @continue
            @endif

            @php
              $unit_name = $sell_line->product->unit->short_name;

              if(!empty($sell_line->sub_unit)) {
                $unit_name = $sell_line->sub_unit->short_name;
              }
            @endphp

            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>
                  {{ $sell_line->product->name }}
                  @if( $sell_line->product->type == 'variable')
                    - {{ $sell_line->variations->product_variation->name}}
                    - {{ $sell_line->variations->name}}
                  @endif
                </td>
                <td><span class="display_currency" data-currency_symbol="true">{{ $sell_line->unit_price_inc_tax }}</span></td>
                <td>{{@format_quantity($sell_line->quantity_returned)}} {{$unit_name}}</td>
                <td>
                  @php
                    $line_total = $sell_line->unit_price_inc_tax * $sell_line->quantity_returned;
                    $total_before_tax += $line_total ;
                  @endphp
                  <span class="display_currency" data-currency_symbol="true">{{$line_total}}</span>
                </td>
            </tr>
            @endforeach
          </tbody>
      </table>
    </div>
  </div>
  <div class="row">
    <div class="col-sm-6 col-sm-offset-6 col-xs-6 col-xs-offset-6">
      <table class="table">
        <tr>
          <th>@lang('purchase.net_total_amount'): </th>
          <td></td>
          <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $total_before_tax }}</span></td>
        </tr>

        <tr>
          <th>@lang('lang_v1.return_discount'): </th>
          <td><b>(-)</b></td>
          <td class="text-right">@if($sell->return_parent->discount_type == 'percentage')
              @<strong><small>{{$sell->return_parent->discount_amount}}%</small></strong> -
              @endif
          <span class="display_currency pull-right" data-currency_symbol="true">{{ $total_discount }}</span></td>
        </tr>
        
        <tr>
          <th>@lang('lang_v1.total_return_tax'):</th>
          <td><b>(+)</b></td>
          <td class="text-right">
              @if(!empty($sell_taxes))
                @foreach($sell_taxes as $k => $v)
                  <strong><small>{{$k}}</small></strong> - <span class="display_currency pull-right" data-currency_symbol="true">{{ $v }}</span><br>
                @endforeach
              @else
              0.00
              @endif
            </td>
        </tr>
        <tr>
          <th>@lang('lang_v1.return_total'):</th>
          <td></td>
          <td><span class="display_currency pull-right" data-currency_symbol="true" >{{ $sell->return_parent->final_total }}</span></td>
        </tr>
      </table>
    </div>
  </div>

  {{-- Estado del reembolso: cuánto se le pagó al cliente, con qué método, y si queda pendiente.
       Sin esto el gerente no podía distinguir en el detalle una devolución Pagada de una
       Parcial, aunque la lista sí lo mostraba.
       OJO: en este controller $sell es la venta PADRE; la devolución es $sell->return_parent
       (nombres invertidos en UltimatePOS). Los pagos del reembolso viven en la devolución. --}}
  @php
      $return_tx = $sell->return_parent;
      $refund_payments = $return_tx ? $return_tx->payment_lines()->where('is_return', 0)->get() : collect();
      $refunded_total = $refund_payments->sum('amount');
      $return_total = $return_tx ? (float) $return_tx->final_total : 0;
      $pending = max(0, $return_total - $refunded_total);
      $method_labels = [
          'cash' => ['Efectivo', '#fbc02d', '#000'],
          'card' => ['Tarjeta', '#1976d2', '#fff'],
          'bank_transfer' => ['Transferencia', '#607d8b', '#fff'],
          'cheque' => ['Cheque', '#455a64', '#fff'],
          'other' => ['Otro / crédito', '#9e9e9e', '#fff'],
      ];
      $status = $return_tx ? $return_tx->payment_status : null;
      $status_labels = [
          'paid' => ['Pagado', '#2e7d32'],
          'partial' => ['Parcial', '#f57c00'],
          'due' => ['Debido', '#c62828'],
      ];
      $status_info = $status_labels[$status] ?? ['—', '#757575'];
  @endphp
  <div class="row" style="margin-top:15px;">
    <div class="col-md-12">
      <div style="padding:12px; background:#f8f9fa; border-left:4px solid {{ $status_info[1] }}; border-radius:4px;">
        <div style="margin-bottom:8px;">
          <strong>Estado del reembolso al cliente:</strong>
          <span class="label" style="background:{{ $status_info[1] }}; color:#fff; padding:4px 10px; font-size:13px;">{{ $status_info[0] }}</span>
        </div>
        <table class="table table-condensed" style="margin-bottom:0;">
          <tr>
            <td><strong>Total a devolver:</strong></td>
            <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $return_total }}</span></td>
          </tr>
          @foreach($refund_payments as $rp)
            @php $mlbl = $method_labels[$rp->method] ?? $method_labels['other']; @endphp
            <tr>
              <td>
                Reembolsado — <span class="label" style="background:{{ $mlbl[1] }}; color:{{ $mlbl[2] }};">{{ $mlbl[0] }}</span>
                <small class="text-muted">{{ \Carbon\Carbon::parse($rp->paid_on)->format('d/m/Y H:i') }}</small>
              </td>
              <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $rp->amount }}</span></td>
            </tr>
          @endforeach
          @if($refund_payments->isEmpty())
            <tr><td colspan="2" class="text-muted text-center"><em>Sin pagos registrados</em></td></tr>
          @endif
          <tr style="border-top:2px solid #ddd;">
            <td><strong>Pendiente de entregar al cliente:</strong></td>
            <td class="text-right"><strong style="color:{{ $pending > 0.01 ? '#c62828' : '#2e7d32' }};"><span class="display_currency" data-currency_symbol="true">{{ $pending }}</span></strong></td>
          </tr>
        </table>
      </div>
    </div>
  </div>

  <div class="row" style="margin-top:15px;">
    <div class="col-md-12">
          <strong>@lang('lang_v1.activities'):</strong><br>
          @includeIf('activity_log.activities', ['activity_type' => 'sell'])
      </div>
  </div>
</div>
<div class="modal-footer">
    <a href="#" class="print-invoice tw-dw-btn tw-dw-btn-primary tw-text-white" data-href="{{action([\App\Http\Controllers\SellReturnController::class, 'printInvoice'], [$sell->return_parent->id])}}"><i class="fa fa-print" aria-hidden="true"></i> @lang("messages.print")</a>
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