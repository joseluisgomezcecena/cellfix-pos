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

            {{-- HISTORIAL DE GARANTÍAS DEL EQUIPO (cadena) --}}
            @php
                $chain = $claim->chainToRoot();
                $children = $claim->childClaims()->orderBy('claim_date')->get();
            @endphp
            @if($chain->count() > 1 || $children->count() > 0)
                <hr>
                <h5><i class="fa fa-link"></i> Historial de garantías de este equipo</h5>
                <p class="text-muted" style="font-size:12px;">
                    Trazabilidad completa de la cadena: venta original → cada garantía previa → ésta → garantías posteriores (si las hay).
                </p>
                @if($chain->first()->parentClaim === null && $chain->first()->originalSell)
                    @php $rootSell = $chain->first()->originalSell; @endphp
                    <div style="padding:8px 12px; margin-bottom:6px; background:#e8f5e9; border-left:4px solid #2e7d32; border-radius:3px;">
                        <strong><i class="fa fa-shopping-cart"></i> Venta original #{{ $rootSell->invoice_no }}</strong>
                        — {{ \Carbon\Carbon::parse($rootSell->transaction_date)->format('d/m/Y H:i') }}
                        @if($chain->first()->original_product_name)
                            <br><small>{{ $chain->first()->original_product_name }}</small>
                        @endif
                    </div>
                @endif
                @foreach($chain as $node)
                    @php
                        $isCurrent = $node->id === $claim->id;
                        $bg = $isCurrent ? '#fff3e0' : '#f5f5f5';
                        $border = $isCurrent ? '#f57c00' : '#9e9e9e';
                    @endphp
                    <div style="padding:8px 12px; margin-bottom:6px; background:{{ $bg }}; border-left:4px solid {{ $border }}; border-radius:3px;">
                        <strong>
                            <i class="fa fa-wrench"></i>
                            @if($isCurrent) [[ ESTA ]] @endif
                            {{ $node->ref_no }}
                            @if($node->status === 'cancelled')<span class="label label-default">Cancelada</span>@endif
                        </strong>
                        — {{ \Carbon\Carbon::parse($node->claim_date)->format('d/m/Y H:i') }}
                        <br>
                        <small>
                            {{ \App\WarrantyClaim::claimTypeLabel($node->claim_type) }}
                            @if($node->replacement_product_name) → <strong>{{ $node->replacement_product_name }}</strong>@endif
                            @if(!is_null($node->price_difference))
                                @php $pd = (float) $node->price_difference; @endphp
                                · <span style="color:{{ $pd >= 0 ? '#2e7d32' : '#c62828' }};">
                                    {{ $pd >= 0 ? '+' : '' }}${{ number_format($pd, 2) }}
                                </span>
                            @endif
                            @if(!is_null($node->refund_amount))
                                · <span style="color:#c62828;">-${{ number_format((float) $node->refund_amount, 2) }} reembolso</span>
                            @endif
                            @if(!$isCurrent)
                                <a href="#" data-href="{{ route('warranty-claims.show', $node->id) }}" class="btn-modal" data-container=".view_modal" style="margin-left:8px;">(ver)</a>
                            @endif
                        </small>
                    </div>
                @endforeach
                @foreach($children as $child)
                    <div style="padding:8px 12px; margin-bottom:6px; background:#f5f5f5; border-left:4px solid #9e9e9e; border-radius:3px;">
                        <strong><i class="fa fa-wrench"></i> {{ $child->ref_no }}
                            @if($child->status === 'cancelled')<span class="label label-default">Cancelada</span>@endif
                        </strong>
                        — {{ \Carbon\Carbon::parse($child->claim_date)->format('d/m/Y H:i') }}
                        <br>
                        <small>
                            {{ \App\WarrantyClaim::claimTypeLabel($child->claim_type) }}
                            @if($child->replacement_product_name) → <strong>{{ $child->replacement_product_name }}</strong>@endif
                            <a href="#" data-href="{{ route('warranty-claims.show', $child->id) }}" class="btn-modal" data-container=".view_modal" style="margin-left:8px;">(ver)</a>
                        </small>
                    </div>
                @endforeach

                @if($claim->parentClaim && $claim->parentClaim->status === 'cancelled')
                    <div class="alert alert-warning" style="margin-top:6px; padding:8px;">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>Aviso:</strong> la garantía padre ({{ $claim->parentClaim->ref_no }}) fue cancelada.
                        Esta garantía sigue activa pero su cadena quedó rota.
                    </div>
                @endif
            @endif
        </div>
        <div class="modal-footer">
            <a href="{{ route('warranty-claims.print', $claim->id) }}" target="_blank" class="btn btn-primary">
                <i class="fa fa-print"></i> Imprimir ticket
            </a>
            <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
        </div>
    </div>
</div>
