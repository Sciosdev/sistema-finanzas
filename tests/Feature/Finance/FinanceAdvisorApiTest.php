<?php

use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\CreditInstallment;
use App\Models\Finance\CreditPurchase;
use App\Models\Finance\ExpectedIncome;
use App\Models\Finance\Movement;
use App\Models\Finance\PlannedPayment;
use App\Models\Finance\PlannerSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-07-19 10:00:00');
    config()->set('finance.owner_email', null);
    config()->set('finance.advisor', [
        'api_token' => null,
        'history_days' => 90,
        'horizon_days' => 45,
        'transaction_limit' => 60,
        'include_descriptions' => true,
    ]);
});

afterEach(function () {
    Carbon::setTestNow();
    config()->set('finance.owner_email', null);
});

function configureFinanceAdvisorApi(?User $owner = null, bool $includeDescriptions = true): User
{
    $owner ??= User::factory()->create(['email' => 'advisor-owner@example.com']);
    config()->set('finance.owner_email', $owner->email);
    config()->set('finance.advisor', [
        'api_token' => 'ADVISOR_TOKEN_THAT_IS_AT_LEAST_32_CHARACTERS_LONG',
        'history_days' => 90,
        'horizon_days' => 45,
        'transaction_limit' => 60,
        'include_descriptions' => $includeDescriptions,
    ]);

    return $owner;
}

function advisorAccount(User $user, array $attributes = []): Account
{
    return Account::create(array_merge([
        'user_id' => $user->id,
        'name' => 'Cuenta principal',
        'type' => 'bank',
        'opening_balance' => 10000,
        'is_active' => true,
    ], $attributes));
}

function advisorCategory(User $user, string $name = 'Ropa'): Category
{
    return Category::create([
        'user_id' => $user->id,
        'name' => $name,
        'type' => 'expense',
        'group' => 'Discrecional',
        'keywords' => mb_strtolower($name),
        'is_active' => true,
    ]);
}

function advisorExpense(
    User $user,
    Category $category,
    string $date,
    float $amount,
    string $description,
    ?Account $account = null,
): Movement {
    return Movement::create([
        'user_id' => $user->id,
        'happened_on' => $date,
        'movement_type' => 'expense',
        'amount' => $amount,
        'description' => $description,
        'account_id' => $account?->id,
        'category_id' => $category->id,
        'source' => 'manual',
    ]);
}

it('requires a separately configured advisor bearer token', function () {
    $this->getJson('/api/finance/advisor/snapshot')
        ->assertServiceUnavailable()
        ->assertJsonPath('status', 'not_configured');

    configureFinanceAdvisorApi();

    $this->getJson('/api/finance/advisor/snapshot')
        ->assertUnauthorized()
        ->assertJsonPath('status', 'unauthorized');

    $this->withToken('incorrect-token')
        ->getJson('/api/finance/advisor/snapshot')
        ->assertUnauthorized();
});

it('requires the configured finance owner to exist', function () {
    config()->set('finance.owner_email', 'missing-owner@example.com');
    config()->set('finance.advisor.api_token', 'ADVISOR_TOKEN_THAT_IS_AT_LEAST_32_CHARACTERS_LONG');

    $this->withToken('ADVISOR_TOKEN_THAT_IS_AT_LEAST_32_CHARACTERS_LONG')
        ->getJson('/api/finance/advisor/snapshot')
        ->assertServiceUnavailable()
        ->assertJsonPath('status', 'owner_not_found');
});

it('shows the advisor read-only status on the owner maintenance screen', function () {
    $owner = configureFinanceAdvisorApi();

    $this->actingAs($owner)
        ->get(route('finance.security.index'))
        ->assertOk()
        ->assertSee('Asesor financiero local')
        ->assertSee('API lectura: activa')
        ->assertSee('/api/finance/advisor/snapshot');
});

it('returns an owner-only read-only snapshot with trends and advice context', function () {
    $owner = configureFinanceAdvisorApi();
    $account = advisorAccount($owner);
    $clothes = advisorCategory($owner);
    PlannerSetting::create(['user_id' => $owner->id, 'minimum_buffer' => 1000]);

    advisorExpense($owner, $clothes, '2026-04-10', 100, 'Ropa abril', $account);
    advisorExpense($owner, $clothes, '2026-05-10', 100, 'Ropa mayo', $account);
    advisorExpense($owner, $clothes, '2026-06-10', 100, 'Ropa junio', $account);
    advisorExpense($owner, $clothes, '2026-07-19', 800, 'Compra de ropa actual', $account);

    ExpectedIncome::create([
        'user_id' => $owner->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-25',
        'name' => 'Ingreso quincenal',
        'amount' => 5000,
        'received_amount' => 0,
        'status' => 'pending',
    ]);

    $payment = PlannedPayment::create([
        'user_id' => $owner->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-22',
        'name' => 'Internet',
        'amount' => 1000,
        'paid_amount' => 0,
        'status' => 'pending',
        'account_id' => $account->id,
    ]);

    $credit = CreditPurchase::create([
        'user_id' => $owner->id,
        'purchase_date' => '2026-07-01',
        'name' => 'NU',
        'total_amount' => 2000,
        'months' => 2,
        'first_due_month' => '2026-07-01',
        'account_id' => $account->id,
        'status' => 'active',
    ]);
    CreditInstallment::create([
        'credit_purchase_id' => $credit->id,
        'user_id' => $owner->id,
        'period_month' => '2026-07-01',
        'due_date' => '2026-07-23',
        'installment_number' => 1,
        'amount' => 1000,
        'paid_amount' => 0,
        'status' => 'pending',
    ]);

    $other = User::factory()->create();
    $otherCategory = advisorCategory($other, 'Categoría ajena');
    advisorExpense($other, $otherCategory, '2026-07-19', 999999, 'MOVIMIENTO_AJENO_SECRETO');

    $movementCount = Movement::count();
    $response = $this->withToken('ADVISOR_TOKEN_THAT_IS_AT_LEAST_32_CHARACTERS_LONG')
        ->getJson('/api/finance/advisor/snapshot?user_id='.$other->id)
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonPath('ok', true)
        ->assertJsonPath('snapshot.read_only', true)
        ->assertJsonPath('snapshot.scope.owner_only', true)
        ->assertJsonPath('snapshot.scope.history_days', 90)
        ->assertJsonPath('snapshot.scope.horizon_days', 45)
        ->assertJsonPath('snapshot.spending_trends.0.category', 'Ropa')
        ->assertJsonPath('snapshot.spending_trends.0.current_month_amount', 800)
        ->assertJsonPath('snapshot.spending_trends.0.average_previous_three_months', 100)
        ->assertJsonPath('snapshot.recent_transactions.0.description', 'Compra de ropa actual')
        ->assertJsonPath('snapshot.credits.0.name', 'NU')
        ->assertJsonPath('snapshot.credits.0.balance_due', 2000);

    expect($response->getContent())
        ->toContain('category_acceleration')
        ->not->toContain('MOVIMIENTO_AJENO_SECRETO')
        ->not->toContain('advisor-owner@example.com')
        ->not->toContain('ADVISOR_TOKEN_THAT_IS_AT_LEAST_32_CHARACTERS_LONG')
        ->not->toContain('"user_id"');

    expect(Movement::count())->toBe($movementCount)
        ->and($payment->fresh()->status)->toBe('pending')
        ->and($credit->fresh()->status)->toBe('active');
});

it('can omit transaction descriptions without disabling the advisor', function () {
    $owner = configureFinanceAdvisorApi(includeDescriptions: false);
    $account = advisorAccount($owner);
    $category = advisorCategory($owner, 'Comida');
    advisorExpense($owner, $category, '2026-07-19', 250, 'DESCRIPCION_PRIVADA', $account);

    $response = $this->withToken('ADVISOR_TOKEN_THAT_IS_AT_LEAST_32_CHARACTERS_LONG')
        ->getJson('/api/finance/advisor/snapshot')
        ->assertOk()
        ->assertJsonPath('snapshot.scope.descriptions_included', false)
        ->assertJsonPath('snapshot.recent_transactions.0.description', null);

    expect($response->getContent())->not->toContain('DESCRIPCION_PRIVADA');
});

it('does not expose a write method for the advisor snapshot', function () {
    configureFinanceAdvisorApi();

    $this->withToken('ADVISOR_TOKEN_THAT_IS_AT_LEAST_32_CHARACTERS_LONG')
        ->postJson('/api/finance/advisor/snapshot')
        ->assertMethodNotAllowed();
});

it('ships a local client that stores the token with windows dpapi', function () {
    $script = file_get_contents(base_path('tools/finance-advisor.ps1'));

    expect($script)
        ->toContain('ConvertFrom-SecureString')
        ->toContain('ConvertTo-SecureString')
        ->toContain('/api/finance/advisor/snapshot')
        ->toContain("Read-Host 'Pega FINANCE_ADVISOR_API_TOKEN' -AsSecureString")
        ->not->toContain('FINANCE_CPANEL_API_TOKEN')
        ->not->toContain('FINANCE_DEPLOY_API_TOKEN');
});

it('ships a complete operating manual for financial review agents', function () {
    $manual = file_get_contents(base_path('docs/manual-asesor-financiero-agentes.md'));

    expect($manual)
        ->toContain('Reglas no negociables')
        ->toContain('tools/finance-advisor.ps1')
        ->toContain('Nunca pedir, mostrar, copiar ni guardar el token')
        ->toContain('saldo seguro del día')
        ->toContain('saldo proyectado del día')
        ->toContain('Método obligatorio de análisis')
        ->toContain('Formato recomendado de respuesta')
        ->toContain('Límites del análisis')
        ->toContain('Solución de problemas')
        ->toContain('Rotación y revocación')
        ->toContain('Checklist rápido')
        ->toContain('Esta fue una consulta de solo lectura')
        ->not->toContain('FINANCE_CPANEL_API_TOKEN=')
        ->not->toContain('FINANCE_DEPLOY_API_TOKEN=');
});
