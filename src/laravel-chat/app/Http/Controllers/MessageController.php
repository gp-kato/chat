<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Group;
use App\Models\Invitation;
use App\Models\Message;
use App\Events\MessageEvent;
use App\Http\Requests\ShowGroupRequest;

class MessageController extends Controller
{
    private const FETCH_LIMIT = 50;

    use AuthorizesRequests;

    public function show(ShowGroupRequest $request, Group $group) {
        $this->authorize('view', $group);

        return view('chat', [
            'group'           => $group,
            'messages'        => Message::latestForGroup($group, self::FETCH_LIMIT),
            'isAdmin'         => Gate::allows('admin', $group),
        ]);
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

        $beforeId = $validated['before_id'] ?? null;

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
