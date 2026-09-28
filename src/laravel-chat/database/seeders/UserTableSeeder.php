<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultPassword = 'password';

        $demoAccounts = [
            [
                'name' => '管理者',
                'email' => 'admin@example.com',
            ],
            [
                'name' => '招待ユーザー',
                'email' => 'invitee@example.com',
            ],
        ];

        foreach ($demoAccounts as $account) {
            User::query()->firstOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make($defaultPassword),
                    'email_verified_at' => now(),
                ]
            );
        }

        User::factory(5)->create();
    }
}
