<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_rental_contracts', function (Blueprint $table) {
            $table->boolean('manual_override')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('finance_rental_contracts', function (Blueprint $table) {
            $table->dropColumn('manual_override');
        });
    }
};
