@extends('layout')

@section('title', 'ChatRoom')

@section('content')
    <div class="container">
        <h1>{{ $group->name }}</h1>
        <hr>
        <ul>
            @if($messages->isEmpty())
                <li>No messages.</li>
            @else
                @foreach($messages as $message)
                    <li class="d-flex {{ $message->user->id === auth()->id() ? 'justify-content-end' : 'justify-content-start' }}">
                        {{ $message->content }} <br>
                        <small>by {{ $message->user->name }}</small>
                    </li>
                @endforeach
            @endif
        </ul>
        <form action="{{ route('store', ['group' => $group->id]) }}" method="POST">
            @csrf
            <input type="hidden" name="user_id" value="{{ auth()->id() }}">
            <input type="hidden" name="group_id" value="{{ $group->id }}">
            <textarea name="content" rows="3" required class="form-control"></textarea>
            <br>
            <button type="submit" class="btn btn-primary">送信</button>
        </form>
    </div>
@endsection
