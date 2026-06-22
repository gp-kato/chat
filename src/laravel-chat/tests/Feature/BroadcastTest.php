<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\Group;
use App\Models\Message;

class BroadcastTest extends TestCase
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

    private function joinGroup(User $user, Group $group): void
    {
        $group->users()->attach($user->id, [
            'joined_at' => now(),
            'left_at' => null,
            'role' => 'member',
        ]);
    }

    private function leftUser(User $user, Group $group): void
    {
        $group->users()->updateExistingPivot($user->id, [
            'left_at' => now(),
        ]);
    }

    private function applicant(User $user, Group $group): void
    {
        $group->users()->attach($user->id, [
            'joined_at' => null,
            'left_at' => null,
            'role' => 'applicant',
        ]);
    }

    public function test_user_can_broadcast_to_group_channel()
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $this->assertTrue(
            $this->group->fresh()->isActiveMember($this->user)
        );
    }

    public function test_user_cannot_broadcast_to_group_channel_if_left()
    {
        $this->actingAs($this->user);
        $this->applicant($this->user, $this->group);

        $this->assertFalse(
            $this->group->fresh()->isActiveMember($this->user)
        );
    }

    public function test_user_cannot_broadcast_to_group_channel_if_applicant()
    {
        $this->actingAs($this->user);
        $this->leftUser($this->user, $this->group);

        $this->assertFalse(
            $this->group->fresh()->isActiveMember($this->user)
        );
    }

    public function test_active_member_can_access_api_and_broadcast()
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $response = $this->getJson(
            route('groups.messages.fetch', $this->group->id)
        );

        $broadcastResponse = $this->post(
            '/broadcasting/auth',
            [
                'channel_name' => "private-group.{$this->group->id}",
                'socket_id' => '1234.5678',
            ]
        );

        $response->assertOk();
        $broadcastResponse->assertOk();
    }

    public function test_left_user_cannot_access_api_and_broadcast()
    {
        $this->actingAs($this->user);
        $this->leftUser($this->user, $this->group);

        $response = $this->getJson(
            route('groups.messages.fetch', $this->group->id)
        );
        $response->assertForbidden();

        $this->assertFalse(
            $this->group->fresh()->isActiveMember($this->user)
        );
    }

    public function test_applicant_cannot_access_api_and_broadcast()
    {
        $this->actingAs($this->user);
        $this->applicant($this->user, $this->group);

        $response = $this->getJson(
            route('groups.messages.fetch', $this->group->id)
        );
        $response->assertForbidden();

        $this->assertFalse(
            $this->group->fresh()->isActiveMember($this->user)
        );
    }
}
