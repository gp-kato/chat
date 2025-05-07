@extends('layout')

@section('title', 'AdminPage')

@section('content')
    <div class="container">
        @foreach (['success'] as $msg)
            @if (session($msg))
                <div class="alert alert-{{ $msg }}">
                    {{ session($msg) }}
                </div>
            @endif
        @endforeach
        <form method="GET" action="{{ route('index') }}" class="mb-4 flex space-x-4">
            <input type="text" name="keyword" placeholder="名前またはメールアドレス" value="{{ request('keyword') }}" class="border p-2 rounded">
            <select name="status" class="border p-2 rounded">
                <option value="">ステータスを選択</option>
                <option value="参加済み" {{ request('status') == '参加済み' ? 'selected' : '' }}>参加済み</option>
                <option value="退会済み" {{ request('status') == '退会済み' ? 'selected' : '' }}>退会済み</option>
            </select>
            <button type="submit" class="px-4 py-2 rounded">検索</button>
        </form>
        <form method="POST" action="{{route('invite') }}">
            @csrf
            <input type="text" name="name" placeholder="名前" value="{{ old('name') }}" class="border p-2 rounded" required>
            <input type="email" name="email" placeholder="メールアドレス" value="{{ old('email') }}" class="border p-2 rounded" required>
            <select name="group_id" required>
                <option value="">グループを選択</option>
                @foreach ($groups as $group)
                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 rounded">招待</button>
        </form>
        <div style="margin-bottom: 1rem;">
            <span>並び替え:</span>
            <a href="{{ route('index', array_merge(request()->all(), ['sort' => 'name', 'direction' => request('direction') === 'asc' ? 'desc' : 'asc'])) }}">
                名前順
            </a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ユーザー名</th>
                    <th>メール</th>
                    @foreach ($groups as $group)
                        <th>{{ $group->id }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        @foreach ($groups as $group)
                            <td>
                                @if ($group->users->contains('id', $user->id))
                                    <span>参加済み</span>
                                @else
                                    <span>未招待</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
