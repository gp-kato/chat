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
        <form action="{{ route('add') }}" method="POST">
            @csrf
            <input type="text" name="name">
            <input type="text" name="description">
            <button type="submit" class="btn btn-primary">新チャット作成</button>
        </form>
    </div>
@endsection
