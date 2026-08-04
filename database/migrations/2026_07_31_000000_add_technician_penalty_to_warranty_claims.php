<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penalización al técnico cuando se registra una garantía sobre una reparación.
 * La comisión que ganó el técnico por esa reparación se le resta de sus
 * comisiones de LA SEMANA en que se registra la garantía (no retroactivamente).
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('warranty_claims', function (Blueprint $t) {
            $t->unsignedInteger('original_technician_id')->nullable()->after('original_product_name');
            $t->decimal('technician_commission_penalty', 22, 4)->nullable()->after('original_technician_id');
            $t->index('original_technician_id');
        });
    }

    public function down()
    {
        Schema::table('warranty_claims', function (Blueprint $t) {
            $t->dropIndex(['original_technician_id']);
            $t->dropColumn(['original_technician_id', 'technician_commission_penalty']);
        });
    }
};
