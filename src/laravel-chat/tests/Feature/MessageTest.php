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

    public function test_message(): void
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
}
