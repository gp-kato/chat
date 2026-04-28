<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\Group;
use App\Models\Invitation;

class RejoinTest extends TestCase
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

    private function leftadminGroup(User $user, Group $group): void
    {
        $group->users()->attach($user->id, [
            'joined_at' => '2025-04-07 08:30:17',
            'left_at' => now(),
            'role' => 'admin',
        ]);
    }

    public function test_rejoining_role_is_member(): void
    {
        $this->actingAs($this->user);
        $this->leftadminGroup($this->user, $this->group);

        $inviter = User::factory()->create();
        $token = 'dummyToken123';
        Invitation::create([
            'group_id' => $this->group->id,
            'inviter_id'    => $inviter->id,
            'token' => $token,
            'invitee_email' => $this->user->email,
            'expires_at' => now()->addDay(),
            'accepted_at' => null,
        ]);
        $response = $this->get(route('groups.invitations.join.token', [
            'token' => $token,
            'group' => $this->group->id,
        ]));

        $response->assertRedirect(route('groups.index', absolute: false));
        $this->assertDatabaseHas('group_user', [
            'user_id' => $this->user->id,
            'group_id' => $this->group->id,
            'role' => 'member',
            'left_at' => null,
        ]);

        $this->assertEquals(1, DB::table('group_user')
        ->where('user_id', $this->user->id)
        ->where('group_id', $this->group->id)
        ->count());
    }
}
