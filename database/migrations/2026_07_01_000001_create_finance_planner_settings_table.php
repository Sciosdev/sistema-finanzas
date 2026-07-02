<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_planner_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('minimum_buffer', 14, 2)->default(0);
            $table->boolean('count_overdue_income')->default(false);
            // Reservado para fase 2 (gasto diario estimado); el MVP no lo lee.
            $table->boolean('use_daily_spend_estimate')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_planner_settings');
    }
};
