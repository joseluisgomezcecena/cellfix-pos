<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sales_goals', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->default(0); // 0 = todas las sucursales
            $table->string('metric')->default('equipos_weekly');
            $table->unsignedInteger('target_qty')->default(0);
            $table->decimal('target_amount', 22, 4)->default(0);
            $table->timestamps();

            $table->unique(['business_id', 'location_id', 'metric'], 'unique_goal');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sales_goals');
    }
};
