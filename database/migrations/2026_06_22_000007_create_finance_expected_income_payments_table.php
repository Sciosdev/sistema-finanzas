<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_expected_income_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expected_income_id')->constrained('finance_expected_incomes')->cascadeOnDelete();
            $table->foreignId('movement_id')->nullable()->constrained('finance_movements')->nullOnDelete();
            $table->decimal('amount_applied', 14, 2);
            $table->date('paid_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'expected_income_id'], 'fei_pay_user_income_idx');
            $table->index(['user_id', 'movement_id'], 'fei_pay_user_movement_idx');
            $table->unique(['expected_income_id', 'movement_id'], 'fei_pay_income_movement_unique');
        });

        $this->migrateLegacyMovementLinks();
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_expected_income_payments');
    }

    private function migrateLegacyMovementLinks(): void
    {
        DB::table('finance_expected_incomes')
            ->whereNotNull('movement_id')
            ->orderBy('id')
            ->get()
            ->each(function ($income): void {
                $movement = DB::table('finance_movements')
                    ->where('id', $income->movement_id)
                    ->where('user_id', $income->user_id)
                    ->first();

                if (! $movement) {
                    return;
                }

                $amount = (float) ($income->received_amount ?: $movement->amount ?: $income->amount);

                DB::table('finance_expected_income_payments')->updateOrInsert(
                    [
                        'expected_income_id' => $income->id,
                        'movement_id' => $movement->id,
                    ],
                    [
                        'user_id' => $income->user_id,
                        'amount_applied' => max(0.01, round($amount, 2)),
                        'paid_on' => $income->received_on ?: $movement->happened_on,
                        'notes' => 'Migrado desde vínculo anterior de ingreso esperado.',
                        'created_at' => $income->updated_at ?: now(),
                        'updated_at' => $income->updated_at ?: now(),
                    ]
                );
            });
    }
};
