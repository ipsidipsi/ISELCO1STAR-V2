<template>
  <ion-page>
    <ion-content  :fullscreen="true" class="bg-gray-50">
      <div class="flex items-center justify-center min-h-screen px-4">
        <div class="w-full max-w-md">
          <!-- Logo/Header -->
          <div class="text-center mb-8">
            <div class="w-20 h-20 mx-auto mb-4 bg-teal rounded-full flex items-center justify-center">
              <ion-icon :icon="ticketOutline" class="text-4xl text-white"></ion-icon>
            </div>
            <h1 class="text-3xl font-bold text-navy-700">ISELCO-I STAR</h1>
            <p class="text-gray-600 mt-2">Ticketing System</p>
          </div>

          <!-- Login Card -->
          <div class="bg-white rounded-2xl shadow-lg p-8">
            <h2 class="text-2xl font-semibold text-navy-700 mb-6">Sign In</h2>

            <form @submit.prevent="handleLogin">
              <!-- Login Field -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Username or Mobile Number
                </label>
                <ion-input
                  v-model="loginValue"
                  type="text"
                  placeholder="Enter username or mobile number"
                  class="border border-gray-300 rounded-lg px-4 py-3 w-full focus:ring-2 focus:ring-teal focus:border-transparent"
                  :class="{ 'border-red-500': errors.login }"
                ></ion-input>
                <p v-if="errors.login" class="text-red-500 text-sm mt-1">{{ errors.login }}</p>
              </div>

              <!-- Password Field -->
              <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Password
                </label>
                <ion-input
                  v-model="password"
                  :type="showPassword ? 'text' : 'password'"
                  placeholder="Enter your password"
                  class="border border-gray-300 rounded-lg px-4 py-3 w-full focus:ring-2 focus:ring-teal focus:border-transparent"
                  :class="{ 'border-red-500': errors.password }"
                ></ion-input>
                <p v-if="errors.password" class="text-red-500 text-sm mt-1">{{ errors.password }}</p>
              </div>

              <!-- Show Password Toggle -->
              <div class="flex items-center mb-6">
                <input
                  type="checkbox"
                  id="showPassword"
                  v-model="showPassword"
                  class="w-4 h-4 text-teal border-gray-300 rounded focus:ring-teal"
                />
                <label for="showPassword" class="ml-2 text-sm text-gray-700">
                  Show password
                </label>
              </div>

              <!-- Error Message -->
              <div v-if="errorMessage" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-700 text-sm">{{ errorMessage }}</p>
              </div>

              <!-- Login Button -->
              <button
                type="submit"
                :disabled="loading"
                class="w-full bg-teal hover:bg-teal-600 text-white font-semibold py-3 px-4 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="!loading">Sign In</span>
                <ion-spinner v-else name="crescent" class="w-5 h-5"></ion-spinner>
              </button>
            </form>

            <!-- Forgot Password -->
            <div class="mt-6 text-center">
              <button
                @click="openForgotPassword"
                class="text-sm text-teal hover:text-teal-700 font-medium"
              >
                Forgot Password?
              </button>
            </div>
          </div>

          <!-- Footer -->
          <div class="text-center mt-6 text-sm text-gray-600">
            © 2025 ISELCO-I. All rights reserved.
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { IonPage, IonContent, IonInput, IonIcon, IonSpinner } from '@ionic/vue'
import { ticketOutline } from 'ionicons/icons'
import { useAuth } from '@/composables/useAuth'

// Composable for auth logic
const { login, loading, errorMessage, errors } = useAuth()

// Form state
const loginValue = ref('')
const password = ref('')
const showPassword = ref(false)

async function handleLogin() {
  await login(loginValue.value, password.value)
}

function openForgotPassword() {
  // TODO: Implement forgot password modal
  console.log('Open forgot password modal')
}
</script>

<style scoped>
/* Custom input styling to match Tailwind */
ion-input {
  --background: white;
  --padding-start: 0;
  --padding-end: 0;
}
</style>
