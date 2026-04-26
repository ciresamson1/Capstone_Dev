import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const explicitBroadcaster = import.meta.env.VITE_BROADCASTER;
const hasPusherConfig = Boolean(import.meta.env.VITE_PUSHER_APP_KEY);
const broadcaster = explicitBroadcaster || (hasPusherConfig ? 'pusher' : 'reverb');

const scheme = import.meta.env.VITE_REVERB_SCHEME
    || import.meta.env.VITE_PUSHER_SCHEME
    || 'https';
const isTls = scheme === 'https';

const configuredHost = import.meta.env.VITE_REVERB_HOST || import.meta.env.VITE_PUSHER_HOST;
const wsHost = configuredHost || (broadcaster === 'reverb' ? window.location.hostname : undefined);
const wsPort = Number(
    import.meta.env.VITE_REVERB_PORT
    || import.meta.env.VITE_PUSHER_PORT
    || (isTls ? 443 : 80),
);

const echoConfig = {
    broadcaster,
    key: import.meta.env.VITE_REVERB_APP_KEY || import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: isTls,
    enabledTransports: ['ws', 'wss'],
};

if (wsHost) {
    echoConfig.wsHost = wsHost;
    echoConfig.wsPort = wsPort;
    echoConfig.wssPort = wsPort;
}

window.Echo = new Echo(echoConfig);