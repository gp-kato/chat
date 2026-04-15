<?php

namespace App\Services;

use App\Models\Group;
use App\Models\User;
use App\Exceptions\Domain\LastAdminException;

class GroupAdminService
{
    public function ensureNotLastAdmin(Group $group, User $target): void
    {
        // 対象がadminでなければチェック不要
        if (!$group->isAdmin($target)) {
            return;
        }

        $adminCount = $this->adminCount($group);

        if ($adminCount <= 1) {
            throw new LastAdminException();
        }
    }

    public function adminCount(Group $group): int
    {
        return $group->users()
            ->wherePivot('role', 'admin')
            ->wherePivotNull('left_at')
            ->wherePivotNotNull('joined_at')
            ->lockForUpdate()
            ->count();
    }
}
