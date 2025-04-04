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

    public function messages() {
        return $this->hasMany(Message::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'group_user')
        ->withPivot('joined_at', 'left_at', 'role')
         ->withTimestamps();
    }

    public function isJoinedBy($user)
    {
        return $this->users()->where('user_id', $user->id)->exists();
    }
}
