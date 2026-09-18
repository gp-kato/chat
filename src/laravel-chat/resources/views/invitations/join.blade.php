@extends('layout')

@section('title', 'グループ招待')

@section('content')
    <div class="container">
        <h1>{{ $group->name }} に参加しますか？</h1>
        <form method="POST" action="{{ route('groups.invitations.join', ['group' => $group->id, 'token' => $token]) }}">
            @csrf
            <button type="submit">参加する</button>
        </form>
    </div>
@endsection
