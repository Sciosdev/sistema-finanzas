<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_credit_payment_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('finance_accounts')->restrictOnDelete();
            $table->foreignId('charge_credit_purchase_id')->nullable()->constrained('finance_credit_purchases', 'id', 'fcpc_charge_credit_foreign')->nullOnDelete();
            $table->string('idempotency_key', 100);
            $table->string('request_hash', 64);
            $table->decimal('paid_total', 14, 2);
            $table->decimal('amount', 14, 2);
            $table->string('concept');
            $table->text('reason');
            $table->json('source_installment_ids');
            $table->json('before_state');
            $table->json('after_state');
            $table->timestamp('reverted_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'idempotency_key'], 'fcpc_user_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_credit_payment_corrections');
    }
};
