<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Ticket Garantía {{ $claim->ref_no }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        .ticket { max-width: 380px; margin: 0 auto; border: 1px dashed #333; padding: 15px; }
        .center { text-align: center; }
        .right { text-align: right; }
        h2 { margin: 0; font-size: 18px; }
        h3 { margin: 6px 0; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; }
        table.info td { padding: 3px 4px; vertical-align: top; }
        .box { border: 1px solid #666; padding: 8px; margin: 8px 0; }
        .box h4 { margin: 0 0 4px 0; font-size: 13px; background: #f0f0f0; padding: 3px 5px; }
        .motivo { min-height: 40px; }
        .highlight { color: #c62828; font-weight: bold; }
        .print-btn { margin: 15px auto; text-align: center; }
        @media print { .print-btn { display: none; } body { margin: 0; } .ticket { border: none; } }
    </style>
</head>
<body>

<div class="print-btn">
    <button onclick="window.print()">🖨️ Imprimir</button>
</div>

<div class="ticket">
    <div class="center">
        <h2>{{ $business->name ?? 'CELFIX' }}</h2>
        <div>{{ $claim->location->name ?? '' }}</div>
        <div style="font-size:11px;">{{ $claim->location->landmark ?? '' }}</div>
        <hr>
        <h3>TICKET DE GARANTÍA</h3>
        <div><strong>{{ $claim->ref_no }}</strong></div>
        <div style="font-size:11px;">{{ \Carbon\Carbon::parse($claim->claim_date)->format('d/m/Y H:i') }}</div>
    </div>

    <table class="info">
        <tr><td><strong>Cliente:</strong></td><td>{{ $claim->contact->name ?? '—' }}</td></tr>
        @if($claim->contact && $claim->contact->mobile)
            <tr><td><strong>Móvil:</strong></td><td>{{ $claim->contact->mobile }}</td></tr>
        @endif
        <tr><td><strong>Venta original:</strong></td><td>#{{ $claim->originalSell->invoice_no ?? '—' }}</td></tr>
        <tr><td><strong>Atendió:</strong></td><td>{{ $claim->createdBy->first_name ?? '' }} {{ $claim->createdBy->last_name ?? '' }}</td></tr>
    </table>

    <div class="box">
        <h4>MOTIVO</h4>
        <div class="motivo">{{ $claim->motivo }}</div>
    </div>

    <div class="box">
        <h4>TIPO</h4>
        <div>{{ \App\WarrantyClaim::claimTypeLabel($claim->claim_type) }}</div>
    </div>

    <div class="box">
        <h4>EQUIPO DEVUELTO POR EL CLIENTE</h4>
        <div>{{ $claim->original_product_name }}</div>
        @if(!empty($original_imei))
            <div style="font-size: 11px; margin-top: 3px;"><strong>IMEI/SKU:</strong> {{ $original_imei }}</div>
        @endif
    </div>

    @if($claim->replacement_product_name)
        <div class="box">
            <h4>EQUIPO ENTREGADO AL CLIENTE</h4>
            <div>{{ $claim->replacement_product_name }}</div>
            @if(!empty($replacement_imei))
                <div style="font-size: 11px; margin-top: 3px;"><strong>IMEI/SKU:</strong> {{ $replacement_imei }}</div>
            @endif
        </div>
    @endif

    @if(!is_null($claim->refund_amount) || !is_null($claim->price_difference))
        <div class="box">
            <h4>DIFERENCIA / REEMBOLSO</h4>
            <table>
                @if(!is_null($claim->refund_amount))
                    <tr>
                        <td>Reembolso al cliente:</td>
                        <td class="right highlight">-${{ number_format((float) $claim->refund_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Método:</td>
                        <td class="right">{{ strtoupper($claim->refund_method) }}</td>
                    </tr>
                @endif
                @if(!is_null($claim->price_difference))
                    @php $pd = (float) $claim->price_difference; @endphp
                    <tr>
                        <td>{{ $pd >= 0 ? 'Cliente paga:' : 'Se devuelve al cliente:' }}</td>
                        <td class="right" style="color:{{ $pd >= 0 ? '#2e7d32' : '#c62828' }}; font-weight:bold;">
                            {{ $pd >= 0 ? '+' : '' }}${{ number_format($pd, 2) }}
                        </td>
                    </tr>
                    <tr>
                        <td>Método:</td>
                        <td class="right">{{ strtoupper($claim->price_difference_method) }}</td>
                    </tr>
                @endif
            </table>
        </div>
    @endif

    <div style="margin-top:15px; font-size:10px;">
        <div><strong>NOTAS:</strong></div>
        <ul style="padding-left:15px; margin:5px 0;">
            <li>El equipo devuelto por el cliente queda pendiente de revisión.</li>
            <li>Este ticket certifica el registro del reclamo de garantía.</li>
            <li>Conserva este comprobante para cualquier aclaración.</li>
        </ul>
    </div>

    <div style="margin-top:25px;">
        <div style="border-top:1px solid #666; padding-top:5px; text-align:center;">
            Firma del cliente
        </div>
    </div>
</div>

<script>
    // Auto-imprimir al abrir en pestaña nueva
    setTimeout(function () { window.print(); }, 400);
</script>

</body>
</html>
