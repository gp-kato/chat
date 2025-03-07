@extends('layout')

@section('title', 'GroupChat')

@section('content')
    <div class="container">
        <h1>Group Chat</h1>
        <div id="messages" class="chat-list">
            <table>
                <thead>
                    <tr>
                        <th>チャット名</th>
                        <th>説明</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groups as $group)
                        <tr>
                            <td>
                                <a href="{{ route('show', [$group->id]) }}">{{ $group->name }}</a>
                            </td>
                            <td>{{ $group->description }}</td>
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
