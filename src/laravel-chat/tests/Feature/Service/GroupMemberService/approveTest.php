<?php

namespace Tests\Feature\Service\GroupMemberService;

use App\Models\Group;
use App\Models\User;
use App\Services\GroupMemberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class approveTest extends TestCase
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

    private function applicant(User $user, Group $group): void
    {
        $group->users()->attach($user->id, [
            'role' => 'applicant',
        ]);
    }

    public function test_approve_member_applicant(): void
    {
        $this->actingAs($this->user);
        $this->applicant($this->user, $this->group);

        $service = app(GroupMemberService::class);

        $service->approveApplicant($this->group, $this->user);

        $this->assertDatabaseHas('group_user', [
            'group_id' => $this->group->id,
            'user_id' => $this->user->id,
            'joined_at' => now(),
            'left_at' => null,
            'role' => 'member',
        ]);
    }

    public function test_cannot_approve_non_applicant(): void
    {
        $this->actingAs($this->user);

        $service = app(GroupMemberService::class);

        $service->approveApplicant($this->group, $this->user);

        $this->assertDatabaseMissing('group_user', [
            'group_id' => $this->group->id,
            'user_id' => $this->user->id,
            'joined_at' => now(),
            'left_at' => null,
            'role' => 'member',
        ]);
    }
}
