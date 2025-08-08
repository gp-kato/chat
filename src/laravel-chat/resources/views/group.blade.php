@extends('layout')

@section('title', 'GroupChat')

@section('content')
    <div class="container">
        @foreach (['success', 'info', 'error'] as $msg)
            @if (session($msg))
                <div class="alert alert-{{ $msg }}">
                    {{ session($msg) }}
                </div>
            @endif
        @endforeach
        <h1>Group Chat</h1>
        <div id="messages" class="chat-list">
            <table>
                <thead>
                    <tr>
                        <th>チャット名</th>
                        <th>説明</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groups as $group)
                        <tr>
                            <td>
                                <a href="{{ route('show', [$group->id]) }}">{{ $group->name }}</a>
                            </td>
                            <td>{{ $group->description }}</td>
                            <td>
                                @if ($group->is_joined)
                                    <form action="{{ route('groups.leave', $group->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">退会</button>
                                    </form>
                                @else
                                    <p>ー</p>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('groups.add') }}" method="POST">
            @csrf
            <label for="name">チャット名</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}">
            <label for="description">説明文</label>
            <input type="text" id="description" name="description" value="{{ old('description') }}">
            <button type="submit" class="btn btn-primary">新チャット作成</button>
        </form>
    </div>
@endsection
