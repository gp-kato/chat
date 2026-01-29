<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    protected $fillable = [
        'group_id',
        'inviter_id',
        'invitee_email',
        'token',
        'accepted_at',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function inviter() {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    public function group() {
        return $this->belongsTo(Group::class);
    }

    public function user() {
        return $this->belongsTo(User::class, 'invitee_email', 'email');
    }

    public function scopeActiveForGroup($query, Group $group) {
        return $query
            ->where('group_id', $group->id)
            ->where('expires_at', '>', now())
            ->whereNull('accepted_at')
        ->get();
    }
}
