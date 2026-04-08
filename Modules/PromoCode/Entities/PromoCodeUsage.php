<?php

namespace Modules\PromoCode\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PromoCodeUsage extends Model
{
    use HasFactory;

    protected $table = 'promo_code_usage';

    public $timestamps = false;

    protected $fillable = [
        'promo_company_id',
        'transaction_id',
        'total_discount',
        'applied_at',
    ];

    protected $casts = [
        'total_discount' => 'decimal:4',
        'applied_at' => 'datetime',
    ];

    /**
     * Get the promo company that was used.
     */
    public function promoCompany()
    {
        return $this->belongsTo(PromoCompany::class, 'promo_company_id');
    }

    /**
     * Get the transaction that used the promo code.
     */
    public function transaction()
    {
        return $this->belongsTo(\App\Transaction::class, 'transaction_id');
    }

    /**
     * Create a new usage record.
     */
    public static function recordUsage($promoCompanyId, $transactionId, $totalDiscount)
    {
        return static::create([
            'promo_company_id' => $promoCompanyId,
            'transaction_id' => $transactionId,
            'total_discount' => $totalDiscount,
            'applied_at' => now(),
        ]);
    }

    /**
     * Get usage statistics for a promo company.
     */
    public static function getStatistics($promoCompanyId, $startDate = null, $endDate = null)
    {
        $query = static::where('promo_company_id', $promoCompanyId);

        if ($startDate) {
            $query->where('applied_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('applied_at', '<=', $endDate);
        }

        return [
            'total_uses' => $query->count(),
            'total_discount_amount' => $query->sum('total_discount'),
        ];
    }
}
