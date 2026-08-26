<?php

namespace Tests\Feature\Service\GroupMemberService;

use App\Models\Group;
use App\Models\User;
use App\Services\GroupMemberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class rejectTest extends TestCase
{
    use RefreshDatabase;

    private ?User $user = null;

    private ?Group $group = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(); // 1回だけユーザーを作成
        $this->group = Group::factory()->create(); // 1回だけグループを作成
    }

    private function applicant(User $user, Group $group): void
    {
        $group->users()->attach($user->id, [
            'role' => 'applicant',
        ]);
    }

    private function joinGroup(User $user, Group $group): void
    {
        $group->users()->attach($user->id, [
            'joined_at' => now(),
            'left_at' => null,
        ]);
    }

    public function test_can_reject_applicant(): void
    {
        $this->actingAs($this->user);
        $this->applicant($this->user, $this->group);

        $service = app(GroupMemberService::class);

        $service->reject($this->group, $this->user);

        $this->assertDatabaseCount('group_user', 0);
    }

    public function test_can_reject_non_applicant(): void
    {
        $this->actingAs($this->user);

        $service = app(GroupMemberService::class);

        $this->expectException(\DomainException::class);

        $service->reject($this->group, $this->user);
    }

    public function test_reject_keeps_existing_group_user_record_when_user_is_not_applicant(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $service = app(GroupMemberService::class);

        try {
            $service->reject($this->group, $this->user);

        $this->fail('\DomainException was not thrown.');
        } catch (\DomainException $e) {
            // 想定どおり例外が発生
        }

        $this->assertDatabaseHas('group_user', [
            'group_id' => $this->group->id,
            'user_id' => $this->user->id,
            'role' => 'member',
            'left_at' => null,
        ]);
    }
}
