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
        Schema::create('inventory_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->string('reference_no')->unique();
            $table->unsignedInteger('from_location_id');
            $table->unsignedInteger('to_location_id');
            $table->unsignedInteger('created_by');
            $table->enum('status', ['pending', 'in_transit', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->datetime('transferred_at')->nullable();
            $table->datetime('received_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('from_location_id')->references('id')->on('business_locations');
            $table->foreign('to_location_id')->references('id')->on('business_locations');
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventory_transfers');
    }
};
