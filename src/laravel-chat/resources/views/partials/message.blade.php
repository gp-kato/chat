<li class="d-flex {{ $message->user->id === auth()->id() ? 'justify-content-end' : 'justify-content-start' }}" data-id="{{ $message->id }}">
    <div>
        {!! nl2br(e($message->content)) !!}
        <br>
        <small>by {{ $message->user->name }}</small>
    </div>
</li>
