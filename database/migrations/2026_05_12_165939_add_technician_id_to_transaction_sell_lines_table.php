<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('transaction_sell_lines', 'technician_id')) {
                $table->unsignedBigInteger('technician_id')->nullable()->after('product_id');
                $table->foreign('technician_id')->references('id')->on('technicians')->onDelete('set null');
                $table->index('technician_id');
            }
        });
    }

    public function down()
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            if (Schema::hasColumn('transaction_sell_lines', 'technician_id')) {
                $table->dropForeign(['technician_id']);
                $table->dropIndex(['technician_id']);
                $table->dropColumn('technician_id');
            }
        });
    }
};
