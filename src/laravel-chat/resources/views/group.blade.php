@extends('layout')

@section('title', 'GroupChat')

@section('content')
    <div class="container">
        <h1>Group Chat</h1>
        <h2>チャット</h2>
        <div id="messages" class="chat-list">
            <ul>
                @foreach($groups as $group)
                    <li>
                        <a href="{{ route('show', [$group->id]) }}">{{ $group->name }}</a>
                    </li>
                @endforeach
            </ul>
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
            <input type="text" name="name">
            <label for="description">説明文</label>
            <input type="text" name="description">
            <button type="submit" class="btn btn-primary">新チャット作成</button>
        </form>
    </div>
@endsection
