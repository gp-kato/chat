<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body>
        <div class="content wrapper">
            <p>{{ $group->name }} グループに招待されました。</p>
            <p>以下のリンクから参加してください：</p>
            <p><a href="{{ $url }}">{{ $url }}</a></p>
            <p>※ このリンクは1ヶ月後に無効になります。</p>
        </div>
    </body>
</html>
