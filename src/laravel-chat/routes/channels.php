<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('group.{groupId}', function (User $user, $groupId) {
    $group = Group::find($groupId);

    return $group?->isActiveMember($user) ?? false;
});
