<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * repair_status marca una venta como orden de reparación:
     *   null       => venta normal
     *   'pending'  => equipo recibido, pendiente de entrega
     *   'delivered'=> reparación entregada (pagada y con técnico asignado)
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('repair_status', 20)->nullable()->after('status');
            $table->index('repair_status');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['repair_status']);
            $table->dropColumn('repair_status');
        });
    }
};
