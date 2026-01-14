import axios from 'axios'
import type { AxiosInstance } from 'axios'
import { useAuthStore } from '@/stores/auth'

// API Base URL - update this to match your backend
const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

// Create axios instance
const api: AxiosInstance = axios.create({
    baseURL: API_BASE_URL,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
})

// Request interceptor - attach token
api.interceptors.request.use(
    (config) => {
        const authStore = useAuthStore()
        if (authStore.token) {
            config.headers.Authorization = `Bearer ${authStore.token}`
        }

        // If data is FormData, let browser set Content-Type with boundary
        if (config.data instanceof FormData) {
            delete config.headers['Content-Type'];
        }

        return config
    },
    (error) => {
        return Promise.reject(error)
    }
)

// Response interceptor - handle errors
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            // Unauthorized - clear auth and redirect to login
            // BUT prevent infinite loop if the request itself was /logout
            const isLogoutRequest = error.config.url?.endsWith('/logout') || error.config.url?.endsWith('/login')

            if (!isLogoutRequest) {
                const authStore = useAuthStore()
                authStore.logout()
            }
        }
        return Promise.reject(error)
    }
)

export default api
