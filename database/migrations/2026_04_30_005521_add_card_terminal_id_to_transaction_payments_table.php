<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transaction_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('transaction_payments', 'card_terminal_id')) {
                $table->unsignedBigInteger('card_terminal_id')->nullable()->after('card_type');
                $table->index('card_terminal_id');
            }
        });
    }

    public function down()
    {
        Schema::table('transaction_payments', function (Blueprint $table) {
            if (Schema::hasColumn('transaction_payments', 'card_terminal_id')) {
                $table->dropIndex(['card_terminal_id']);
                $table->dropColumn('card_terminal_id');
            }
        });
    }
};
