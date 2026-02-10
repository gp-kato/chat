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
    public function index() {
        $user = Auth::user();
        $groups = Group::withExists(['users as is_joined' => function ($query) use ($user) {
            $query->where('users.id', $user->id)
            ->whereNull('left_at')
            ->whereNotNull('joined_at');
        }])->get();
        return view('group', compact('groups'));
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

    use AuthorizesRequests;

    public function edit(ShowGroupRequest $request, Group $group) {
        $this->authorize('admin', $group);

        $query = $request->validatedQuery();

        $activeUsers = $group->activeUsers();

        return view('edit', [
            'group'           => $group,
            'removableUsers'  => $group->removableUsers($activeUsers),
            'invitations'     => Invitation::activeForGroup($group),
            'searchResults'   => $query
            ? User::searchNotJoined($query, $activeUsers->pluck('id'))
            : collect(),
        ]);
    }

    public function update(UpdateGroupRequest $request, Group $group) {
        $request->validated();

        $group->fill($request->only(['name', 'description']));

        $group->save();

        return redirect()->route('groups.messages.show', compact('group'))->with('success', 'グループは更新されました');
    }
}
