<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vendor_locations', function (Blueprint $table) {
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedInteger('location_id');

            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('cascade');

            $table->primary(['vendor_id', 'location_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_locations');
    }
};
