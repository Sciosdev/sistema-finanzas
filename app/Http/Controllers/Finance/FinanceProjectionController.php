<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\PlannerSetting;
use App\Services\Finance\FinanceProjectionService;
use Illuminate\Http\Request;

class FinanceProjectionController extends Controller
{
    public function __construct(private readonly FinanceProjectionService $projectionService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $horizon = (int) $request->query('horizonte', 15);
        if (! in_array($horizon, FinanceProjectionService::HORIZONS, true)) {
            $horizon = 15;
        }

        return view('finance.projection.index', [
            'projection' => $this->projectionService->project($user, $horizon),
            'horizon' => $horizon,
            'horizons' => FinanceProjectionService::HORIZONS,
            'settings' => PlannerSetting::where('user_id', $user->id)->first(),
        ]);
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'minimum_buffer' => ['required', 'numeric', 'min:0'],
            'count_overdue_income' => ['nullable', 'boolean'],
        ]);

        PlannerSetting::updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'minimum_buffer' => round((float) $data['minimum_buffer'], 2),
                'count_overdue_income' => (bool) ($data['count_overdue_income'] ?? false),
            ]
        );

        return back()->with('success', 'Configuración del planificador guardada.');
    }
}
