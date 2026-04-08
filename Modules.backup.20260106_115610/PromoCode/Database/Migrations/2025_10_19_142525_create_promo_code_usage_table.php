<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promo_code_usage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('promo_company_id');
            $table->unsignedInteger('transaction_id');
            $table->decimal('total_discount', 22, 4);
            $table->timestamp('applied_at');

            // Foreign key constraints
            $table->foreign('promo_company_id')
                  ->references('id')
                  ->on('promo_companies')
                  ->onDelete('cascade');

            $table->foreign('transaction_id')
                  ->references('id')
                  ->on('transactions')
                  ->onDelete('cascade');

            // Indexes for analytics queries
            $table->index('promo_company_id');
            $table->index('transaction_id');
            $table->index('applied_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promo_code_usage');
    }
};
