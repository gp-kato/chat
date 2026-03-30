<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    private function activeMemberQuery(User $user) {
        return $this->users()
        ->where('user_id', $user->id)
        ->wherePivotNull('left_at');
    }

    public function messages() {
        return $this->hasMany(Message::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'group_user')
        ->withPivot('joined_at', 'left_at', 'role')
         ->withTimestamps();
    }

    public function invitations() {
        return $this->hasMany(Invitation::class);
    }

    public function isJoinedBy(User $user)
    {
        return $this->activeMemberQuery($user)
        ->wherePivotNotNull('joined_at')
        ->exists();
    }

    public function isActiveMember(User $user)
    {
        return $this->activeMemberQuery($user)->exists();
    }

    public function isAdmin(User $user) {
        return $this->activeMemberQuery($user)
        ->wherePivot('role', 'admin')
        ->wherePivotNotNull('joined_at')
        ->exists();
    }

    public function activeUsers() {
        return $this->users()->wherePivot('left_at', null)->get();
    }

    public function removableUsers($activeUsers) {
        return $activeUsers->filter(
            fn ($user) => $user->pivot->role === 'member'
        )->values();
    }
}
