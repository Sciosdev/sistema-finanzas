<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinancePendingResolutionService;
use Illuminate\Http\Request;

class FinancePendingController extends Controller
{
    public function __construct(private readonly FinancePendingResolutionService $pending)
    {
    }

    public function index(Request $request)
    {
        $result = $this->pending->run($request->user());

        return view('finance.pending.index', [
            'groups' => $result['groups'],
            'summary' => $result['summary'],
        ]);
    }
}
