<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\Group;
use App\Models\Invitation;

class AdminTest extends TestCase
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

    public function test_edit_screen_can_be_rendered_with_admin(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $response = $this->get(route('groups.edit', $this->group->id));

        $response->assertStatus(200);
    }

    public function test_edit_screen_cannot_rendered_without_admin(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $response = $this->get(route('groups.edit', $this->group->id));

        $response->assertForbidden();
    }

    public function test_edit_screen_cannot_rendered_without_member(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('groups.edit', $this->group->id));

        $response->assertForbidden();
    }

    public function test_edit_screen_cannot_rendered_with_other_admin(): void
    {
        $otherGroup = Group::factory()->create();
        $this->adminGroup($this->user, $otherGroup);
        $this->actingAs($this->user);

        $response = $this->get(route('groups.edit', $this->group->id));

        $response->assertForbidden();
    }

    public function test_edit_screen_cannot_rendered_with_leftadmin(): void
    {
        $this->actingAs($this->user);
        $this->leftadminGroup($this->user, $this->group);

        $response = $this->get(route('groups.edit', $this->group->id));

        $response->assertForbidden();
    }

    public function test_editing_chatgroup(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $response = $this->put(route('groups.update', $this->group->id), [
            'name' => 'name',
            'description' => 'description',
        ]);

        $this->assertAuthenticated();
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('groups.messages.show', $this->group->id, absolute: false));

        $this->assertDatabaseHas('groups', [
            'id' => $this->group->id,
            'name' => 'name',
            'description' => 'description',
        ]);
    }

    public function test_cannot_be_editing_chatgroup_with_other_admin(): void
    {
        $otherGroup = Group::factory()->create();
        $this->adminGroup($this->user, $otherGroup);
        $this->actingAs($this->user);

        $inviteUser = User::factory()->create();

        $response = $this->put(route('groups.update', $this->group->id), [
            'name' => 'name',
            'description' => 'description',
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('groups', [
            'id' => $this->group->id,
            'name' => 'name',
            'description' => 'description',
        ]);
    }

    public function test_cannot_be_editing_chatgroup_without_admin(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $response = $this->put(route('groups.update', $this->group->id), [
            'name' => 'name',
            'description' => 'description',
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('groups', [
            'id' => $this->group->id,
            'name' => 'name',
            'description' => 'description',
        ]);
    }

    public function test_cannot_be_editing_chatgroup_with_leftadmin(): void
    {
        $this->actingAs($this->user);
        $this->leftadminGroup($this->user, $this->group);

        $response = $this->put(route('groups.update', $this->group->id), [
            'name' => 'name',
            'description' => 'description',
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('groups', [
            'id' => $this->group->id,
            'name' => 'name',
            'description' => 'description',
        ]);
    }

    public function test_can_be_invitation_with_admin(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $inviteUser = User::factory()->create([
            'email' => 'invitee@example.com',
        ]);

        $response = $this->post(
            route('groups.invitations.invite', $this->group),
            ['user_id' => $inviteUser->id,]
        );

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);

        $this->assertDatabaseHas('invitations', [
            'group_id'       => $this->group->id,
            'inviter_id'     => $this->user->id,
            'accepted_at'    => null,
        ]);
    }

    public function test_cannot_be_invitation_with_other_admin(): void
    {
        $otherGroup = Group::factory()->create();
        $this->adminGroup($this->user, $otherGroup);
        $this->actingAs($this->user);

        $inviteUser = User::factory()->create();

        $response = $this->post(
            route('groups.invitations.invite', $this->group),
            ['user_id' => $inviteUser->id,]
        );

        $response->assertStatus(302);

        $this->assertDatabaseMissing('invitations', [
            'group_id'       => $this->group->id,
        ]);
    }

    public function test_cannot_be_invitation_without_admin(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $inviteUser = User::factory()->create();

        $response = $this->post(
            route('groups.invitations.invite', $this->group),
            ['user_id' => $inviteUser->id,]
        );

        $response->assertStatus(302);

        $this->assertDatabaseMissing('invitations', [
            'group_id'       => $this->group->id,
        ]);
    }

    public function test_cannot_be_invitation_with_leftadmin(): void
    {
        $this->actingAs($this->user);
        $this->leftadminGroup($this->user, $this->group);

        $inviteUser = User::factory()->create();

        $response = $this->post(
            route('groups.invitations.invite', $this->group),
            ['user_id' => $inviteUser->id,]
        );

        $response->assertStatus(302);

        $this->assertDatabaseMissing('invitations', [
            'group_id'       => $this->group->id,
        ]);
    }

    public function test_can_be_remove_user_with_admin(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $memberUser = User::factory()->create();
        $this->group->users()->attach($memberUser->id, [
            'joined_at' => '2025-04-07 08:30:17',
            'left_at' => null,
        ]);

        $response = $this->delete(
            route('groups.members.remove', [
                'group' => $this->group->id,
                'user'  => $memberUser->id,
            ])
        );

        $this->assertAuthenticated();
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('group_user', [
            'group_id' => $this->group->id,
            'user_id'  => $memberUser->id,
            'left_at'  => now(),
        ]);
    }

    public function test_cannot_be_remove_user_with_other_admin(): void
    {
        $otherGroup = Group::factory()->create();
        $this->adminGroup($this->user, $otherGroup);
        $this->actingAs($this->user);

        $memberUser = User::factory()->create();
        $this->group->users()->attach($memberUser->id, [
            'joined_at' => '2025-04-07 08:30:17',
            'left_at' => null,
        ]);

        $response = $this->delete(
            route('groups.members.remove', [
                'group' => $this->group->id,
                'user'  => $memberUser->id,
            ])
        );

        $response->assertStatus(302);

        $this->assertDatabaseMissing('group_user', [
            'group_id' => $this->group->id,
            'user_id'  => $memberUser->id,
            'left_at'  => now(),
        ]);
    }

    public function test_cannot_be_remove_user_without_admin(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $memberUser = User::factory()->create();

        $this->group->users()->attach($memberUser->id, [
            'joined_at' => '2025-04-07 08:30:17',
            'left_at' => null,
        ]);

        $response = $this->delete(
            route('groups.members.remove', [
                'group' => $this->group->id,
                'user'  => $memberUser->id,
            ])
        );

        $response->assertStatus(302);

        $this->assertDatabaseMissing('group_user', [
            'group_id' => $this->group->id,
            'user_id'  => $memberUser->id,
            'left_at'  => now(),
        ]);
    }

    public function test_cannot_be_remove_user_with_leftadmin(): void
    {
        $this->actingAs($this->user);
        $this->leftadminGroup($this->user, $this->group);

        $memberUser = User::factory()->create();

        $this->group->users()->attach($memberUser->id, [
            'joined_at' => '2025-04-07 08:30:17',
            'left_at' => null,
        ]);

        $response = $this->delete(
            route('groups.members.remove', [
                'group' => $this->group->id,
                'user'  => $memberUser->id,
            ])
        );

        $response->assertStatus(302);

        $this->assertDatabaseMissing('group_user', [
            'group_id' => $this->group->id,
            'user_id'  => $memberUser->id,
            'left_at'  => now(),
        ]);
    }

    public function test_can_be_resend_with_admin(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $memberUser = User::factory()->create();
        $this->group->users()->attach($memberUser->id, [
            'joined_at' => null,
        ]);

        $invitation = Invitation::create([
            'group_id'      => $this->group->id,
            'inviter_id'    => $this->user->id,
            'invitee_email' => 'invitee@example.com',
            'token'         => \Illuminate\Support\Str::uuid(),
            'created_at' => '2025-04-07 08:30:17',
            'expires_at' => '2025-05-07 08:30:17',
        ]);

        $response = $this->post(
            route('groups.invitations.resend', [
                'group' => $this->group->id,
                'invitation' => $invitation->id
            ])
        );

        $this->assertAuthenticated();
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('invitations', [
            'expires_at' => now()->addDays(31),
        ]);
    }

    public function test_cannot_be_resend_with_other_admin(): void
    {
        $otherGroup = Group::factory()->create();
        $this->adminGroup($this->user, $otherGroup);
        $this->actingAs($this->user);

        $memberUser = User::factory()->create();
        $this->group->users()->attach($memberUser->id, [
            'joined_at' => null,
        ]);

        $invitation = Invitation::create([
            'group_id'      => $this->group->id,
            'inviter_id'    => $this->user->id,
            'invitee_email' => 'invitee@example.com',
            'token'         => \Illuminate\Support\Str::uuid(),
            'created_at' => '2025-04-07 08:30:17',
            'expires_at' => '2025-05-07 08:30:17',
        ]);

        $response = $this->post(
            route('groups.invitations.resend', [
                'group' => $this->group->id,
                'invitation' => $invitation->id
            ])
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('invitations', [
            'expires_at' => '2025-05-07 08:30:17',
        ]);
    }

    public function test_cannot_be_resend_without_admin(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $memberUser = User::factory()->create();
        $this->group->users()->attach($memberUser->id, [
            'joined_at' => null,
        ]);

        $invitation = Invitation::create([
            'group_id'      => $this->group->id,
            'inviter_id'    => $this->user->id,
            'invitee_email' => 'invitee@example.com',
            'token'         => \Illuminate\Support\Str::uuid(),
            'created_at' => '2025-04-07 08:30:17',
            'expires_at' => '2025-05-07 08:30:17',
        ]);

        $response = $this->post(
            route('groups.invitations.resend', [
                'group' => $this->group->id,
                'invitation' => $invitation->id
            ])
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('invitations', [
            'expires_at' => '2025-05-07 08:30:17',
        ]);
    }

    public function test_cannot_be_resend_with_leftadmin(): void
    {
        $this->actingAs($this->user);
        $this->leftadminGroup($this->user, $this->group);

        $memberUser = User::factory()->create();
        $this->group->users()->attach($memberUser->id, [
            'joined_at' => null,
        ]);

        $invitation = Invitation::create([
            'group_id'      => $this->group->id,
            'inviter_id'    => $this->user->id,
            'invitee_email' => 'invitee@example.com',
            'token'         => \Illuminate\Support\Str::uuid(),
            'created_at' => '2025-04-07 08:30:17',
            'expires_at' => '2025-05-07 08:30:17',
        ]);

        $response = $this->post(
            route('groups.invitations.resend', [
                'group' => $this->group->id,
                'invitation' => $invitation->id
            ])
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('invitations', [
            'expires_at' => '2025-05-07 08:30:17',
        ]);
    }
}
