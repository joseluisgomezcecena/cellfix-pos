<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLayawayItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('layaway_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('layaway_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('variation_id')->nullable();
            $table->decimal('quantity', 22, 4);
            $table->decimal('unit_price', 22, 4);
            $table->decimal('total_price', 22, 4);
            $table->timestamps();

            $table->foreign('layaway_id')->references('id')->on('layaways')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('variation_id')->references('id')->on('variations')->onDelete('cascade');

            $table->index('layaway_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('layaway_items');
    }
}