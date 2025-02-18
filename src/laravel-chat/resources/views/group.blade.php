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
    </div>
@endsection
