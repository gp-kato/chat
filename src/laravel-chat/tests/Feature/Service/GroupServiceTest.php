<?php

namespace Tests\Feature\Service;

use App\Models\Group;
use App\Models\Invitation;
use App\Models\User;
use App\Services\GroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    private function applicant(User $user, Group $group): void
    {
        $group->users()->attach($user->id, [
            'role' => 'applicant',
        ]);
    }

    public function test_member_can_view_grouplist_when_no_filter(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);
        $notJoinedGroup = Group::factory()->create(['name' => 'Beta Group']);

        $service = app(GroupService::class);

        $groups = $service->listForUser($this->user);

        $this->assertCount(2, $groups);

        $joined = $groups->firstWhere('id', $this->group->id);
        $this->assertNotNull($joined);
        $this->assertTrue($joined->is_joined);

        $notJoined = $groups->firstWhere('id', $notJoinedGroup->id);
        $this->assertNotNull($notJoined);
        $this->assertFalse($notJoined->is_joined);
    }

    public function test_member_can_view_grouplist_filters_by_joined_status(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);
        $notJoinedGroup = Group::factory()->create(['name' => 'Beta Group']);

        $service = app(GroupService::class);

        $groups = $service->listForUser($this->user, 'joined');

        $this->assertCount(1, $groups);
        $this->assertSame($this->group->id, $groups->first()->id);
        $this->assertTrue($groups->first()->is_joined);

        $joined = $groups->firstWhere('id', $this->group->id);
        $this->assertNotNull($joined);
        $this->assertTrue($joined->is_joined);

        $notJoined = $groups->firstWhere('id', $notJoinedGroup->id);
        $this->assertNull($notJoined);
        $this->assertNull($notJoinedGroup->is_joined);
    }

    public function test_member_can_view_grouplist_filters_by_not_joined_status(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);
        $notJoinedGroup = Group::factory()->create(['name' => 'Beta Group']);

        $service = app(GroupService::class);

        $groups = $service->listForUser($this->user, 'not_joined');

        $this->assertCount(1, $groups);

        $joined = $groups->firstWhere('id', $this->group->id);
        $this->assertNull($joined);
        $this->assertNull($this->group->is_joined);

        $notJoined = $groups->firstWhere('id', $notJoinedGroup->id);
        $this->assertNotNull($notJoined);
    }

    public function test_cannot_view_archive_group(): void
    {
        $this->actingAs($this->user);
        $archivedgroup = Group::factory()->create(['archived_at' => now()]);

        $service = app(GroupService::class);

        $groups = $service->listForUser($this->user);

        $this->assertCount(1, $groups);

        $nonarchive = $groups->firstWhere('id', $this->group->id);
        $this->assertNotNull($nonarchive);

        $archived = $groups->firstWhere('id', $archivedgroup->id);
        $this->assertNull($archived);
    }

    public function test_applying_is_set_according_to_application_status(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $applyingGroup = Group::factory()->create(['name' => 'Applying Group']);
        $applyingGroup->users()->attach($this->user->id, [
            'joined_at' => now(),
            'left_at' => null,
            'role' => 'applicant',
        ]);

        $service = app(GroupService::class);

        $groups = $service->listForUser($this->user);

        $joinedGroup = $groups->firstWhere('id', $this->group->id);
        $this->assertNotNull($joinedGroup);
        $this->assertFalse($joinedGroup->is_applying);

        $applyingGroupFromList = $groups->firstWhere('id', $applyingGroup->id);
        $this->assertNotNull($applyingGroupFromList);
        $this->assertTrue($applyingGroupFromList->is_applying);
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

        $this->assertDatabaseHas('group_user', [
            'user_id' => $this->user->id,
            'role' => 'admin',
            'joined_at' => now(),
            'left_at' => null,
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

    public function test_admin_is_excluded_from_removableusers(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $anotheradmin = User::factory()->create();
        $this->adminGroup($anotheradmin, $this->group);

        $service = app(GroupService::class);

        $result = $service->prepareEditData($this->group, 'searchable');

        $this->assertCount(2, $result['activeUsers']);
        $this->assertTrue($result['activeUsers']->contains(fn ($user) => $user->id === $this->user->id));
        $this->assertFalse($result['removableUsers']->contains(fn ($user) => $user->id === $anotheradmin->id));
    }

    public function test_cannot_prepare_expired_invite(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        Invitation::create([
            'group_id' => $this->group->id,
            'inviter_id' => $this->user->id,
            'invitee_email' => 'invite@example.com',
            'token' => 'token',
            'expires_at' => now()->subDay(),
        ]);

        $service = app(GroupService::class);

        $result = $service->prepareEditData($this->group, 'searchable');

        $this->assertCount(0, $result['invitations']);
        $this->assertFalse($result['invitations']->contains(fn ($invitation) => $invitation->invitee_email === 'invite@example.com'));
    }

    public function test_cannot_prepare_approved_invite(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        Invitation::create([
            'group_id' => $this->group->id,
            'inviter_id' => $this->user->id,
            'invitee_email' => 'invite@example.com',
            'token' => 'token',
            'accepted_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        $service = app(GroupService::class);

        $result = $service->prepareEditData($this->group, 'searchable');

        $this->assertCount(0, $result['invitations']);
        $this->assertFalse($result['invitations']->contains(fn ($invitation) => $invitation->invitee_email === 'invite@example.com'));
    }

    public function test_can_get_non_member_that_matches_the_search(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $searchableUser = User::factory()->create([
            'name' => 'Searchable User',
            'email' => 'searchable@example.com',
        ]);
        $this->group->users()->attach($searchableUser->id, [
            'role' => 'null',
        ]);

        $service = app(GroupService::class);

        $result = $service->prepareEditData($this->group, 'searchable');

        $this->assertCount(1, $result['searchResults']);
        $this->assertTrue($result['searchResults']->contains(fn ($user) => $user->id === $searchableUser->id));
    }

    public function test_cannot_get_member_that_matches_the_search(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $searchableUser = User::factory()->create([
            'name' => 'Searchable User',
            'email' => 'searchable@example.com',
        ]);
        $this->joinGroup($searchableUser, $this->group);

        $service = app(GroupService::class);

        $result = $service->prepareEditData($this->group, 'searchable');

        $this->assertCount(0, $result['searchResults']);
        $this->assertFalse($result['searchResults']->contains(fn ($user) => $user->id === $searchableUser->id));
    }

    public function test_no_criteria_results_will_be_empty(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $service = app(GroupService::class);

        $result = $service->prepareEditData($this->group, null);

        $this->assertCount(0, $result['searchResults']);
        $this->assertFalse($result['searchResults']->contains(fn ($user) => $user->id === $this->user->id));
    }
}
