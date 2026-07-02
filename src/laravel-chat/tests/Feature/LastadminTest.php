<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LastadminTest extends TestCase
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

    private function adminGroup(User $user, Group $group): void
    {
        $group->users()->attach($user->id, [
            'joined_at' => now(),
            'left_at' => null,
            'role' => 'admin',
        ]);
    }

    public function test_cannot_leave_with_last_admin(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $response = $this->delete(route('groups.members.leave', $this->group->id));

        $this->assertAuthenticated();
        $response->assertRedirect();
        $response->assertSessionHas('error', '管理者が1人しかいないため、退会できません。');

        $this->assertDatabaseHas('group_user', [
            'group_id' => $this->group->id,
            'user_id' => $this->user->id,
            'role' => 'admin',
            'left_at' => null,
        ]);
    }

    public function test_cannot_remove_admin(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $response = $this->delete(
            route('groups.members.remove', [
                'group' => $this->group->id,
                'user' => $this->user->id,
            ])
        );

        $this->assertAuthenticated();
        $response->assertRedirect();
        $response->assertSessionHas('error', '管理者同士では退会出来ません');

        $this->assertDatabaseHas('group_user', [
            'group_id' => $this->group->id,
            'user_id' => $this->user->id,
            'role' => 'admin',
            'left_at' => null,
        ]);
    }
}
