<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinanceHealthCheckService;

class FinanceHealthController extends Controller
{
    public function __construct(private readonly FinanceHealthCheckService $healthChecks)
    {
    }

    public function index()
    {
        $checks = $this->healthChecks->run();
        $summary = $checks->countBy('status');

        return view('finance.health.index', [
            'checks' => $checks,
            'summary' => [
                'ok' => $summary->get('ok', 0),
                'warning' => $summary->get('warning', 0),
                'fail' => $summary->get('fail', 0),
                'total' => $checks->count(),
            ],
        ]);
    }
}
