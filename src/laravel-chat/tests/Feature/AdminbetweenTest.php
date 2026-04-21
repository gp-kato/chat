<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\Group;

class AdminbetweenTest extends TestCase
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

    public function test_cannot_remove_between_admin(): void
    {
        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $another = User::factory()->create();
        $this->adminGroup($another, $this->group);

        $response = $this->delete(
            route('groups.members.remove', [
                'group' => $this->group->id,
                'user'  => $another->id,
            ])
        );

        $response->assertRedirect();

        $this->assertDatabaseMissing('group_user', [
            'group_id' => $this->group->id,
            'user_id'  => $another->id,
            'left_at'  => now(),
        ]);
    }
}
