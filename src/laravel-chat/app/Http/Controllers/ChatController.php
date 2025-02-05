<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Group;
use App\Models\Message;

class ChatController extends Controller
{
    public function index() {
        $users = User::all();
        $groups = Group::all();
        return view('group', compact('groups', 'users'));
    }

    public function show($group_id) {
        $groups = Group::all();
        $group = $groups->firstWhere('id', $group_id); // 特定のグループを取得
        $group_id = (int)$group_id;
        $messages = Message::where('group_id', $group_id)->get();
        return view('chat', compact('messages', 'group'));
    }

    public function store(Request $request, Group $group) {
        $request->validate([
            'content' => 'required|string|max:140',
        ]);

        // 新しいメッセージを作成
        $group->messages()->create([
            'user_id' => auth::id(),
            'group_id' => $group->id, // 明示的に group_id を設定
            'content' => $request->content,
        ]);

        return redirect()->route('show', $group->id);
    }
}
