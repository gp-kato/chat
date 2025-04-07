@extends('layout')

@section('title', 'GroupChat')

@section('content')
    <div class="container">
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
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
                                @if ($group->isJoinedBy(auth()->user()))
                                    <form action="{{ route('leave', $group->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">退会</button>
                                    </form>
                                @else
                                    <form action="{{ route('join', $group->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-success">参加</button>
                                    </form>
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

        <form action="{{ route('add') }}" method="POST">
            @csrf
            <label for="name">チャット名</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}">
            <label for="description">説明文</label>
            <input type="text" id="description" name="description" value="{{ old('description') }}">
            <button type="submit" class="btn btn-primary">新チャット作成</button>
        </form>
    </div>
@endsection
