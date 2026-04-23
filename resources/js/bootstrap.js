import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const broadcaster = import.meta.env.VITE_BROADCASTER || 'reverb';
const isTls = (import.meta.env.VITE_REVERB_SCHEME || 'https') === 'https';
const wsHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
const wsPort = Number(import.meta.env.VITE_REVERB_PORT || (isTls ? 443 : 80));

window.Echo = new Echo({
    broadcaster,
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost,
    wsPort,
    wssPort: wsPort,
    forceTLS: isTls,
    enabledTransports: ['ws', 'wss'],
});