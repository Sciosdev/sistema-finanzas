<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Distribución del Resumen por usuario (orden, tamaños, ocultos y
            // auto-ajuste). Es solo preferencia visual; no afecta datos financieros.
            $table->json('dashboard_layout')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('dashboard_layout');
        });
    }
};
