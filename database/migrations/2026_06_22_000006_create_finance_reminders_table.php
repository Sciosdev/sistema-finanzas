<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('reminder_type')->default('other');
            $table->string('vehicle_type')->nullable();
            $table->date('due_date');
            $table->decimal('amount', 14, 2)->nullable();
            $table->string('recurrence')->default('none');
            $table->unsignedSmallInteger('notify_days_before')->default(15);
            $table->string('status')->default('pending');
            $table->date('completed_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'due_date']);
            $table->index(['user_id', 'reminder_type']);
            $table->index(['user_id', 'vehicle_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_reminders');
    }
};
