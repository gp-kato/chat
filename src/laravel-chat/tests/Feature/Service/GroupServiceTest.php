<?php

namespace Tests\Feature\Service;

use App\Models\Group;
use App\Models\Invitation;
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

        $groups = $service->listForUser($this->user);

        $this->assertCount(1, $groups);
        $this->assertSame($this->group->id, $groups->first()->id);
        $this->assertTrue($groups->first()->is_joined);
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

        $member = User::factory()->create();
        $this->group->users()->attach($member->id, [
            'joined_at' => now(),
            'left_at' => null,
            'role' => 'member',
        ]);

        $applicant = User::factory()->create();
        $this->group->users()->attach($applicant->id, [
            'joined_at' => now(),
            'left_at' => null,
            'role' => 'applicant',
        ]);

        $searchableUser = User::factory()->create([
            'name' => 'Searchable User',
            'email' => 'searchable@example.com',
        ]);

        Invitation::create([
            'group_id' => $this->group->id,
            'inviter_id' => $this->user->id,
            'invitee_email' => 'invite@example.com',
            'token' => 'token',
            'expires_at' => now()->addDay(),
        ]);

        $service = app(GroupService::class);

        $result = $service->prepareEditData($this->group, 'searchable');

        $this->assertCount(2, $result['activeUsers']);
        $this->assertTrue($result['activeUsers']->contains(fn ($user) => $user->id === $this->user->id));
        $this->assertTrue($result['activeUsers']->contains(fn ($user) => $user->id === $member->id));
        $this->assertTrue($result['removableUsers']->contains(fn ($user) => $user->id === $member->id));
        $this->assertFalse($result['removableUsers']->contains(fn ($user) => $user->id === $this->user->id));
        $this->assertTrue($result['applicants']->contains(fn ($user) => $user->id === $applicant->id));
        $this->assertCount(1, $result['invitations']);
        $this->assertTrue($result['invitations']->contains(fn ($invitation) => $invitation->invitee_email === 'invite@example.com'));
        $this->assertCount(1, $result['searchResults']);
        $this->assertTrue($result['searchResults']->contains(fn ($user) => $user->id === $searchableUser->id));
    }
}
