<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Group;
use Illuminate\Support\Carbon;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    private ?User $user = null;
    private ?Group $group = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(); // 1回だけユーザーを作成
        $this->group = Group::factory()->create(); // 1回だけグループを作成
        Carbon::setTestNow('2025-04-15 19:00:00');
    }

    private function joinGroup(User $user, Group $group): void
    {
        $group->users()->attach($user->id, [
            'joined_at' => now(),
            'left_at' => null,
        ]);
    }

    public function test_chat_screen_can_be_rendered_with_join_group(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $response = $this->get(route('groups.messages.show', $this->group->id));

        $response->assertStatus(200);
    }

    public function test_can_writing_message_with_join_group(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $response = $this->post(route('groups.messages.store', $this->group->id), [
            'content' => 'content',
        ]);

        $this->assertAuthenticated();
        $response->assertSessionHasNoErrors();
        $response->assertStatus(201)->assertJson([
            'message'  =>  'メッセージを送信しました',
        ]);

        $this->assertDatabaseHas('messages', [
            'content' => 'content',
            'group_id' => $this->group->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_chat_screen_can_not_rendered_without_login(): void
    {
        $response = $this->get(route('groups.messages.show', $this->group->id));

        $response->assertRedirect(route('login'));
    }

    public function test_can_not_write_message_without_login(): void
    {
        $formData = [
            'content' => 'content',
        ];

        $response = $this->post(route('groups.messages.store', $this->group->id), $formData);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('messages', $formData);
    }

    public function test_writing_message_fails_when_content_is_missing(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $formData = [
            'content' => '',
        ];

        $response = $this->post(route('groups.messages.store', $this->group->id), $formData);

        $response->assertSessionHasErrors(['content']);

        $this->assertDatabaseMissing('messages');
    }

    public function test_writing_message_fails_when_content_exceeds_max_length(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $formData = [
            'content' => str_repeat('a', 141),
        ];

        $response = $this->post(route('groups.messages.store', $this->group->id), $formData);

        $response->assertSessionHasErrors(['content']);

        $this->assertDatabaseMissing('messages', ['content' => 'content']);
    }

    public function test_write_message_succeeds_with_max_length(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $validContent = str_repeat('a', 140);

        $response = $this->post(route('groups.messages.store', $this->group->id), [
            'content' => $validContent,
        ]);

        $this->assertAuthenticated();
        $response->assertSessionHasNoErrors();
        $response->assertStatus(201)->assertJson([
            'message'  =>  'メッセージを送信しました',
        ]);

        $this->assertDatabaseHas('messages', [
            'content' => $validContent,
        ]);
    }

    public function test_chat_screen_cannot_be_rendered_without_join_group(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('groups.messages.show', $this->group->id));

        $response->assertRedirect(route('groups.index', absolute: false));
    }

    public function test_cannot_writing_message_without_join_group(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('groups.messages.store', $this->group->id), [
            'content' => 'content',
        ]);

        $this->assertAuthenticated();
        $response->assertStatus(302);
    }
}
