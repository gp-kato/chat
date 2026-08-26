<?php

namespace Tests\Feature\Service\GroupMemberService;

use App\Exceptions\Domain\AlreadyMemberException;
use App\Exceptions\Domain\InvalidInvitationException;
use App\Models\Group;
use App\Models\Invitation;
use App\Models\User;
use App\Services\GroupMemberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class joinbyinvitationTest extends TestCase
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

    public function test_join_by_invitation(): void
    {
        $this->actingAs($this->user);

        $inviter = User::factory()->create();
        $this->adminGroup($inviter, $this->group);

        $token = 'dummyToken123';
        Invitation::create([
            'group_id' => $this->group->id,
            'inviter_id' => $inviter->id,
            'invitee_email' => $this->user->email,
            'token' => $token,
            'expires_at' => now()->addDays(31),
            'accepted_at' => null,
        ]);

        $service = app(GroupMemberService::class);

        $service->joinByInvitation($this->group, $token, $this->user);

        $this->assertDatabaseHas('group_user', [
            'group_id' => $this->group->id,
            'user_id' => $this->user->id,
            'joined_at' => now(),
            'left_at' => null,
            'role' => 'member',
        ]);

        $this->assertDatabaseHas('invitations', [
            'group_id' => $this->group->id,
            'token' => $token,
            'accepted_at' => now(),
        ]);
    }

    public function test_join_fails_with_invalid_token(): void
    {
        $this->actingAs($this->user);

        $inviter = User::factory()->create();
        $this->adminGroup($inviter, $this->group);

        $token = 'dummyToken123';
        Invitation::create([
            'group_id' => $this->group->id,
            'inviter_id' => $inviter->id,
            'invitee_email' => $this->user->email,
            'token' => 'dummyToken456',
            'expires_at' => now()->addDays(31),
            'accepted_at' => null,
        ]);

        $service = app(GroupMemberService::class);

        try {
            $service->joinByInvitation($this->group, $token, $this->user);

            $this->fail('InvalidInvitationException was not thrown.');
        } catch (InvalidInvitationException $e) {
            // 想定どおり例外が発生
        }

        $this->assertDatabaseCount('group_user', 1);

        $this->assertDatabaseHas('invitations', [
            'group_id' => $this->group->id,
            'token' => 'dummyToken456',
            'accepted_at' => null,
        ]);
    }

    public function test_join_fails_with_expired_invitation(): void
    {
        $this->actingAs($this->user);

        $inviter = User::factory()->create();
        $this->adminGroup($inviter, $this->group);

        $token = 'dummyToken123';
        Invitation::create([
            'group_id' => $this->group->id,
            'inviter_id' => $inviter->id,
            'invitee_email' => $this->user->email,
            'token' => $token,
            'expires_at' => now()->subDay(),
            'accepted_at' => null,
        ]);

        $service = app(GroupMemberService::class);

        try {
            $service->joinByInvitation($this->group, $token, $this->user);

            $this->fail('InvalidInvitationException was not thrown.');
        } catch (InvalidInvitationException $e) {
            // 想定どおり例外が発生
        }

        $this->assertDatabaseCount('group_user', 1);

        $this->assertDatabaseHas('invitations', [
            'group_id' => $this->group->id,
            'expires_at' => now()->subDay(),
            'accepted_at' => null,
        ]);
    }

    public function test_join_fails_with_difurans_email(): void
    {
        $this->actingAs($this->user);

        $inviter = User::factory()->create();
        $this->adminGroup($inviter, $this->group);

        $token = 'dummyToken123';
        Invitation::create([
            'group_id' => $this->group->id,
            'inviter_id' => $inviter->id,
            'invitee_email' => 'dummy@emailcom',
            'token' => $token,
            'expires_at' => now()->addDays(31),
            'accepted_at' => null,
        ]);

        $service = app(GroupMemberService::class);

        try {
            $service->joinByInvitation($this->group, $token, $this->user);

            $this->fail('InvalidInvitationException was not thrown.');
        } catch (InvalidInvitationException $e) {
            // 想定どおり例外が発生
        }

        $this->assertDatabaseCount('group_user', 1);

        $this->assertDatabaseHas('invitations', [
            'group_id' => $this->group->id,
            'invitee_email' => 'dummy@emailcom',
            'accepted_at' => null,
        ]);
    }

    public function test_join_fails_with_already_member(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $inviter = User::factory()->create();
        $this->adminGroup($inviter, $this->group);

        $token = 'dummyToken123';
        Invitation::create([
            'group_id' => $this->group->id,
            'inviter_id' => $inviter->id,
            'invitee_email' => $this->user->email,
            'token' => $token,
            'expires_at' => now()->addDays(31),
            'accepted_at' => null,
        ]);

        $service = app(GroupMemberService::class);

        try {
            $service->joinByInvitation($this->group, $token, $this->user);

            $this->fail('AlreadyMemberException was not thrown.');
        } catch (AlreadyMemberException $e) {
            // 想定どおり例外が発生
        }

        $this->assertDatabaseCount('group_user', 2);

        $this->assertDatabaseHas('invitations', [
            'group_id' => $this->group->id,
            'accepted_at' => null,
        ]);
    }
}
