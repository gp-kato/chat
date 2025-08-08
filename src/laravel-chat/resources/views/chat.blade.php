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
        @if($isAdmin)
            <form method="GET" action="{{ route('show', ['group' => $group->id]) }}" class="mb-4">
                <input type="text" name="query" placeholder="名前またはメールアドレス" value="{{ request('query') }}" class="border p-2 rounded">
                <button type="submit" class="bg-gray-200 px-4 py-2 rounded">検索</button>
            </form>
            @if(request('query'))
                @if($searchResults->isNotEmpty())
                    <form method="POST" action="{{ route('invitation.invite', ['group' => $group->id]) }}">
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
            <a href="{{ route('groups.edit', $group->id) }}">このグループを編集</a>
            <h1>メンバーを退会</h2>
            <table>
                <thead>
                    <tr>
                        <th>ユーザー名</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($removableUsers as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>
                                <form action="{{ route('groups.remove', ['group' => $group->id, 'user' => $user->id]) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">退会</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
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
                                <form method="POST" action="{{ route('invitation.resend', ['group' => $group->id, 'invitation' => $invitation->id]) }}">
                                    @csrf
                                    <button type="submit">再送</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
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
            <form action="{{ route('messages.store', ['group' => $group->id]) }}" method="POST">
                @csrf
                <textarea name="content" rows="3" required class="form-control"></textarea>
                <br>
                <button type="submit" class="btn btn-primary">送信</button>
            </form>
            <a href="/">Back To Chatlist</a>
        </div>
    </div>
@endsection
