<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EditTest extends TestCase
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

    public function test_edit_screen_can_be_rendered_when_admin(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $response = $this->get(route('groups.edit', $this->group->id));

        $response->assertStatus(200);
    }

    public function test_edit_screen_can_be_rendered_with_query_param(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $searchUser = User::factory()->create([
            'name' => 'Alice Example',
            'email' => 'alice@example.com',
        ]);

        $response = $this->get(route('groups.edit', [
            'group' => $this->group->id,
            'query' => 'alice',
        ]));

        $response->assertStatus(200);
        $response->assertSee('value="alice"', false);
        $response->assertSee('Alice Example');
        $response->assertSee($searchUser->email);
    }

    public function test_edit_screen_can_be_rendered_with_query_param_no_results(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        // グループに他のユーザーを参加させ、キーワードが存在しないクエリを使用
        $groupMember = User::factory()->create([
            'name' => 'Bob Member',
            'email' => 'bob@example.com',
        ]);
        $this->joinGroup($groupMember, $this->group);

        $response = $this->get(route('groups.edit', [
            'group' => $this->group->id,
            'query' => 'nonexistent',
        ]));

        $response->assertStatus(200);
        $response->assertSee('value="nonexistent"', false);
        $response->assertSee('検索結果が見つかりませんでした。');
    }

    public function test_edit_screen_cannot_be_rendered_without_admin(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $response = $this->get(route('groups.edit', $this->group->id));

        $response->assertForbidden();
    }

    public function test_edit_screen_cannot_be_rendered_without_member(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('groups.edit', $this->group->id));

        $response->assertForbidden();
    }

    public function test_edit_screen_cannot_be_rendered_with_other_admin(): void
    {
        $otherGroup = Group::factory()->create();
        $this->adminGroup($this->user, $otherGroup);
        $this->actingAs($this->user);

        $response = $this->get(route('groups.edit', $this->group->id));

        $response->assertForbidden();
    }

    public function test_edit_screen_cannot_be_rendered_after_left_admin(): void
    {
        $this->actingAs($this->user);
        $this->leftadminGroup($this->user, $this->group);

        $response = $this->get(route('groups.edit', $this->group->id));

        $response->assertForbidden();
    }
}
