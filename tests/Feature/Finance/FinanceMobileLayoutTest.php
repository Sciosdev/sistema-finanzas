<?php

use App\Models\Finance\Movement;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

function mobileUser(): User
{
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    return $user;
}

it('links the static mobile stylesheet in the head', function () {
    $this->actingAs(mobileUser())
        ->get(route('finance.movements.index'))
        ->assertOk()
        ->assertSee('css/finance-mobile.css', false);
});

it('renders the mobile bottom navigation with quick capture', function () {
    $this->actingAs(mobileUser())
        ->get(route('finance.dashboard'))
        ->assertOk()
        ->assertSee('finance-bottom-nav', false)
        ->assertSee('Capturar')
        ->assertSee('capture=1', false)
        ->assertSee('button-toggle-menu', false);
});

it('keeps the desktop table but adds a mobile card list on movements', function () {
    Carbon::setTestNow('2026-06-28 10:00:00');
    $user = mobileUser();

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-10',
        'movement_type' => 'expense',
        'amount' => 150,
        'description' => 'Tacos del centro',
        'source' => 'manual',
    ]);

    $this->actingAs($user)
        ->get(route('finance.movements.index', ['month' => '2026-06']))
        ->assertOk()
        ->assertSee('finance-mobile-list', false)               // lista de tarjetas móvil
        ->assertSee('table-responsive d-none d-md-block', false) // tabla solo en escritorio
        ->assertSee('Tacos del centro');                         // dato visible en ambas vistas
});

it('keeps the desktop table but adds a mobile card list on cuts', function () {
    $user = mobileUser();

    $this->actingAs($user)
        ->get(route('finance.cuts.index'))
        ->assertOk()
        ->assertSee('finance-mobile-list', false)
        ->assertSee('table-responsive d-none d-md-block', false);
});

it('keeps the desktop table but adds a per-account mobile editor on accounts', function () {
    $user = mobileUser();
    $cash = \App\Models\Finance\Account::where('user_id', $user->id)->where('name', 'Efectivo')->firstOrFail();

    $this->actingAs($user)
        ->get(route('finance.accounts.index'))
        ->assertOk()
        ->assertSee('finance-mobile-list', false)
        ->assertSee('table-responsive d-none d-md-block', false)
        ->assertSee('account_form_m_' . $cash->id, false); // form propio de la tarjeta móvil
});

it('keeps the desktop tables but adds mobile cards on credits', function () {
    $user = mobileUser();
    $nu = \App\Models\Finance\Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();

    $credit = \App\Models\Finance\CreditPurchase::create([
        'user_id' => $user->id,
        'purchase_date' => '2026-06-01',
        'name' => 'Compra a meses',
        'total_amount' => 1200,
        'months' => 2,
        'first_due_month' => '2026-06-01',
        'account_id' => $nu->id,
        'status' => 'active',
    ]);
    $installment = $credit->installments()->create([
        'user_id' => $user->id,
        'period_month' => '2026-06-01',
        'installment_number' => 1,
        'amount' => 600,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->get(route('finance.credits.index'))
        ->assertOk()
        ->assertSee('finance-mobile-list', false)
        ->assertSee('installment-form-m-' . $installment->id, false)
        ->assertSee('table-responsive d-none d-md-block', false);
});

it('ships mobile css that stacks the dashboard one per row', function () {
    $css = file_get_contents(public_path('css/finance-mobile.css'));

    expect($css)->toContain('.finance-dashboard-grid .dashboard-widget')
        ->and($css)->toContain('.dashboard-widget-size-panel')
        ->and($css)->toContain('.finance-bottom-nav');

    // El Resumen sigue cargando bien en móvil.
    $this->actingAs(mobileUser())
        ->get(route('finance.dashboard'))
        ->assertOk()
        ->assertSee('finance-dashboard-grid', false);
});

it('moves the theme toggle into the user menu and hides the top buttons on mobile', function () {
    $this->actingAs(mobileUser())
        ->get(route('finance.dashboard'))
        ->assertOk()
        ->assertSee('Tema claro / oscuro')          // toggle ahora dentro del menú "A"
        ->assertSee('id="light-dark-mode"', false)
        ->assertSee('d-none d-md-flex', false);      // Menú/Capturar ocultos en teléfono
});

it('hides the whole topbar on mobile and puts the theme toggle in the side menu', function () {
    $this->actingAs(mobileUser())
        ->get(route('finance.dashboard'))
        ->assertOk()
        ->assertSee('topbar d-none d-md-flex', false) // topbar oculto en teléfono
        ->assertSee('js-theme-toggle', false);         // toggle de tema dentro del menú lateral ("Más")
});

it('focuses the capture form only when arriving with capture=1', function () {
    $user = mobileUser();

    $this->actingAs($user)
        ->get(route('finance.movements.index', ['capture' => 1]))
        ->assertOk()
        ->assertSee('scrollIntoView', false);

    $this->actingAs($user)
        ->get(route('finance.movements.index'))
        ->assertOk()
        ->assertDontSee('scrollIntoView', false);
});
