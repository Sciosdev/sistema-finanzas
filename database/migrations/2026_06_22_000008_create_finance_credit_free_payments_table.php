<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_credit_free_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('credit_purchase_id')->constrained('finance_credit_purchases')->cascadeOnDelete();
            $table->foreignId('movement_id')->nullable()->constrained('finance_movements')->nullOnDelete();
            $table->decimal('amount_applied', 14, 2);
            $table->date('paid_on');
            $table->string('payment_type')->default('free_payment');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'credit_purchase_id'], 'fcfp_user_credit_idx');
            $table->index(['user_id', 'paid_on'], 'fcfp_user_paid_on_idx');
            $table->index(['user_id', 'movement_id'], 'fcfp_user_movement_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_credit_free_payments');
    }
};
