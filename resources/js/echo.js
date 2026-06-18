import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const isHttps = window.location.protocol === 'https:';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
    wsPort: isHttps ? 443 : (import.meta.env.VITE_REVERB_PORT ?? 80),
    wssPort: isHttps ? 443 : (import.meta.env.VITE_REVERB_PORT ?? 443),
    forceTLS: isHttps,
    enabledTransports: ['ws', 'wss'],
});
