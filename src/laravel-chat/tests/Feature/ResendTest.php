<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ResendTest extends TestCase
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

    public function test_can_resend_when_admin(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $invitation = Invitation::create([
            'group_id' => $this->group->id,
            'inviter_id' => $this->user->id,
            'invitee_email' => 'invitee@example.com',
            'token' => \Illuminate\Support\Str::uuid(),
            'created_at' => '2025-04-07 08:30:17',
            'expires_at' => '2025-05-07 08:30:17',
        ]);

        $response = $this->post(
            route('groups.invitations.resend', [
                'group' => $this->group->id,
                'invitation' => $invitation->id,
            ])
        );

        $this->assertAuthenticated();
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('invitations', [
            'expires_at' => now()->addDays(31),
        ]);
    }

    public function test_cannot_resend_with_other_admin(): void
    {
        $otherGroup = Group::factory()->create();
        $this->adminGroup($this->user, $otherGroup);
        $this->actingAs($this->user);

        $invitation = Invitation::create([
            'group_id' => $this->group->id,
            'inviter_id' => $this->user->id,
            'invitee_email' => 'invitee@example.com',
            'token' => \Illuminate\Support\Str::uuid(),
            'created_at' => '2025-04-07 08:30:17',
            'expires_at' => '2025-05-07 08:30:17',
        ]);

        $response = $this->post(
            route('groups.invitations.resend', [
                'group' => $this->group->id,
                'invitation' => $invitation->id,
            ])
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('invitations', [
            'expires_at' => '2025-05-07 08:30:17',
        ]);
    }

    public function test_cannot_resend_without_admin(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $invitation = Invitation::create([
            'group_id' => $this->group->id,
            'inviter_id' => $this->user->id,
            'invitee_email' => 'invitee@example.com',
            'token' => \Illuminate\Support\Str::uuid(),
            'created_at' => '2025-04-07 08:30:17',
            'expires_at' => '2025-05-07 08:30:17',
        ]);

        $response = $this->post(
            route('groups.invitations.resend', [
                'group' => $this->group->id,
                'invitation' => $invitation->id,
            ])
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('invitations', [
            'expires_at' => '2025-05-07 08:30:17',
        ]);
    }

    public function test_cannot_resend_after_left_admin(): void
    {
        $this->actingAs($this->user);
        $this->leftadminGroup($this->user, $this->group);

        $invitation = Invitation::create([
            'group_id' => $this->group->id,
            'inviter_id' => $this->user->id,
            'invitee_email' => 'invitee@example.com',
            'token' => \Illuminate\Support\Str::uuid(),
            'created_at' => '2025-04-07 08:30:17',
            'expires_at' => '2025-05-07 08:30:17',
        ]);

        $response = $this->post(
            route('groups.invitations.resend', [
                'group' => $this->group->id,
                'invitation' => $invitation->id,
            ])
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('invitations', [
            'expires_at' => '2025-05-07 08:30:17',
        ]);
    }
}
