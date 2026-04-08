<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pos_session_discounts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('session_id', 100);
            $table->integer('affiliated_business_id')->unsigned();
            $table->foreign('affiliated_business_id')->references('id')->on('affiliated_businesses')->onDelete('cascade');
            $table->integer('selected_discount_id')->unsigned();
            $table->foreign('selected_discount_id')->references('id')->on('affiliated_discount_options')->onDelete('cascade');
            $table->timestamp('created_at')->useCurrent();

            // Index for quick session lookups
            $table->index('session_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pos_session_discounts');
    }
};
