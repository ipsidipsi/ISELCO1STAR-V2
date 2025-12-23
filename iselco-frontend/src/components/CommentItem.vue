<template>
  <div class="comment-item p-4 bg-white/60 rounded-lg border border-gray-200 hover:border-teal/30 transition-colors">
    <div class="flex items-start gap-3">
      <!-- User Avatar -->
      <div class="flex-shrink-0">
        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-teal-400 to-blue-500 flex items-center justify-center text-white font-bold">
          {{ getUserInitials(comment.user) }}
        </div>
      </div>

      <!-- Comment Content -->
      <div class="flex-1 min-w-0">
        <!-- Header -->
        <div class="flex items-center gap-2 mb-1">
          <span class="font-semibold text-navy-700">
            {{ comment.user.employee_name || comment.user.username }}
          </span>
          <span class="text-xs text-gray-500">
            {{ formatTime(comment.created_at) }}
          </span>
          <span v-if="comment.updated_at !== comment.created_at" class="text-xs text-gray-400 italic">
            (edited)
          </span>
        </div>

        <!-- Message -->
        <p class="text-gray-800 leading-relaxed whitespace-pre-wrap">{{ comment.message }}</p>

        <!-- Actions -->
        <div v-if="canEdit || canDelete" class="flex gap-2 mt-2">
          <button
            v-if="canEdit"
            @click="$emit('edit', comment)"
            class="text-xs text-blue-600 hover:text-blue-700 font-medium"
          >
            Edit
          </button>
          <button
            v-if="canDelete"
            @click="$emit('delete', comment)"
            class="text-xs text-red-600 hover:text-red-700 font-medium"
          >
            Delete
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import type { Comment } from '@/composables/useComments'

const props = defineProps<{
  comment: Comment
}>()

defineEmits<{
  (e: 'edit', comment: Comment): void
  (e: 'delete', comment: Comment): void
}>()

const authStore = useAuthStore()

const canEdit = computed(() => {
  return props.comment.user_id === authStore.user?.id
})

const canDelete = computed(() => {
  const isOwner = props.comment.user_id === authStore.user?.id
  const isAdmin = authStore.user?.roles?.some(r => r.slug === 'admin')
  return isOwner || isAdmin
})

function getUserInitials(user: any) {
  const name = user.employee_name || user.username
  return name
    .split(' ')
    .map((n: string) => n[0])
    .join('')
    .toUpperCase()
    .substring(0, 2)
}

function formatTime(date: string) {
  const now = new Date()
  const then = new Date(date)
  const diff = now.getTime() - then.getTime()
  
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  const days = Math.floor(diff / 86400000)
  
  if (minutes < 1) return 'Just now'
  if (minutes < 60) return `${minutes}m ago`
  if (hours < 24) return `${hours}h ago`
  if (days < 7) return `${days}d ago`
  
  return then.toLocaleDateString('en-US', { 
    month: 'short', 
    day: 'numeric',
    year: then.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
  })
}
</script>

<style scoped>
.comment-item {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
