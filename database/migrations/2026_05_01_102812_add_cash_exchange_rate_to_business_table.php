<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('business', function (Blueprint $table) {
            if (!Schema::hasColumn('business', 'cash_exchange_rate')) {
                $table->decimal('cash_exchange_rate', 10, 4)->default(18.0000)->after('p_exchange_rate');
            }
        });
    }

    public function down()
    {
        Schema::table('business', function (Blueprint $table) {
            if (Schema::hasColumn('business', 'cash_exchange_rate')) {
                $table->dropColumn('cash_exchange_rate');
            }
        });
    }
};
