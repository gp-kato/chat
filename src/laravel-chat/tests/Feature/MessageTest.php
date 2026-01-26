<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Group;
use App\Models\Message;
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

    private function joinGroupAsLeft(User $user, Group $group): void
    {
        $group->users()->attach($user->id, [
            'joined_at' => now()->subDays(2),
            'left_at'   => now()->subDay(),
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

        $response->assertForbidden();
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

    public function test_fetch_messages_initial_load()
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        Message::factory()->count(50)->create([
            'group_id' => $this->group->id,
        ]);

        $response = $this->getJson(
            route('groups.messages.fetch', $this->group->id)
        );

        $response->assertOk();
        $response->assertJson([
            'has_more' => false,
        ]);

        $this->assertNotEmpty($response->json('html'));
    }

    public function test_fetch_messages_has_more_true()
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        Message::factory()->count(51)->create([
            'group_id' => $this->group->id,
        ]);

        $response = $this->getJson(
            route('groups.messages.fetch', $this->group->id)
        );

        $response->assertOk();
        $response->assertJson([
            'has_more' => true,
        ]);

        $this->assertNotEmpty($response->json('html'));
    }

    public function test_fetch_messages_with_before_id()
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $messages = Message::factory()
            ->count(3)
            ->sequence(
                ['content' => 'message-1'],
                ['content' => 'message-2'],
                ['content' => 'message-3'],
            )
            ->create([
                'group_id' => $this->group->id,
            ]);

        $beforeId = $messages[1]->id;

        $response = $this->getJson(
            route('groups.messages.fetch', [
                'group' => $this->group->id,
                'before_id' => $beforeId,
            ])
        );

        $response->assertOk();
        $response->assertJsonPath('has_more', false);

        $html = $response->json('html');

        $this->assertStringContainsString('message-1', $html);

        $this->assertStringNotContainsString('message-2', $html);

        $this->assertStringNotContainsString('message-3', $html);
    }

    public function test_fetch_messages_forbidden_for_non_member()
    {
        $this->actingAs($this->user);

        $response = $this->getJson(
            route('groups.messages.fetch', $this->group->id)
        );

        $response->assertStatus(403);
    }

    public function test_fetch_messages_validation_error()
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $response = $this->getJson(
            route('groups.messages.fetch', [
                'group' => $this->group->id,
                'before_id' => 'invalid',
            ])
        );


        $response->assertStatus(422);

        $response->assertJsonValidationErrors(['before_id']);
    }

    public function test_can_not_fetch_without_login(): void
    {
        Message::factory()->count(50)->create([
            'group_id' => $this->group->id,
        ]);

        $response = $this->getJson(
            route('groups.messages.fetch', $this->group->id)
        );

        $response->assertStatus(401);
    }

    public function test_can_not_fetch_with_0message()
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $response = $this->getJson(
            route('groups.messages.fetch', $this->group->id)
        );

        $response->assertOk();
        $response->assertJson([
            'has_more' => false,
        ]);

        $this->assertEmpty($response->json('html'));
    }

    public function test_chat_screen_cannot_be_rendered_with_left(): void
    {
        $this->actingAs($this->user);
        $this->joinGroupAsLeft($this->user, $this->group);

        $response = $this->get(route('groups.messages.show', $this->group->id));

        $response->assertForbidden();
    }

    public function test_cannot_writing_message_with_left(): void
    {
        $this->actingAs($this->user);
        $this->joinGroupAsLeft($this->user, $this->group);

        $formData = [
            'content' => 'content',
        ];

        $response = $this->post(route('groups.messages.store', $this->group->id), $formData);

        $response->assertRedirect(route('groups.index', absolute: false));
        $response->assertSessionHas('error', 'このグループに参加していません');

        $this->assertDatabaseMissing('messages', $formData);
    }

    public function test_cannot_fetch_with_left(): void
    {
        $this->actingAs($this->user);
        $this->joinGroupAsLeft($this->user, $this->group);

        $response = $this->getJson(
            route('groups.messages.fetch', $this->group->id)
        );

        $response->assertStatus(403);
    }
}
