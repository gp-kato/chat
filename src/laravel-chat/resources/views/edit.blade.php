@extends('layout')

@section('title', $group->name . ' - EditingGroup')

@section('content')
    <div class="content wrapper">
        <ul>
            @if (session('success'))
                <div
                    x-data="{ show: true }"
                    x-show="show"
                    x-init="setTimeout(() => show = false, 3000)"
                    x-transition
                    class="fixed top-5 right-5 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg z-50"
                >{{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div
                    x-data="{ show: true }"
                    x-show="show"
                    x-init="setTimeout(() => show = false, 5000)"
                    x-transition
                    class="fixed top-5 right-5 bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg z-50"
                >{{ session('error') }}
                </div>
            @endif
            @if (session('info'))
                <div
                    x-data="{ show: true }"
                    x-show="show"
                    x-init="setTimeout(() => show = false, 3000)"
                    x-transition
                    class="fixed top-5 right-5 bg-blue-500 text-white px-6 py-4 rounded-lg shadow-lg z-50"
                >{{ session('info') }}
                </div>
            @endif
            <div class="p-6 space-y-8">
                <h1 class="text-3xl font-bold">{{ $group->name }}の編集</h1>
                <div class="bg-white rounded-xl shadow border p-6">
                    <h1 class="text-3xl font-bold">グループ基本情報</h1>
                    <p class="text-gray-500 mt-2">
                        グループ情報やメンバーを管理します
                    </p>
                    <form action="{{ route('groups.update', $group->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="space-y-4">
                            <div>
                                <label for="name" class="block text-sm font-medium mb-1">
                                    グループ名
                                </label>

                                <input type="text"
                                    name="name"
                                    id="name"
                                    value="{{ old('name', $group->name) }}"
                                    class="w-full border rounded-lg px-3 py-2"
                                    required
                                >
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-medium mb-1">
                                    説明文
                                </label>

                                <textarea
                                    name="description"
                                    id="description"
                                    class="w-full border rounded-lg px-3 py-2 h-32"
                                    required
                                >{{ old('description', $group->description) }}</textarea>
                            </div>

                            <div class="flex gap-3">
                                <button
                                    type="submit"
                                    class="bg-blue-600 text-white px-5 py-3 rounded-lg"
                                    onclick="return confirm('本当にこのグループの情報を更新しますか？');">
                                    更新
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                @if(is_null($group->archived_at))
                    <form action="{{ route('groups.archive', $group->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn btn-warning bg-red-500 px-5 py-3"
                        onclick="return confirm('本当にこのグループをアーカイブしますか？');">グループをアーカイブ</button>
                    </form>
                @endif
                <div class="bg-white rounded-xl shadow border p-6">
                    <h1  class="text-3xl font-bold">メンバー管理</h1>
                    <table class="w-full border-collapse">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-3">ユーザー</th>
                                <th class="p-3" colspan="2">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($removableUsers as $user)
                                <tr>
                                    <td class="p-3"><p>{{ $user->name }}</p></td>
                                    <td colspan="2" class="p-3">
                                        <form action="{{ route('groups.members.remove', ['group' => $group->id, 'user' => $user->id]) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger">退会</button>
                                        </form>
                                        @if($user->pivot->role !== 'admin')
                                            <form action="{{ route('groups.members.transfer', ['group' => $group->id, 'user' => $user->id]) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="btn btn-danger">管理権を付与</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            @foreach($applicants as $user)
                                <tr>
                                    <td>
                                        <p>{{ $user->name }} (申請者)</p>
                                        <p>{{ $user->email }}</p>
                                    </td>
                                    @if($user->pivot->role == 'applicant')
                                        <td>
                                            <form action="{{ route('groups.members.approval', ['group' => $group->id, 'user' => $user->id]) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="btn btn-danger">申請を承認</button>
                                            </form>
                                        </td>
                                        <td>
                                            <form action="{{ route('groups.members.reject', ['group' => $group->id, 'user' => $user->id]) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger">申請を拒否</button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div id="search" class="bg-white rounded-xl shadow border p-6">
                    <h1  class="text-3xl font-bold">ユーザーを招待</h1>
                    <form method="GET" action="{{ route('groups.edit', ['group' => $group->id]) }}#search" class="mb-4">
                        <input type="text" name="query" placeholder="名前またはメールアドレス" value="{{ request('query') }}" class="border p-2 rounded">
                        <button type="submit" class="bg-gray-200 px-4 py-2 rounded">検索</button>
                    </form>
                    @if(request('query'))
                        @if($searchResults->isNotEmpty())
                            <form method="POST" action="{{ route('groups.invitations.invite', ['group' => $group->id]) }}#invitations">
                                @csrf
                                <table class="table-auto w-full">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="p-3">選択</th>
                                            <th class="p-3">名前</th>
                                            <th class="p-3">メールアドレス</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($searchResults as $user)
                                            <tr>
                                                <td class="p-3"><input type="radio" name="user_id" value="{{ $user->id }}" required></td>
                                                <td class="p-3">{{ $user->name }}</td>
                                                <td class="p-3">{{ $user->email }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <button type="submit" class="mt-4 px-5 py-3 rounded">招待</button>
                            </form>
                        @else
                            <p>検索結果が見つかりませんでした。</p>
                        @endif
                    @endif
                </div>
                <div id="invitations" class="bg-white rounded-xl shadow border p-6">
                    <h1  class="text-3xl font-bold">招待管理</h1>
                    <table class="w-full border-collapse">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-3">招待されたユーザー</th>
                                <th class="p-3">招待したユーザー</th>
                                <th class="p-3">残り期限</th>
                                <th class="p-3">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invitations as $invitation)
                                <tr>
                                    <td class="p-3">{{ $invitation->invitee_email }}</td>
                                    <td class="p-3">{{ $invitation->inviter->name ?? '-' }}</td>
                                    <td class="p-3">
                                        {{ $invitation->expires_at->diffForHumans() }}
                                        （{{ $invitation->expires_at->format('Y-m-d') }}）
                                    </td>
                                    <td class="p-3">
                                        <form method="POST" action="{{ route('groups.invitations.resend', ['group' => $group->id, 'invitation' => $invitation->id]) }}#invitations">
                                            @csrf
                                            <button type="submit">再送</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="bg-white rounded-xl shadow border p-6">
                    <h1  class="text-3xl font-bold">グループ管理者の降格</h1>
                    <form action="{{ route('groups.members.demote', $group->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn btn-warning bg-blue-500 px-5 py-3"
                        onclick="return confirm('本当にこのグループの管理者をやめますか？');">管理者を降格</button>
                    </form>
                </div>
                <a href="{{ route('groups.messages.show', [$group->id]) }}">Back To Chat</a>
            </div>
        </ul>
    </div>
@endsection
