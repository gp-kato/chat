@extends('layout')

@section('title', 'ChatRoom')

@section('content')
    <div class="container">
        <h1>{{ $group->name }}</h1>
        <hr>
        <ul class="message-list">
            @forelse($messages as $message)
                <li class="d-flex {{ $message->user->id === auth()->id() ? 'justify-content-end' : 'justify-content-start' }}">
                    {{ $message->content }}
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
        <form class="message" action="{{ route('store', ['group' => $group->id]) }}" method="POST">
            @csrf
            <input type="hidden" name="user_id" value="{{ auth()->id() }}">

            <textarea name="content" rows="3" required class="form-control"></textarea>
            <br>
            <button type="submit" class="btn btn-primary">送信</button>
        </form>
    </div>
@endsection
