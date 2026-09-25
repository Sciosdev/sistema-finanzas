<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_card_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('finance_accounts')->restrictOnDelete();
            $table->foreignId('reference_credit_purchase_id')->nullable()
                ->constrained('finance_credit_purchases')->nullOnDelete();
            $table->date('received_on');
            $table->date('period_month');
            $table->decimal('amount', 14, 2);
            $table->string('description');
            $table->text('notes');
            $table->string('idempotency_key', 80);
            $table->timestamps();
            $table->unique(['user_id', 'idempotency_key'], 'finance_refund_user_key_unique');
            $table->index(['user_id', 'account_id', 'period_month'], 'finance_refund_account_month');
        });

        Schema::table('finance_credit_free_payments', function (Blueprint $table) {
            $table->foreignId('card_refund_id')->nullable()
                ->constrained('finance_card_refunds')->cascadeOnDelete();
            $table->foreignId('target_installment_id')->nullable()
                ->constrained('finance_credit_installments')->restrictOnDelete();
            $table->json('allocation_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('finance_credit_free_payments', function (Blueprint $table) {
            $table->dropForeign(['card_refund_id']);
            $table->dropForeign(['target_installment_id']);
            $table->dropColumn(['card_refund_id', 'target_installment_id', 'allocation_snapshot']);
        });
        Schema::dropIfExists('finance_card_refunds');
    }
};
