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
                    'left_at' => null,
                    'role' => 'member'
                ]
            ]);
        });
        return redirect()->route('groups.index')->with('success', 'グループに参加しました');
    }

    public function application(Group $group) {
        $user = Auth::user();

        if ($group->isJoinedBy($user)) {
            return redirect()->back()->with('info', '既にグループに参加しています');
        }

        DB::transaction(function () use ($group, $user) {
            $group->users()->syncWithoutDetaching([
                $user->id => [
                    'role' => 'applicant'
                ]
            ]);
        });
        return redirect()->route('groups.index')->with('success', 'グループに参加申請を送りました');
    }

    public function cancelApplication(Group $group, GroupMemberService $service) {
        $service->cancelApplication($group, Auth::user());

        return redirect()->route('groups.index')->with('success', 'グループへの参加申請をキャンセルしました');
    }

    public function leave(Group $group, GroupMemberService $service) {
        $user = Auth::user();
        if (!$group->isActiveMember($user)) {
            return back()->with('info', 'グループに参加していません');
        }
        try {
            $service->leave($group, $user);

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
            DB::transaction(function () use ($group, $user, $service) {
                $service->remove($group, $user);
            });

            return back()->with('success', 'グループから退会させました');
        } catch (\App\Exceptions\Domain\LastAdminException $e) {
            return back()->with('error', '管理者が1人しかいないため、退会できません。');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
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

    public function demote(Group $group, GroupMemberService $service) {
        $this->authorize('admin', $group);
        $user = Auth::user();

        try {
            $service->demote($group, $user);

            return redirect()->route('groups.index')->with('success', '管理者から降格しました');
        } catch (\App\Exceptions\Domain\LastAdminException $e) {
            return redirect()->back()->with('error', '管理者が1人しかいないため、降格できません。');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', '降格処理中にエラーが発生しました');
        }
    }
}
