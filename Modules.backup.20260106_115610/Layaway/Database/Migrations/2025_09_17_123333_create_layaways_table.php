<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLayawaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('layaways', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('contact_id');
            $table->unsignedInteger('business_location_id');
            $table->unsignedInteger('created_by');
            $table->string('layaway_number')->unique();
            $table->decimal('total_amount', 22, 4);
            $table->decimal('down_payment_percentage', 5, 2)->default(20);
            $table->decimal('down_payment_amount', 22, 4);
            $table->decimal('balance_due', 22, 4);
            $table->date('payment_deadline');
            $table->enum('status', ['pending', 'active', 'completed', 'cancelled', 'expired'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            $table->index('layaway_number');
            $table->index('status');
            $table->index('payment_deadline');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('layaways');
    }
}