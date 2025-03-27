<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Message;
use App\Models\User;
use App\Models\Group;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $group = Group::factory()->create();

        $response = $this->get('/group/1');

        $response->assertStatus(200);
    }

    public function test_writing_message(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $group = Group::factory()->create();

        $response = $this->post("/group/{$group->id}", [
            'content' => 'content',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('show', [$group->id], absolute: false));
    }

    public function test_chat_screen_can_not_rendered_without_login(): void
    {
        $response = $this->get('/group/{$group->id}');

        $response->assertRedirect('/login');
    }

    public function test_can_not_writ_message_without_login(): void
    {
        $group = Group::factory()->create();

        $response = $this->post("/group/{$group->id}", [
            'content' => 'content',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_writing_message_fails_when_content_is_missing(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $group = Group::factory()->create();

        $response = $this->post("/group/{$group->id}", [
            'content' => '',
        ]);

        $response->assertSessionHasErrors(['content']);
    }

    public function test_writing_message_fails_when_content_exceeds_max_length(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $group = Group::factory()->create();

        $response = $this->post("/group/{$group->id}", [
            'content' => str_repeat('a', 141),
        ]);

        $response->assertSessionHasErrors(['content']);
    }
}
