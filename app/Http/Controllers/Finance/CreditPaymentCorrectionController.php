<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\CreditPaymentCorrection;
use App\Services\Finance\CreditPaymentCorrectionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CreditPaymentCorrectionController extends Controller
{
    public function store(Request $request, CreditPaymentCorrectionService $corrections)
    {
        $data = $request->validate([
            'installment_ids' => ['required', 'array', 'min:1', 'max:60'],
            'installment_ids.*' => ['required', 'integer', 'distinct'],
            'expected_paid_total' => ['required', 'numeric', 'min:0.01'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'concept' => ['required', 'string', 'max:255'],
            'charge_period_month' => ['required', 'date_format:Y-m'],
            'charge_due_date' => ['required', 'date'],
            'charge_category_id' => ['nullable', 'integer', Rule::exists('finance_categories', 'id')->where(fn ($query) => $query->where('user_id', $request->user()->id))],
            'target_period_month' => ['required', 'date_format:Y-m'],
            'target_due_date' => ['required', 'date'],
            'paid_on' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:10000'],
            'idempotency_key' => ['required', 'uuid'],
        ]);

        try {
            $correction = $corrections->separateCharge($request->user(), $data);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['correction' => $exception->getMessage()]);
        }

        return back()->with('success', 'Corrección #'.$correction->id.' registrada: el cargo separado y las compras conservan el mismo total de dinero pagado.');
    }

    public function destroy(Request $request, CreditPaymentCorrection $correction, CreditPaymentCorrectionService $corrections)
    {
        abort_unless((int) $correction->user_id === (int) $request->user()->id, 403);

        try {
            $corrections->undo($request->user(), $correction);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['correction' => $exception->getMessage()]);
        }

        return back()->with('success', 'Corrección #'.$correction->id.' revertida. Se restauraron las compras, mensualidades y movimientos originales.');
    }
}
