<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Group;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
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
            return redirect()->route('groups.index')->with('error', '無効な招待リンクです');
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
        return redirect()->route('groups.index')->with('success', 'グループに参加しました');
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
