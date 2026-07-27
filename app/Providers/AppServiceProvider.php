<?php

namespace App\Providers;

use App\Models\User;
use App\Services\Finance\CreditEffectiveScheduleService;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Singleton a propósito: el reparto virtual de abonos libres se memoiza
        // por crédito dentro del request. Con instancias sueltas cada pantalla
        // recalcularía (y recargaría) lo mismo decenas de veces.
        $this->app->singleton(CreditEffectiveScheduleService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
        Paginator::useBootstrapFive();

        Gate::define('finance.owner', fn (User $user): bool => $user->isFinanceOwner());
    }
}
