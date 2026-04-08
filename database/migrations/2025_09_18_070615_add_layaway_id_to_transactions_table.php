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
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'layaway_id')) {
                $table->unsignedBigInteger('layaway_id')->nullable()->after('id');
                $table->foreign('layaway_id')->references('id')->on('layaways')->onDelete('set null');
                $table->index('layaway_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['layaway_id']);
            $table->dropIndex(['layaway_id']);
            $table->dropColumn('layaway_id');
        });
    }
};
