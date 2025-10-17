import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';

Echo.channel("demo-channel").listen("MessageEvent", function (e) {
    const div = document.getElementById("messages");
    const html = e.html;

    div.insertAdjacentHTML("beforeend", html);

    const newMessage = div.lastElementChild;

    if (e.user_id === window.App.user_id) {
        newMessage.classList.add("justify-content-end");
    }
});

//document.getElementById("send").addEventListener("click", function () {
    //const message = document.getElementById("message").value;
    //if (message === "") return;
    //axios.post("/", { message: message })
        //.then(() => {
            //document.getElementById("message").value = "";
        //})
//});
