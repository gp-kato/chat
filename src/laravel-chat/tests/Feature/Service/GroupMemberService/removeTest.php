<?php

namespace Tests\Feature\Service\GroupMemberService;

use App\Models\Group;
use App\Models\User;
use App\Services\GroupMemberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RemoveTest extends TestCase
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

    public function test_last_admin_cannot_remove(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $service = app(GroupMemberService::class);

        $this->expectException(\DomainException::class);

        $service->remove($this->group, $this->user);
    }

    public function test_admin_can_remove_member(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $target = User::factory()->create();
        $this->joinGroup($target, $this->group);

        $service = app(GroupMemberService::class);

        $service->remove($this->group, $target);

        $this->assertDatabaseHas('group_user', [
            'user_id' => $target->id,
            'left_at' => now(),
        ]);
    }

    public function test_cannot_remove_admin(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $target = User::factory()->create();
        $this->adminGroup($target, $this->group);

        $service = app(GroupMemberService::class);

        $this->expectException(\DomainException::class);

        $service->remove($this->group, $target);
    }
}
