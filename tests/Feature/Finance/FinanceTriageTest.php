<?php

use Illuminate\Support\Facades\DB;

afterEach(function () {
    config()->set('finance.health_token', null);
});

it('returns 404 when no triage token is configured', function () {
    config()->set('finance.health_token', '');

    $this->get('/_health/triage?key=whatever')
        ->assertNotFound();
});

it('returns 404 when the triage token is incorrect', function () {
    config()->set('finance.health_token', 'correct-secret');

    $this->get('/_health/triage?key=wrong-secret')
        ->assertNotFound();
});

it('returns 404 when no key is provided', function () {
    config()->set('finance.health_token', 'correct-secret');

    $this->get('/_health/triage')
        ->assertNotFound();
});

it('returns a plain text report with the correct token', function () {
    config()->set('finance.health_token', 'correct-secret');

    $response = $this->get('/_health/triage?key=correct-secret');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/plain');

    $response->assertSee('Sistema de Finanzas - triage', false)
        ->assertSee('.env presente', false)
        ->assertSee('APP_KEY presente', false)
        ->assertSee('conexión DB', false);
});

it('does not expose secret values', function () {
    config()->set('finance.health_token', 'correct-secret');

    // Clave AES válida (32 bytes) para no romper el cifrado de cookies del stack web.
    $secretKey = base64_encode(random_bytes(32));
    config()->set('app.key', 'base64:' . $secretKey);

    $this->get('/_health/triage?key=correct-secret')
        ->assertOk()
        ->assertDontSee($secretKey, false)
        ->assertDontSee('correct-secret', false);
});

it('reports a controlled error instead of a 500 when the database fails', function () {
    config()->set('finance.health_token', 'correct-secret');

    DB::shouldReceive('connection')->andThrow(new RuntimeException('db down'));

    $this->get('/_health/triage?key=correct-secret')
        ->assertOk()
        ->assertSee('conexión DB', false)
        ->assertSee('ERROR', false);
});
