<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Group;
use App\Models\User;

class GroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
    
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_adding_chatgroup(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $group = Group::factory()->create();

        $response = $this->post('/group', [
            'name' => $group->name,
            'description' => 'description',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('index', absolute: false));
    }

    public function test_group_screen_can_not_rendered_without_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_can_not_adding_chat_without_login(): void
    {
        $group = Group::factory()->create();

        $response = $this->post('/group', [
            'name' => $group->name,
            'description' => 'description',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_group_adding_fails_when_name_is_missing(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/group', [
            'name' => '',
            'description' => 'description',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_group_adding_fails_when_name_exceeds_max_length(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/group', [
            'name' => str_repeat('a', 11),
            'description' => 'description',
        ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_group_adding_fails_when_description_exceeds_max_length(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/group', [
            'name' => 'Valid Name',
            'description' => str_repeat('a', 41),
        ]);

        $response->assertSessionHasErrors(['description']);
    }
}
