<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\Group;
use App\Models\User;
use App\Models\Invitation;
use App\Mail\GroupInvitation;

class InvitationController extends Controller
{
    public function invite(Request $request, Group $group) {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->user_id);
        if (!$group->isAdmin(Auth::user())) {
            return redirect()->back()->with('error', '管理者権限が必要です');
        }
        if ($group->isActiveMember($user)) {
            return back()->with('info', "{$user->name}さんは既にこのグループのメンバーです。");
        }
        try {
            $result = DB::transaction(function () use ($group, $user) {
                $existing = Invitation::where('group_id', $group->id)
                    ->where('invitee_email', $user->email)
                    ->where('expires_at', '>', now())
                    ->whereNull('accepted_at')
                    ->lockForUpdate() // 占有ロック
                    ->first();
                if ($existing) {
                    return ['success' => false, 'reason' => 'already_invited'];
                }
                $token = Str::random(32);
                $invitation = Invitation::create([
                    'group_id' => $group->id,
                    'inviter_id' => Auth::id(),
                    'invitee_email' => $user->email,
                    'token' => $token,
                    'expires_at' => now()->addDays(31),
                ]);
                $url = route('groups.invitations.join.token', ['token' => $token, 'group' => $group->id, ]);
                Mail::to($user->email)->send(new GroupInvitation($group, $url));
                return ['success' => true];
            });
            if ($result['success']) {
                return back()->with('success', "{$user->name}さんを招待しました。");
            } else {
                if ($result['reason'] === 'already_invited') {
                    return redirect()->back()->with('error', "{$user->name}さんには既に招待が送られています。");
                }
                return redirect()->back()->with('error', '退会処理に失敗しました');
            }
        } catch (\Exception $e) {
            return back()->with('error', "招待に失敗しました。時間をおいて再試行してください。");
        }
    }

    public function resend(Group $group, Invitation $invitation) {
        if (!$group->isAdmin(Auth::user())) {
            return redirect()->back()->with('error', '管理者権限が必要です');
        }
        if ($invitation->group_id !== $group->id) {
            return back()->with('error', 'この招待はこのグループに属していません');
        }
        if ($invitation->expires_at < now()) {
            return back()->with('error', 'この招待は期限切れです');
        }
        try {
            DB::transaction(function () use ($group, $invitation) {
                $invitation->expires_at = now()->addDays(31);
                $invitation->save();
                $url = route('groups.invitations.join.token', ['token' => $invitation->token,'group' => $group->id,]);
                Mail::to($invitation->invitee_email)->send(new GroupInvitation($group, $url));
            });
            return back()->with('success', "{$invitation->invitee_email} に招待を再送信しました。");
        } catch (\Exception $e) {
            return back()->with('error', "再送に失敗しました。時間をおいて再試行してください。");
        }
    }
}
