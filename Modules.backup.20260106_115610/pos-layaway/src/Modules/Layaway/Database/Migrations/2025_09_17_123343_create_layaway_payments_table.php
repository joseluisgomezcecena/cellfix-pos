<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLayawayPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('layaway_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('layaway_id');
            $table->decimal('amount', 22, 4);
            $table->string('payment_method');
            $table->dateTime('payment_date');
            $table->unsignedInteger('processed_by');
            $table->unsignedInteger('cash_register_id')->nullable();
            $table->unsignedInteger('transaction_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('layaway_id')->references('id')->on('layaways')->onDelete('cascade');
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('cash_register_id')->references('id')->on('cash_registers')->onDelete('set null');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('set null');

            $table->index('layaway_id');
            $table->index('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('layaway_payments');
    }
}