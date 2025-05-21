<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Group;
use App\Models\Message;
use App\Http\Requests\UpdateGroupRequest;

class ChatController extends Controller
{
    public function index() {
        $groups = Group::all();
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

    public function show(Group $group) {
        $user = Auth::user();

        if (!$group->isJoinedBy($user)) {
            return redirect()->route('index')->with('error', 'このグループに参加していません');
        }

        $messages = $group->messages()->oldest()->get();
        $users = $group->users;
        $group->isAdmin($user);

        return view('chat', compact('messages', 'group', 'users'));
    }
    
    public function store(Request $request, Group $group) {
        $user = Auth::user();

        if (!$group->isJoinedBy($user)) {
            return redirect()->route('index')->with('error', 'このグループに参加していません');
        }

        $request->validate([
            'content' => 'required|string|max:140',
        ]);

        // 新しいメッセージを作成
        $group->messages()->create([
            'user_id' => auth::id(),
            'content' => $request->content,
        ]);

        return redirect()->route('show', $group->id);
    }

    public function join(Group $group) {
        $user = Auth::user();

        if ($group->isJoinedBy($user)) {
            return redirect()->back()->with('info', 'すでにグループに参加しています');
        }
    
        $group->users()->syncWithoutDetaching([
            $user->id => [
                'joined_at' => now(),
                'left_at' => null
            ]
        ]);
    
        return redirect()->back()->with('success', 'グループに参加しました');
    }

    public function leave(Group $group) {
        $user = Auth::user();
    
        if ($group->isActiveMember($user)) {
            $group->users()->updateExistingPivot($user->id, [
                'left_at' => now(),
            ]);

            return redirect()->back()->with('success', 'グループから退会しました');
        }
    
        return redirect()->back()->with('info', 'グループに参加していません');
    }

    public function search(Request $request, Group $group) {
        $query = $request->input('query');
        $users = collect();
        $user = Auth::user();
        $group->isAdmin($user);

        if (!empty($query)) {
            $users = User::where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%");
            })
            ->where('id', '!=', auth()->id())
            ->get();
        }

        return view('chat', [
            'group' => $group,
            'users' => $users,
            'messages' => $group->messages()->oldest()->get(),
        ]);
    }

    public function invite(Request $request) {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->user_id);

        return back()->with('success', "{$user->name}さんを招待しました。");
    }

    public function edit(Group $group) {
        return view('edit', compact('group'));
    }

    public function update(UpdateGroupRequest $request, Group $group) {
        $request->validated();

        $group->fill($request->only(['name', 'description']));

        $group->save();
    
        return redirect()->route('show', compact('group'))->with('success', 'グループは更新されました');
    }
}
