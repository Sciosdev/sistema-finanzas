<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_planned_payments', function (Blueprint $table) {
            $table->boolean('is_automatic_charge')->default(false)->after('is_san_juan');
            $table->boolean('is_forced_charge_window')->default(false)->after('is_automatic_charge');
            $table->unsignedTinyInteger('charge_window_before_days')->default(0)->after('is_forced_charge_window');
            $table->unsignedTinyInteger('charge_window_after_days')->default(0)->after('charge_window_before_days');
        });
    }

    public function down(): void
    {
        Schema::table('finance_planned_payments', function (Blueprint $table) {
            $table->dropColumn([
                'is_automatic_charge',
                'is_forced_charge_window',
                'charge_window_before_days',
                'charge_window_after_days',
            ]);
        });
    }
};
