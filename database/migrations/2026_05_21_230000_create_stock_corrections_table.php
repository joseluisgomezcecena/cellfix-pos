<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stock_corrections', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('variation_id');
            $table->enum('type', ['add', 'deduct']);
            $table->decimal('quantity', 22, 4);
            $table->string('reason');
            $table->decimal('qty_before', 22, 4)->nullable();
            $table->decimal('qty_after', 22, 4)->nullable();
            $table->text('note')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'location_id']);
            $table->index('product_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_corrections');
    }
};
