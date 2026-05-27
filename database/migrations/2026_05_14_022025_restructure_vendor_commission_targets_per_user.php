<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Commission targets are now per-vendor (user) instead of per-location.
     * Drop the table and recreate with user_id as the scope.
     */
    public function up()
    {
        Schema::dropIfExists('vendor_commission_targets');

        Schema::create('vendor_commission_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('brand_id');
            $table->unsignedInteger('meta_units')->default(0);
            $table->decimal('commission_per_unit', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('cascade');
            $table->unique(['user_id', 'brand_id'], 'unique_target_per_user_brand');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_commission_targets');
    }
};
