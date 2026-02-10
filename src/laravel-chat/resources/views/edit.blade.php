@extends('layout')

@section('title', 'EditngGroup')

@section('content')
    <div class="content wrapper">
        <ul>
            @foreach (['success', 'error'] as $msg)
                @if (session($msg))
                    <div class="alert alert-{{ $msg }}">
                        {{ session($msg) }}
                    </div>
                @endif
            @endforeach
            <div class="container">
                <h1>グループを編集</h1>
                <form action="{{ route('groups.update', $group->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="name">グループ名</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $group->name) }}" required>
                    </div>
                    <div>
                        <label for="description">説明文</label>
                        <input type="text" name="description" id="description" value="{{ old('description', $group->description) }}" required>
                    </div>
                    <button type="submit">グループを更新</button>
                </form>
                <hr>
                <table>
                    <thead>
                        <tr>
                            <th>ユーザー</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($removableUsers as $user)
                            <tr>
                                <td>
                                    <p>{{ $user->name }}</p>
                                </td>
                                <td>
                                    <form action="{{ route('groups.members.remove', ['group' => $group->id, 'user' => $user->id]) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">退会</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <br>
                <form method="GET" action="{{ route('groups.edit', ['group' => $group->id]) }}" class="mb-4">
                    <input type="text" name="query" placeholder="名前またはメールアドレス" value="{{ request('query') }}" class="border p-2 rounded">
                    <button type="submit" class="bg-gray-200 px-4 py-2 rounded">検索</button>
                </form>
                @if(request('query'))
                    @if($searchResults->isNotEmpty())
                        <form method="POST" action="{{ route('groups.invitations.invite', ['group' => $group->id]) }}">
                            @csrf
                            <table class="table-auto w-full">
                                <thead>
                                    <tr>
                                        <th>選択</th>
                                        <th>名前</th>
                                        <th>メールアドレス</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($searchResults as $user)
                                        <tr>
                                            <td>
                                                <input type="radio" name="user_id" value="{{ $user->id }}" required>
                                            </td>
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->email }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <button type="submit" class="mt-4 px-4 py-2 rounded">招待</button>
                        </form>
                    @else
                        <p>検索結果が見つかりませんでした。</p>
                    @endif
                @endif
                <table>
                    <thead>
                        <tr>
                            <th>招待されたユーザー</th>
                            <th>招待したユーザー</th>
                            <th>残り期限</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invitations as $invitation)
                            <tr>
                                <td>{{ $invitation->invitee_email }}</td>
                                <td>{{ $invitation->inviter->name ?? '-' }}</td>
                                <td>
                                    {{ $invitation->expires_at->diffForHumans() }}
                                    （{{ $invitation->expires_at->format('Y-m-d') }}）
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('groups.invitations.resend', ['group' => $group->id, 'invitation' => $invitation->id]) }}">
                                        @csrf
                                        <button type="submit">再送</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </ul>
    </div>
@endsection
