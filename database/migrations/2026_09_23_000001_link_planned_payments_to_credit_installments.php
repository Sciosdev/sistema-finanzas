<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_planned_payments', function (Blueprint $table) {
            $table->foreignId('credit_installment_id')
                ->nullable()
                ->after('credit_purchase_id')
                ->unique()
                ->constrained('finance_credit_installments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('finance_planned_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('credit_installment_id');
        });
    }
};
