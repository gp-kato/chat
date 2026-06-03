<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Group;
use App\Models\User;

class ApplicantTest extends TestCase
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

    private function adminGroup(User $user, Group $group): void
    {
        $group->users()->attach($user->id, [
            'joined_at' => now(),
            'left_at' => null,
            'role' => 'admin',
        ]);
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
}
