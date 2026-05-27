<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('repair_product_commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('product_id');
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['business_id', 'product_id'], 'unique_repair_commission');
            $table->index('product_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('repair_product_commissions');
    }
};
