<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\User;
use App\Models\Group;
use App\Models\Invitation;
use App\Http\Requests\UpdateGroupRequest;
use App\Http\Requests\ShowGroupRequest;

class GroupController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request) {
        $user = Auth::user();
        $filter = $request->query('filter');

        $query = Group::query();

        if ($filter === 'joined') {
            $query->whereHas('users', function ($q) use ($user) {
                $q->where('users.id', $user->id)
                    ->whereNull('left_at')
                    ->whereNotNull('joined_at');
            });
        }

        if ($filter === 'not_joined') {
            $query->whereDoesntHave('users', function ($q) use ($user) {
                $q->where('users.id', $user->id)
                    ->whereNull('left_at')
                    ->whereNotNull('joined_at');
            });
        }

        $groups = $query->withExists(['users as is_joined' => function ($query) use ($user) {
            $query->where('users.id', $user->id)
            ->whereNull('left_at')
            ->whereNotNull('joined_at');
        }])->get();
        return view('group', compact('groups', 'filter'));
    }

    public function add(Request $request) {
        $request->validate([
            'name' => 'required|string|max:10',
            'description' => 'required|string|max:40',
        ]);

        $group = Group::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        $group->users()->attach(Auth::id(), ['role' => 'admin', 'joined_at' => now()]);

        return redirect()->route('groups.index');
    }

    public function edit(ShowGroupRequest $request, Group $group) {
        $this->authorize('admin', $group);

        $query = $request->validatedQuery();

        $activeUsers = $group->activeUsers();

        return view('edit', [
            'group'           => $group,
            'removableUsers'  => $group->removableUsers($activeUsers),
            'invitations'     => Invitation::activeForGroup($group)->get(),
            'searchResults'   => $query
            ? User::searchNotJoined($query, $activeUsers->pluck('id'))->get()
            : collect(),
        ]);
    }

    public function update(UpdateGroupRequest $request, Group $group) {
        $request->validated();

        $this->authorize('admin', $group);

        $group->fill($request->only(['name', 'description']));

        $group->save();

        return redirect()->route('groups.messages.show', compact('group'))->with('success', 'グループは更新されました');
    }
}
