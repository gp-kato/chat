<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

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
        Carbon::setTestNow('2025-04-15 19:00:00');
    }

    private function applicant(User $user, Group $group): void
    {
        $group->users()->attach($user->id, [
            'role' => 'applicant',
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

    private function joinGroup(User $user, Group $group): void
    {
        $group->users()->attach($user->id, [
            'joined_at' => now(),
            'left_at' => null,
        ]);
    }

    private function adminGroup(User $user, Group $group): void
    {
        $group->users()->attach($user->id, [
            'joined_at' => now(),
            'left_at' => null,
            'role' => 'admin',
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

    public function test_application_to_the_join_group_when_leftuser(): void
    {
        $this->actingAs($this->user);
        $this->leftadminGroup($this->user, $this->group);

        $response = $this->post(route('groups.members.application', $this->group));

        $this->assertAuthenticated();
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('groups.index', absolute: false));

        $this->assertDatabaseHas('group_user', [
            'role' => 'applicant',
            'left_at' => null,
            'joined_at' => null,
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

    public function test_cannot_cancel_application(): void
    {
        $this->actingAs($this->user);

        $response = $this->delete(route('groups.members.cancelApplication', $this->group));

        $this->assertAuthenticated();
        $response->assertRedirect(route('groups.index', absolute: false));
        $response->assertSessionHas('error', 'グループに参加申請していません');
    }

    public function test_member_cannot_apply(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $response = $this->post(route('groups.members.application', $this->group));

        $response->assertRedirect();
        $response->assertSessionHas('info', '既にグループに参加しています');
    }

    public function test_applicant_is_not_in_activemembers(): void
    {
        $this->actingAs($this->user);
        $this->applicant($this->user, $this->group);

        $activeMembers = $this->group->isActiveMember($this->user);

        $this->assertFalse(
            $activeMembers
        );
    }

    public function test_can_invite_applicant(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $inviteUser = User::factory()->create([
            'email' => 'invitee@example.com',
        ]);

        $this->group->users()->attach($inviteUser->id, [
            'role' => 'applicant',
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

    public function test_applicant_is_not_in_activeusers(): void
    {
        $this->actingAs($this->user);
        $this->applicant($this->user, $this->group);

        $activeUsers = $this->group->activeUsers();

        $this->assertFalse(
            $activeUsers->contains($this->user)
        );
    }

    public function test_applicant_displaying_application(): void
    {
        $this->actingAs($this->user);
        $this->applicant($this->user, $this->group);

        $response = $this->get(route('groups.index'));

        $response->assertSeeText('申請中');
        $response->assertDontSeeText('参加申請');
    }

    public function test_leftuser_displaying_application_to_join(): void
    {
        $this->actingAs($this->user);
        $this->leftadminGroup($this->user, $this->group);

        $response = $this->get(route('groups.index'));

        $response->assertSeeText('参加申請');
        $response->assertDontSeeText('申請中');
    }

    public function test_applicant_can_cancel_application(): void
    {
        $this->actingAs($this->user);
        $this->applicant($this->user, $this->group);

        $response = $this->delete(route('groups.members.cancelApplication', $this->group));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('group_user', [
            'user_id' => $this->user->id,
            'group_id' => $this->group->id,
            'role' => 'applicant',
        ]);
    }

    public function test_applicant_cannot_view_chat(): void
    {
        $this->actingAs($this->user);
        $this->applicant($this->user, $this->group);

        $response = $this->get(route('groups.messages.show', $this->group->id));

        $response->assertForbidden();
    }

    public function test_applicant_cannot_write_chat(): void
    {
        $this->actingAs($this->user);
        $this->applicant($this->user, $this->group);

        $response = $this->post(route('groups.messages.store', $this->group->id), [
            'content' => 'テストメッセージ',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'このグループに参加していません');
    }

    public function test_applicant_cannot_fetch_messages(): void
    {
        $this->actingAs($this->user);
        $this->applicant($this->user, $this->group);

        $response = $this->get(route('groups.messages.fetch', $this->group->id));

        $response->assertForbidden();
    }

    public function test_can_approve_applicant(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $applicant = User::factory()->create();
        $this->applicant($applicant, $this->group);

        $response = $this->put(
            route('groups.members.approval', [
                'group' => $this->group->id,
                'user' => $applicant->id,
            ])
        );

        $this->assertAuthenticated();
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('group_user', [
            'group_id' => $this->group->id,
            'user_id' => $applicant->id,
            'joined_at' => now(),
            'left_at' => null,
            'role' => 'member',
        ]);
    }

    public function test_cannot_approve_nonapplicant(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $nonapplicant = User::factory()->create();

        $response = $this->put(
            route('groups.members.approval', [
                'group' => $this->group->id,
                'user' => $nonapplicant->id,
            ])
        );

        $this->assertAuthenticated();
        $response->assertRedirect();

        $this->assertDatabaseMissing('group_user', [
            'group_id' => $this->group->id,
            'user_id' => $nonapplicant->id,
            'joined_at' => now(),
            'left_at' => null,
            'role' => 'member',
        ]);
    }

    public function test_cannot_approve_anotheradmin(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $anotheradmin = User::factory()->create();
        $this->adminGroup($anotheradmin, $this->group);

        $response = $this->put(
            route('groups.members.approval', [
                'group' => $this->group->id,
                'user' => $anotheradmin->id,
            ])
        );

        $this->assertAuthenticated();
        $response->assertRedirect();

        $this->assertDatabaseHas('group_user', [
            'group_id' => $this->group->id,
            'user_id' => $anotheradmin->id,
            'role' => 'admin',
        ]);

        $this->assertDatabaseMissing('group_user', [
            'group_id' => $this->group->id,
            'user_id' => $anotheradmin->id,
            'joined_at' => now(),
            'left_at' => null,
            'role' => 'member',
        ]);
    }

    public function test_cannot_approve_leftuser(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $leftuser = User::factory()->create();
        $this->leftadminGroup($leftuser, $this->group);

        $response = $this->put(
            route('groups.members.approval', [
                'group' => $this->group->id,
                'user' => $leftuser->id,
            ])
        );

        $this->assertAuthenticated();
        $response->assertRedirect();

        $this->assertDatabaseHas('group_user', [
            'group_id' => $this->group->id,
            'user_id' => $leftuser->id,
            'left_at' => now(),
        ]);

        $this->assertDatabaseMissing('group_user', [
            'group_id' => $this->group->id,
            'user_id' => $leftuser->id,
            'joined_at' => now(),
            'left_at' => null,
            'role' => 'member',
        ]);
    }

    public function test_cannot_approve_applicant_when_admin_has_left(): void
    {
        $this->actingAs($this->user);
        $this->leftadminGroup($this->user, $this->group);

        $applicant = User::factory()->create();
        $this->applicant($applicant, $this->group);

        $response = $this->put(
            route('groups.members.approval', [
                'group' => $this->group->id,
                'user' => $applicant->id,
            ])
        );

        $this->assertAuthenticated();
        $response->assertForbidden();

        $this->assertDatabaseHas('group_user', [
            'group_id' => $this->group->id,
            'user_id' => $applicant->id,
            'role' => 'applicant',
            'joined_at' => null,
        ]);

        $this->assertDatabaseMissing('group_user', [
            'group_id' => $this->group->id,
            'user_id' => $applicant->id,
            'joined_at' => now(),
            'left_at' => null,
            'role' => 'member',
        ]);
    }

    public function test_can_reject_applicant(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $applicant = User::factory()->create();
        $this->applicant($applicant, $this->group);

        $response = $this->delete(
            route('groups.members.reject', [
                'group' => $this->group->id,
                'user' => $applicant->id,
            ])
        );

        $this->assertAuthenticated();
        $response->assertRedirect();

        $this->assertDatabaseMissing('group_user', [
            'group_id' => $this->group->id,
            'user_id' => $applicant->id,
        ]);
    }

    public function test_cannot_reject_nonapplicant(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $nonapplicant = User::factory()->create();

        $response = $this->delete(
            route('groups.members.reject', [
                'group' => $this->group->id,
                'user' => $nonapplicant->id,
            ])
        );

        $this->assertAuthenticated();
        $response->assertRedirect();

        $this->assertDatabaseMissing('group_user', [
            'group_id' => $this->group->id,
            'user_id' => $nonapplicant->id,
        ]);
    }

    public function test_cannot_reject_anotheradmin(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $anotheradmin = User::factory()->create();
        $this->adminGroup($anotheradmin, $this->group);

        $response = $this->delete(
            route('groups.members.reject', [
                'group' => $this->group->id,
                'user' => $anotheradmin->id,
            ])
        );

        $this->assertAuthenticated();
        $response->assertRedirect();

        $this->assertDatabaseHas('group_user', [
            'group_id' => $this->group->id,
            'user_id' => $anotheradmin->id,
            'role' => 'admin',
        ]);
    }

    public function test_cannot_reject_joinmember(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $joinimember = User::factory()->create();
        $this->joinGroup($joinimember, $this->group);

        $response = $this->delete(
            route('groups.members.reject', [
                'group' => $this->group->id,
                'user' => $joinimember->id,
            ])
        );

        $this->assertAuthenticated();
        $response->assertRedirect();

        $this->assertDatabaseHas('group_user', [
            'group_id' => $this->group->id,
            'user_id' => $joinimember->id,
            'role' => 'member',
        ]);
    }

    public function test_cannot_reject_leftuser(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $leftuser = User::factory()->create();
        $this->leftadminGroup($leftuser, $this->group);

        $response = $this->delete(
            route('groups.members.reject', [
                'group' => $this->group->id,
                'user' => $leftuser->id,
            ])
        );

        $this->assertAuthenticated();
        $response->assertRedirect();

        $this->assertDatabaseHas('group_user', [
            'user_id' => $leftuser->id,
            'left_at' => now(),
        ]);
    }

    public function test_cannot_reject_applicant_when_admin_has_left(): void
    {
        $this->actingAs($this->user);
        $this->leftadminGroup($this->user, $this->group);

        $applicant = User::factory()->create();
        $this->applicant($applicant, $this->group);

        $response = $this->delete(
            route('groups.members.reject', [
                'group' => $this->group->id,
                'user' => $applicant->id,
            ])
        );

        $this->assertAuthenticated();
        $response->assertForbidden();

        $this->assertDatabaseHas('group_user', [
            'group_id' => $this->group->id,
            'user_id' => $applicant->id,
            'role' => 'applicant',
        ]);
    }
}
