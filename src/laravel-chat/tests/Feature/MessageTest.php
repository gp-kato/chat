<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Group;

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
    }

    public function test_chat_screen_can_be_rendered(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('show', $this->group->id));

        $response->assertStatus(200);
    }

    public function test_writing_message(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('store', $this->group->id), [
            'content' => 'content',
        ]);

        $this->assertAuthenticated();
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('show', [$this->group->id], absolute: false));

        $this->assertDatabaseHas('messages', [
            'content' => 'content',
            'group_id' => $this->group->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_chat_screen_can_not_rendered_without_login(): void
    {
        $response = $this->get(route('show', $this->group->id));

        $response->assertRedirect(route('login'));
    }

    public function test_can_not_write_message_without_login(): void
    {
        $formData = [
            'content' => 'content',
        ];

        $response = $this->post(route('store', $this->group->id), $formData);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('messages', $formData);
    }

    public function test_writing_message_fails_when_content_is_missing(): void
    {
        $this->actingAs($this->user);

        $formData = [
            'content' => '',
        ];

        $response = $this->post(route('store', $this->group->id), $formData);

        $response->assertSessionHasErrors(['content']);

        $this->assertDatabaseMissing('messages');
    }

    public function test_writing_message_fails_when_content_exceeds_max_length(): void
    {
        $this->actingAs($this->user);

        $formData = [
            'content' => str_repeat('a', 141),
        ];
    
        $response = $this->post(route('store', $this->group->id), $formData);

        $response->assertSessionHasErrors(['content']);

        $this->assertDatabaseMissing('messages', ['content' => 'content']);
    }

    public function test_write_message_succeeds_with_max_length(): void
    {
        $this->actingAs($this->user);
    
        $validContent = str_repeat('a', 140);
    
        $response = $this->post(route('store', $this->group->id), [
            'content' => $validContent,
        ]);
    
        $this->assertAuthenticated();
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('show', [$this->group->id], absolute: false));
    
        $this->assertDatabaseHas('messages', [
            'content' => $validContent,
        ]);
    }
}
