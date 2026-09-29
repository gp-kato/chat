<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Seeder;

class GroupTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $invitee = User::where('email', 'invitee@example.com')->first();

        $group = Group::query()->firstOrCreate(
            ['name' => '履歴表示確認用グループ'],
            ['description' => 'メッセージ履歴の表示と追加読み込みを確認するグループです。']
        );

        $joinedAt = now();

        foreach ([
            $admin->id => 'admin',
            $invitee->id => 'member',
        ] as $userId => $role) {
            $pivot = [
                'role' => $role,
                'joined_at' => $joinedAt,
                'left_at' => null,
            ];

            $group->users()->syncWithoutDetaching([$userId => $pivot]);
            $group->users()->updateExistingPivot($userId, $pivot);
        }
    }
}
