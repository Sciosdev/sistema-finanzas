<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_planned_payments', function (Blueprint $table) {
            $table->foreignId('credit_purchase_id')
                ->nullable()
                ->after('movement_id')
                ->constrained('finance_credit_purchases')
                ->nullOnDelete();

            $table->index(['user_id', 'credit_purchase_id'], 'fpp_user_credit_purchase_idx');
        });
    }

    public function down(): void
    {
        Schema::table('finance_planned_payments', function (Blueprint $table) {
            $table->dropIndex('fpp_user_credit_purchase_idx');
            $table->dropConstrainedForeignId('credit_purchase_id');
        });
    }
};
