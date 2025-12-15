@extends('layout')

@section('title', 'ChatRoom')

@section('content')
    <div class="container">
        <h1>{{ $group->name }}</h1>
        @foreach (['success', 'info', 'error'] as $msg)
            @if (session($msg))
                <div class="alert alert-{{ $msg }}">
                    {{ session($msg) }}
                </div>
            @endif
        @endforeach
        @if($isAdmin)
            <form method="GET" action="{{ route('groups.messages.show', ['group' => $group->id]) }}" class="mb-4">
                <input type="text" name="query" placeholder="名前またはメールアドレス" value="{{ request('query') }}" class="border p-2 rounded">
                <button type="submit" class="bg-gray-200 px-4 py-2 rounded">検索</button>
            </form>
            @if(request('query'))
                @if($searchResults->isNotEmpty())
                    <form method="POST" action="{{ route('groups.invitations.invite', ['group' => $group->id]) }}">
                        @csrf
                        <table class="table-auto w-full">
                            <thead>
                                <tr>
                                    <th>選択</th>
                                    <th>名前</th>
                                    <th>メールアドレス</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($searchResults as $user)
                                    <tr>
                                        <td>
                                            <input type="radio" name="user_id" value="{{ $user->id }}" required>
                                        </td>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <button type="submit" class="mt-4 px-4 py-2 rounded">招待</button>
                    </form>
                @else
                    <p>検索結果が見つかりませんでした。</p>
                @endif
            @endif
            <a href="{{ route('groups.edit', $group->id) }}">このグループを編集</a>
            <h1>メンバーを退会</h2>
            <table>
                <thead>
                    <tr>
                        <th>ユーザー名</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($removableUsers as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>
                                <form action="{{ route('groups.members.remove', ['group' => $group->id, 'user' => $user->id]) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">退会</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <table>
                <thead>
                    <tr>
                        <th>招待されたユーザー</th>
                        <th>招待したユーザー</th>
                        <th>残り期限</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invitations as $invitation)
                        <tr>
                            <td>{{ $invitation->invitee_email }}</td>
                            <td>{{ $invitation->inviter->name ?? '-' }}</td>
                            <td>
                                {{ $invitation->expires_at->diffForHumans() }}
                                （{{ $invitation->expires_at->format('Y-m-d') }}）
                            </td>
                            <td>
                                <form method="POST" action="{{ route('groups.invitations.resend', ['group' => $group->id, 'invitation' => $invitation->id]) }}">
                                    @csrf
                                    <button type="submit">再送</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        <hr>
        <ul class="message-list" id="messages">
            @forelse($messages as $message)
                @include('partials.message', ['message' => $message])
            @empty
                <li class="no-messages text-muted text-center">No messages.</li>
            @endforelse
        </ul>
        <div class="message">
            <textarea name="content" rows="3" required class="form-control" id="message"></textarea>
            <br>
            <button type="button" class="btn btn-primary" id="send">送信</button>
        </div>
        <a href="/">Back To Chatlist</a>
    </div>
@endsection

@section('js')  {{-- 「js」セクションにスクリプトを注入 --}}
    <script type="module">
        window.App = window.App || {}; // グローバル名前空間の初期化
        window.App.user_id = {!! json_encode(auth()->id()) !!}; // ログインユーザーIDを格納
        const groupId = {{ $group->id }};

        document.addEventListener('DOMContentLoaded', () => {
            const messages = document.getElementById('messages');
            let loading = false;
            let hasMore = true;

            // ページ読み込み時に最下部へスクロール
            messages.scrollTop = messages.scrollHeight;

            messages.addEventListener('scroll', async () => {
                if (loading || !hasMore) return;

                // 上端近くまで来たら過去メッセージをロード
                if (messages.scrollTop <= 50) {
                    loading = true;

                    const firstMessage = messages.querySelector('li:first-child');
                    const beforeId = firstMessage ? firstMessage.dataset.id : null;

                    await loadMessages(beforeId);

                    loading = false;
                }
            });

            async function loadMessages(beforeId) {
                try {
                    const url = `/groups/${groupId}/messages/fetch?before_id=${beforeId}`;
                    const res = await fetch(url, { method: 'GET' });

                    if (!res.ok) {
                        throw new Error(`HTTP error: ${res.status}`);
                    }

                    const data = await res.json();

                    if (data.error) {
                        showErrorWithRetry("メッセージの取得に失敗しました。");
                        return;
                    }

                    updateNetworkIndicator('online');

                    document.querySelectorAll('.error-banner').forEach(b => b.remove());

                    if (data.html.trim()) {
                        const prevScrollHeight = messages.scrollHeight;
                        const prevScrollTop = messages.scrollTop;

                        // 過去分を上に追加
                        messages.insertAdjacentHTML('afterbegin', data.html);

                        // 挿入後の高さを取得してスクロール補正
                        const newScrollHeight = messages.scrollHeight;
                        messages.scrollTop = prevScrollTop + (newScrollHeight - prevScrollHeight);
                    }

                    hasMore = data.has_more;

                } catch (e) {
                    // 通信エラー時の処理
                    updateNetworkIndicator('offline');
                    showErrorWithRetry("メッセージの取得に失敗しました ", beforeId);
                } finally {
                    loading = false;
                }
            }

            function updateNetworkIndicator(state) {
                const bar = document.getElementById('network-indicator');

                bar.classList.remove('hidden', 'online', 'offline');

                if (state === 'online') {
                    bar.textContent = '接続中';
                    bar.classList.add('online');
                    setTimeout(() => bar.classList.add('hidden'), 800);
                }

                if (state === 'offline') {
                    bar.textContent = 'ネットワークが切断されました';
                    bar.classList.add('offline');
                }
            }

            Echo.connector.pusher.connection.bind('connected', () => {
                updateNetworkIndicator('online');
            });

            Echo.connector.pusher.connection.bind('disconnected', () => {
                updateNetworkIndicator('offline');
            });

            Echo.connector.pusher.connection.bind('reconnected', () => {
                updateNetworkIndicator('online');
            });

            Echo.connector.pusher.connection.bind('error', (err) => {
                console.error('WebSocket error:', err);
                updateNetworkIndicator('offline');
            });

            // ====== エラー + 再試行バナー ======
            function showErrorWithRetry(message, beforeId) {
                // すでに表示中のバナーがあれば消す
                const oldBanner = document.querySelector('.error-banner');
                if (oldBanner) oldBanner.remove();

                const banner = document.createElement('div');
                banner.className = 'error-banner integrated';

                const msg = document.createElement('span');
                msg.textContent = message;

                const btn = document.createElement('button');
                btn.className = 'retry-btn';
                btn.textContent = '再読み込み';

                btn.addEventListener('click', async () => {
                    banner.remove(); // バナーごと削除
                    await loadMessages(beforeId);
                });

                banner.appendChild(msg);
                banner.appendChild(btn);
                document.body.appendChild(banner);
            }
        });

        Echo.private(`group.${groupId}`).listen("MessageEvent", function (e) {
            const div = document.getElementById("messages");
            const html = e.html;
            const noMessages = div.querySelector(".no-messages");
            if (noMessages) {
                noMessages.remove();
            }

            div.insertAdjacentHTML("beforeend", html);

            const newMessage = div.lastElementChild;

            if (e.user_id === window.App.user_id) {
                newMessage.classList.add("justify-content-end");
            }
        });

        document.getElementById("send").addEventListener("click", function () {
            const message = document.getElementById("message").value;
            if (message === "") return;
            axios.post(`/groups/${groupId}/messages`, { content: message })

            .then(() => {
                document.getElementById("message").value = "";
            })

            .catch(error => {
                if (!error.response) {
                    console.error("ネットワークエラー:", error);
                    alert("ネットワークエラーが発生しました。ネットワーク接続を確認してください。");
                    return;
                }

                // ステータスコード別処理
                const status = error.response.status;

                // バリデーションエラー
                if (status === 400 || status === 422) {
                    const errors = error.response.data.errors;
                    const messageError = errors?.content?.[1] || "正しい入力値で入力してください。";
                    alert(messageError);
                    return;
                }

                // その他のサーバーエラー
                console.error("サーバーエラー:", error.response);
                alert("サーバーエラーが発生しました。後でもう一度お試しください。");
            });
        });
    </script>
@endsection
