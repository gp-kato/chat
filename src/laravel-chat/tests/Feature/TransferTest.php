<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\Group;

class TransferTest extends TestCase
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

    public function test_can_transfer_admin_rights_when_admin(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $memberUser = User::factory()->create();
        $this->joinGroup($memberUser, $this->group);

        $response = $this->put(
            route('groups.members.transfer', [
                'group' => $this->group->id,
                'user'  => $memberUser->id,
            ])
        );

        $this->assertAuthenticated();
        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        $this->assertDatabaseHas('group_user', [
            'group_id' => $this->group->id,
            'user_id'  => $memberUser->id,
            'role'       => 'admin',
        ]);

        $this->assertTrue(
            $this->group->isAdmin($memberUser),
        );
    }

    public function test_cannot_transfer_admin_rights_without_admin(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $memberUser = User::factory()->create();
        $this->joinGroup($memberUser, $this->group);

        $response = $this->put(
            route('groups.members.transfer', [
                'group' => $this->group->id,
                'user'  => $memberUser->id,
            ])
        );

        $response->assertForbidden();

        $this->assertDatabaseMissing('group_user', [
            'group_id' => $this->group->id,
            'user_id'  => $memberUser->id,
            'role'       => 'mmember',
        ]);

        $this->assertFalse(
            $this->group->isAdmin($memberUser),
        );
    }

    public function test_cannot_transfer_admin_rights_without_member(): void
    {
        $this->actingAs($this->user);

        $memberUser = User::factory()->create();
        $this->joinGroup($memberUser, $this->group);

        $response = $this->put(
            route('groups.members.transfer', [
                'group' => $this->group->id,
                'user'  => $memberUser->id,
            ])
        );

        $response->assertForbidden();

        $this->assertDatabaseMissing('group_user', [
            'group_id' => $this->group->id,
            'user_id'  => $memberUser->id,
            'role'       => 'mmember',
        ]);

        $this->assertFalse(
            $this->group->isAdmin($memberUser),
        );
    }

    public function test_cannot_transfer_admin_rights_with_other_admin(): void
    {
        $otherGroup = Group::factory()->create();
        $this->adminGroup($this->user, $otherGroup);
        $this->actingAs($this->user);

        $memberUser = User::factory()->create();
        $this->joinGroup($memberUser, $this->group);

        $response = $this->put(
            route('groups.members.transfer', [
                'group' => $this->group->id,
                'user'  => $memberUser->id,
            ])
        );

        $response->assertForbidden();

        $this->assertDatabaseMissing('group_user', [
            'group_id' => $this->group->id,
            'user_id'  => $memberUser->id,
            'role'       => 'mmember',
        ]);

        $this->assertFalse(
            $this->group->isAdmin($memberUser),
        );
    }

    public function test_cannot_transfer_admin_rights_after_left_admin(): void
    {
        $this->actingAs($this->user);
        $this->leftadminGroup($this->user, $this->group);

        $memberUser = User::factory()->create();
        $this->joinGroup($memberUser, $this->group);

        $response = $this->put(
            route('groups.members.transfer', [
                'group' => $this->group->id,
                'user'  => $memberUser->id,
            ])
        );

        $response->assertForbidden();

        $this->assertDatabaseMissing('group_user', [
            'group_id' => $this->group->id,
            'user_id'  => $memberUser->id,
            'role'       => 'mmember',
        ]);

        $this->assertFalse(
            $this->group->isAdmin($memberUser),
        );
    }
}
