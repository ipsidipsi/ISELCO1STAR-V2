import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'

/**
 * Authentication Composable
 * 
 * Separates authentication logic from the template
 * Handles login, validation, and error states
 */
export function useAuth() {
    const authStore = useAuthStore()

    const loading = ref(false)
    const errorMessage = ref('')
    const errors = ref<{ login?: string; password?: string }>({})

    async function login(loginValue: string, password: string) {
        // Reset states
        loading.value = true
        errorMessage.value = ''
        errors.value = {}

        // Validation
        if (!loginValue) {
            errors.value.login = 'Username or mobile number is required'
            loading.value = false
            return
        }

        if (!password) {
            errors.value.password = 'Password is required'
            loading.value = false
            return
        }

        // Attempt login
        try {
            await authStore.login(loginValue, password)
        } catch (error: any) {
            errorMessage.value = error.message || 'Invalid credentials'
        } finally {
            loading.value = false
        }
    }

    async function logout() {
        await authStore.logout()
    }

    async function changePassword(current: string | null, newPass: string, confirm: string) {
        loading.value = true
        errorMessage.value = ''

        try {
            await authStore.changePassword(current, newPass, confirm)
        } catch (error: any) {
            errorMessage.value = error.message
        } finally {
            loading.value = false
        }
    }

    return {
        login,
        logout,
        changePassword,
        loading,
        errorMessage,
        errors,
    }
}
