<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Group;
use App\Models\User;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    private ?User $user = null;
    private ?Group $group = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(); // すべてのテストで使うユーザーを作成
        $this->group = Group::factory()->create(); // 1回だけグループを作成
    }

    public function test_application_to_the_join_group(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('groups.members.application', $this->group));

        $this->assertAuthenticated();
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('groups.index', absolute: false));

        $this->assertDatabaseHas('group_user', [
            'role' => 'applicant',
        ]);
    }
}
