@extends('layout')

@section('title', 'GroupChat')

@section('content')
    <div class="container">
        <h1>Group Chat</h1>
        <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
        <div x-data="{ show: false }">
            <h2 @click="show = !show" style="cursor: pointer;">ユーザー</h2>
            <ul x-show="show">
                @foreach($users as $user)
                    <li>{{ $user->name }}</li>
                @endforeach
            </ul>
        </div>
        <h2>チャット</h2>
        <div id="messages">
            <ul>
                @foreach($groups as $group)
                    <li>
                        <a href="{{ url('group/' . $group->id) }}">{{ $group->name }}</a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endsection
