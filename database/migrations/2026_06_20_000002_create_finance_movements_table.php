<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('happened_on');
            $table->string('movement_type');
            $table->decimal('amount', 14, 2);
            $table->string('description');
            $table->foreignId('account_id')->nullable()->constrained('finance_accounts')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('finance_categories')->nullOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('finance_people')->nullOnDelete();
            $table->boolean('is_san_juan')->default(false);
            $table->boolean('is_rent')->default(false);
            $table->boolean('is_unknown')->default(false);
            $table->string('source')->default('manual');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'happened_on']);
            $table->index(['user_id', 'movement_type']);
            $table->index(['user_id', 'is_san_juan']);
            $table->index(['user_id', 'is_rent']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_movements');
    }
};
