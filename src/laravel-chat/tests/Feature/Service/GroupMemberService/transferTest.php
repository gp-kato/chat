<?php

namespace Tests\Feature\Service\GroupMemberService;

use App\Models\Group;
use App\Models\User;
use App\Services\GroupMemberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class transferTest extends TestCase
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
        ]);
    }

    private function leftGroup(User $user, Group $group): void
    {
        $group->users()->attach($user->id, [
            'joined_at' => '2025-04-07 08:30:17',
            'left_at' => now(),
        ]);
    }

    public function test_can_change_member_role_to_admin(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $service = app(GroupMemberService::class);

        $service->transferAdmin($this->group, $this->user);

        $this->assertDatabaseHas('group_user', [
            'group_id' => $this->group->id,
            'user_id' => $this->user->id,
            'joined_at' => now(),
            'left_at' => null,
            'role' => 'admin',
        ]);
    }

    public function test_cannot_change_left_member_role_to_admin(): void
    {
        $this->actingAs($this->user);
        $this->leftGroup($this->user, $this->group);

        $service = app(GroupMemberService::class);

        $service->transferAdmin($this->group, $this->user);

        $this->assertDatabaseMissing('group_user', [
            'group_id' => $this->group->id,
            'user_id' => $this->user->id,
            'role' => 'admin',
        ]);
    }

    public function test_cannot_change_non_member_role_to_admin(): void
    {
        $this->actingAs($this->user);

        $service = app(GroupMemberService::class);

        $service->transferAdmin($this->group, $this->user);

        $this->assertDatabaseMissing('group_user', [
            'group_id' => $this->group->id,
            'user_id' => $this->user->id,
            'role' => 'admin',
        ]);
    }
}
