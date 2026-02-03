<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'group_id',
        'content',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function group() {
        return $this->belongsTo(Group::class);
    }

    public function scopeLatestForGroup($query, Group $group, int $limit) {
        return $query
            ->where('group_id', $group->id)
            ->with('user')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->sortBy('id')
        ->values();
    }
}
