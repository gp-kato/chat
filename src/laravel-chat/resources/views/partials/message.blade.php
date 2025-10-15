<!DOCTYPE html>
<html lang="ja">
    <body>
        <li class="d-flex {{ $message->user->id === auth()->id() ? 'justify-content-end' : 'justify-content-start' }}">
            <div>
                {!! nl2br(e($message->content)) !!}
                <br>
                <small>by {{ $message->user->name }}</small>
            </div>
        </li>
    </body>
</html>
