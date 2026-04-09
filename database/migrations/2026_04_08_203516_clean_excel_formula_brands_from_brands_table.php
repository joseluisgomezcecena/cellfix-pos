<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove brands that were imported with Excel formulas as names.
     */
    public function up()
    {
        // Only delete formula brands that are not used by any product
        DB::table('brands')
            ->where('name', 'LIKE', '=%')
            ->whereNotIn('id', function ($query) {
                $query->select('brand_id')
                    ->from('products')
                    ->whereNotNull('brand_id');
            })
            ->delete();
    }

    /**
     * No rollback — deleted data was invalid.
     */
    public function down()
    {
        //
    }
};
