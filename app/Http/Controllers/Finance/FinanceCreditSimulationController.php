<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinanceProjectionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinanceCreditSimulationController extends Controller
{
    public function simulate(Request $request)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'horizon_days' => ['required', 'integer', Rule::in(FinanceProjectionService::HORIZONS)],
            'strategy' => ['required', Rule::in(['cheapest', 'lowest_monthly', 'safest_flow', 'balanced'])],
        ]);

        return redirect()->route('finance.projection.index', [
            'horizonte' => (int) $data['horizon_days'],
            'credit_amount' => round((float) $data['amount'], 2),
            'credit_horizon_days' => (int) $data['horizon_days'],
            'credit_strategy' => $data['strategy'],
        ]);
    }
}
