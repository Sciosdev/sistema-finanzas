<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FinanceOperationController extends Controller
{
    public function index(Request $request)
    {
        return view('finance.operations.index', [
            'gitInitialized' => is_dir(base_path('.git')),
            'isSecureContextHint' => $request->isSecure() || $request->getHost() === '127.0.0.1' || $request->getHost() === 'localhost',
        ]);
    }
}
