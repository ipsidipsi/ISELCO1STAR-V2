import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import axios from 'axios';

// Polyfill Pusher on window for Echo to use
(window as any).Pusher = Pusher;

// Get API base URL and strip '/api' suffix if present to get root URL
const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';
const baseUrl = apiUrl.endsWith('/api') ? apiUrl.slice(0, -4) : apiUrl;

(window as any).Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    authorizer: (channel: any, _options: any) => {
        return {
            authorize: (socketId: string, callback: Function) => {
                // Get token from localStorage (managed by Pinia persisted state 'auth' key)
                const storageItem = localStorage.getItem('auth');
                let token = null;

                if (storageItem) {
                    try {
                        const authData = JSON.parse(storageItem);
                        token = authData.token;
                    } catch (e) {
                        console.error('Failed to parse auth token', e);
                    }
                }

                if (!token) {
                    console.warn('Echo: No auth token found, cannot authorize channel', channel.name);
                    callback(true, 'No auth token');
                    return;
                }

                axios.post(baseUrl + '/broadcasting/auth', {
                    socket_id: socketId,
                    channel_name: channel.name
                }, {
                    headers: {
                        Authorization: `Bearer ${token}`
                    }
                })
                    .then(response => {
                        callback(false, response.data);
                    })
                    .catch(error => {
                        console.error('Echo authorization failed', error);
                        callback(true, error);
                    });
            }
        };
    },
});

console.log('Echo initialized with Reverb');
