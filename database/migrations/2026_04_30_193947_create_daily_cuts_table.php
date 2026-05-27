<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('daily_cuts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id');
            $table->date('cut_date');

            // Top-level totals for fast querying / reporting
            $table->decimal('total_sales', 22, 4)->default(0);
            $table->decimal('total_cash', 22, 4)->default(0);
            $table->decimal('total_card', 22, 4)->default(0);
            $table->decimal('total_transfer', 22, 4)->default(0);
            $table->decimal('total_cheque', 22, 4)->default(0);
            $table->decimal('total_other', 22, 4)->default(0);
            $table->decimal('total_expenses', 22, 4)->default(0);
            $table->unsignedInteger('total_transactions')->default(0);

            // Detailed breakdown stored as JSON (sales by brand, payments by terminal, denominations, etc.)
            $table->json('summary')->nullable();

            $table->timestamp('generated_at')->nullable();
            $table->unsignedInteger('generated_by')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('cascade');
            $table->unique(['business_id', 'location_id', 'cut_date'], 'unique_daily_cut');
            $table->index('cut_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('daily_cuts');
    }
};
