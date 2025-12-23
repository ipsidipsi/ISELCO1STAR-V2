<template>
  <div class="comment-input">
    <div class="flex gap-3">
      <!-- User Avatar -->
      <div class="flex-shrink-0">
        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-teal-400 to-blue-500 flex items-center justify-center text-white font-bold">
          {{ userInitials }}
        </div>
      </div>

      <!-- Input Area -->
      <div class="flex-1">
        <textarea
          v-model="message"
          :placeholder="editMode ? 'Edit your comment...' : 'Write a comment...'"
          rows="3"
          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal focus:border-transparent resize-none"
          @keydown.ctrl.enter="submit"
          @keydown.meta.enter="submit"
        ></textarea>

        <!-- Character Counter & Actions -->
        <div class="flex items-center justify-between mt-2">
          <span class="text-xs text-gray-500">
            {{ message.length }} / 5000 characters
            <span v-if="!editMode" class="ml-2 text-gray-400">Ctrl+Enter to send</span>
          </span>

          <div class="flex gap-2">
            <button
              v-if="editMode"
              @click="$emit('cancel')"
              type="button"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
            >
              Cancel
            </button>
            <button
              @click="submit"
              :disabled="!canSubmit"
              type="button"
              class="px-4 py-2 text-sm font-medium text-white bg-teal hover:bg-teal-600 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              <ion-spinner v-if="submitting" name="crescent" class="w-4 h-4"></ion-spinner>
              <span>{{ editMode ? 'Update' : 'Send' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { IonSpinner } from '@ionic/vue'
import { useAuthStore } from '@/stores/auth'

const props = defineProps<{
  submitting: boolean
  editMode: boolean
  editText?: string
}>()

const emit = defineEmits<{
  (e: 'submit', message: string): void
  (e: 'cancel'): void
}>()

const authStore = useAuthStore()

const message = ref('')

const userInitials = computed(() => {
  const user = authStore.user
  if (!user) return '??'
  const name = user.employee_name || user.username
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .substring(0, 2)
})

const canSubmit = computed(() => {
  return message.value.trim().length > 0 && 
         message.value.length <= 5000 && 
         !props.submitting
})

// Watch for edit mode changes
watch(() => props.editText, (newVal) => {
  if (newVal !== undefined) {
    message.value = newVal
  }
}, { immediate: true })

// Reset after successful submission
watch(() => props.submitting, (newVal, oldVal) => {
  if (oldVal && !newVal && !props.editMode) {
    message.value = ''
  }
})

function submit() {
  if (canSubmit.value) {
    emit('submit', message.value.trim())
    if (!props.editMode) {
      message.value = ''
    }
  }
}
</script>
