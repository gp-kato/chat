<?php

namespace App\Http\Controllers;

use App\Mail\GroupInvitation;
use App\Models\Group;
use App\Models\Invitation;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class InvitationController extends Controller
{
    use AuthorizesRequests;

    public function invite(Request $request, Group $group, InvitationService $service)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->user_id);

        $this->authorize('admin', $group);

        try {
            $result = DB::transaction(function () use ($group, $user, $service) {
                $service->invite($group, $user);

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
            return back()->with('error', '招待に失敗しました。時間をおいて再試行してください。');
        }
    }

    public function resend(Group $group, Invitation $invitation, InvitationService $service)
    {
        $this->authorize('admin', $group);
        if ($invitation->group_id !== $group->id) {
            return back()->with('error', 'この招待はこのグループに属していません');
        }
        if ($invitation->expires_at < now()) {
            return back()->with('error', 'この招待は期限切れです');
        }
        try {
            DB::transaction(function () use ($group, $invitation, $service) {
                $service->resend($group, $invitation);
            });

            return back()->with('success', "{$invitation->invitee_email} に招待を再送信しました。");
        } catch (\Exception $e) {
            return back()->with('error', '再送に失敗しました。時間をおいて再試行してください。');
        }
    }
}
