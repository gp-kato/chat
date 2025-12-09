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
    private const FETCH_LIMIT = 50;

    public function show(Request $request, Group $group) {
        $user = Auth::user();

        if (!$group->isJoinedBy($user)) {
            return redirect()->route('groups.index')->with('error', 'このグループに参加していません');
        }
        $validated = $request->validate([
            'query' => ['nullable', 'string', 'max:100']
        ]);
        $query = $validated['query'] ?? null;
        $messages = $group->messages()
            ->with('user')
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get()
            ->sortBy('id')
        ->values();
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

        return response()->json([
            'message' => 'メッセージを送信しました'
        ], 201);
    }

    public function fetch(Request $request, Group $group) {
        $user = Auth::user();

        $validated = $request->validate([
            'before_id' => 'nullable|numeric',
        ]);

        $beforeId = $validated['before_id'];

        if (!$group->isJoinedBy($user)) {
            abort(403, 'You are not a member of this group.');
        }

        $query = $group->messages()
            ->with('user')
            ->orderBy('id', 'desc')
        ->limit(self::FETCH_LIMIT + 1);

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        $messages = $query->get();

        if ($messages->isNotEmpty()) {
            $oldestId = $messages->last()->id;
        }

        $hasMore = $messages->count() > self::FETCH_LIMIT;

        if ($hasMore) {
            // 余分な1件を削除（表示は50件のみ）
            $messages = $messages->slice(0, self::FETCH_LIMIT);
        }

        $messages = $messages->sortBy('id')->values();

        $html = '';

        foreach ($messages as $message) {
            $html .= view('partials.message', ['message' => $message])->render();
        }

        return response()->json([
            'html' => $html,
            'has_more' => $hasMore,
        ]);
    }
}
