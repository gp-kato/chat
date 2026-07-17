<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShowGroupRequest;
use App\Http\Requests\UpdateGroupRequest;
use App\Models\Group;
use App\Services\GroupService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, GroupService $service)
    {
        $user = Auth::user();
        $filter = $request->query('filter');

        $groups = $service->listForUser($user, $filter);

        return view('group', compact('groups', 'filter'));
    }

    public function add(Request $request, GroupService $service)
    {
        $data = $request->validate([
            'name' => 'required|string|max:10',
            'description' => 'required|string|max:40',
        ]);

        $user = Auth::user();

        $service->create($data, $user);

        return redirect()->route('groups.index');
    }

    public function archive(Group $group)
    {
        $this->authorize('admin', $group);

        $group->archived_at = now();
        $group->save();

        return redirect()->route('groups.index');
    }

    public function edit(ShowGroupRequest $request, Group $group, GroupService $service)
    {
        $this->authorize('admin', $group);

        $query = $request->validatedQuery();

        $editData = $service->prepareEditData($group, $query);

        return view('edit', [
            'group' => $group,
            'editData' => $editData,
        ]);
    }

    public function update(UpdateGroupRequest $request, Group $group)
    {
        $request->validated();

        $this->authorize('admin', $group);

        $group->fill($request->only(['name', 'description']));

        $group->save();

        return redirect()->route('groups.messages.show', compact('group'))->with('success', 'グループは更新されました');
    }
}
