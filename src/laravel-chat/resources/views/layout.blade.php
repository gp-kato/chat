<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>@yield('title')</title>
        <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <header id="header" class="wrapper">
            <div class="site-title">
            </div>
        </header>

        <main>
            @yield('content')
        </main>

        <footer id="footer" class="wrapper">
        </footer>
    </body>
</html>
