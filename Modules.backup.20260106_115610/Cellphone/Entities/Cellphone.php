<?php

namespace Modules\Cellphone\Entities;

use App\Product;
use App\Warranty;
use Illuminate\Database\Eloquent\Model;

/**
 * Cellphone Entity - Extends Product functionality for cellphone-specific features
 * This class works with the existing products table using custom fields
 */
class Cellphone extends Product
{
    /**
     * The table associated with the model.
     * Using the products table since we're extending Product model
     *
     * @var string
     */
    protected $table = 'products';

    /**
     * Get field mapping from config
     */
    protected static function getFieldMapping()
    {
        return config('cellphone.field_mapping');
    }

    /**
     * Scope query to only cellphone products
     * Cellphones are identified by the cellphone_flag field
     * This ensures only products created through the Cellphone module are shown
     */
    public function scopeCellphones($query)
    {
        $mapping = self::getFieldMapping();
        $flagValue = config('cellphone.cellphone_flag_value');

        return $query->where($mapping['cellphone_flag'], $flagValue);
    }

    /**
     * Get Marca (Brand)
     */
    public function getMarcaAttribute()
    {
        $field = self::getFieldMapping()['marca'];
        return $this->$field;
    }

    /**
     * Set Marca (Brand)
     */
    public function setMarcaAttribute($value)
    {
        $field = self::getFieldMapping()['marca'];
        $this->attributes[$field] = $value;
    }

    /**
     * Get Modelo (Model)
     */
    public function getModeloAttribute()
    {
        $field = self::getFieldMapping()['modelo'];
        return $this->$field;
    }

    /**
     * Set Modelo (Model)
     */
    public function setModeloAttribute($value)
    {
        $field = self::getFieldMapping()['modelo'];
        $this->attributes[$field] = $value;
    }

    /**
     * Get Ubicacion (Location)
     */
    public function getUbicacionAttribute()
    {
        $field = self::getFieldMapping()['ubicacion'];
        return $this->$field;
    }

    /**
     * Set Ubicacion (Location)
     */
    public function setUbicacionAttribute($value)
    {
        $field = self::getFieldMapping()['ubicacion'];
        $this->attributes[$field] = $value;
    }

    /**
     * Get Estado (Condition)
     */
    public function getEstadoAttribute()
    {
        $field = self::getFieldMapping()['estado'];
        return $this->$field;
    }

    /**
     * Set Estado (Condition)
     */
    public function setEstadoAttribute($value)
    {
        $field = self::getFieldMapping()['estado'];
        $this->attributes[$field] = $value;
    }

    /**
     * Get Observaciones (Notes)
     */
    public function getObservacionesAttribute()
    {
        $field = self::getFieldMapping()['observaciones'];
        return $this->$field;
    }

    /**
     * Set Observaciones (Notes)
     */
    public function setObservacionesAttribute($value)
    {
        $field = self::getFieldMapping()['observaciones'];
        $this->attributes[$field] = $value;
    }

    /**
     * Mark this product as a cellphone
     * Automatically sets the cellphone flag
     */
    public function markAsCellphone()
    {
        $field = self::getFieldMapping()['cellphone_flag'];
        $this->attributes[$field] = config('cellphone.cellphone_flag_value');
    }

    /**
     * Check if this product is marked as a cellphone
     * @return bool
     */
    public function isCellphone()
    {
        $field = self::getFieldMapping()['cellphone_flag'];
        return $this->$field === config('cellphone.cellphone_flag_value');
    }

    /**
     * Get IMEI - stored in SKU field
     */
    public function getImeiAttribute()
    {
        return $this->sku;
    }

    /**
     * Set IMEI - stores in SKU field
     */
    public function setImeiAttribute($value)
    {
        $this->attributes['sku'] = $value;
    }

    /**
     * Validate IMEI format
     * @param string $imei
     * @return bool
     */
    public static function validateImei($imei)
    {
        $pattern = config('cellphone.imei_pattern');
        return preg_match($pattern, $imei);
    }

    /**
     * Check if IMEI is unique
     * @param string $imei
     * @param int|null $excludeProductId
     * @return bool
     */
    public static function isImeiUnique($imei, $excludeProductId = null)
    {
        $query = self::where('sku', $imei);

        if ($excludeProductId) {
            $query->where('id', '!=', $excludeProductId);
        }

        return $query->count() === 0;
    }

    /**
     * Get cellphone statistics for dashboard
     * @param int $business_id
     * @return array
     */
    public static function getStatistics($business_id)
    {
        $mapping = self::getFieldMapping();

        return [
            'total_cellphones' => self::cellphones()->where('business_id', $business_id)->count(),
            'by_marca' => self::cellphones()
                ->where('business_id', $business_id)
                ->selectRaw("{$mapping['marca']} as marca, COUNT(*) as count")
                ->groupBy($mapping['marca'])
                ->get(),
            'by_estado' => self::cellphones()
                ->where('business_id', $business_id)
                ->selectRaw("{$mapping['estado']} as estado, COUNT(*) as count")
                ->groupBy($mapping['estado'])
                ->get(),
            'warranties_expiring' => self::cellphones()
                ->where('business_id', $business_id)
                ->whereNotNull('warranty_id')
                ->with('warranty')
                ->get()
                ->filter(function ($product) {
                    // Filter products with warranties expiring in next 30 days
                    // This would need transaction data to calculate properly
                    return true;
                })
                ->count(),
        ];
    }

    /**
     * Search cellphones
     * @param $query
     * @param array $filters
     * @return mixed
     */
    public function scopeSearchCellphones($query, $filters = [])
    {
        $mapping = self::getFieldMapping();

        $query->cellphones();

        if (!empty($filters['marca'])) {
            $query->where($mapping['marca'], 'like', '%' . $filters['marca'] . '%');
        }

        if (!empty($filters['modelo'])) {
            $query->where($mapping['modelo'], 'like', '%' . $filters['modelo'] . '%');
        }

        if (!empty($filters['imei'])) {
            $query->where('sku', 'like', '%' . $filters['imei'] . '%');
        }

        if (!empty($filters['estado'])) {
            $query->where($mapping['estado'], $filters['estado']);
        }

        if (!empty($filters['warranty_id'])) {
            $query->where('warranty_id', $filters['warranty_id']);
        }

        if (!empty($filters['ubicacion'])) {
            $query->where($mapping['ubicacion'], 'like', '%' . $filters['ubicacion'] . '%');
        }

        return $query;
    }
}
