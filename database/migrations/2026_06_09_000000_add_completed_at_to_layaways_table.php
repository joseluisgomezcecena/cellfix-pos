<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * completed_at marca el momento en que un apartado pasó a status='completed'
     * con balance_due = 0. Es la fecha que el corte diario usa para "consolidar"
     * todos los pagos del apartado en un único día (el día de entrega), en vez de
     * sumarlos en los días en que se hicieron los pagos parciales.
     */
    public function up()
    {
        Schema::table('layaways', function (Blueprint $table) {
            $table->dateTime('completed_at')->nullable()->after('status');
            $table->index('completed_at');
        });

        // Backfill: para apartados ya cerrados (status=completed Y balance=0),
        // usamos updated_at como fecha de completación.
        DB::table('layaways')
            ->where('status', 'completed')
            ->where('balance_due', 0)
            ->whereNull('completed_at')
            ->update(['completed_at' => DB::raw('updated_at')]);
    }

    public function down()
    {
        Schema::table('layaways', function (Blueprint $table) {
            $table->dropIndex(['completed_at']);
            $table->dropColumn('completed_at');
        });
    }
};
