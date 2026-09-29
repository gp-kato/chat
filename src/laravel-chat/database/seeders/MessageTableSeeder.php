<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;

class MessageTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $invitee = User::where('email', 'invitee@example.com')->first();

        $group = Group::query()
            ->where('name', '履歴表示確認用グループ')
            ->firstOrFail();

        Message::query()
            ->where('group_id', $group->id)
            ->where('content', 'like', '履歴確認用メッセージ #%')
            ->delete();

        $startTime = now()->subSeconds(200);

        for ($i = 1; $i <= 200; $i++) {
            $sender = $i % 2 === 1 ? $admin : $invitee;
            $createdAt = $startTime->copy()->addSeconds($i);

            Message::create([
                'group_id' => $group->id,
                'user_id' => $sender->id,
                'content' => sprintf('履歴確認用メッセージ #%03d', $i),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }
}
