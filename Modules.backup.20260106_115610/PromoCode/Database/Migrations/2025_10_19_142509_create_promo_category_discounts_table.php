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
        Schema::create('promo_category_discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('promo_company_id');
            $table->enum('category_name', ['equipos', 'pantallas', 'accesorios', 'desbloqueos']);
            $table->enum('discount_type', ['percentage', 'fixed']);
            $table->decimal('discount_value', 10, 2);
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('promo_company_id')
                  ->references('id')
                  ->on('promo_companies')
                  ->onDelete('cascade');

            // Unique constraint: one discount per category per company
            $table->unique(['promo_company_id', 'category_name'], 'unique_company_category');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promo_category_discounts');
    }
};
