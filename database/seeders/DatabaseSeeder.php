<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Finance\FinanceCatalogService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Use firstOrCreate to prevent a unique constraint violation.
        // It will find the user if it exists, or create a new one if it doesn't.
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'remember_token' => Str::random(10),
            ]
        );

        app(FinanceCatalogService::class)->ensureForUser($user);

        // You can also uncomment this line to create 10 more users using the factory,
        // which will generate unique data for each.
        // User::factory(10)->create();
    }
}
