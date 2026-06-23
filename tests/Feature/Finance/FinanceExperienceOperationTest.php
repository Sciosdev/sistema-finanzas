<?php

use App\Models\Finance\Reminder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

it('lets users create reminders and see them on the dashboard', function () {
    Carbon::setTestNow('2026-06-22 09:00:00');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('finance.reminders.store'), [
            'title' => 'Refrendo carro',
            'reminder_type' => 'refrendo',
            'vehicle_type' => 'car',
            'due_date' => '2026-06-30',
            'amount' => 1200,
            'recurrence' => 'annual',
            'notify_days_before' => 15,
            'notes' => 'Placas carro',
        ])
        ->assertRedirect(route('finance.reminders.index'));

    $this->assertDatabaseHas('finance_reminders', [
        'user_id' => $user->id,
        'title' => 'Refrendo carro',
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->get(route('finance.dashboard'))
        ->assertOk()
        ->assertSee('Próximos recordatorios')
        ->assertSee('Refrendo carro')
        ->assertSee('Carro')
        ->assertSee('$1,200.00');
});

it('marks a recurring reminder as done and creates the next one', function () {
    Carbon::setTestNow('2026-06-22 09:00:00');
    $user = User::factory()->create();

    $reminder = Reminder::create([
        'user_id' => $user->id,
        'title' => 'Verificación moto',
        'reminder_type' => 'verificacion',
        'vehicle_type' => 'motorcycle',
        'due_date' => '2026-07-15',
        'amount' => 650,
        'recurrence' => 'semiannual',
        'notify_days_before' => 20,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->post(route('finance.reminders.complete', $reminder), [
            'completed_on' => '2026-07-10',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Recordatorio marcado como hecho y se generó el siguiente.');

    $reminder->refresh();

    expect($reminder->status)->toBe('done');
    expect($reminder->completed_on->toDateString())->toBe('2026-07-10');

    $this->assertDatabaseHas('finance_reminders', [
        'user_id' => $user->id,
        'title' => 'Verificación moto',
        'status' => 'pending',
        'due_date' => '2027-01-15 00:00:00',
    ]);
});

it('shows operation guidance for notifications tailscale and github', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('finance.operations.index'))
        ->assertOk()
        ->assertSee('Notificaciones web')
        ->assertSee('Ubuntu + Tailscale')
        ->assertSee('GitHub')
        ->assertSee('No subí nada ni ejecuté comandos de Git');
});

it('shows the improved Spanish login screen', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Bienvenido')
        ->assertSee('Correo')
        ->assertSee('Contraseña')
        ->assertSee('Entrar')
        ->assertDontSee('test@example.com')
        ->assertDontSee('Sign In');
});
