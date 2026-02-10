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
            <a href="{{ route('groups.edit', $group->id) }}">このグループを編集</a>
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
                        notifyFetchError("メッセージの取得に失敗しました", beforeId);
                        return;
                    }

                    clearFetchError();

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
                    notifyFetchError("通信エラーが発生しました", beforeId);
                } finally {
                    loading = false;
                }
            }

            const notificationState = {
                network: 'online',
                fetchError: null
            };

            function notifyNetwork(state) {
                notificationState.network = state;
                renderNotification();
            }

            function notifyFetchError(message, beforeId) {
                notificationState.fetchError = { message, beforeId };
                renderNotification();
            }

            function clearFetchError() {
                notificationState.fetchError = null;
                renderNotification();
            }

            function renderNotification() {
                const bar = document.getElementById('network-indicator');

                bar.className = 'network-indicator';
                bar.innerHTML = '';

                if (notificationState.network === 'offline') {
                    bar.textContent = 'ネットワークが切断されています';
                    bar.classList.add('offline');
                    return;
                }

                if (notificationState.fetchError) {
                    const span = document.createElement('span');
                    span.textContent = notificationState.fetchError.message;

                    const btn = document.createElement('button');
                    btn.className = 'retry-btn';
                    btn.textContent = '再読み込み';
                    btn.onclick = () => {
                        clearFetchError();
                        loadMessages(notificationState.fetchError.beforeId);
                    };

                    bar.appendChild(span);
                    bar.appendChild(btn);
                    bar.classList.add('error');
                    return;
                }

                if (notificationState.network === 'connecting') {
                    bar.textContent = '接続中...';
                    bar.classList.add('connecting');
                    return;
                }

                bar.textContent = '接続中';
                bar.classList.add('online');
                setTimeout(() => bar.classList.add('hidden'), 800);
            }

            window.addEventListener('offline', () => {
                notifyNetwork('offline');
            });

            window.addEventListener('online', () => {
                notifyNetwork('online');
            });

            function mapPusherStateToNetwork(state) {
                switch (state) {
                    case 'connected':
                    return 'online';

                    case 'connecting':
                    case 'reconnecting':
                    return 'connecting';

                    case 'disconnected':
                    case 'unavailable':
                    case 'failed':
                    return 'offline';

                    default:
                    return null;
                }
            }

            Echo.connector.pusher.connection.bind('state_change', ({ previous, current }) => {
                const mapped = mapPusherStateToNetwork(current);
                if (!mapped) return;

                notifyNetwork(mapped);
            });
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

        document.getElementById("send").addEventListener("click", function (e) {
            const sendButton = e.currentTarget;
            sendButton.disabled = true;
            sendButton.textContent = "送信中...";

            const message = document.getElementById("message").value;
            if (message === "") return;

            function showToast(message) {
                const toast = document.getElementById('toast');
                toast.textContent = message;
                toast.className = 'toast show';
                setTimeout(() => {
                    toast.className = toast.className.replace('show', '');
                }, 3000);
            }

            axios.post(`/groups/${groupId}/messages`, { content: message })

            .then((response) => {
                showToast('メッセージを送信しました');
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
            })

            .finally(() => {
                sendButton.disabled = false;
                sendButton.textContent = "送信";
            });
        });
    </script>
@endsection
