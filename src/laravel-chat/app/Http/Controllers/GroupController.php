<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Group;
use App\Http\Requests\UpdateGroupRequest;

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

        return redirect()->route('index');
    }

    public function edit(Group $group) {
        $user = Auth::user();
        $users = $group->users()->where('role', 'member')->get();
        $removableUsers = $group->users()
        ->wherePivot('left_at', null)
        ->where('role', 'member')
        ->withPivot('left_at')
        ->get();
        return view('edit', compact('group', 'users', 'removableUsers'));
    }

    public function update(UpdateGroupRequest $request, Group $group) {
        $request->validated();

        $group->fill($request->only(['name', 'description']));

        $group->save();
    
        return redirect()->route('show', compact('group'))->with('success', 'グループは更新されました');
    }
}
