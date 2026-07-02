<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_credit_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->string('name');
            $table->string('provider')->nullable();
            $table->decimal('available_amount', 14, 2)->default(0);
            $table->decimal('min_amount', 14, 2)->default(0);
            $table->string('cost_type')->default('total_percent');
            $table->decimal('cost_percent', 8, 4)->default(0);
            $table->decimal('fixed_fee', 14, 2)->default(0);
            $table->unsignedSmallInteger('term_months')->default(1);
            $table->unsignedTinyInteger('payment_day')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_credit_options');
    }
};
