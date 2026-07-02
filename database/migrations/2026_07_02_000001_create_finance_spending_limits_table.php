<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_spending_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('finance_categories')->cascadeOnDelete();
            $table->string('period_type');
            $table->decimal('limit_amount', 14, 2);
            $table->decimal('warning_threshold_percent', 5, 2)->default(80);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'category_id', 'period_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_spending_limits');
    }
};
