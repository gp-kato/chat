<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\Group;

class DemoteTest extends TestCase
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

    private function joinGroup(User $user, Group $group): void
    {
        $group->users()->attach($user->id, [
            'joined_at' => now(),
            'left_at' => null,
        ]);
    }

    private function leftadminGroup(User $user, Group $group): void
    {
        $group->users()->attach($user->id, [
            'joined_at' => '2025-04-07 08:30:17',
            'left_at' => now(),
            'role' => 'admin',
        ]);
    }

    public function test_can_demote_not_last_admin(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $anotheradmin = User::factory()->create();
        $this->adminGroup($anotheradmin, $this->group);

        $response = $this->put(
            route('groups.members.demote', [
                'group' => $this->group->id,
            ])
        );

        $this->assertAuthenticated();
        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        $this->assertDatabaseHas('group_user', [
            'group_id' => $this->group->id,
            'user_id'  => $this->user->id,
            'role'       => 'member',
        ]);

        $this->assertFalse(
            $this->group->isAdmin($this->user),
        );
    }

    public function test_cannot_demote_with_last_admin(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $response = $this->put(
            route('groups.members.demote', [
                'group' => $this->group->id,
            ])
        );

        $this->assertAuthenticated();
        $response->assertStatus(302);

        $this->assertDatabaseHas('group_user', [
            'group_id' => $this->group->id,
            'user_id'  => $this->user->id,
            'role'       => 'admin',
        ]);

        $this->assertTrue(
            $this->group->isAdmin($this->user),
        );
    }

    public function test_cannot_demote_with_not_admin(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $response = $this->put(
            route('groups.members.demote', [
                'group' => $this->group->id,
            ])
        );

        $this->assertAuthenticated();
        $response->assertStatus(403);
    }

    public function test_cannot_demote_with_other_admin(): void
    {
        $otherGroup = Group::factory()->create();
        $this->adminGroup($this->user, $otherGroup);
        $this->actingAs($this->user);

        $response = $this->put(
            route('groups.members.demote', [
                'group' => $this->group->id,
            ])
        );

        $this->assertAuthenticated();
        $response->assertStatus(403);

        $this->assertDatabaseHas('group_user', [
            'group_id' => $otherGroup->id,
            'user_id'  => $this->user->id,
            'role'       => 'admin',
        ]);

        $this->assertTrue(
            $otherGroup->isAdmin($this->user),
        );
    }

    public function test_cannot_demote_with_left_admin(): void
    {
        $this->actingAs($this->user);
        $this->leftadminGroup($this->user, $this->group);

        $response = $this->put(
            route('groups.members.demote', [
                'group' => $this->group->id,
            ])
        );

        $this->assertAuthenticated();
        $response->assertStatus(403);
    }
}
