<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title">Garantía {{ $claim->ref_no }}
                @if($claim->status === 'cancelled')
                    <span class="label label-default">Cancelada</span>
                @else
                    <span class="label label-success">Completada</span>
                @endif
            </h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($claim->claim_date)->format('d/m/Y H:i') }}</p>
                    <p><strong>Sucursal:</strong> {{ $claim->location->name ?? '—' }}</p>
                    <p><strong>Registrado por:</strong> {{ $claim->createdBy->first_name ?? '—' }} {{ $claim->createdBy->last_name ?? '' }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Venta original:</strong> #{{ $claim->originalSell->invoice_no ?? '—' }}</p>
                    <p><strong>Cliente:</strong> {{ $claim->contact->name ?? '—' }}</p>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-12">
                    <h5>Motivo</h5>
                    <div class="well well-sm">{{ $claim->motivo }}</div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <p><strong>Tipo:</strong> {{ \App\WarrantyClaim::claimTypeLabel($claim->claim_type) }}</p>
                    <p><strong>Equipo devuelto por el cliente:</strong> {{ $claim->original_product_name }}</p>
                    @if($claim->replacement_product_name)
                        <p><strong>Equipo entregado al cliente:</strong> {{ $claim->replacement_product_name }}</p>
                    @endif
                    @if(!is_null($claim->refund_amount))
                        <p><strong>Reembolso:</strong>
                            <span style="color:#c62828;">-${{ number_format((float) $claim->refund_amount, 2) }}</span>
                            ({{ strtoupper($claim->refund_method) }})
                        </p>
                    @endif
                    @if(!is_null($claim->price_difference))
                        @php $pd = (float) $claim->price_difference; @endphp
                        <p><strong>Diferencia:</strong>
                            <span style="color:{{ $pd >= 0 ? '#2e7d32' : '#c62828' }};">
                                {{ $pd >= 0 ? '+' : '' }}${{ number_format($pd, 2) }}
                            </span>
                            ({{ strtoupper($claim->price_difference_method) }})
                        </p>
                    @endif
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <a href="{{ route('warranty-claims.print', $claim->id) }}" target="_blank" class="btn btn-primary">
                <i class="fa fa-print"></i> Imprimir ticket
            </a>
            <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
        </div>
    </div>
</div>
