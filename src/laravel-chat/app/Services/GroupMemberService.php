<?php

namespace App\Services;

use App\Exceptions\Domain\AlreadyMemberException;
use App\Exceptions\Domain\InvalidInvitationException;
use App\Models\Group;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GroupMemberService
{
    public function __construct(
        private GroupAdminService $adminService
    ) {}

    public function joinByInvitation(?Invitation $invitation, User $user): void
    {
        if (! $invitation) {
            throw new InvalidInvitationException();
        }

        $group = $invitation->group;

        if ($group->isActiveMember($user) || $group->isApplicant($user)) {
            throw new AlreadyMemberException('既にグループに参加しているか、参加申請中です');
        }

        DB::transaction(function () use ($group, $user, $invitation) {
            $invitation->accepted_at = now();
            $invitation->save();

            $group->users()->syncWithoutDetaching([
                $user->id => [
                    'joined_at' => now(),
                    'left_at' => null,
                    'role' => 'member',
                ],
            ]);
        });
    }

    public function apply(Group $group, User $user): void
    {
        if ($group->isActiveMember($user) || $group->isApplicant($user)) {
            throw new AlreadyMemberException('既にグループに参加しているか、参加申請中です');
        }

        DB::transaction(function () use ($group, $user) {
            $group->users()->syncWithoutDetaching([
                $user->id => [
                    'role' => 'applicant',
                    'joined_at' => null,
                    'left_at' => null,
                ],
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

    public function remove(Group $group, User $target): void
    {
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

    public function transferAdmin(Group $group, User $user)
    {
        $group->users()->updateExistingPivot($user->id, [
            'role' => 'admin',
        ]);
    }

    public function demote(Group $group, User $user)
    {
        DB::transaction(function () use ($group, $user) {
            $this->adminService->ensureNotLastAdmin($group, $user);

            $group->users()->updateExistingPivot($user->id, [
                'role' => 'member',
            ]);
        });
    }

    public function cancelApplication(Group $group, User $user)
    {
        if (! $group->isApplicant($user)) {
            throw new \DomainException('グループに参加申請していません');
        }

        $group->users()->detach($user->id);
    }

    public function approveApplicant(Group $group, User $user)
    {
        $group->users()->updateExistingPivot($user->id, [
            'joined_at' => now(),
            'left_at' => null,
            'role' => 'member',
        ]);
    }

    public function reject(Group $group, User $target)
    {
        if (! $group->isApplicant($target)) {
            throw new \DomainException('グループに参加申請していません');
        }

        $group->users()->detach($target->id);
    }
}
