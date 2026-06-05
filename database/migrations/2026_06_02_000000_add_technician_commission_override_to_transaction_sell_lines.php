<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * technician_commission_override permite poner un monto manual en /technicians/report.
     * Cuando es NULL, se calcula la comisión desde repair_product_commissions.
     * Cuando NO es NULL, se usa este valor exacto, ignorando la tabla de comisiones.
     */
    public function up()
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->decimal('technician_commission_override', 22, 4)->nullable()->after('repair_anticipo');
        });
    }

    public function down()
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            $table->dropColumn('technician_commission_override');
        });
    }
};
