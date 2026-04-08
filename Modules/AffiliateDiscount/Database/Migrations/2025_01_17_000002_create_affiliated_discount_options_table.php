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
        Schema::create('affiliated_discount_options', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('affiliated_business_id')->unsigned();
            $table->foreign('affiliated_business_id')->references('id')->on('affiliated_businesses')->onDelete('cascade');
            $table->enum('category_name', ['equipos', 'pantallas', 'accesorios', 'desbloqueos', 'servicios', 'reparaciones']);
            $table->enum('discount_type', ['fixed', 'percentage']);
            $table->decimal('discount_value', 10, 2);
            $table->string('discount_label', 50); // e.g., "$100 PESOS", "10% DESC."
            $table->boolean('is_active')->default(1);
            $table->integer('display_order')->default(0);
            $table->timestamps();

            // Indexes (custom name to avoid MySQL 64-char limit)
            $table->index(['affiliated_business_id', 'category_name', 'is_active'], 'aff_disc_opt_bus_cat_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('affiliated_discount_options');
    }
};
