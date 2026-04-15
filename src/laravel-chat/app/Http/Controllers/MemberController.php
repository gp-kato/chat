<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\User;
use App\Models\Group;
use App\Services\GroupMemberService;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
    use AuthorizesRequests;

    public function join($groupId, $token) {
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

    public function leave(Group $group, GroupMemberService $service) {
        $user = Auth::user();
        if (!$group->isActiveMember($user)) {
            return back()->with('info', 'グループに参加していません');
        }
        try {
            $service->remove($group, $user);

            return back()->with('success', 'グループから退会しました');

        } catch (\App\Exceptions\Domain\LastAdminException $e) {
            return back()->with('error', '管理者が1人しかいないため、退会できません。');

        } catch (\Throwable $e) {
            return back()->with('error', '退会処理中にエラーが発生しました');
        }
    }

    public function remove(Group $group, User $user, GroupMemberService $service) {
        if (!$group->isActiveMember($user)) {
            return redirect()->back()->with('error', 'このユーザーは既に退会済みです');
        }
        $this->authorize('admin', $group);
        try {
            $service->leave($group, $user);

            return back()->with('success', 'グループから退会させました');
        } catch (\App\Exceptions\Domain\LastAdminException $e) {
            return back()->with('error', '管理者が1人しかいないため、退会できません。');
        } catch (\Throwable $e) {
            return back()->with('error', '退会処理中にエラーが発生しました');
        }
    }

    public function transfer(Group $group, User $user) {
        $this->authorize('admin', $group);
        $group->users()->updateExistingPivot($user->id, [
            'role' => 'admin',
        ]);
        return redirect()->back()->with('success', '管理権を与えました');
    }
}
