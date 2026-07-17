<?php

namespace App\Services;

use App\Events\MessageEvent;
use App\Models\Group;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageService
{
    private const FETCH_LIMIT = 50;

    public function getRecentMessages(Group $group, int $limit)
    {
        $messages = Message::latestForGroup($group, self::FETCH_LIMIT)
            ->get()
            ->sortBy('id')
            ->values();

            return $messages;
    }

    public function post(Group $group, Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:140',
        ]);

        $message = $group->messages()->create([
            'user_id' => Auth::id(),
            'content' => $request->content,
        ]);

        event(new MessageEvent($message));

        return $message;
    }

    public function fetch(Group $group, ?int $beforeId)
    {
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

        return [
            'html' => $html,
            'has_more' => $hasMore,
        ];
    }
}
