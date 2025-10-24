import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';

Echo.channel(`group.${groupId}`).listen("MessageEvent", function (e) {
    const div = document.getElementById("messages");
    const html = e.html;

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
                console.warn("バリデーションエラー:", error.response.data);
                alert("入力内容に誤りがあります。\n" + JSON.stringify(error.response.data));
                return;
            }

            // その他のサーバーエラー
            console.error("サーバーエラー:", error.response);
            alert("サーバーエラーが発生しました。後でもう一度お試しください。");
        });
});
