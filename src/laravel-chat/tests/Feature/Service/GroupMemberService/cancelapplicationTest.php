<?php

namespace Tests\Feature\Service\GroupMemberService;


use App\Models\Group;
use App\Models\User;
use App\Services\GroupMemberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class cancelapplicationTest extends TestCase
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

    public function test_can_cancel_application_when_applicant(): void
    {
        $this->actingAs($this->user);
        $this->applicant($this->user, $this->group);

        $service = app(GroupMemberService::class);

        $service->cancelApplication($this->group, $this->user);

        $this->assertDatabaseCount('group_user', 0);
    }

    public function test_cancel_application_does_not_modify_group_user_when_user_is_not_applicant(): void
    {
        $this->actingAs($this->user);

        $this->assertDatabaseMissing('group_user', [
            'group_id' => $this->group->id,
            'user_id' => $this->user->id,
        ]);

        $service = app(GroupMemberService::class);

        try {
            $service->cancelApplication($this->group, $this->user);

            $this->fail('\DomainException was not thrown.');
        } catch (\DomainException $e) {
            // 想定どおり例外が発生
        }

        $this->assertDatabaseMissing('group_user', [
            'group_id' => $this->group->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_cancel_application_keeps_existing_group_user_record_when_user_is_not_applicant(): void
    {
        $this->actingAs($this->user);

        $this->group->users()->attach($this->user->id, [
            'role' => 'member',
            'joined_at' => now(),
            'left_at' => null,
        ]);

        $this->assertDatabaseHas('group_user', [
            'group_id' => $this->group->id,
            'user_id' => $this->user->id,
            'role' => 'member',
        ]);

        $service = app(GroupMemberService::class);

        try {
            $service->cancelApplication($this->group, $this->user);

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
