<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_export_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('export_type'); // 'movements', 'daily_cuts', 'planned_payments', etc.
            $table->string('filename');
            $table->string('period')->nullable(); // 'Y-m' or 'Y'
            $table->unsignedInteger('record_count')->default(0);
            $table->unsignedInteger('file_size')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('export_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_export_logs');
    }
};
