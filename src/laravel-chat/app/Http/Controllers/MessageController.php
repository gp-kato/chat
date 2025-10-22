<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Group;
use App\Models\Invitation;
use App\Events\MessageEvent;

class MessageController extends Controller
{
    public function show(Request $request, Group $group) {
        $user = Auth::user();

        if (!$group->isJoinedBy($user)) {
            return redirect()->route('groups.index')->with('error', 'このグループに参加していません');
        }
        $validated = $request->validate([
            'query' => ['nullable', 'string', 'max:100']
        ]);
        $query = $validated['query'] ?? null;
        $messages = $group->messages()->oldest()->get();
        $removableUsers = $group->users()
        ->wherePivot('left_at', null)
        ->where('role', 'member')
        ->withPivot('left_at')
        ->get();
        $users = $group->users()
        ->wherePivot('left_at', null)
        ->withPivot('left_at')
        ->get();
        $isAdmin = $group->isAdmin(Auth::user());
        $invitations = Invitation::where('group_id', $group->id)
            ->where('expires_at', '>', now())
            ->whereNull('accepted_at')
            ->get();
        $searchResults = collect();
        if (!empty($query)) {
            $query = addcslashes($query, '%_\\');
            $joinedUserIds = $group->users()
                ->wherePivot('left_at', null)
                ->pluck('users.id')
                ->toArray();
            $searchResults = User::where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
                })
            ->whereNotIn('id', $joinedUserIds)
            ->get();
        }
        return view('chat', compact('messages', 'group', 'users','removableUsers','isAdmin', 'invitations', 'query', 'searchResults'));
    }

    public function store(Request $request, Group $group) {
        $user = Auth::user();

        if (!$group->isJoinedBy($user)) {
            return redirect()->route('groups.index')->with('error', 'このグループに参加していません');
        }

        $request->validate([
            'content' => 'required|string|max:140',
        ]);

        // 新しいメッセージを作成
        $message = $group->messages()->create([
            'user_id' => Auth::id(),
            'content' => $request->content,
        ]);

        event(new MessageEvent($message));

        return response()->json(['status' => '201']);
    }
}
