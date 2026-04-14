<?php

namespace App\Services;

use App\Models\Group;
use App\Models\User;
use App\Services\GroupAdminService;
use Illuminate\Support\Facades\DB;

class GroupMemberService
{
    public function __construct(
        private GroupAdminService $adminService
    ) {}

    public function demote(Group $group, User $actor, User $target): void
    {
        DB::transaction(function () use ($group, $target) {

            $this->adminService->ensureNotLastAdmin($group, $target);

            $group->users()->updateExistingPivot($target->id, [
                'role' => 'member',
            ]);
        });
    }

    public function leave(Group $group, User $user): void
    {
        DB::transaction(function () use ($group, $user) {

            $this->adminService->ensureNotLastAdmin($group, $user);

            $group->users()->updateExistingPivot($user->id, [
                'left_at' => now(),
            ]);
        });
    }
}
