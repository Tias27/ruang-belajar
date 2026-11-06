import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const config = document.querySelector('meta[name="realtime-config"]');
window.Echo = null;

if (config) {
    try {
        const options = JSON.parse(config.content);
        const isHttps = window.location.protocol === 'https:';
        if (options.key) {
            window.Echo = new Echo({
                broadcaster: 'reverb',
                key: options.key,
                wsHost: options.host || window.location.hostname,
                wsPort: isHttps ? 443 : options.port,
                wssPort: isHttps ? 443 : options.port,
                forceTLS: isHttps,
                enabledTransports: ['ws', 'wss'],
            });
        }
    } catch (error) {
        console.warn('Realtime study room tidak aktif, memakai polling.', error);
    }
}
