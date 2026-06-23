<?php

use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\Movement;
use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

afterEach(function () {
    File::deleteDirectory(storage_path('app/private/finance-exports'));
});

it('exports filtered movements to a Spanish CSV compatible with Excel', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    $account = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $category = Category::where('user_id', $user->id)->where('name', 'Comida')->firstOrFail();

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
        'category_id' => $category->id,
        'source' => 'manual',
    ]);

    $response = $this->actingAs($user)
        ->get(route('finance.movements.export', ['month' => '2026-06', 'q' => 'Uber']));

    $response->assertOk()->assertHeader('content-disposition');

    $path = $response->baseResponse->getFile()->getPathname();
    $content = File::get($path);

    expect($content)->toContain('Fecha,Tipo,Cuenta,Categoría,Persona,Descripción,Monto,Notas');
    expect($content)->toContain('Uber Carro');
    expect($content)->not->toContain('Taqueria');
});

it('exports report movements filtered by category', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    $account = Account::where('user_id', $user->id)->where('name', 'NU')->firstOrFail();
    $food = Category::where('user_id', $user->id)->where('name', 'Comida')->firstOrFail();
    $home = Category::where('user_id', $user->id)->where('name', 'Casa')->firstOrFail();

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-20',
        'movement_type' => 'expense',
        'amount' => 100,
        'description' => 'Comida prueba',
        'account_id' => $account->id,
        'category_id' => $food->id,
        'source' => 'manual',
    ]);

    Movement::create([
        'user_id' => $user->id,
        'happened_on' => '2026-06-20',
        'movement_type' => 'expense',
        'amount' => 200,
        'description' => 'Casa prueba',
        'account_id' => $account->id,
        'category_id' => $home->id,
        'source' => 'manual',
    ]);

    $response = $this->actingAs($user)
        ->get(route('finance.reports.export', ['month' => '2026-06', 'category_id' => $food->id]));

    $response->assertOk()->assertHeader('content-disposition');

    $content = File::get($response->baseResponse->getFile()->getPathname());

    expect($content)->toContain('Comida prueba');
    expect($content)->not->toContain('Casa prueba');
});

it('previews and stores historical CSV movements after review', function () {
    $user = User::factory()->create();
    app(FinanceCatalogService::class)->ensureForUser($user);

    $csv = implode("\n", [
        'fecha,tipo,descripcion,monto,cuenta,categoria,notas,diferencia_conciliacion',
        '2025-12-31,egreso,Saldo Telcel,50,NU,Servicios,Reporte 2025,0',
    ]);

    $this->actingAs($user)
        ->post(route('finance.imports.historical.preview'), [
            'file' => UploadedFile::fake()->createWithContent('historico.csv', $csv),
        ])
        ->assertRedirect(route('finance.imports.historical.index'))
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->get(route('finance.imports.historical.index'))
        ->assertOk()
        ->assertSee('Vista previa')
        ->assertSee('Saldo Telcel')
        ->assertSee('Guardar movimientos válidos');

    $this->actingAs($user)
        ->post(route('finance.imports.historical.store'))
        ->assertRedirect(route('finance.movements.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('finance_movements', [
        'user_id' => $user->id,
        'happened_on' => '2025-12-31 00:00:00',
        'movement_type' => 'expense',
        'description' => 'Saldo Telcel',
        'amount' => 50,
        'source' => 'historical_import',
    ]);
});
