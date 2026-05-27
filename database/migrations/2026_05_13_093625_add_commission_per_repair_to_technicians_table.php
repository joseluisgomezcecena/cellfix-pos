<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('technicians', function (Blueprint $table) {
            if (!Schema::hasColumn('technicians', 'commission_per_repair')) {
                $table->decimal('commission_per_repair', 12, 2)->default(0)->after('notes');
            }
        });
    }

    public function down()
    {
        Schema::table('technicians', function (Blueprint $table) {
            if (Schema::hasColumn('technicians', 'commission_per_repair')) {
                $table->dropColumn('commission_per_repair');
            }
        });
    }
};
