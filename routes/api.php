<?php

use App\Http\Controllers\Finance\FinanceAdvisorApiController;
use App\Http\Controllers\Finance\FinanceDeploymentApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('finance/deployment')
    ->middleware('finance.deploy-token')
    ->group(function () {
        Route::get('status', [FinanceDeploymentApiController::class, 'status'])
            ->middleware('throttle:30,1')
            ->name('api.finance.deployment.status');
        Route::post('deploy', [FinanceDeploymentApiController::class, 'deploy'])
            ->middleware('throttle:3,1')
            ->name('api.finance.deployment.deploy');
    });

Route::prefix('finance/advisor')
    ->middleware('finance.advisor-token')
    ->group(function () {
        Route::get('snapshot', [FinanceAdvisorApiController::class, 'snapshot'])
            ->middleware('throttle:12,1')
            ->name('api.finance.advisor.snapshot');
    });
