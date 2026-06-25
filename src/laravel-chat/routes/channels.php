<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;
use App\Models\Group;

Broadcast::channel('group.{groupId}', function (User $user, $groupId) {
    $group = Group::find($groupId);
    return $group?->isActiveMember($user) ?? false;
});
