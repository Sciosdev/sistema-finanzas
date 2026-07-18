<?php

use App\Models\User;
use App\Services\Finance\FinanceBackupService;
use App\Services\Finance\FinanceDeploymentService;
use App\Services\Finance\FinanceMaintenanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    config()->set('finance.owner_email', null);
    config()->set('finance.deployment', [
        'cpanel_url' => null,
        'cpanel_username' => null,
        'cpanel_api_token' => null,
        'repository_root' => null,
        'branch' => 'main',
        'agent_api_token' => null,
        'connect_timeout' => 1,
        'timeout' => 5,
    ]);
});

afterEach(function () {
    config()->set('finance.owner_email', null);
    Cache::flush();
});

function deploymentOwner(): User
{
    $owner = User::factory()->create(['email' => 'deploy-owner@example.com']);
    config()->set('finance.owner_email', $owner->email);

    return $owner;
}

function configureFinanceDeployment(bool $withAgentToken = true): void
{
    config()->set('finance.deployment', [
        'cpanel_url' => 'https://cpanel.example.com:2083',
        'cpanel_username' => 'financeuser',
        'cpanel_api_token' => 'CPANEL_TOKEN_THAT_IS_LONG_ENOUGH_123456',
        'repository_root' => '/home/financeuser/finanzas',
        'branch' => 'main',
        'agent_api_token' => $withAgentToken
            ? 'AGENT_TOKEN_THAT_IS_AT_LEAST_32_CHARACTERS_LONG'
            : null,
        'connect_timeout' => 1,
        'timeout' => 5,
    ]);
}

/**
 * @return array<string, mixed>
 */
function financeDeploymentRepository(string $commit, string $message): array
{
    return [
        'name' => 'sistema-finanzas',
        'type' => 'git',
        'branch' => 'main',
        'repository_root' => '/home/financeuser/finanzas',
        'source_repository' => [
            'remote_name' => 'origin',
            'url' => 'https://github.com/Sciosdev/sistema-finanzas.git',
        ],
        'last_update' => [
            'identifier' => $commit,
            'message' => $message,
            'author' => 'Deploy Test <deploy@example.com>',
            'date' => 1784246400,
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function financeDeploymentCpanelResponse(mixed $data, bool $ok = true): array
{
    return [
        'apiversion' => 3,
        'module' => 'VersionControl',
        'result' => [
            'data' => $data,
            'errors' => $ok ? null : ['Falló la operación.'],
            'messages' => null,
            'metadata' => [],
            'status' => $ok ? 1 : 0,
            'warnings' => null,
        ],
    ];
}

function bindFinanceDeploymentDependencies(): void
{
    app()->instance(FinanceBackupService::class, new class extends FinanceBackupService
    {
        public function createMigrationPackage(): array
        {
            return [
                'ok' => true,
                'type' => 'migration',
                'name' => 'pre-deploy-backup.zip',
                'size' => 2048,
                'message' => 'Backup previo creado.',
            ];
        }
    });

    app()->instance(FinanceMaintenanceService::class, new class extends FinanceMaintenanceService
    {
        public function clearOptimizationCache(): array
        {
            return [
                'ok' => true,
                'action' => 'optimize:clear',
                'output' => 'Caché limpia.',
            ];
        }

        public function runMigrations(): array
        {
            return [
                'ok' => true,
                'action' => 'migrate --force',
                'output' => 'Migraciones aplicadas.',
            ];
        }
    });
}

function fakeSuccessfulFinanceDeployment(): void
{
    Http::fakeSequence()
        ->push(financeDeploymentCpanelResponse([
            financeDeploymentRepository(str_repeat('a', 40), 'Versión anterior'),
        ]))
        ->push(financeDeploymentCpanelResponse(
            financeDeploymentRepository(str_repeat('b', 40), 'Nueva versión')
        ))
        ->push(financeDeploymentCpanelResponse([
            financeDeploymentRepository(str_repeat('b', 40), 'Nueva versión'),
        ]));
}

it('shows deployment configuration in the owner maintenance screen', function () {
    $owner = deploymentOwner();

    $this->actingAs($owner)
        ->get(route('finance.security.index'))
        ->assertOk()
        ->assertSee('Despliegue desde GitHub')
        ->assertSee('Update from Remote')
        ->assertSee('FINANCE_CPANEL_URL')
        ->assertSee('API agentes: inactiva');
});

it('forbids a normal user from starting a web deployment', function () {
    $user = User::factory()->create(['email' => 'normal@example.com']);
    config()->set('finance.owner_email', 'owner@example.com');

    $this->actingAs($user)
        ->post(route('finance.maintenance.deploy'), ['confirm_deploy' => '1'])
        ->assertForbidden();
});

it('requires explicit web confirmation before deploying', function () {
    $owner = deploymentOwner();
    configureFinanceDeployment();
    Http::fake();

    $this->actingAs($owner)
        ->post(route('finance.maintenance.deploy'))
        ->assertSessionHasErrors('confirm_deploy');

    Http::assertNothingSent();
});

it('runs the fixed protected deployment pipeline through cpanel uapi', function () {
    configureFinanceDeployment();
    bindFinanceDeploymentDependencies();
    fakeSuccessfulFinanceDeployment();

    $result = app(FinanceDeploymentService::class)->deploy(
        source: 'test',
        actorId: 123,
        ip: '127.0.0.1',
    );

    expect($result['ok'])->toBeTrue()
        ->and($result['status'])->toBe('completed')
        ->and($result['backup']['name'])->toBe('pre-deploy-backup.zip')
        ->and($result['before']['commit'])->toBe(str_repeat('a', 40))
        ->and($result['after']['commit'])->toBe(str_repeat('b', 40))
        ->and(collect($result['steps'])->pluck('name')->all())->toBe([
            'repository_check',
            'backup',
            'update_from_remote',
            'optimize_clear',
            'migrate',
            'deployment_check',
        ]);

    Http::assertSentCount(3);
    Http::assertSent(function (ClientRequest $request): bool {
        if (parse_url($request->url(), PHP_URL_PATH) !== '/execute/VersionControl/update') {
            return false;
        }

        return $request['repository_root'] === '/home/financeuser/finanzas'
            && $request['branch'] === 'main'
            && $request['source_repository'] === '{"remote_name":"origin"}'
            && $request->hasHeader('Authorization', 'cpanel financeuser:CPANEL_TOKEN_THAT_IS_LONG_ENOUGH_123456');
    });

    expect(json_encode($result))->not->toContain('CPANEL_TOKEN')
        ->not->toContain('AGENT_TOKEN');
});

it('does not update code when the pre-deployment backup fails', function () {
    configureFinanceDeployment();

    app()->instance(FinanceBackupService::class, new class extends FinanceBackupService
    {
        public function createMigrationPackage(): array
        {
            return [
                'ok' => false,
                'message' => 'No se pudo crear el backup.',
            ];
        }
    });

    Http::fakeSequence()->push(financeDeploymentCpanelResponse([
        financeDeploymentRepository(str_repeat('a', 40), 'Versión anterior'),
    ]));

    $result = app(FinanceDeploymentService::class)->deploy('test');

    expect($result['ok'])->toBeFalse()
        ->and($result['message'])->toContain('código no se actualizó');

    Http::assertSentCount(1);
    Http::assertNotSent(fn (ClientRequest $request): bool => parse_url($request->url(), PHP_URL_PATH) === '/execute/VersionControl/update');
});

it('rejects a second deployment while the filesystem lock is held', function () {
    configureFinanceDeployment();
    Http::fake();

    $directory = storage_path('app/private');
    File::ensureDirectoryExists($directory);
    $handle = fopen($directory.DIRECTORY_SEPARATOR.'finance-deployment.lock', 'c+');
    expect($handle)->not->toBeFalse()
        ->and(flock($handle, LOCK_EX | LOCK_NB))->toBeTrue();

    try {
        $result = app(FinanceDeploymentService::class)->deploy('test');

        expect($result['ok'])->toBeFalse()
            ->and($result['status'])->toBe('busy');
        Http::assertNothingSent();
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
});

it('protects the deployment api with an independent bearer token', function () {
    configureFinanceDeployment();

    $this->getJson('/api/finance/deployment/status')
        ->assertUnauthorized()
        ->assertJsonPath('status', 'unauthorized');

    $this->withToken('incorrect-token')
        ->getJson('/api/finance/deployment/status')
        ->assertUnauthorized();
});

it('returns api status without exposing either secret', function () {
    configureFinanceDeployment();
    Http::fakeSequence()->push(financeDeploymentCpanelResponse([
        financeDeploymentRepository(str_repeat('a', 40), 'Versión instalada'),
    ]));

    $response = $this->withToken('AGENT_TOKEN_THAT_IS_AT_LEAST_32_CHARACTERS_LONG')
        ->getJson('/api/finance/deployment/status?refresh=1')
        ->assertOk()
        ->assertJsonPath('deployment.configured', true)
        ->assertJsonPath('deployment.api_configured', true)
        ->assertJsonPath('deployment.repository.commit', str_repeat('a', 40));

    expect($response->getContent())->not->toContain('CPANEL_TOKEN')
        ->not->toContain('AGENT_TOKEN');
});

it('requires confirmation and a valid idempotency key in the deployment api', function () {
    configureFinanceDeployment();
    Http::fake();
    $headers = ['Authorization' => 'Bearer AGENT_TOKEN_THAT_IS_AT_LEAST_32_CHARACTERS_LONG'];

    $this->postJson('/api/finance/deployment/deploy', [], $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('confirm');

    $this->postJson('/api/finance/deployment/deploy', ['confirm' => true], $headers)
        ->assertUnprocessable()
        ->assertJsonPath('status', 'validation_error');

    Http::assertNothingSent();
});

it('ignores operational fields from api input and replays an idempotent result', function () {
    configureFinanceDeployment();
    bindFinanceDeploymentDependencies();
    fakeSuccessfulFinanceDeployment();

    $headers = [
        'Authorization' => 'Bearer AGENT_TOKEN_THAT_IS_AT_LEAST_32_CHARACTERS_LONG',
        'Idempotency-Key' => 'deploy-test-0001',
    ];
    $payload = [
        'confirm' => true,
        'branch' => 'malicious-branch',
        'repository_root' => '/tmp/other',
        'command' => 'anything',
    ];

    $this->postJson('/api/finance/deployment/deploy', $payload, $headers)
        ->assertOk()
        ->assertJsonPath('status', 'completed')
        ->assertJsonPath('replayed', false);

    $this->postJson('/api/finance/deployment/deploy', $payload, $headers)
        ->assertOk()
        ->assertJsonPath('status', 'completed')
        ->assertJsonPath('replayed', true);

    Http::assertSentCount(3);
    Http::assertSent(fn (ClientRequest $request): bool => parse_url($request->url(), PHP_URL_PATH) === '/execute/VersionControl/update'
        && $request['branch'] === 'main'
        && $request['repository_root'] === '/home/financeuser/finanzas');
});

it('never invokes shell commands in deployment code', function () {
    $files = [
        base_path('app/Services/Finance/FinanceDeploymentService.php'),
        base_path('app/Http/Controllers/Finance/FinanceDeploymentApiController.php'),
        base_path('app/Http/Middleware/EnsureFinanceDeployApiToken.php'),
        base_path('routes/api.php'),
    ];

    foreach (['shell_exec', 'exec(', 'passthru', 'proc_open', 'system(', 'eval('] as $forbidden) {
        foreach ($files as $file) {
            expect(file_get_contents($file))->not->toContain($forbidden);
        }
    }

});
