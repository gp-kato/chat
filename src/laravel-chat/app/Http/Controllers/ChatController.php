<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Group;
use App\Models\Message;

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

        Group::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('index');
    }

    public function show(Group $group) {
        $user = Auth::user();

        if (!$group->isJoinedBy($user)) {
            return redirect()->route('index')->with('error', 'このグループに参加していません');
        }

        $messages = $group->messages()->oldest()->get();
        return view('chat', compact('messages', 'group'));
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
        }
    
        return redirect()->back()->with('success', 'グループから退会しました');
    }
}
