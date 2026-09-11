<?php

namespace Tests;

use App\Models\Group;
use App\Models\GroupUser;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function joinGroup(User $user, Group $group): GroupUser
    {
        return $this->createGroupUser($user, $group, 'member');
    }

    protected function adminGroup(User $user, Group $group): GroupUser
    {
        return $this->createGroupUser($user, $group, 'admin');
    }

    protected function applicant(User $user, Group $group): GroupUser
    {
        return $this->createGroupUser($user, $group, 'applicant');
    }

    protected function leftUser(User $user, Group $group): GroupUser
    {
        return $this->createGroupUser($user, $group, 'leftuser');
    }

    protected function leftadminGroup(User $user, Group $group): GroupUser
    {
        return $this->createGroupUser($user, $group, 'leftadmin');
    }

    private function createGroupUser(User $user, Group $group, string $state): GroupUser
    {
        return GroupUser::factory()->{$state}()->create([
            'group_id' => $group->id,
            'user_id' => $user->id,
        ]);
    }
}
