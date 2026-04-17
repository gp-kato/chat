<?php

namespace Tests\Feature\Service\GroupAdminService;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\Group;
use App\Services\GroupMemberService;
use App\Exceptions\Domain\LastAdminException;

class ensureNotLastAdminTest extends TestCase
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

    public function test_last_admin_cannot_leave(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $service = app(GroupMemberService::class);

        $this->expectException(LastAdminException::class);

        $service->leave($this->group, $this->user);
    }

    public function test_admin_can_leave_if_multiple_admins(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $anotheradmin = User::factory()->create();
        $this->adminGroup($anotheradmin, $this->group);

        $service = app(GroupMemberService::class);

        $service->leave($this->group, $this->user);

        $this->assertDatabaseHas('group_user', [
            'user_id' => $this->user->id,
            'left_at' => now(),
        ]);
    }
}
