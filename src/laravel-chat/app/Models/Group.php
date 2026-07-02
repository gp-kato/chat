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

    public function activeUsersQuery()
    {
        return $this->users()
            ->wherePivot('role', '!=', 'applicant')
            ->wherePivotNull('left_at')
            ->wherePivotNotNull('joined_at');
    }

    private function activeMemberQuery(User $user)
    {
        return $this->activeUsersQuery()
            ->where('users.id', $user->id);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'group_user')
            ->withPivot('joined_at', 'left_at', 'role')
            ->withTimestamps();
    }

    public function invitations()
    {
        return $this->hasMany(Invitation::class);
    }

    public function isActiveMember(User $user)
    {
        return $this->activeMemberQuery($user)->exists();
    }

    public function isAdmin(User $user)
    {
        return $this->activeMemberQuery($user)
            ->wherePivot('role', 'admin')
            ->exists();
    }

    public function activeUsers()
    {
        return $this->activeUsersQuery()->get();
    }

    public function removableUsers($activeUsers)
    {
        return $activeUsers->filter(
            fn ($user) => $user->pivot->role === 'member'
        )->values();
    }

    public function isApplicant(User $user): bool
    {
        return $this->users()
            ->where('users.id', $user->id)
            ->wherePivot('role', 'applicant')
            ->exists();
    }

    public function applicants()
    {
        return $this->users()
            ->wherePivot('role', 'applicant')
            ->get();
    }
}
