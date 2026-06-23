<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_daily_cuts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('cut_date');
            $table->decimal('expected_leftover', 14, 2)->default(0);
            $table->decimal('cash_amount', 14, 2)->default(0);
            $table->decimal('cards_amount', 14, 2)->default(0);
            $table->decimal('real_total', 14, 2)->default(0);
            $table->decimal('pending_payments', 14, 2)->default(0);
            $table->decimal('difference', 14, 2)->default(0);
            $table->decimal('amount_missing', 14, 2)->default(0);
            $table->string('status')->default('review');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'cut_date']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('finance_daily_cut_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_cut_id')->constrained('finance_daily_cuts')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('finance_accounts')->cascadeOnDelete();
            $table->decimal('balance', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['daily_cut_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_daily_cut_balances');
        Schema::dropIfExists('finance_daily_cuts');
    }
};
