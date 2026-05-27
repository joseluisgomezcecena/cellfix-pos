<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds reward_points override per product.
     * If null, the global business rule (amount_for_unit_rp) applies.
     * If set, the product earns this many points per unit sold.
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'reward_points')) {
                $table->integer('reward_points')->nullable()->after('warranty_id');
            }
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'reward_points')) {
                $table->dropColumn('reward_points');
            }
        });
    }
};
