<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Group;
use App\Models\User;

class AdminController extends Controller
{
    public function admin() {}

    public function index(Request $request) {
        $query = User::query();
    
        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%");
            });
        }
    
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'joined') {
                $query->whereHas('groups', fn($q) => $q->whereNull('left_at'));
            } elseif ($status === 'left') {
                $query->whereHas('groups', fn($q) => $q->whereNotNull('left_at'));
            }
        }
    
        if ($request->filled('sort')) {
            $sort = $request->input('sort');
            $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';
        
            if (in_array($sort, ['name', 'email'])) {
                $query->orderBy($sort, $direction);
            }
        }

        $users = $query->get();
        $groups = Group::all();
    
        return view('admin', ['users' => $users, 'groups' => $groups]);
    }

    public function invite(Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'group_id' => 'required|exists:groups,id',
        ]);

        $group = Group::findOrFail($request->group_id);
        $user = User::where('email', $request->email)->first();
    
        $group->users()->syncWithoutDetaching([
            $user->id => [
                'joined_at' => now(),
                'left_at' => null
            ]
        ]);
    
        return redirect()->route('index')->with('success', "招待をしました");
    }
}
