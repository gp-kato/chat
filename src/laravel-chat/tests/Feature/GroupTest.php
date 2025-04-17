<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Carbon;

class GroupTest extends TestCase
{
    use RefreshDatabase;

    private ?User $user = null;
    private ?Group $group = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(); // すべてのテストで使うユーザーを作成
        $this->group = Group::factory()->create(); // 1回だけグループを作成
        Carbon::setTestNow('2025-04-15 19:00:00');
    }

    public function test_group_screen_can_be_rendered(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('index'));

        $response->assertStatus(200);
    }

    public function test_adding_chatgroup(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('add'), [
            'name' => 'name',
            'description' => 'description',
        ]);

        $this->assertAuthenticated();
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('index', absolute: false));

        $this->assertDatabaseHas('groups', [
            'name' => 'name',
            'description' => 'description',
        ]);
    }

    public function test_group_screen_can_not_rendered_without_login(): void
    {
        $response = $this->get(route('index'));

        $response->assertRedirect(route('login'));
    }

    public function test_can_not_adding_chat_without_login(): void
    {
        $formData = [
            'name' => 'name',
            'description' => 'description',
        ];
    
        $response = $this->post(route('add'), $formData);
    
        $response->assertRedirect(route('login'));
    
        // フォームの内容に一致するデータがDBに存在しないことを確認
        $this->assertDatabaseMissing('groups', $formData);
    }

    public function test_group_adding_fails_when_name_is_missing(): void
    {
        $this->actingAs($this->user);

        $formData = [
            'name' => '',
            'description' => 'description',
        ];

        $response = $this->post(route('add'), $formData);

        $response->assertInvalid(['name']);
        $response->assertValid(['description']);

        $this->assertDatabaseMissing('groups', ['description' => 'description']);
    }

    public function test_group_adding_fails_when_description_is_missing(): void
    {
        $this->actingAs($this->user);

        $formData = [
            'name' => 'name',
            'description' => '',
        ];

        $response = $this->post(route('add'), $formData);

        $response->assertvalid(['name']);
        $response->assertInValid(['description']);

        $this->assertDatabaseMissing('groups', ['name' => 'name']);
    }

    public function test_group_adding_fails_when_name_exceeds_max_length(): void
    {
        $this->actingAs($this->user);

        $formData = [
            'name' => str_repeat('a', 11),
            'description' => 'description',
        ];

        $response = $this->post(route('add'), $formData);

        $response->assertInvalid(['name']);
        $response->assertValid(['description']);

        $this->assertDatabaseMissing('groups', ['description' => 'description']);
    }

    public function test_group_adding_fails_when_description_exceeds_max_length(): void
    {
        $this->actingAs($this->user);

        $formData = [
            'name' => 'Valid Name',
            'description' => str_repeat('a', 41),
        ];

        $response = $this->post(route('add'), $formData);

        $response->assertvalid(['name']);
        $response->assertInValid(['description']);

        $this->assertDatabaseMissing('groups', ['name' => 'name']);
    }

    public function test_group_adding_succeeds_with_max_length(): void
    {
        $this->actingAs($this->user);
    
        $validName = str_repeat('a', 10);
        $validDescription = str_repeat('b', 40);
    
        $response = $this->post(route('add'), [
            'name' => $validName,
            'description' => $validDescription,
        ]);
    
        $this->assertAuthenticated();
        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('index', absolute: false));
    
        $this->assertDatabaseHas('groups', [
            'name' => $validName,
            'description' => $validDescription,
        ]);
    }

    public function test_join_chatgroup(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('join', $this->group->id));

        $response->assertRedirect(route('index', absolute: false));
        $this->assertDatabaseHas('group_user', [
            'user_id' => $this->user->id,
            'group_id' => $this->group->id,
        ]);
    }

    public function test_can_not_rejoin_chatgroup_with_joined_group(): void
    {
        $this->actingAs($this->user);
        $this->group->users()->attach($this->user->id);

        $response = $this->post(route('join', $this->group->id));

        $response->assertRedirect(route('index', absolute: false));
        $this->assertDatabaseHas('group_user', [
            'user_id' => $this->user->id,
            'group_id' => $this->group->id,
        ]);

        $this->assertEquals(1, DB::table('group_user')
        ->where('user_id', $this->user->id)
        ->where('group_id', $this->group->id)
        ->count());
    }

    public function test_can_leave_chatgroup(): void
    {
        $this->actingAs($this->user);
        $this->group->users()->attach($this->user->id);

        $response = $this->delete(route('leave', $this->group->id));

        $response->assertRedirect(route('index', absolute: false));
        $this->assertDatabaseHas('group_user',[
            'user_id' => $this->user->id,
            'group_id' => $this->group->id,
            'left_at' => now(),
        ]);
    }

    public function test_can_leave_chatgroup_with_not_join(): void
    {
        $this->actingAs($this->user);

        $response = $this->delete(route('leave', $this->group->id));

        $response->assertRedirect(route('index', absolute: false));
        $response->assertSessionHas('info', 'グループに参加していません');
    }

    public function test_can_rejoin_with_left(): void
    {
        $this->actingAs($this->user);
        $this->group->users()->syncWithoutDetaching($this->user->id, [
            'joined_at' => '2025-04-07 08:30:17',
            'left_at' => null,
        ]);

        $response = $this->post(route('join', $this->group->id));

        $response->assertRedirect(route('index', absolute: false));
        $this->assertDatabaseHas('group_user', [
            'user_id' => $this->user->id,
            'group_id' => $this->group->id,
            'joined_at' => now(),
            'left_at' => null,
        ]);

        $this->assertEquals(1, DB::table('group_user')
        ->where('user_id', $this->user->id)
        ->where('group_id', $this->group->id)
        ->count());
    }
}
