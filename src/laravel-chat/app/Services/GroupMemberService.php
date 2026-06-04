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

    public function leave(Group $group, User $user): void
    {
        DB::transaction(function () use ($group, $user) {

            $this->adminService->ensureNotLastAdmin($group, $user);

            $group->users()->updateExistingPivot($user->id, [
                'left_at' => now(),
            ]);
        });
    }

    public function remove(Group $group, User $target): void{
        if ($group->isAdmin($target)) {
            throw new \DomainException('管理者同士では退会出来ません');
        }

        DB::transaction(function () use ($group, $target) {
            $this->adminService->ensureNotLastAdmin($group, $target);

            $group->users()->updateExistingPivot($target->id, [
                'left_at' => now(),
            ]);
        });
    }

    public function demote(Group $group, User $user) {
        DB::transaction(function () use ($group, $user) {
            $this->adminService->ensureNotLastAdmin($group, $user);

            $group->users()->updateExistingPivot($user->id, [
                'role' => 'member',
            ]);
        });
    }

    public function cancelApplication(Group $group, User $user) {
        if (!$group->isApplicant($user)) {
            throw new \DomainException('グループに参加申請していません');
        }

        $group->users()->detach($user->id);
    }
}
