<?php

namespace App\Services;

use App\Models\Group;
use App\Models\User;
use App\Models\Invitation;
use Illuminate\Support\Facades\Auth;

class GroupService
{
    public function listForUser(User $user, ?string $filter = null)
    {
        $query = Group::query()
            ->whereNull('archived_at')
            ->withExists([
                'activeUsersQuery as is_joined' => function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                },

                'users as is_applying' => function ($q) use ($user) {
                    $q->where('users.id', $user->id)
                        ->where('role', 'applicant');
                },
            ]);

        if ($filter === 'joined') {
            $query->whereHas('activeUsersQuery', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        if ($filter === 'not_joined') {
            $query->whereDoesntHave('activeUsersQuery', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        return $query->get();
    }

    public function create(array $data, User $user): Group
    {
        $group = Group::create([
            'name' => $data['name'],
            'description' => $data['description'],
        ]);

        $group->users()->attach(Auth::id(), ['role' => 'admin', 'joined_at' => now()]);

        return $group;
    }

    public function prepareEditData(Group $group, ?string $query)
    {
        $activeUsers = $group->activeUsers();

        return [
            'activeUsers' => $activeUsers,
            'removableUsers' => $group->removableUsers($activeUsers),
            'applicants' => $group->applicants(),
            'invitations' => Invitation::activeForGroup($group)->get(),
            'searchResults' => $query
            ? User::searchNotJoined($query, $activeUsers->pluck('id'))->get()
            : collect(),
        ];
    }
}
