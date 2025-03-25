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

    public function test_group(): void
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
}
