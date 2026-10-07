<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WarrantyClaim extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'claim_date' => 'datetime',
        'refund_denomination_breakdown' => 'array',
        'price_difference_denomination_breakdown' => 'array',
    ];

    public function contact()
    {
        return $this->belongsTo(\App\Contact::class, 'contact_id');
    }

    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    public function originalSell()
    {
        return $this->belongsTo(\App\Transaction::class, 'original_sell_transaction_id');
    }

    public function expenseTransaction()
    {
        return $this->belongsTo(\App\Transaction::class, 'expense_transaction_id');
    }

    public function paymentTransaction()
    {
        return $this->belongsTo(\App\Transaction::class, 'payment_transaction_id');
    }

    // Cadena de garantías: cuando el equipo defectuoso de ESTE claim llegó
    // al cliente como replacement de otro, parent_claim_id apunta a ese.
    public function parentClaim()
    {
        return $this->belongsTo(self::class, 'parent_claim_id');
    }

    // Garantías que nacieron porque este claim entregó un equipo defectuoso.
    public function childClaims()
    {
        return $this->hasMany(self::class, 'parent_claim_id');
    }

    /**
     * Sube la cadena desde este claim hasta la raíz y devuelve TODOS los claims
     * en orden cronológico (incluido éste). Si no tiene padre, devuelve [éste].
     * Pensado para pintar el timeline del historial.
     *
     * @return \Illuminate\Support\Collection
     */
    public function chainToRoot()
    {
        $chain = [$this];
        $current = $this;
        $safety = 0;
        while ($current->parent_claim_id && $safety++ < 50) {
            $parent = self::find($current->parent_claim_id);
            if (!$parent) break;
            array_unshift($chain, $parent);
            $current = $parent;
        }
        return collect($chain);
    }

    /**
     * Precio efectivo que el cliente pagó por el equipo que ahora está devolviendo
     * en ESTE claim. Es la base para calcular price_difference / refund_amount:
     *
     *   root sell price + Σ price_difference de todas las garantías previas
     *
     * Si el claim es la primera de la cadena (parent_claim_id = NULL), es
     * simplemente el precio de la venta original.
     */
    public function effectivePaidPrice(): float
    {
        $root_sell = $this->originalSell;
        if (!$root_sell) return 0.0;
        $root_line = \App\TransactionSellLine::where('transaction_id', $root_sell->id)
            ->where('variation_id', $this->resolveRootVariationId())
            ->first();
        $price = $root_line ? (float) $root_line->unit_price_inc_tax : 0.0;

        // Sumar price_difference de todos los ancestros (padres), NO de este claim.
        $ancestor = $this->parentClaim;
        $safety = 0;
        while ($ancestor && $safety++ < 50) {
            if ($ancestor->status !== 'cancelled' && $ancestor->price_difference !== null) {
                $price += (float) $ancestor->price_difference;
            }
            $ancestor = $ancestor->parentClaim;
        }
        return round($price, 2);
    }

    /**
     * El variation_id del equipo que compró originalmente el cliente (en la venta raíz).
     * Para claims sin padre es el original_variation_id de este claim; para claims
     * con padre, es el original_variation_id del ancestro raíz.
     */
    public function resolveRootVariationId(): ?int
    {
        $current = $this;
        $safety = 0;
        while ($current->parent_claim_id && $safety++ < 50) {
            $parent = self::find($current->parent_claim_id);
            if (!$parent) break;
            $current = $parent;
        }
        return $current->original_variation_id ? (int) $current->original_variation_id : null;
    }

    /** ¿La cadena de este claim terminó en un refund (bloquea nuevas garantías)? */
    public function chainEndedInRefund(): bool
    {
        // Buscar hojas (claims sin child) de la cadena a la que pertenece este claim.
        $root = $this;
        $safety = 0;
        while ($root->parent_claim_id && $safety++ < 50) {
            $next = self::find($root->parent_claim_id);
            if (!$next) break;
            $root = $next;
        }
        return $this->descendantEndsInRefund($root);
    }

    private function descendantEndsInRefund(self $node): bool
    {
        if ($node->status === 'completed' && $node->claim_type === 'refund') return true;
        foreach ($node->childClaims as $child) {
            if ($this->descendantEndsInRefund($child)) return true;
        }
        return false;
    }

    public static function claimTypeLabel($type)
    {
        return [
            'refund' => 'Reembolso en efectivo/tarjeta',
            'replacement_same' => 'Cambio por mismo equipo (garantía)',
            'replacement_higher' => 'Cambio por equipo de mayor valor',
            'replacement_lower' => 'Cambio por equipo de menor valor',
        ][$type] ?? $type;
    }
}
