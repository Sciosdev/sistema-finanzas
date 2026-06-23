<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_planned_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('period_month');
            $table->date('due_date')->nullable();
            $table->string('name');
            $table->decimal('amount', 14, 2);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->date('paid_on')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('finance_categories')->nullOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('finance_people')->nullOnDelete();
            $table->foreignId('movement_id')->nullable()->constrained('finance_movements')->nullOnDelete();
            $table->boolean('is_credit')->default(false);
            $table->boolean('is_san_juan')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'period_month']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('finance_credit_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('purchase_date');
            $table->string('name');
            $table->decimal('total_amount', 14, 2);
            $table->unsignedSmallInteger('months');
            $table->date('first_due_month');
            $table->unsignedTinyInteger('due_day')->nullable();
            $table->foreignId('account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('finance_categories')->nullOnDelete();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('finance_credit_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_purchase_id')->constrained('finance_credit_purchases')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('period_month');
            $table->date('due_date')->nullable();
            $table->unsignedSmallInteger('installment_number');
            $table->decimal('amount', 14, 2);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->date('paid_on')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('movement_id')->nullable()->constrained('finance_movements')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'period_month']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_credit_installments');
        Schema::dropIfExists('finance_credit_purchases');
        Schema::dropIfExists('finance_planned_payments');
    }
};
