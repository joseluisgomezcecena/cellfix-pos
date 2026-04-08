<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('layaway_payments', function (Blueprint $table) {
            $table->unsignedInteger('transaction_payment_id')->nullable()->after('cash_register_id');
            $table->foreign('transaction_payment_id')->references('id')->on('transaction_payments')->onDelete('set null');
            $table->index('transaction_payment_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('layaway_payments', function (Blueprint $table) {
            $table->dropForeign(['transaction_payment_id']);
            $table->dropIndex(['transaction_payment_id']);
            $table->dropColumn('transaction_payment_id');
        });
    }
};
