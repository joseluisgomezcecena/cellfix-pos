<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('discount_locations', function (Blueprint $table) {
            $table->unsignedInteger('discount_id');
            $table->unsignedInteger('location_id');

            $table->foreign('discount_id')->references('id')->on('discounts')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('cascade');

            $table->primary(['discount_id', 'location_id']);
        });

        // Migrate existing location_id data to the pivot table
        DB::statement('
            INSERT INTO discount_locations (discount_id, location_id)
            SELECT id, location_id FROM discounts WHERE location_id IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('discount_locations');
    }
};
