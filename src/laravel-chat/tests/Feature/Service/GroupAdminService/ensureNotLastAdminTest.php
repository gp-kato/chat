<?php

namespace Tests\Feature\Service\GroupAdminService;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Group;
use App\Exceptions\Domain\LastAdminException;

class ensureNotLastAdminTest extends TestCase
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

    public function test_non_admin_is_ignored(): void
    {
        $this->actingAs($this->user);
        $this->joinGroup($this->user, $this->group);

        $service = app(\App\Services\GroupAdminService::class);

        // 例外が出ないことを確認
        $service->ensureNotLastAdmin($this->group, $this->user);

        $this->assertTrue(true); // 到達確認
    }

    public function test_throw_exception_when_last_admin(): void
    {
        $group = $this->group;
        $admin = $this->user;

        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $service = app(\App\Services\GroupAdminService::class);

        $this->expectException(\App\Exceptions\Domain\LastAdminException::class);

        DB::transaction(function () use ($service, $group, $admin) {
            $service->ensureNotLastAdmin($group, $admin);
        });
    }

    public function test_pass_when_multiple_admins(): void
    {
        $group = $this->group;
        $admin1 = $this->user;

        $this->actingAs($this->user);
        $this->adminGroup($this->user, $this->group);

        $anotheradmin = User::factory()->create();
        $this->adminGroup($anotheradmin, $this->group);

        $admin2 = $anotheradmin;

        $service = app(\App\Services\GroupAdminService::class);

        // 例外が出ないこと
        DB::transaction(function () use ($service, $group, $admin1) {
            $service->ensureNotLastAdmin($group, $admin1);
        });

        $this->assertTrue(true);
    }

    public function test_left_admin_is_not_counted(): void
    {
        $this->actingAs($this->user);
        $this->leftadminGroup($this->user, $this->group);

        $activeadmin = User::factory()->create();
        $this->adminGroup($activeadmin, $this->group);

        $service = app(\App\Services\GroupAdminService::class);

        $this->expectException(\App\Exceptions\Domain\LastAdminException::class);

        $service->ensureNotLastAdmin($this->group, $activeadmin);
    }
}
