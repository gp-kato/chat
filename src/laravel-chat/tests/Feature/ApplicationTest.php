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

    private function applicant(User $user, Group $group): void
    {
        $group->users()->attach($user->id, [
            'role' => 'applicant',
        ]);
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

    public function test_application_cancel(): void
    {
        $this->actingAs($this->user);
        $this->applicant($this->user, $this->group);

        $response = $this->delete(route('groups.members.cancelApplication', $this->group));

        $this->assertAuthenticated();
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('groups.index', absolute: false));

        $this->assertDatabaseMissing('group_user', [
            'role' => 'applicant',
        ]);
    }
}
