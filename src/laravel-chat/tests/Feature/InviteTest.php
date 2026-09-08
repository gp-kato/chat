<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InviteTest extends TestCase
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

    public function test_can_invite_when_admin(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $inviteUser = User::factory()->create([
            'email' => 'invitee@example.com',
        ]);

        $response = $this->post(
            route('groups.invitations.invite', $this->group),
            ['user_id' => $inviteUser->id]
        );

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        $this->assertDatabaseHas('invitations', [
            'group_id' => $this->group->id,
            'inviter_id' => $this->user->id,
            'accepted_at' => null,
        ]);
    }

    public function test_cannot_invite_with_other_admin(): void
    {
        $otherGroup = Group::factory()->create();
        $this->adminGroup($this->user, $otherGroup);
        $this->actingAs($this->user);

        $inviteUser = User::factory()->create();

        $response = $this->post(
            route('groups.invitations.invite', $this->group),
            ['user_id' => $inviteUser->id]
        );

        $response->assertForbidden();

        $this->assertDatabaseMissing('invitations', [
            'group_id' => $this->group->id,
        ]);
    }

    public function test_cannot_invite_without_admin(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $inviteUser = User::factory()->create();

        $response = $this->post(
            route('groups.invitations.invite', $this->group),
            ['user_id' => $inviteUser->id]
        );

        $response->assertForbidden();

        $this->assertDatabaseMissing('invitations', [
            'group_id' => $this->group->id,
        ]);
    }

    public function test_cannot_invite_after_left_admin(): void
    {
        $this->actingAs($this->user);
        $this->leftadminGroup($this->user, $this->group);

        $inviteUser = User::factory()->create();

        $response = $this->post(
            route('groups.invitations.invite', $this->group),
            ['user_id' => $inviteUser->id]
        );

        $response->assertForbidden();

        $this->assertDatabaseMissing('invitations', [
            'group_id' => $this->group->id,
        ]);
    }

    public function test_cannot_invite_member(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $inviteUser = User::factory()->create();
        $this->joinGroup($inviteUser, $this->group);

        $response = $this->post(
            route('groups.invitations.invite', $this->group),
            ['user_id' => $inviteUser->id]
        );

        $response->assertRedirect();

        $this->assertDatabaseMissing('invitations', [
            'group_id' => $this->group->id,
        ]);
    }

}
