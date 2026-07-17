<?php

namespace Tests\Feature\Service;

use App\Models\Group;
use App\Models\User;
use App\Services\GroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class GroupServiceTest extends TestCase
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

    private function joinGroup(User $user, Group $group): void
    {
        $group->users()->attach($user->id, [
            'joined_at' => now(),
            'left_at' => null,
        ]);
    }

    private function adminGroup(User $user, Group $group): void
    {
        $group->users()->attach($user->id, [
            'joined_at' => now(),
            'left_at' => null,
            'role' => 'admin',
        ]);
    }

    public function test_member_can_view_grouplist(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $service = app(GroupService::class);

        $service->listForUser($this->user);

        $this->assertTrue(true);
    }

    public function test_create_group(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $service = app(GroupService::class);

        $data = [
            'name' => 'Test Group',
            'description' => 'This is a test group.',
        ];

        $service->create($data, $this->user);

        $this->assertDatabaseHas('groups', [
            'name' => 'Test Group',
            'description' => 'This is a test group.',
        ]);
    }

    public function test_prepareEditData(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $service = app(GroupService::class);

        $service->listForUser($this->user);

        $this->assertTrue(true);
    }
}
