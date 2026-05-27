<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vendor_commission_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id');
            $table->unsignedInteger('brand_id');
            $table->unsignedInteger('meta_units')->default(0);
            $table->decimal('commission_per_unit', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('cascade');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('cascade');
            $table->unique(['location_id', 'brand_id'], 'unique_target_per_location_brand');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_commission_targets');
    }
};
