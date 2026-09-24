<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_credit_purchases', function (Blueprint $table) {
            $table->unsignedTinyInteger('planned_payment_day')->nullable()->after('due_day');
        });
    }

    public function down(): void
    {
        Schema::table('finance_credit_purchases', function (Blueprint $table) {
            $table->dropColumn('planned_payment_day');
        });
    }
};
