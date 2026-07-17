<?php

namespace App\Services;

use App\Mail\GroupInvitation;
use App\Models\Group;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InvitationService
{
    public function invite(Group $group, User $user)
    {
        if ($group->isActiveMember($user)) {
            return back()->with('info', "{$user->name}さんは既にこのグループのメンバーです。");
        }
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
        $url = route('groups.invitations.join.token', ['token' => $token, 'group' => $group->id]);
        Mail::to($user->email)->send(new GroupInvitation($group, $url));
        return ['success' => true];
    }

    public function resend(Group $group, Invitation $invitation)
    {
        $invitation->expires_at = now()->addDays(31);
        $invitation->save();
        $url = route('groups.invitations.join.token', ['token' => $invitation->token, 'group' => $group->id]);
        Mail::to($invitation->invitee_email)->send(new GroupInvitation($group, $url));
    }
}
