<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('warranty_claims', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedInteger('business_id')->index();
            $t->unsignedInteger('location_id')->index();
            $t->string('ref_no', 191);
            $t->dateTime('claim_date')->index();
            $t->unsignedInteger('created_by');
            $t->unsignedInteger('contact_id')->nullable()->index();
            $t->unsignedInteger('original_sell_transaction_id')->nullable()->index();
            $t->unsignedInteger('original_variation_id')->nullable();
            $t->string('original_product_name', 255)->nullable();
            $t->enum('claim_type', [
                'refund',
                'replacement_same',
                'replacement_higher',
                'replacement_lower',
            ]);
            $t->text('motivo');
            $t->unsignedInteger('replacement_variation_id')->nullable();
            $t->string('replacement_product_name', 255)->nullable();
            $t->decimal('refund_amount', 22, 4)->nullable();
            $t->string('refund_method', 50)->nullable();
            $t->unsignedInteger('refund_card_terminal_id')->nullable();
            $t->json('refund_denomination_breakdown')->nullable();
            $t->decimal('price_difference', 22, 4)->nullable();
            $t->string('price_difference_method', 50)->nullable();
            $t->unsignedInteger('price_difference_card_terminal_id')->nullable();
            $t->json('price_difference_denomination_breakdown')->nullable();
            $t->unsignedInteger('expense_transaction_id')->nullable();
            $t->unsignedInteger('payment_transaction_id')->nullable();
            $t->enum('status', ['completed', 'cancelled'])->default('completed');
            $t->timestamps();
        });

        // Marca en transactions para excluir del conteo de "equipos vendidos" y de comisiones.
        Schema::table('transactions', function (Blueprint $t) {
            $t->boolean('is_warranty_exchange')->default(false)->after('type');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $t) {
            $t->dropColumn('is_warranty_exchange');
        });
        Schema::dropIfExists('warranty_claims');
    }
};
