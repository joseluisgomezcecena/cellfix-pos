<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('transaction_sell_lines', 'repair_entry_date')) {
                $table->date('repair_entry_date')->nullable()->after('technician_id');
            }
            if (!Schema::hasColumn('transaction_sell_lines', 'repair_anticipo')) {
                $table->decimal('repair_anticipo', 22, 4)->nullable()->after('repair_entry_date');
            }
        });
    }

    public function down()
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            if (Schema::hasColumn('transaction_sell_lines', 'repair_anticipo')) {
                $table->dropColumn('repair_anticipo');
            }
            if (Schema::hasColumn('transaction_sell_lines', 'repair_entry_date')) {
                $table->dropColumn('repair_entry_date');
            }
        });
    }
};
