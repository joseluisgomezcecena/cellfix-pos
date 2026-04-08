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
        Schema::create('promo_companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('company_name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(1);
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('business_id')
                  ->references('id')
                  ->on('business')
                  ->onDelete('cascade');

            // Indexes for faster queries
            $table->index('company_name');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promo_companies');
    }
};
