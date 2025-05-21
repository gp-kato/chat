@extends('layout')

@section('title', 'ChatRoom')

@section('content')
    <div class="container">
        <h1>{{ $group->name }}</h1>
        @foreach (['success', 'info', 'error'] as $msg)
            @if (session($msg))
                <div class="alert alert-{{ $msg }}">
                    {{ session($msg) }}
                </div>
            @endif
        @endforeach
        @if($group->isAdmin(auth()->user()))
            <form method="GET" action="{{ route('search', ['group' => $group->id]) }}" class="mb-4">
                <input type="text" name="query" placeholder="名前またはメールアドレス" value="{{ request('query') }}" class="border p-2 rounded">
                <button type="submit" class="bg-gray-200 px-4 py-2 rounded">検索</button>
            </form>
            @if(request('query'))
                @if($users->isNotEmpty())
                    <form method="POST" action="{{ route('invite', ['group' => $group->id]) }}">
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
                                @foreach($users as $user)
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
            <a href="{{ route('edit', $group->id) }}">このグループを編集</a>
        @endif
        <hr>
        <ul class="message-list">
            @forelse($messages as $message)
                <li class="d-flex {{ $message->user->id === auth()->id() ? 'justify-content-end' : 'justify-content-start' }}">
                    <div>
                        {!! nl2br(e($message->content)) !!}
                        <br>
                        <small>by {{ $message->user->name }}</small>
                    </div>
                </li>
            @empty
                <li>No messages.</li>
            @endforelse
        </ul>
        <div class="message">
            <form action="{{ route('store', ['group' => $group->id]) }}" method="POST">
                @csrf
                <textarea name="content" rows="3" required class="form-control"></textarea>
                <br>
                <button type="submit" class="btn btn-primary">送信</button>
            </form>
            <a href="/">Back To Chatlist</a>
        </div>
    </div>
@endsection
