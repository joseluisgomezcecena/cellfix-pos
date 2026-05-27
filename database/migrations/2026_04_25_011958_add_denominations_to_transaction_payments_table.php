<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds a JSON column to store the cash denomination breakdown for a payment.
     * Example value: {"20":3,"50":2,"100":1,"coins":12.50}
     */
    public function up()
    {
        Schema::table('transaction_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('transaction_payments', 'denomination_breakdown')) {
                $table->json('denomination_breakdown')->nullable()->after('note');
            }
        });
    }

    public function down()
    {
        Schema::table('transaction_payments', function (Blueprint $table) {
            if (Schema::hasColumn('transaction_payments', 'denomination_breakdown')) {
                $table->dropColumn('denomination_breakdown');
            }
        });
    }
};
