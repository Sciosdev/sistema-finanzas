<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Finance\AccountController;
use App\Http\Controllers\Finance\CategoryController;
use App\Http\Controllers\Finance\FinanceBuildDeployController;
use App\Http\Controllers\Finance\CreditPurchaseController;
use App\Http\Controllers\Finance\DailyCutController;
use App\Http\Controllers\Finance\ExpectedIncomeController;
use App\Http\Controllers\Finance\FinanceDashboardController;
use App\Http\Controllers\Finance\FinanceHealthController;
use App\Http\Controllers\Finance\FinanceMaintenanceController;
use App\Http\Controllers\Finance\FinanceOperationController;
use App\Http\Controllers\Finance\FinancePendingController;
use App\Http\Controllers\Finance\FinanceReportController;
use App\Http\Controllers\Finance\FinanceRestoreController;
use App\Http\Controllers\Finance\FinanceSecurityController;
use App\Http\Controllers\Finance\FinanceTriageController;
use App\Http\Controllers\Finance\FinanceUserController;
use App\Http\Controllers\Finance\HistoricalImportController;
use App\Http\Controllers\Finance\MovementController;
use App\Http\Controllers\Finance\MonthlyReviewController;
use App\Http\Controllers\Finance\PlannedPaymentController;
use App\Http\Controllers\Finance\ReminderController;
use App\Http\Controllers\Finance\SanJuanController;
use App\Http\Controllers\RoutingController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

require __DIR__ . '/auth.php';

// Endpoint mínimo de triage para errores 500 en producción. Público pero
// protegido por token; fuera del middleware auth y registrado antes del grupo
// con catch-all para que no lo intercepte la ruta {any}.
Route::get('_health/triage', FinanceTriageController::class)->name('health.triage');

Route::group(['prefix' => '/', 'middleware' => 'auth'], function () {
    Route::get('', [FinanceDashboardController::class, 'index'])->name('root');
    Route::get('dashboard/index', [FinanceDashboardController::class, 'index'])->name('dashboard.index');

    Route::prefix('finanzas')->name('finance.')->group(function () {
        Route::get('', [FinanceDashboardController::class, 'index'])->name('dashboard');
        Route::post('resumen/diseno', [FinanceDashboardController::class, 'saveLayout'])->name('dashboard.layout');
        Route::get('reportes', [FinanceReportController::class, 'index'])->name('reports.index');
        Route::get('reportes/exportar', [FinanceReportController::class, 'export'])->middleware('finance.owner')->name('reports.export');
        Route::get('importar-historico', [HistoricalImportController::class, 'index'])->name('imports.historical.index');
        Route::get('importar-historico/plantilla', [HistoricalImportController::class, 'template'])->name('imports.historical.template');
        Route::post('importar-historico/vista-previa', [HistoricalImportController::class, 'preview'])->name('imports.historical.preview');
        Route::post('importar-historico/guardar', [HistoricalImportController::class, 'store'])->name('imports.historical.store');
        Route::get('revision-mensual', [MonthlyReviewController::class, 'index'])->name('monthly-review.index');
        Route::post('revision-mensual/{key}/aplicar', [MonthlyReviewController::class, 'apply'])->name('monthly-review.apply');
        Route::post('revision-mensual/{key}/ignorar', [MonthlyReviewController::class, 'ignore'])->name('monthly-review.ignore');
        Route::post('revision-mensual/restaurar-ignoradas', [MonthlyReviewController::class, 'restoreIgnored'])->name('monthly-review.restore-ignored');
        Route::get('operacion', [FinanceOperationController::class, 'index'])->name('operations.index');
        Route::get('pendientes', [FinancePendingController::class, 'index'])->name('pending.index');
        Route::middleware('finance.owner')->group(function () {
            Route::get('seguridad', [FinanceSecurityController::class, 'index'])->name('security.index');
            Route::get('diagnostico', [FinanceHealthController::class, 'index'])->name('health.index');
            Route::get('usuarios', [FinanceUserController::class, 'index'])->name('users.index');
            Route::post('usuarios', [FinanceUserController::class, 'store'])->name('users.store');
            Route::post('seguridad/deshacer/{token}', [FinanceSecurityController::class, 'undoDelete'])->name('security.undo-delete');
            Route::post('seguridad/backups/database', [FinanceSecurityController::class, 'createDatabaseBackup'])->name('security.backups.database');
            Route::post('seguridad/backups/full', [FinanceSecurityController::class, 'createFullBackup'])->name('security.backups.full');
            Route::post('seguridad/backups/migration', [FinanceSecurityController::class, 'createMigrationPackage'])->name('security.backups.migration');
            Route::post('seguridad/backups/externo', [FinanceSecurityController::class, 'createExternalBackup'])->name('security.backups.external');
            Route::get('seguridad/backups/{type}/{filename}', [FinanceSecurityController::class, 'downloadBackup'])
                ->where(['type' => 'database|full|migration', 'filename' => '[^/]+'])
                ->name('security.backups.download');
            Route::post('seguridad/fallas/{failure}/resolver', [FinanceSecurityController::class, 'resolveFailure'])->name('security.failures.resolve');
            Route::post('seguridad/mantenimiento/migrar', [FinanceMaintenanceController::class, 'runMigrations'])->name('maintenance.run-migrations');
            Route::post('seguridad/mantenimiento/limpiar-cache', [FinanceMaintenanceController::class, 'clearOptimizationCache'])->name('maintenance.clear-cache');
            Route::post('seguridad/restaurar', [FinanceRestoreController::class, 'restoreFromBackup'])->name('security.restore.backup');
            Route::post('seguridad/restaurar-subir', [FinanceRestoreController::class, 'restoreFromUpload'])->name('security.restore.upload');
            Route::post('seguridad/build/subir', [FinanceBuildDeployController::class, 'uploadBuild'])->name('build.upload');
            Route::post('seguridad/build/rollback', [FinanceBuildDeployController::class, 'rollbackBuild'])->name('build.rollback');
            Route::post('seguridad/build/limpiar', [FinanceBuildDeployController::class, 'cleanupBuildBackups'])->name('build.cleanup');
        });

        Route::get('cuentas', [AccountController::class, 'index'])->name('accounts.index');
        Route::post('cuentas', [AccountController::class, 'store'])->name('accounts.store');
        Route::post('cuentas/colores-sugeridos', [AccountController::class, 'applySuggestedColors'])->name('accounts.apply-colors');
        Route::put('cuentas/{account}', [AccountController::class, 'update'])->name('accounts.update');

        Route::get('movimientos', [MovementController::class, 'index'])->name('movements.index');
        Route::get('movimientos/exportar', [MovementController::class, 'export'])->middleware('finance.owner')->name('movements.export');
        Route::post('movimientos', [MovementController::class, 'store'])->name('movements.store');
        Route::post('movimientos/actualizacion-masiva', [MovementController::class, 'bulkUpdate'])->name('movements.bulk-update');
        Route::get('movimientos/sugerencias', [MovementController::class, 'suggestions'])->name('movements.suggestions.index');
        Route::post('movimientos/sugerencias/aplicar', [MovementController::class, 'applySuggestions'])->name('movements.suggestions.apply');
        Route::get('movimientos/{movement}/editar', [MovementController::class, 'edit'])->name('movements.edit');
        Route::put('movimientos/{movement}', [MovementController::class, 'update'])->name('movements.update');
        Route::delete('movimientos/{movement}', [MovementController::class, 'destroy'])->name('movements.destroy');

        Route::get('cortes', [DailyCutController::class, 'index'])->name('cuts.index');
        Route::post('cortes', [DailyCutController::class, 'store'])->name('cuts.store');

        Route::get('flujo-planeado', [PlannedPaymentController::class, 'index'])->name('planned.index');
        Route::post('flujo-planeado', [PlannedPaymentController::class, 'store'])->name('planned.store');
        Route::post('flujo-planeado/copiar', [PlannedPaymentController::class, 'copyMonth'])->name('planned.copy');
        Route::put('flujo-planeado/{payment}', [PlannedPaymentController::class, 'update'])->name('planned.update');
        Route::post('flujo-planeado/{payment}/pagado', [PlannedPaymentController::class, 'markPaid'])->name('planned.paid');
        Route::post('flujo-planeado/{payment}/pagado-con-credito', [PlannedPaymentController::class, 'markPaidWithCredit'])->name('planned.credit-paid');
        Route::post('flujo-planeado/{payment}/pagado-con-credito-nuevo', [PlannedPaymentController::class, 'markPaidWithNewCredit'])->name('planned.credit-new');
        Route::get('flujo-planeado/{payment}/vincular', [PlannedPaymentController::class, 'link'])->name('planned.link');
        Route::post('flujo-planeado/{payment}/vincular', [PlannedPaymentController::class, 'linkMovement'])->name('planned.link-movement');
        Route::post('flujo-planeado/{payment}/registrado', [PlannedPaymentController::class, 'markRegistered'])->name('planned.registered');
        Route::post('flujo-planeado/{payment}/no-pagado', [PlannedPaymentController::class, 'skip'])->name('planned.skip');
        Route::delete('flujo-planeado/{payment}', [PlannedPaymentController::class, 'destroy'])->name('planned.destroy');

        Route::get('ingresos-esperados', [ExpectedIncomeController::class, 'index'])->name('expected-incomes.index');
        Route::post('ingresos-esperados', [ExpectedIncomeController::class, 'store'])->name('expected-incomes.store');
        Route::post('ingresos-esperados/copiar', [ExpectedIncomeController::class, 'copyMonth'])->name('expected-incomes.copy');
        Route::put('ingresos-esperados/{income}', [ExpectedIncomeController::class, 'update'])->name('expected-incomes.update');
        Route::get('ingresos-esperados/{income}/vincular', [ExpectedIncomeController::class, 'link'])->name('expected-incomes.link');
        Route::post('ingresos-esperados/{income}/vincular', [ExpectedIncomeController::class, 'linkMovement'])->name('expected-incomes.link-movement');
        Route::post('ingresos-esperados/{income}/desligar', [ExpectedIncomeController::class, 'unlinkMovement'])->name('expected-incomes.unlink-movement');
        Route::post('ingresos-esperados/{income}/recibido', [ExpectedIncomeController::class, 'markReceived'])->name('expected-incomes.received');
        Route::post('ingresos-esperados/{income}/registrado', [ExpectedIncomeController::class, 'markRegistered'])->name('expected-incomes.registered');
        Route::post('ingresos-esperados/{income}/no-recibido', [ExpectedIncomeController::class, 'skip'])->name('expected-incomes.skip');
        Route::delete('ingresos-esperados/abonos/{payment}', [ExpectedIncomeController::class, 'destroyPayment'])->name('expected-incomes.payments.destroy');
        Route::delete('ingresos-esperados/{income}', [ExpectedIncomeController::class, 'destroy'])->name('expected-incomes.destroy');

        Route::get('recordatorios', [ReminderController::class, 'index'])->name('reminders.index');
        Route::post('recordatorios', [ReminderController::class, 'store'])->name('reminders.store');
        Route::put('recordatorios/{reminder}', [ReminderController::class, 'update'])->name('reminders.update');
        Route::post('recordatorios/{reminder}/hecho', [ReminderController::class, 'complete'])->name('reminders.complete');
        Route::post('recordatorios/{reminder}/omitir', [ReminderController::class, 'skip'])->name('reminders.skip');

        Route::get('creditos', [CreditPurchaseController::class, 'index'])->name('credits.index');
        Route::post('creditos', [CreditPurchaseController::class, 'store'])->name('credits.store');
        Route::post('creditos/recalcular-fechas', [CreditPurchaseController::class, 'recalculateDueDates'])->name('credits.recalculate-dates');
        Route::post('creditos/{credit}/abonos-libres', [CreditPurchaseController::class, 'storeFreePayment'])->name('credits.free-payments.store');
        Route::delete('creditos/abonos-libres/{payment}', [CreditPurchaseController::class, 'destroyFreePayment'])->name('credits.free-payments.destroy');
        Route::put('creditos/{credit}', [CreditPurchaseController::class, 'update'])->name('credits.update');
        Route::delete('creditos/{credit}', [CreditPurchaseController::class, 'destroy'])->name('credits.destroy');
        Route::put('creditos/mensualidades/{installment}', [CreditPurchaseController::class, 'updateInstallment'])->name('credits.installments.update');
        Route::delete('creditos/mensualidades/{installment}', [CreditPurchaseController::class, 'destroyInstallment'])->name('credits.installments.destroy');
        Route::post('creditos/mensualidades/{installment}/pagado', [CreditPurchaseController::class, 'markInstallmentPaid'])->name('credits.installments.paid');
        Route::post('creditos/mensualidades/{installment}/registrado', [CreditPurchaseController::class, 'markInstallmentRegistered'])->name('credits.installments.registered');

        Route::get('san-juan', [SanJuanController::class, 'index'])->name('san-juan.index');
        Route::post('san-juan/rentas', [SanJuanController::class, 'storeRentalContract'])->name('san-juan.rentals.store');
        Route::put('san-juan/rentas/{contract}', [SanJuanController::class, 'updateRentalContract'])->name('san-juan.rentals.update');
        Route::delete('san-juan/rentas/{contract}', [SanJuanController::class, 'destroyRentalContract'])->name('san-juan.rentals.destroy');
        Route::post('san-juan/rentas/{contract}/recibido', [SanJuanController::class, 'markRentalReceived'])->name('san-juan.rentals.received');

        Route::get('categorias', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('categorias', [CategoryController::class, 'store'])->name('categories.store');
        Route::post('categorias/colores-sugeridos', [CategoryController::class, 'applySuggestedColors'])->name('categories.apply-colors');
        Route::put('categorias/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::post('categorias/{category}/unificar', [CategoryController::class, 'merge'])->name('categories.merge');
        Route::delete('categorias/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    });

    Route::get('{first}/{second}/{third}', [RoutingController::class, 'thirdLevel'])->name('third');
    Route::get('{first}/{second}', [RoutingController::class, 'secondLevel'])->name('second');
    Route::get('{any}', [RoutingController::class, 'root'])->name('any');
});
