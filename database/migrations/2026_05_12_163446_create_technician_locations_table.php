<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('technician_locations', function (Blueprint $table) {
            $table->unsignedBigInteger('technician_id');
            $table->unsignedInteger('location_id');

            $table->foreign('technician_id')->references('id')->on('technicians')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('cascade');

            $table->primary(['technician_id', 'location_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('technician_locations');
    }
};
