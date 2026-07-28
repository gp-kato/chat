<?php

namespace Tests\Feature\Service;

use App\Models\Group;
use App\Models\Message;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class MessageServiceTest extends TestCase
{
    use RefreshDatabase;

    private ?User $user = null;

    private ?Group $group = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(); // 1回だけユーザーを作成
        $this->group = Group::factory()->create(); // 1回だけグループを作成
    }

    private function joinGroup(User $user, Group $group): void
    {
        $group->users()->attach($user->id, [
            'joined_at' => now(),
            'left_at' => null,
        ]);
    }

    public function test_getRecentMessages(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $service = app(MessageService::class);

        $message = Message::factory()->create([
            'group_id' => $this->group->id,
            'user_id' => $this->user->id,
        ]);

        $messages = $service->getRecentMessages($this->group, 10);

        $this->assertCount(1, $messages);
        $this->assertSame($message->id, $messages->first()->id);
    }

    public function test_can_post_message(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $service = app(MessageService::class);

        $request = new Request([
            'content' => 'content',
        ]);

        $service->post($this->group, $request);

        $this->assertDatabaseHas('messages', [
            'content' => 'content',
        ]);
    }

    public function test_can_fetch_recent_messages(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $service = app(MessageService::class);

        Message::factory()->count(51)->create([
            'group_id' => $this->group->id,
        ]);

        $messages = $service->fetch($this->group, null);

        $this->assertTrue($messages['has_more']);
        $this->assertIsString($messages['html']);
    }
}
