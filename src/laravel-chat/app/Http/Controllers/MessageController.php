<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Services\MessageService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class MessageController extends Controller
{
    private const FETCH_LIMIT = 50;

    use AuthorizesRequests;

    public function show(Group $group, MessageService $service)
    {
        $this->authorize('view', $group);

        $messages = $service->getRecentMessages($group, self::FETCH_LIMIT);

        return view('chat', [
            'group' => $group,
            'messages' => $messages,
            'isAdmin' => Gate::allows('admin', $group),
        ]);
    }

    public function store(Group $group, MessageService $service)
    {
        $user = Auth::user();

        if (! $group->isActiveMember($user)) {
            return redirect()->route('groups.index')->with('error', 'このグループに参加していません');
        }

        $service->post($group, request());

        return response()->json([
            'message' => 'メッセージを送信しました',
        ], 201);
    }

    public function fetch(Group $group, Request $request, MessageService $service)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'before_id' => 'nullable|numeric',
        ]);

        $beforeId = $validated['before_id'] ?? null;

        if (! $group->isActiveMember($user)) {
            abort(403, 'You are not a member of this group.');
        }

        return response()->json(
            $service->fetch($group, $beforeId)
        );
    }
}
