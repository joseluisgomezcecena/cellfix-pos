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
        Schema::create('transaction_affiliate_discounts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('transaction_id')->unsigned();
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            $table->integer('affiliated_business_id')->unsigned();
            $table->foreign('affiliated_business_id')->references('id')->on('affiliated_businesses')->onDelete('cascade');
            $table->integer('discount_option_id')->unsigned()->nullable();
            $table->foreign('discount_option_id')->references('id')->on('affiliated_discount_options')->onDelete('set null');
            $table->string('category_name', 50);
            $table->integer('items_affected')->default(0);
            $table->decimal('total_discount_applied', 22, 4)->default(0);
            $table->timestamp('created_at')->useCurrent();

            // Indexes for reporting (custom names to avoid MySQL 64-char limit)
            $table->index(['transaction_id'], 'trans_aff_disc_transaction_idx');
            $table->index(['affiliated_business_id', 'created_at'], 'trans_aff_disc_bus_date_idx');
            $table->index('category_name', 'trans_aff_disc_category_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_affiliate_discounts');
    }
};
