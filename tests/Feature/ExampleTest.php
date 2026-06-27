<?php

use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\DailyCut;
use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\Movement;
use App\Models\Finance\Person;
use App\Models\Finance\PlannedPayment;
use App\Models\Finance\RentalContract;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use App\Services\Finance\FinanceExcelImportService;
use App\Services\Finance\FinanceSummaryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

it('redirects guests to login', function () {
    $this->get('/')->assertRedirect(route('login'));
});

it('shows the finance dashboard to authenticated users', function () {
    $user = User::firstOrCreate(
        ['email' => 'feature-test@example.com'],
        [
            'name' => 'Feature Test',
            'password' => Hash::make('password'),
        ],
    );

    // El enlace de Diagnóstico es owner-only; este test valida la vista del dueño.
    config(['finance.owner_email' => 'feature-test@example.com']);

    $this->actingAs($user)
        ->get('/finanzas')
        ->assertOk()
        ->assertSee('Finanzas')
        ->assertSee('Saldo proyectado antes de obligaciones')
        ->assertSee('Oportunidades de mejora')
        ->assertSee('Nuevo movimiento')
        ->assertSee('Corte diario')
        ->assertSee('Diagnóstico')
        ->assertSee('/finanzas/diagnostico')
        ->assertSee('Diseño')
        ->assertSee('Auto ajuste')
        ->assertSee('financeDashboardGrid')
        ->assertSee('data-save-url', false)
        ->assertSee('data-server-layout', false);
});

it('lets authenticated users edit their movements', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $account = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $originalCategory = Category::where('user_id', $user->id)->where('name', 'Comida')->firstOrFail();
    $newCategory = Category::where('user_id', $user->id)->where('name', 'Gasolina')->firstOrFail();

    $movement = Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-20',
        'movement_type' => 'expense',
        'amount' => 50,
        'description' => 'Movimiento mal capturado',
        'account_id' => $account->id,
        'category_id' => $originalCategory->id,
        'source' => 'manual',
    ]);

    $this->actingAs($user)
        ->get(route('finance.movements.edit', $movement))
        ->assertOk()
        ->assertSee('Editar movimiento')
        ->assertSee('Movimiento mal capturado');

    $this->actingAs($user)
        ->put(route('finance.movements.update', $movement), [
            'happened_on' => '2026-06-21',
            'movement_type' => 'expense',
            'amount' => 75.50,
            'description' => 'Gasolina corregida',
            'account_id' => $account->id,
            'category_id' => $newCategory->id,
            'person_id' => null,
            'is_san_juan' => '0',
            'is_rent' => '0',
            'is_unknown' => '0',
            'notes' => 'Correccion manual',
        ])
        ->assertRedirect(route('finance.movements.index', ['month' => '2026-06']));

    $movement->refresh();

    expect($movement->happened_on->toDateString())->toBe('2026-06-21');
    expect((float) $movement->amount)->toBe(75.50);
    expect($movement->description)->toBe('Gasolina corregida');
    expect($movement->category_id)->toBe($newCategory->id);
    expect($movement->notes)->toBe('Correccion manual');
});

it('lets users search movement history', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $account = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $category = Category::where('user_id', $user->id)->where('name', 'Uber carro')->firstOrFail();
    $food = Category::where('user_id', $user->id)->where('name', 'Comida')->firstOrFail();

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-20',
        'movement_type' => 'expense',
        'amount' => 47.35,
        'description' => 'Uber Carro',
        'account_id' => $account->id,
        'category_id' => $category->id,
        'source' => 'manual',
    ]);

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-20',
        'movement_type' => 'expense',
        'amount' => 276,
        'description' => 'Taqueria',
        'account_id' => $account->id,
        'category_id' => $food->id,
        'source' => 'manual',
    ]);

    $this->actingAs($user)
        ->get('/finanzas/movimientos?month=2026-06&q=Uber&per_page=77')
        ->assertOk()
        ->assertSee('Búsqueda: Uber')
        ->assertSee('Personalizado')
        ->assertSee('value="77"', false)
        ->assertSee('Mostrando 1 a 1 de 1 movimientos')
        ->assertSee('Uber Carro')
        ->assertDontSee('Taqueria');
});

it('calculates NU daily yield from consecutive cuts', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $nu = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $cash = Account::where('user_id', $user->id)->where('name', 'Efectivo')->firstOrFail();
    $food = Category::where('user_id', $user->id)->where('name', 'Comida')->firstOrFail();

    $previousCut = DailyCut::create([
        'user_id' => $user->id,
        'cut_date' => '2026-06-19',
        'cards_amount' => 1000,
        'real_total' => 1000,
        'status' => 'ok',
    ]);
    $previousCut->balances()->create([
        'account_id' => $nu->id,
        'balance' => 1000,
    ]);

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-20',
        'movement_type' => 'expense',
        'amount' => 50,
        'description' => 'Comida NU',
        'account_id' => $nu->id,
        'category_id' => $food->id,
        'source' => 'manual',
    ]);

    $this->actingAs($user)
        ->post(route('finance.cuts.store'), [
            'cut_date' => '2026-06-20',
            'balances' => [
                $cash->id => 0,
                $nu->id => 951.08,
            ],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $yield = Movement::where('user_id', $user->id)
        ->where('account_id', $nu->id)
        ->where('movement_type', 'yield')
        ->whereDate('happened_on', '2026-06-20')
        ->firstOrFail();

    expect($yield->description)->toBe('Rendimiento NU');
    expect((float) $yield->amount)->toBe(1.08);
    expect($yield->source)->toBe('auto:daily-cut');
});

it('does not import real movements dated after the latest imported cut', function () {
    $user = User::factory()->create();

    $book = new Spreadsheet();
    $incomes = $book->getActiveSheet();
    $incomes->setTitle('Ingresos Reales');
    $incomes->fromArray([
        ['Fecha', 'Mes', 'Importe', 'Concepto', 'Categoria', 'Fuente', 'Notas', 'Categoria sugerida', 'Usar'],
    ], null, 'A3');
    $incomes->setCellValue('A4', '2026-06-20');
    $incomes->setCellValue('C4', 100);
    $incomes->setCellValue('D4', 'Ingreso recibido');
    $incomes->setCellValue('E4', 'Otros ingresos');
    $incomes->setCellValue('I4', 'Si');
    $incomes->setCellValue('A5', '2026-06-28');
    $incomes->setCellValue('C5', 2000);
    $incomes->setCellValue('D5', 'Oswaldo 2000R');
    $incomes->setCellValue('E5', 'Rentas San Juan');
    $incomes->setCellValue('I5', 'Si');

    $cuts = $book->createSheet();
    $cuts->setTitle('Conciliacion Diaria');
    $cuts->setCellValue('A4', '2026-06-20');
    $cuts->setCellValue('L4', 500);

    $path = tempnam(sys_get_temp_dir(), 'finance-import-') . '.xlsx';
    (new Xlsx($book))->save($path);

    try {
        $counts = app(FinanceExcelImportService::class)->import($user, $path, true);
    } finally {
        @unlink($path);
    }

    expect($counts['incomes'])->toBe(1);
    expect(Movement::where('user_id', $user->id)->where('description', 'Ingreso recibido')->exists())->toBeTrue();
    expect(Movement::where('user_id', $user->id)->where('description', 'Oswaldo 2000R')->exists())->toBeFalse();
});

it('shows upcoming payment timing in Spanish', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => today()->startOfMonth()->toDateString(),
        'due_date' => today()->toDateString(),
        'name' => 'Pago de prueba',
        'amount' => 250,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->get('/finanzas')
        ->assertOk()
        ->assertSee('Próximos pagos')
        ->assertSee('Pago de prueba')
        ->assertSee('Hoy');
});

it('shows upcoming expected rent incomes on the dashboard', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $oswaldo = Person::where('user_id', $user->id)->where('name', 'Oswaldo')->firstOrFail();

    RentalContract::where('user_id', $user->id)
        ->where('person_id', $oswaldo->id)
        ->update([
            'room' => '3',
            'expected_amount' => 2000,
            'due_day' => 28,
            'is_active' => true,
        ]);

    $this->actingAs($user)
        ->get('/finanzas?month=2026-06')
        ->assertOk()
        ->assertSee('Ingresos proyectados')
        ->assertSee('Próximos ingresos')
        ->assertSee('Oswaldo')
        ->assertSee('Renta cuarto 3')
        ->assertSee('$2,000.00');
});

it('lets users create expected incomes for the dashboard', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $account = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $category = Category::where('user_id', $user->id)->where('name', 'Andrea comida')->firstOrFail();
    $andrea = Person::where('user_id', $user->id)->where('name', 'Andrea')->firstOrFail();

    $this->actingAs($user)
        ->post(route('finance.expected-incomes.store'), [
            'period_month' => '2026-06',
            'due_date' => '2026-06-24',
            'name' => 'Comida Andrea',
            'amount' => 500,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'person_id' => $andrea->id,
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->get('/finanzas?month=2026-06')
        ->assertOk()
        ->assertSee('Próximos ingresos')
        ->assertSee('Andrea')
        ->assertSee('Comida Andrea')
        ->assertSee('$500.00');
});

it('lets users create expected incomes with a new person', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $account = Account::where('user_id', $user->id)->where('name', 'BBVA')->firstOrFail();
    $category = Category::where('user_id', $user->id)->where('name', 'SCIOS / FESI')->firstOrFail();

    $this->actingAs($user)
        ->post(route('finance.expected-incomes.store'), [
            'period_month' => '2026-06',
            'due_date' => '2026-06-26',
            'name' => 'Pago ITTLA',
            'amount' => 5800,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'new_person_name' => 'ITTLA',
        ])
        ->assertRedirect();

    $person = Person::where('user_id', $user->id)->where('name', 'ITTLA')->firstOrFail();
    $income = ExpectedIncome::where('user_id', $user->id)->where('name', 'Pago ITTLA')->firstOrFail();

    expect($person->is_tenant)->toBeFalse();
    expect($income->person_id)->toBe($person->id);

    $this->actingAs($user)
        ->get('/finanzas/ingresos-esperados?month=2026-06')
        ->assertOk()
        ->assertSee('Ingresos esperados serán estos')
        ->assertSee('Pago ITTLA')
        ->assertSee('ITTLA')
        ->assertSee('$5,800.00');
});

it('copies expected incomes from one month to another as a template', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $account = Account::where('user_id', $user->id)->where('name', 'BBVA')->firstOrFail();
    $category = Category::where('user_id', $user->id)->where('name', 'SCIOS / FESI')->firstOrFail();
    $person = Person::create([
        'user_id' => $user->id,
        'name' => 'ITTLA',
        'type' => 'other',
        'is_active' => true,
    ]);

    $sourceIncome = ExpectedIncome::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-26',
        'name' => 'Pago ITTLA',
        'amount' => 5800,
        'received_amount' => 5800,
        'received_on' => '2026-06-26',
        'status' => 'received',
        'account_id' => $account->id,
        'category_id' => $category->id,
        'person_id' => $person->id,
        'notes' => 'Pago mensual',
    ]);

    ExpectedIncome::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-28',
        'name' => 'Renta generada',
        'amount' => 2000,
        'status' => 'received',
        'import_key' => 'rental-contract:999:2026-06',
    ]);

    $this->actingAs($user)
        ->post(route('finance.expected-incomes.copy'), [
            'source_month' => '2026-06',
            'target_month' => '2026-07',
        ])
        ->assertRedirect(route('finance.expected-incomes.index', ['month' => '2026-07']));

    $copiedIncome = ExpectedIncome::where('user_id', $user->id)
        ->whereDate('period_month', '2026-07-01')
        ->where('name', 'Pago ITTLA')
        ->firstOrFail();

    $sourceIncome->refresh();

    expect($sourceIncome->status)->toBe('received');
    expect($copiedIncome->id)->not->toBe($sourceIncome->id);
    expect($copiedIncome->due_date->toDateString())->toBe('2026-07-26');
    expect($copiedIncome->status)->toBe('pending');
    expect((float) $copiedIncome->received_amount)->toBe(0.0);
    expect($copiedIncome->received_on)->toBeNull();
    expect($copiedIncome->movement_id)->toBeNull();
    expect($copiedIncome->person_id)->toBe($person->id);
    expect(ExpectedIncome::where('user_id', $user->id)->whereDate('period_month', '2026-07-01')->count())->toBe(1);

    $this->actingAs($user)
        ->get('/finanzas/ingresos-esperados?month=2026-07')
        ->assertOk()
        ->assertSee('Copiar ingresos como plantilla')
        ->assertSee('Pago ITTLA')
        ->assertSee('$5,800.00');
});

it('shows automatic San Juan rents on expected incomes page', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $lazaro = Person::where('user_id', $user->id)->where('name', 'Lazaro')->firstOrFail();
    $cesar = Person::where('user_id', $user->id)->where('name', 'Cesar')->firstOrFail();

    RentalContract::where('user_id', $user->id)->where('person_id', $lazaro->id)->update([
        'expected_amount' => 700,
        'due_day' => 26,
        'is_active' => true,
    ]);
    RentalContract::where('user_id', $user->id)->where('person_id', $cesar->id)->update([
        'room' => '4',
        'expected_amount' => 2200,
        'due_day' => 27,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get('/finanzas/ingresos-esperados?month=2026-06')
        ->assertOk()
        ->assertSee('Lazaro')
        ->assertSee('$700.00')
        ->assertSee('Cesar')
        ->assertSee('Renta cuarto 4')
        ->assertSee('$2,200.00');
});

it('marks an expected income as received and creates the real income movement', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $account = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $category = Category::where('user_id', $user->id)->where('name', 'SCIOS / FESI')->firstOrFail();

    $income = ExpectedIncome::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-25',
        'name' => 'FESI',
        'amount' => 8000,
        'status' => 'pending',
        'account_id' => $account->id,
        'category_id' => $category->id,
    ]);

    $this->actingAs($user)
        ->post(route('finance.expected-incomes.received', $income), [
            'received_on' => '2026-06-24',
        ])
        ->assertRedirect();

    $income->refresh();

    expect($income->status)->toBe('received');
    expect((float) $income->received_amount)->toBe(8000.0);
    expect($income->movement_id)->not->toBeNull();

    $movement = Movement::findOrFail($income->movement_id);

    expect($movement->movement_type)->toBe('income');
    expect($movement->happened_on->toDateString())->toBe('2026-06-24');
    expect((float) $movement->amount)->toBe(8000.0);
    expect($movement->description)->toBe('Ingreso esperado: FESI');
});

it('shows expected income actions in a modal with movement candidates', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $account = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $category = Category::where('user_id', $user->id)->where('name', 'SCIOS / FESI')->firstOrFail();

    $income = ExpectedIncome::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-30',
        'name' => 'FESI Mensualidad',
        'amount' => 8000,
        'status' => 'pending',
        'account_id' => $account->id,
        'category_id' => $category->id,
    ]);

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-29',
        'movement_type' => 'income',
        'amount' => 8000,
        'description' => 'Deposito FESI',
        'account_id' => $account->id,
        'category_id' => $category->id,
        'source' => 'manual',
    ]);

    $response = $this->actingAs($user)
        ->get('/finanzas/ingresos-esperados?month=2026-06')
        ->assertOk()
        ->assertSee('FESI Mensualidad')
        ->assertSee('Acciones')
        ->assertSee('Marcar como recibido')
        ->assertSee('Vincular movimiento existente')
        ->assertSee('Ya lo capture como ingreso')
        ->assertSee('Marcar como no recibido')
        ->assertSee('Editar ingreso')
        ->assertSee('Eliminar ingreso')
        ->assertSee('Monto coincide')
        ->assertSee('Aplicar abono');

    $html = $response->getContent();
    $linkFormStart = strpos($html, 'action="' . route('finance.expected-incomes.link-movement', $income) . '"');
    $linkFormEnd = strpos($html, '</form>', $linkFormStart);
    expect($linkFormStart)->not->toBeFalse();
    expect($linkFormEnd)->not->toBeFalse();
    $linkForm = substr($html, $linkFormStart, $linkFormEnd - $linkFormStart);

    expect($linkForm)->toContain('movement_id');
    expect($linkForm)->toContain('amount_applied');
    expect($linkForm)->not->toContain('received_on');
});

it('shows San Juan rental rows with actions modal instead of loose table controls', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $cesar = Person::where('user_id', $user->id)->where('name', 'Cesar')->firstOrFail();
    RentalContract::where('user_id', $user->id)->where('person_id', $cesar->id)->update([
        'room' => '4',
        'expected_amount' => 2200,
        'due_day' => 27,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get('/finanzas/ingresos-esperados?month=2026-06')
        ->assertOk()
        ->assertSee('Renta cuarto 4')
        ->assertSee('Contrato San Juan')
        ->assertSee('Registrar renta recibida')
        ->assertSee('Administrar contrato')
        ->assertDontSee('form-control form-control-sm mb-1', false)
        ->assertDontSee('title="Editar contrato"', false);
});

it('lets users edit San Juan rental contracts', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $oswaldo = Person::where('user_id', $user->id)->where('name', 'Oswaldo')->firstOrFail();
    $contract = RentalContract::where('user_id', $user->id)->where('person_id', $oswaldo->id)->firstOrFail();

    $this->actingAs($user)
        ->put(route('finance.san-juan.rentals.update', $contract), [
            'person_name' => 'Oswaldo',
            'room' => '3',
            'expected_amount' => 2100,
            'due_day' => 26,
            'starts_on' => null,
            'ends_on' => null,
            'is_active' => '1',
            'notes' => 'Ajuste de prueba',
        ])
        ->assertRedirect();

    $contract->refresh();

    expect($contract->room)->toBe('3');
    expect((float) $contract->expected_amount)->toBe(2100.0);
    expect($contract->due_day)->toBe(26);
    expect($contract->notes)->toBe('Ajuste de prueba');
    expect($contract->manual_override)->toBeTrue();

    $this->actingAs($user)
        ->get('/finanzas?month=2026-06')
        ->assertOk()
        ->assertSee('Oswaldo')
        ->assertSee('$2,100.00');
});

it('lets users create San Juan rental contracts', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $this->actingAs($user)
        ->post(route('finance.san-juan.rentals.store'), [
            'person_name' => 'Nuevo Inquilino',
            'room' => '6',
            'expected_amount' => 1800,
            'due_day' => 12,
        ])
        ->assertRedirect();

    $person = Person::where('user_id', $user->id)->where('name', 'Nuevo Inquilino')->firstOrFail();
    $contract = RentalContract::where('user_id', $user->id)->where('person_id', $person->id)->firstOrFail();

    expect($person->is_tenant)->toBeTrue();
    expect((float) $contract->expected_amount)->toBe(1800.0);
    expect($contract->due_day)->toBe(12);
    expect($contract->manual_override)->toBeTrue();
});

it('lets users delete San Juan rental contracts from the template', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $oswaldo = Person::where('user_id', $user->id)->where('name', 'Oswaldo')->firstOrFail();
    $contract = RentalContract::where('user_id', $user->id)->where('person_id', $oswaldo->id)->firstOrFail();

    $contract->update([
        'room' => '3',
        'expected_amount' => 2000,
        'due_day' => 28,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get('/finanzas/san-juan?month=2026-06')
        ->assertOk()
        ->assertSee('Plantilla de rentas')
        ->assertSee('Plantilla mensual de rentas')
        ->assertSee('$2,000.00')
        ->assertSee('Oswaldo');

    $this->actingAs($user)
        ->delete(route('finance.san-juan.rentals.destroy', $contract))
        ->assertRedirect();

    expect(RentalContract::whereKey($contract->id)->exists())->toBeFalse();

    $oswaldo->refresh();

    expect($oswaldo->is_tenant)->toBeFalse();

    $this->actingAs($user)
        ->get('/finanzas/san-juan?month=2026-06')
        ->assertOk()
        ->assertDontSee('Oswaldo');
});

it('lets users edit categories and safely delete or deactivate them', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $category = Category::create([
        'user_id' => $user->id,
        'name' => 'Streaming',
        'type' => 'expense',
        'group' => 'Servicios',
        'color' => '#123456',
        'keywords' => 'netflix',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->put(route('finance.categories.update', $category), [
            'name' => 'Streaming casa',
            'type' => 'expense',
            'group' => 'Casa',
            'color' => '#654321',
            'keywords' => 'netflix,prime',
            'is_san_juan' => '1',
            'is_rent' => '0',
            'is_active' => '1',
        ])
        ->assertRedirect();

    $category->refresh();

    expect($category->name)->toBe('Streaming casa');
    expect($category->group)->toBe('Casa');
    expect($category->color)->toBe('#654321');
    expect($category->keywords)->toBe('netflix,prime');
    expect($category->is_san_juan)->toBeTrue();
    expect($category->is_active)->toBeTrue();

    $unused = Category::create([
        'user_id' => $user->id,
        'name' => 'Temporal',
        'type' => 'expense',
        'group' => 'Prueba',
        'color' => '#111111',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->delete(route('finance.categories.destroy', $unused))
        ->assertRedirect();

    expect(Category::whereKey($unused->id)->exists())->toBeFalse();

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-10',
        'movement_type' => 'expense',
        'amount' => 100,
        'description' => 'Pago con categoria usada',
        'category_id' => $category->id,
    ]);

    $this->actingAs($user)
        ->delete(route('finance.categories.destroy', $category))
        ->assertRedirect();

    $category->refresh();

    expect($category->is_active)->toBeFalse();
    expect(Movement::where('category_id', $category->id)->exists())->toBeTrue();

    $this->actingAs($user)
        ->get('/finanzas/categorias')
        ->assertOk()
        ->assertSee('Streaming casa')
        ->assertSee('Inactiva');
});

it('lets users register a San Juan rent payment as real income', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $nu = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $oswaldo = Person::where('user_id', $user->id)->where('name', 'Oswaldo')->firstOrFail();
    $contract = RentalContract::where('user_id', $user->id)->where('person_id', $oswaldo->id)->firstOrFail();
    $contract->update([
        'room' => '3',
        'expected_amount' => 2000,
        'due_day' => 28,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->post(route('finance.san-juan.rentals.received', $contract), [
            'month' => '2026-06',
            'received_on' => '2026-06-24',
            'amount' => 2000,
            'account_id' => $nu->id,
        ])
        ->assertRedirect();

    $movement = Movement::where('user_id', $user->id)
        ->where('source', 'rental_contract')
        ->firstOrFail();

    expect($movement->movement_type)->toBe('income');
    expect($movement->happened_on->toDateString())->toBe('2026-06-24');
    expect((float) $movement->amount)->toBe(2000.0);
    expect($movement->person_id)->toBe($oswaldo->id);
    expect($movement->is_rent)->toBeTrue();

    $income = ExpectedIncome::where('user_id', $user->id)
        ->where('import_key', 'rental-contract:' . $contract->id . ':2026-06')
        ->firstOrFail();

    expect($income->status)->toBe('received');
    expect($income->movement_id)->toBe($movement->id);

    $this->actingAs($user)
        ->get('/finanzas?month=2026-06')
        ->assertOk()
        ->assertDontSee('Renta cuarto 3');
});

it('copies planned payments from one month to another as a template', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $category = Category::where('user_id', $user->id)->where('name', 'Casa')->firstOrFail();

    PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-30',
        'name' => 'Luz prueba',
        'amount' => 500,
        'status' => 'paid',
        'paid_amount' => 500,
        'category_id' => $category->id,
    ]);

    $this->actingAs($user)
        ->post(route('finance.planned.copy'), [
            'source_month' => '2026-06',
            'target_month' => '2026-07',
        ])
        ->assertRedirect(route('finance.planned.index', ['month' => '2026-07']));

    $copy = PlannedPayment::where('user_id', $user->id)
        ->whereDate('period_month', '2026-07-01')
        ->where('name', 'Luz prueba')
        ->firstOrFail();

    expect($copy->due_date->toDateString())->toBe('2026-07-30');
    expect($copy->status)->toBe('pending');
    expect((float) $copy->paid_amount)->toBe(0.0);
    expect((float) $copy->amount)->toBe(500.0);
});

it('shows planned flow totals for the selected month', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $category = Category::where('user_id', $user->id)->where('name', 'Casa')->firstOrFail();

    PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-10',
        'name' => 'Luz prueba',
        'amount' => 500,
        'paid_amount' => 500,
        'paid_on' => '2026-06-10',
        'status' => 'paid',
        'category_id' => $category->id,
    ]);

    PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-20',
        'name' => 'Amazon prueba',
        'amount' => 300,
        'status' => 'pending',
        'category_id' => $category->id,
    ]);

    PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-21',
        'name' => 'Pago cancelado',
        'amount' => 100,
        'status' => 'skipped',
        'category_id' => $category->id,
    ]);

    $credit = CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-01',
        'name' => 'Credito prueba',
        'total_amount' => 1200,
        'months' => 3,
        'first_due_month' => '2026-06-01',
        'due_day' => 25,
        'category_id' => $category->id,
    ]);

    CreditInstallment::create([
        'credit_purchase_id' => $credit->id,
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-25',
        'installment_number' => 1,
        'amount' => 400,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->get('/finanzas/flujo-planeado?month=2026-06')
        ->assertOk()
        ->assertSee('Total a pagar este mes')
        ->assertSee('$1,200.00')
        ->assertSee('Ya pagado')
        ->assertSee('$500.00')
        ->assertSee('Pendiente por pagar')
        ->assertSee('$700.00')
        ->assertSee('Obligaciones no pagadas / pendientes de decisión')
        ->assertSee('$100.00');
});

it('shows a unified monthly obligation list for planned payments and credits', function () {
    Carbon::setTestNow('2026-06-22');

    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $category = Category::where('user_id', $user->id)->where('name', 'Casa')->firstOrFail();

    $linkedMovement = Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-09',
        'movement_type' => 'expense',
        'amount' => 75,
        'description' => 'Pago ya vinculado',
        'category_id' => $category->id,
        'source' => 'manual',
    ]);

    PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-26',
        'name' => 'Pago futuro',
        'amount' => 100,
        'status' => 'pending',
        'category_id' => $category->id,
    ]);

    PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-10',
        'name' => 'Pago vencido',
        'amount' => 200,
        'status' => 'overdue',
        'category_id' => $category->id,
    ]);

    PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-09',
        'name' => 'Pago ya pagado',
        'amount' => 75,
        'paid_amount' => 75,
        'paid_on' => '2026-06-09',
        'status' => 'paid',
        'movement_id' => $linkedMovement->id,
        'category_id' => $category->id,
    ]);

    PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-11',
        'name' => 'Pago no pagado',
        'amount' => 300,
        'status' => 'skipped',
        'category_id' => $category->id,
    ]);

    $credit = CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-01',
        'name' => 'Amazon',
        'total_amount' => 400,
        'months' => 1,
        'first_due_month' => '2026-06-01',
        'due_day' => 27,
        'category_id' => $category->id,
    ]);

    CreditInstallment::create([
        'credit_purchase_id' => $credit->id,
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-27',
        'installment_number' => 1,
        'amount' => 400,
        'status' => 'pending',
    ]);

    $summary = app(FinanceSummaryService::class)->monthSummary($user, '2026-06');

    expect($summary['pending_payments'])->toBe(700.0);
    expect($summary['obligation_totals']['pending'])->toBe(700.0);
    expect($summary['obligation_totals']['overdue'])->toBe(200.0);
    expect($summary['obligation_totals']['paid'])->toBe(75.0);
    expect($summary['obligation_totals']['credits'])->toBe(400.0);
    expect($summary['obligation_totals']['planned'])->toBe(375.0);
    expect($summary['obligation_totals']['skipped'])->toBe(300.0);

    $nextPaymentNames = $summary['next_payments']->pluck('name')->all();

    expect($nextPaymentNames)->toContain('Pago futuro');
    expect($nextPaymentNames)->toContain('Pago vencido');
    expect($nextPaymentNames)->toContain('Crédito: Amazon');
    expect($nextPaymentNames)->not->toContain('Pago ya pagado');
    expect($nextPaymentNames)->toContain('Pago no pagado');
    expect($summary['next_payments']->firstWhere('name', 'Pago vencido')['origin_detail'])->toBe('Vencido pendiente');
    expect($summary['next_payments']->firstWhere('name', 'Pago no pagado')['amount_due'])->toBe(0.0);
    expect($summary['skipped_obligations']->pluck('name')->all())->toContain('Pago no pagado');

    $this->actingAs($user)
        ->get('/finanzas?month=2026-06')
        ->assertOk()
        ->assertSee('Obligaciones del mes')
        ->assertSee('Atención: hay obligaciones vencidas por registrar')
        ->assertSee('Pendiente por pagar este mes')
        ->assertSee('$700.00')
        ->assertSee('Vencido pendiente')
        ->assertSee('Créditos / tarjetas')
        ->assertSee('Compra a meses: Amazon')
        ->assertSee('Crédito: Amazon')
        ->assertDontSee('Pago ya pagado')
        ->assertSee('Pago no pagado')
        ->assertSee('No pagado / pendiente de decisión');

    $this->actingAs($user)
        ->get('/finanzas?month=2026-06&detail=amount-missing')
        ->assertOk()
        ->assertSee('Detalle del indicador: Saldo disponible después de obligaciones')
        ->assertSee('Saldo real del corte')
        ->assertSee('Obligaciones pendientes');

    $this->actingAs($user)
        ->get('/finanzas/flujo-planeado?month=2026-06')
        ->assertOk()
        ->assertSee('Pago no pagado')
        ->assertSee('No pagado / pendiente de decisión')
        ->assertSee('Pagado/vinculado');

    Carbon::setTestNow();
});

it('lets users edit planned payments and refresh monthly obligation totals', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $account = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $category = Category::where('user_id', $user->id)->where('name', 'Comida')->firstOrFail();

    $payment = PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-20',
        'name' => 'Amazon viejo',
        'amount' => 99,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->put(route('finance.planned.update', $payment), [
            'due_date' => '2026-06-24',
            'name' => 'Amazon corregido',
            'amount' => 149.50,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'person_id' => null,
            'notes' => 'Correccion de flujo',
        ])
        ->assertRedirect(route('finance.planned.index', ['month' => '2026-06']));

    $payment->refresh();

    expect($payment->due_date->toDateString())->toBe('2026-06-24');
    expect($payment->name)->toBe('Amazon corregido');
    expect((float) $payment->amount)->toBe(149.50);
    expect($payment->account_id)->toBe($account->id);
    expect($payment->category_id)->toBe($category->id);
    expect($payment->notes)->toBe('Correccion de flujo');

    $summary = app(FinanceSummaryService::class)->monthSummary($user, '2026-06');

    expect($summary['pending_payments'])->toBe(149.50);
    expect($summary['next_payments']->firstWhere('name', 'Amazon corregido')['amount_due'])->toBe(149.50);

    $this->actingAs($user)
        ->get('/finanzas/flujo-planeado?month=2026-06')
        ->assertOk()
        ->assertSee('Amazon corregido')
        ->assertSee('$149.50')
        ->assertSee('Editar');
});

it('creates credits by monthly payment amount', function () {
    Carbon::setTestNow('2026-06-22');

    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $category = Category::where('user_id', $user->id)->where('name', 'Crédito / tarjeta')->firstOrFail();

    $this->actingAs($user)
        ->post(route('finance.credits.store'), [
            'purchase_date' => '2026-06-22',
            'name' => 'Amazon',
            'amount_mode' => 'monthly',
            'monthly_amount' => 189.85,
            'months' => 6,
            'first_due_month' => '2026-07',
            'due_day' => 27,
            'category_id' => $category->id,
            'notes' => 'Prueba mensual',
        ])
        ->assertRedirect();

    $credit = CreditPurchase::where('user_id', $user->id)->where('name', 'Amazon')->firstOrFail();

    expect((float) $credit->total_amount)->toBe(1139.10);
    expect($credit->months)->toBe(6);
    expect($credit->installments()->count())->toBe(6);
    expect($credit->installments()->pluck('amount')->map(fn ($amount) => (float) $amount)->all())->toBe([
        189.85,
        189.85,
        189.85,
        189.85,
        189.85,
        189.85,
    ]);

    $first = $credit->installments()->orderBy('installment_number')->firstOrFail();

    expect($first->period_month->toDateString())->toBe('2026-07-01');
    expect($first->due_date->toDateString())->toBe('2026-07-27');

    $this->actingAs($user)
        ->get('/finanzas/creditos')
        ->assertOk()
        ->assertSee('Debes pagar el siguiente mes')
        ->assertSee('$189.85')
        ->assertSee('$1,139.10');

    Carbon::setTestNow();
});

it('lets users bulk edit installments edit one installment and delete a credit', function () {
    Carbon::setTestNow('2026-06-22');

    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $this->actingAs($user)
        ->post(route('finance.credits.store'), [
            'purchase_date' => '2026-06-22',
            'name' => 'Amazon',
            'amount_mode' => 'total',
            'total_amount' => 189.85,
            'months' => 6,
            'first_due_month' => '2026-07',
            'due_day' => 27,
        ])
        ->assertRedirect();

    $credit = CreditPurchase::where('user_id', $user->id)->where('name', 'Amazon')->firstOrFail();

    expect((float) $credit->installments()->orderBy('installment_number')->firstOrFail()->amount)->toBe(31.64);

    $this->actingAs($user)
        ->put(route('finance.credits.update', $credit), [
            'purchase_date' => '2026-06-22',
            'name' => 'Amazon corregido',
            'amount_mode' => 'monthly',
            'monthly_amount' => 189.85,
            'months' => 6,
            'first_due_month' => '2026-07',
            'due_day' => 27,
            'notes' => 'Pago mensual real',
        ])
        ->assertRedirect();

    $credit->refresh();

    expect($credit->name)->toBe('Amazon corregido');
    expect((float) $credit->total_amount)->toBe(1139.10);
    expect((float) $credit->installments()->orderBy('installment_number')->firstOrFail()->amount)->toBe(189.85);

    $first = $credit->installments()->orderBy('installment_number')->firstOrFail();

    $this->actingAs($user)
        ->put(route('finance.credits.installments.update', $first), [
            'period_month' => '2026-07',
            'due_date' => '2026-07-28',
            'amount' => 199.99,
            'status' => 'paid',
            'paid_on' => '2026-07-28',
            'notes' => 'Ajuste individual',
        ])
        ->assertRedirect();

    $first->refresh();
    $credit->refresh();

    expect((float) $first->amount)->toBe(199.99);
    expect($first->status)->toBe('paid');
    expect((float) $first->paid_amount)->toBe(199.99);
    expect($first->due_date->toDateString())->toBe('2026-07-28');
    expect((float) $credit->total_amount)->toBe(1149.24);

    $this->actingAs($user)
        ->delete(route('finance.credits.destroy', $credit))
        ->assertRedirect();

    expect(CreditPurchase::whereKey($credit->id)->exists())->toBeFalse();
    expect(CreditInstallment::where('credit_purchase_id', $credit->id)->exists())->toBeFalse();

    Carbon::setTestNow();
});

it('marks a planned payment as already registered without creating a duplicate expense', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $payment = PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-14',
        'name' => 'Amazon Shopping',
        'amount' => 300,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->post(route('finance.planned.registered', $payment), [
            'paid_on' => '2026-06-14',
        ])
        ->assertRedirect();

    $payment->refresh();

    expect($payment->status)->toBe('paid');
    expect((float) $payment->paid_amount)->toBe(300.0);
    expect($payment->paid_on->toDateString())->toBe('2026-06-14');
    expect(Movement::where('user_id', $user->id)->where('source', 'planned_payment')->count())->toBe(0);
});

it('shows actions for overdue planned payments', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $account = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $category = Category::where('user_id', $user->id)->where('name', 'Comida')->firstOrFail();

    $payment = PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-16',
        'name' => 'Amazon - Amazon Shopping',
        'amount' => 99,
        'status' => 'overdue',
        'category_id' => $category->id,
    ]);

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-14',
        'movement_type' => 'expense',
        'amount' => 99,
        'description' => 'Amazon Shopping',
        'account_id' => $account->id,
        'category_id' => $category->id,
        'source' => 'manual',
    ]);

    $response = $this->actingAs($user)
        ->get('/finanzas/flujo-planeado?month=2026-06')
        ->assertOk()
        ->assertSee('Amazon - Amazon Shopping')
        ->assertSee('Acciones')
        ->assertSee('Marcar como pagado')
        ->assertSee('Vincular movimiento existente')
        ->assertSee('Pagar con tarjeta/credito')
        ->assertSee('Marcar como no pagado')
        ->assertSee('Editar pago')
        ->assertSee('Eliminar del flujo')
        ->assertSee('Monto coincide')
        ->assertSee('Vincular este movimiento');

    $html = $response->getContent();
    $linkFormStart = strpos($html, 'action="' . route('finance.planned.link-movement', $payment) . '"');
    $linkFormEnd = strpos($html, '</form>', $linkFormStart);
    expect($linkFormStart)->not->toBeFalse();
    expect($linkFormEnd)->not->toBeFalse();
    $linkForm = substr($html, $linkFormStart, $linkFormEnd - $linkFormStart);

    expect($linkForm)->not->toContain('paid_on');
});

it('links a planned payment to an existing movement', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $account = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $category = Category::where('user_id', $user->id)->where('name', 'Comida')->firstOrFail();

    $payment = PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-16',
        'name' => 'Amazon - Amazon Shopping',
        'amount' => 99,
        'status' => 'overdue',
        'category_id' => $category->id,
    ]);

    $movement = Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-14',
        'movement_type' => 'expense',
        'amount' => 99,
        'description' => 'Amazon Shopping',
        'account_id' => $account->id,
        'category_id' => $category->id,
        'source' => 'manual',
    ]);

    $this->actingAs($user)
        ->get(route('finance.planned.link', $payment))
        ->assertOk()
        ->assertSee('Vincular pago planeado')
        ->assertSee('Amazon Shopping');

    $this->actingAs($user)
        ->post(route('finance.planned.link-movement', $payment), [
            'movement_id' => $movement->id,
        ])
        ->assertRedirect(route('finance.planned.index', ['month' => '2026-06']));

    $payment->refresh();

    expect($payment->status)->toBe('paid');
    expect((float) $payment->paid_amount)->toBe(99.0);
    expect($payment->paid_on->toDateString())->toBe('2026-06-14');
    expect($payment->movement_id)->toBe($movement->id);
});

it('shows link action for paid planned payments without a linked movement', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    PlannedPayment::create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-16',
        'name' => 'Amazon - Amazon Shopping',
        'amount' => 99,
        'paid_amount' => 99,
        'paid_on' => '2026-06-14',
        'status' => 'paid',
    ]);

    $this->actingAs($user)
        ->get('/finanzas/flujo-planeado?month=2026-06')
        ->assertOk()
        ->assertSee('Amazon - Amazon Shopping')
        ->assertSee('Vincular movimiento existente')
        ->assertSee('Pagar con tarjeta/credito');
});

it('shows credit installment actions in a modal instead of loose table controls', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $account = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $category = Category::where('user_id', $user->id)->where('name', 'Comida')->firstOrFail();

    $credit = CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-10',
        'name' => 'Laptop',
        'total_amount' => 1200,
        'months' => 3,
        'first_due_month' => '2026-06-01',
        'due_day' => 20,
        'account_id' => $account->id,
        'category_id' => $category->id,
        'status' => 'active',
    ]);

    CreditInstallment::create([
        'user_id' => $user->id,
        'credit_purchase_id' => $credit->id,
        'period_month' => '2026-06-01',
        'due_date' => '2026-06-20',
        'installment_number' => 1,
        'amount' => 400,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->get('/finanzas/flujo-planeado?month=2026-06')
        ->assertOk()
        ->assertSee('Laptop')
        ->assertSee('Acciones de mensualidad')
        ->assertSee('Marcar como pagado')
        ->assertSee('Ya lo capture como gasto')
        ->assertSee('Administrar credito')
        ->assertDontSee('form-control form-control-sm mb-1', false)
        ->assertDontSee('title="Ya lo capture como gasto"', false);
});

it('shows income and expense reports by day week fortnight month and year', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-01',
        'movement_type' => 'income',
        'amount' => 1000,
        'description' => 'Pago prueba',
    ]);

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-01',
        'movement_type' => 'yield',
        'amount' => 10,
        'description' => 'Rendimiento prueba',
    ]);

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-01',
        'movement_type' => 'expense',
        'amount' => 200,
        'description' => 'Gasto prueba',
    ]);

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-16',
        'movement_type' => 'expense',
        'amount' => 300,
        'description' => 'Gasto quincena',
    ]);

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-07-05',
        'movement_type' => 'income',
        'amount' => 2000,
        'description' => 'Pago julio',
    ]);

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-07-05',
        'movement_type' => 'expense',
        'amount' => 500,
        'description' => 'Gasto julio',
    ]);

    $this->actingAs($user)
        ->get('/finanzas/reportes?month=2026-06&year=2026')
        ->assertOk()
        ->assertSee('Reportes financieros')
        ->assertSee('Por día')
        ->assertSee('Por semana')
        ->assertSee('Por quincena')
        ->assertSee('Por mes')
        ->assertSee('Por año')
        ->assertSee('2026-06-01')
        ->assertSee('Semana 1')
        ->assertSee('Quincena 2')
        ->assertSee('Jun 2026')
        ->assertSee('$1,010.00')
        ->assertSee('$500.00')
        ->assertSee('$510.00')
        ->assertSee('$2,010.00');
});

it('shows card and cash balances from the latest daily cut', function () {
    $user = User::factory()->create();

    app(FinanceCatalogService::class)->ensureForUser($user);

    $cash = Account::where('user_id', $user->id)->where('name', 'Efectivo')->firstOrFail();
    $nu = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();

    $cut = DailyCut::create([
        'user_id' => $user->id,
        'cut_date' => '2026-06-22',
        'cash_amount' => 315,
        'cards_amount' => 1370.59,
        'real_total' => 1685.59,
        'status' => 'ok',
    ]);

    $cut->balances()->create([
        'account_id' => $cash->id,
        'balance' => 315,
    ]);
    $cut->balances()->create([
        'account_id' => $nu->id,
        'balance' => 1370.59,
    ]);

    $this->actingAs($user)
        ->get('/finanzas?month=2026-06')
        ->assertOk()
        ->assertSee('Resumen de saldos del corte')
        ->assertSee('En tarjetas tienes')
        ->assertSee('$1,370.59')
        ->assertSee('En efectivo tienes')
        ->assertSee('$315.00')
        ->assertSee('NU')
        ->assertSee('Efectivo');
});
