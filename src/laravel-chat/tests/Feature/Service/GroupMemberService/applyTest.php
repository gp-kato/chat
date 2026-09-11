<?php

namespace Tests\Feature\Service\GroupMemberService;

use App\Exceptions\Domain\AlreadyMemberException;
use App\Models\Group;
use App\Models\User;
use App\Services\GroupMemberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class applyTest extends TestCase
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

    public function test_can_subscribe_applicant_when_not_joined(): void
    {
        $this->actingAs($this->user);

        $service = app(GroupMemberService::class);

        $service->apply($this->group, $this->user);

        $this->assertDatabaseHas('group_user', [
            'user_id' => $this->user->id,
            'group_id' => $this->group->id,
            'joined_at' => null,
            'left_at' => null,
            'role' => 'applicant',
        ]);
    }

    public function test_can_subscribe_applicant_when_left(): void
    {
        $this->actingAs($this->user);

        $service = app(GroupMemberService::class);

        $service->apply($this->group, $this->user);

        $this->assertDatabaseHas('group_user', [
            'user_id' => $this->user->id,
            'group_id' => $this->group->id,
            'joined_at' => null,
            'left_at' => null,
            'role' => 'applicant',
        ]);
    }

    public function test_cannot_subscribe_applicant_when_member(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $service = app(GroupMemberService::class);

        try {
            $service->apply($this->group, $this->user);

            $this->fail('AlreadyMemberException was not thrown.');
        } catch (AlreadyMemberException $e) {
            // 想定どおり例外が発生
        }

        $this->assertDatabaseCount('group_user', 1);

        $this->assertDatabaseHas('group_user', [
            'user_id' => $this->user->id,
            'group_id' => $this->group->id,
            'role' => 'member',
            'joined_at' => now(),
            'left_at' => null,
        ]);

        $this->assertDatabaseMissing('group_user', [
            'user_id' => $this->user->id,
            'group_id' => $this->group->id,
            'role' => 'applicant',
        ]);
    }

    public function test_cannot_subscribe_applicant_duplicate(): void
    {
        $this->actingAs($this->user);
        $this->applicant($this->user, $this->group);

        $service = app(GroupMemberService::class);

        try {
            $service->apply($this->group, $this->user);

            $this->fail('AlreadyMemberException was not thrown.');
        } catch (AlreadyMemberException $e) {
            // 想定どおり例外が発生
        }

        $this->assertDatabaseCount('group_user', 1);

        $this->assertDatabaseHas('group_user', [
            'user_id' => $this->user->id,
            'group_id' => $this->group->id,
            'role' => 'applicant',
        ]);
    }
}
