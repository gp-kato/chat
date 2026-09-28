<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeedDemoAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_create_fixed_demo_accounts_for_invitation_flow(): void
    {
        $this->artisan('db:seed')->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'invitee@example.com',
        ]);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $invitee = User::query()->where('email', 'invitee@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertTrue(Hash::check('password', $invitee->password));
    }
}
