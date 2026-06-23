<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_expected_incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('period_month');
            $table->date('due_date')->nullable();
            $table->string('name');
            $table->decimal('amount', 14, 2);
            $table->decimal('received_amount', 14, 2)->default(0);
            $table->date('received_on')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('finance_categories')->nullOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('finance_people')->nullOnDelete();
            $table->foreignId('movement_id')->nullable()->constrained('finance_movements')->nullOnDelete();
            $table->boolean('is_rent')->default(false);
            $table->text('notes')->nullable();
            $table->string('import_key')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'period_month']);
            $table->index(['user_id', 'status']);
            $table->unique(['user_id', 'import_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_expected_incomes');
    }
};
