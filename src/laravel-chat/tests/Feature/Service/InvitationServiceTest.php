<?php

namespace Tests\Feature\Service;

use App\Mail\GroupInvitation;
use App\Models\Group;
use App\Models\Invitation;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvitationServiceTest extends TestCase
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

    public function test_can_invite(): void
    {
        Mail::fake();

        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $inviteUser = User::factory()->create([
            'email' => 'invitee@example.com',
        ]);

        $service = app(InvitationService::class);

        $service->invite($this->group, $inviteUser);

        $invitation = Invitation::where('group_id', $this->group->id)
            ->where('invitee_email', $inviteUser->email)
            ->first();
        $this->assertNotNull($invitation);
        $this->assertNotEmpty($invitation->token);
        $this->assertDatabaseHas('invitations', [
            'group_id' => $this->group->id,
            'inviter_id' => $this->user->id,
            'token' => $invitation->token,
            'invitee_email' => $inviteUser->email,
            'expires_at' => now()->addDays(31),
        ]);

        Mail::assertSent(GroupInvitation::class, function ($mail) use ($inviteUser) {
            return $mail->hasTo($inviteUser->email);
        });
    }

    public function test_cannot_duplicate_invitation(): void
    {
        Mail::fake();

        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $inviteUser = User::factory()->create([
            'email' => 'invitee@example.com',
        ]);

        Invitation::create([
            'group_id' => $this->group->id,
            'inviter_id' => $this->user->id,
            'token' => \Illuminate\Support\Str::uuid(),
            'created_at' => '2025-04-07 08:30:17',
            'invitee_email' => $inviteUser->email,
            'expires_at' => now()->addDays(31),
        ]);

        $service = app(InvitationService::class);

        $result = $service->invite($this->group, $inviteUser);

        $this->assertFalse($result['success']);
        $this->assertSame('already_invited', $result['reason']);

        Mail::assertNotSent(GroupInvitation::class);

        $this->assertDatabaseCount('invitations', 1);
    }

    public function test_cannot_invite_member(): void
    {
        Mail::fake();

        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $inviteUser = User::factory()->create([
            'email' => 'invitee@example.com',
        ]);
        $this->joinGroup($inviteUser, $this->group);

        $service = app(InvitationService::class);

        $result = $service->invite($this->group, $inviteUser);

        $this->assertFalse($result['success']);
        $this->assertSame('already_member', $result['reason']);

        Mail::assertNotSent(GroupInvitation::class);

        $this->assertDatabaseCount('invitations', 0);
    }

    public function test_can_invite_resend(): void
    {
        Mail::fake();

        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $inviteUser = User::factory()->create([
            'email' => 'invitee@example.com',
        ]);

        $invitation = Invitation::create([
            'group_id' => $this->group->id,
            'inviter_id' => $this->user->id,
            'token' => 'dumyToken123',
            'created_at' => '2025-04-07 08:30:17',
            'invitee_email' => $inviteUser->email,
            'expires_at' => '2025-05-07 08:30:17',
        ]);

        $originalToken = $invitation->token;

        $service = app(InvitationService::class);

        $service->resend($this->group, $invitation);

        $invitation->refresh();

        $invitation = Invitation::where('group_id', $this->group->id)
            ->where('invitee_email', $inviteUser->email)
            ->first();
        $this->assertSame($originalToken, $invitation->token);
        $this->assertNotNull($invitation);
        $this->assertNotEmpty($invitation->token);
        $this->assertDatabaseHas('invitations', [
            'group_id' => $this->group->id,
            'inviter_id' => $this->user->id,
            'token' => $invitation->token,
            'invitee_email' => $inviteUser->email,
            'expires_at' => now()->addDays(31),
        ]);

        Mail::assertSent(GroupInvitation::class, function ($mail) use ($inviteUser) {
            return $mail->hasTo($inviteUser->email);
        });

        $this->assertDatabaseCount('invitations', 1);
    }
}
