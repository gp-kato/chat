<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Group;
use App\Models\Message;
use App\Models\Invitation;
use App\Http\Requests\UpdateGroupRequest;
use App\Mail\GroupInvitation;

class ChatController extends Controller
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

    public function show(Group $group) {
        $user = Auth::user();

        if (!$group->isJoinedBy($user)) {
            return redirect()->route('index')->with('error', 'このグループに参加していません');
        }

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
        return view('chat', compact('messages', 'group', 'users','removableUsers','isAdmin', 'invitations'));
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

    public function join($token, $groupId) {
        $group = Group::findOrFail($groupId);
        $user = Auth::user();

        if ($group->isJoinedBy($user)) {
            return redirect()->back()->with('info', 'すでにグループに参加しています');
        }
        $invitation = $group->invitations()
            ->where('token', $token)
            ->where('invitee_email', $user->email)
            ->where('expires_at', '>', now())
            ->whereNull('accepted_at')
            ->first();
        if (!$invitation) {
            return redirect()->route('index')->with('error', '無効な招待リンクです');
        }
        DB::transaction(function () use ($group, $user, $invitation) {
            $invitation->accepted_at = now();
            $invitation->save();
            $group->users()->syncWithoutDetaching([
                $user->id => [
                    'joined_at' => now(),
                    'left_at' => null
                ]
            ]);
        });
        return redirect()->route('index')->with('success', 'グループに参加しました');
    }

    public function leave(Group $group) {
        $user = Auth::user();
        if (!$group->isActiveMember($user)) {
            return redirect()->back()->with('info', 'グループに参加していません');
        }
        try {
            $result = DB::transaction(function () use ($group, $user) {
                if ($group->isAdmin($user)) {
                    $adminCount = $group->users()
                        ->wherePivot('left_at', null)
                        ->wherePivot('role', 'admin')
                        ->wherePivotNotNull('joined_at') // 参加済みの確認
                        ->lockForUpdate() // 占有ロック
                        ->count();
                    if ($adminCount <= 1) {
                        return ['success' => false, 'reason' => 'last_admin'];
                    }
                }
                // 退会処理
                $group->users()->updateExistingPivot($user->id, [
                    'left_at' => now(),
                ]);
                return ['success' => true];
            });
            // トランザクション完了後に結果のHTTPレスポンスを返す
            if ($result['success']) {
                return redirect()->back()->with('success', 'グループから退会しました');
            } else {
                if ($result['reason'] === 'last_admin') {
                    return redirect()->back()->with('error', '管理者が1人しかいないため、退会できません。');
                }
                return redirect()->back()->with('error', '退会処理に失敗しました');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', '退会処理中にエラーが発生しました');
        }
    }

    public function search(Request $request, Group $group) {
        $query = $request->input('query');
        $users = collect();
        $user = Auth::user();
        $isAdmin = $group->isAdmin(Auth::user());
        $removableUsers = $group->users()
        ->wherePivot('left_at', null)
        ->where('role', 'member')
        ->withPivot('left_at')
        ->get();
        $joinedUserIds = $group->users()
        ->wherePivot('left_at', null) // 参加中のユーザーだけを取得
        ->pluck('users.id')
        ->toArray();
        if (!empty($query)) {
            $users = User::where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%");
            })
            ->whereNotIn('id', $joinedUserIds)
            ->get();
        }
        $invitations = Invitation::where('group_id', $group->id)
            ->where('expires_at', '>', now())
            ->whereNull('accepted_at')
            ->get();
        return view('chat', [
            'group' => $group,
            'users' => $users,
            'removableUsers' => $removableUsers,
            'isAdmin' => $isAdmin,
            'invitations' => $invitations,
            'messages' => $group->messages()->oldest()->get(),
        ]);
    }

    public function invite(Request $request, Group $group) {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->user_id);
        if (!$group->isAdmin(Auth::user())) {
            return redirect()->back()->with('error', '管理者権限が必要です');
        }
        $existing = Invitation::where('group_id', $group->id)
            ->where('invitee_email', $user->email)
            ->where('expires_at', '>', now())
            ->first();
        if ($existing) {
            return back()->with('info', "{$user->name}さんには既に招待が送られています。");
        }
        $token = Str::random(32);
        $invitation = Invitation::create([
            'group_id' => $group->id,
            'inviter_id' => auth()->id(),
            'invitee_email' => $user->email,
            'token' => $token,
            'expires_at' => now()->addDays(31),
        ]);
        $url = route('join.token', ['token' => $token, 'group' => $group->id, ]);
        Mail::to($user->email)->send(new GroupInvitation($group, $url));
        return back()->with('success', "{$user->name}さんを招待しました。");
    }

    public function resend(Request $request, Group $group, Invitation $invitation) {
        if (!$group->isAdmin(Auth::user())) {
            return redirect()->back()->with('error', '管理者権限が必要です');
        }
        $existing = Invitation::where('group_id', $group->id)
            ->where('invitee_email', $user->email)
            ->where('expires_at', '>', now())
            ->first();
        if ($existing) {
            $invitation->expires_at = now()->addDays(31);
            $invitation->save();            $url = route('join.token', ['token' => $existing->token, 'group' => $group->id]);
            Mail::to($user->email)->send(new GroupInvitation($group, $url));
            return back()->with('success', "{$user->name}さんに再送信しました。");
        }
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

    public function remove(Group $group, User $user) {
        if (!$group->isActiveMember($user)) {
            return redirect()->back()->with('error', 'このユーザーは既に退会済みです');
        }
        $group->users()->updateExistingPivot($user->id, [
            'left_at' => now(),
        ]);
        return redirect()->back()->with('success', 'グループから退会させました');
    }
}
