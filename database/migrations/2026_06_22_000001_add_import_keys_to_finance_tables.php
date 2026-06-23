<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_movements', function (Blueprint $table) {
            $table->string('import_key')->nullable()->after('source');
            $table->unique(['user_id', 'import_key']);
        });

        Schema::table('finance_planned_payments', function (Blueprint $table) {
            $table->string('import_key')->nullable()->after('notes');
            $table->unique(['user_id', 'import_key']);
        });

        Schema::table('finance_daily_cuts', function (Blueprint $table) {
            $table->string('import_key')->nullable()->after('notes');
            $table->unique(['user_id', 'import_key']);
        });
    }

    public function down(): void
    {
        Schema::table('finance_daily_cuts', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'import_key']);
            $table->dropColumn('import_key');
        });

        Schema::table('finance_planned_payments', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'import_key']);
            $table->dropColumn('import_key');
        });

        Schema::table('finance_movements', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'import_key']);
            $table->dropColumn('import_key');
        });
    }
};
