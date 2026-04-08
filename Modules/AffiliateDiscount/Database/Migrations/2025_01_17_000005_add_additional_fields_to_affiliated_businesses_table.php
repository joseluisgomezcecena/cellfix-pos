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
        Schema::table('affiliated_businesses', function (Blueprint $table) {
            $table->string('contact_phone', 50)->nullable()->after('contact_id');
            $table->string('contact_email', 100)->nullable()->after('contact_phone');
            $table->string('contract_number', 50)->nullable()->after('contact_email');
            $table->date('start_date')->nullable()->after('contract_number');
            $table->date('end_date')->nullable()->after('start_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('affiliated_businesses', function (Blueprint $table) {
            $table->dropColumn(['contact_phone', 'contact_email', 'contract_number', 'start_date', 'end_date']);
        });
    }
};
