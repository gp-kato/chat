<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Models\User;
use App\Models\Group;

use function Symfony\Component\Clock\now;

class ArchiveTest extends TestCase
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

    private function leftadminGroup(User $user, Group $group): void
    {
        $group->users()->attach($user->id, [
            'joined_at' => '2025-04-07 08:30:17',
            'left_at' => now(),
            'role' => 'admin',
        ]);
    }

    public function test_can_archive_chat_when_admin()
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $response = $this->put(route('groups.archive', $this->group->id));

        $this->assertAuthenticated();
        $response->assertSessionHasNoErrors();
        $response->assertStatus(302);
        $response->assertRedirect(route('groups.index', absolute: false));

        $this->assertDatabaseHas('groups', [
            'id' => $this->group->id,
        ]);

        $this->assertNotNull('groups',
            DB::table('groups')->where('id', $this->group->id)->value('archived_at')
        );
    }

    public function test_cannot_archive_chat_without_admin()
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $response = $this->put(route('groups.archive', $this->group->id));

        $response->assertForbidden();

        $this->assertDatabaseHas('groups', [
            'id' => $this->group->id,
            'archived_at' => null,
        ]);
    }

    public function test_cannot_archive_chat_without_member()
    {
        $this->actingAs($this->user);

        $response = $this->put(route('groups.archive', $this->group->id));

        $response->assertForbidden();

        $this->assertDatabaseHas('groups', [
            'id' => $this->group->id,
            'archived_at' => null,
        ]);
    }

    public function test_cannot_archive_chat_with_other_admin()
    {
        $otherGroup = Group::factory()->create();
        $this->adminGroup($this->user, $otherGroup);
        $this->actingAs($this->user);

        $response = $this->put(route('groups.archive', $this->group->id));

        $response->assertForbidden();

        $this->assertDatabaseHas('groups', [
            'id' => $this->group->id,
            'archived_at' => null,
        ]);
    }

    public function test_cannot_archive_chat_after_left_admin()
    {
        $this->actingAs($this->user);
        $this->leftadminGroup($this->user, $this->group);

        $response = $this->put(route('groups.archive', $this->group->id));

        $response->assertForbidden();

        $this->assertDatabaseHas('groups', [
            'id' => $this->group->id,
            'archived_at' => null,
        ]);
    }
}
