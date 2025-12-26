import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// Make Pusher available globally for Echo
declare global {
    interface Window {
        Pusher: typeof Pusher
    }
}

window.Pusher = Pusher

// Get token from Pinia's persisted auth store
function getAuthToken(): string {
    try {
        const authStore = localStorage.getItem('auth')
        if (authStore) {
            const parsed = JSON.parse(authStore)
            return parsed.token || ''
        }
    } catch (e) {
        console.error('Failed to get auth token:', e)
    }
    return ''
}

// Echo configuration for Laravel Reverb
const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY || 'iselco-star-key',
    wsHost: import.meta.env.VITE_REVERB_HOST || 'localhost',
    wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT || 8080,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: 'http://localhost:8000/broadcasting/auth',
    auth: {
        headers: {
            Authorization: `Bearer ${getAuthToken()}`,
            Accept: 'application/json',
        },
    },
} as any) // Type assertion to bypass strict typing

// Update auth headers when token changes
export function updateEchoAuth(token: string) {
    const connector = echo.connector as any
    if (connector.pusher) {
        connector.pusher.config.auth.headers.Authorization = `Bearer ${token}`
    }
}

// Disconnect Echo
export function disconnectEcho() {
    echo.disconnect()
}

// Reconnect Echo
export function reconnectEcho() {
    echo.connector.connect()
}

export default echo
