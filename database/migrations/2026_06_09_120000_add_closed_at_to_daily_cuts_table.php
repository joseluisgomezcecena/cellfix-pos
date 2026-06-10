<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * closed_at marca el momento en que un corte queda "cerrado definitivamente".
     * Una vez cerrado:
     *   - El heartbeat (auto-cut de las 18:00) IGNORA ese (location, fecha).
     *   - ensureCutsForRange NO regenera al abrir reportes.
     *   - Solo un admin puede "reabrir" (poniéndolo en NULL).
     *
     * Backfill: cortes ya generados >= cut_date 18:00 quedan marcados como cerrados
     * (refleja la regla anterior de "frozen after 18:00").
     */
    public function up()
    {
        Schema::table('daily_cuts', function (Blueprint $table) {
            $table->dateTime('closed_at')->nullable()->after('generated_at');
            $table->unsignedInteger('closed_by')->nullable()->after('closed_at');
            $table->index('closed_at');
        });

        // Backfill: cortes generados después de las 18:00 de su mismo día se consideran
        // ya cerrados (heredan la regla anterior "frozen after 18:00").
        DB::statement(
            "UPDATE daily_cuts
                SET closed_at = generated_at
              WHERE closed_at IS NULL
                AND generated_at >= TIMESTAMP(CONCAT(cut_date, ' 18:00:00'))"
        );
    }

    public function down()
    {
        Schema::table('daily_cuts', function (Blueprint $table) {
            $table->dropIndex(['closed_at']);
            $table->dropColumn(['closed_at', 'closed_by']);
        });
    }
};
