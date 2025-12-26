<template>
  <div 
    :class="[
      'comment-bubble-wrapper',
      isMine ? 'mine' : 'theirs'
    ]"
  >
    <!-- Avatar (only for other users, on left) -->
    <div v-if="!isMine" class="avatar-wrapper">
      <div class="avatar">
        {{ getUserInitials(comment.user) }}
      </div>
    </div>

    <!-- Message Bubble -->
    <div class="bubble-container">
      <div 
        :class="[
          'message-bubble',
          isMine ? 'bubble-mine' : 'bubble-theirs'
        ]"
      >
        <!-- Sender name (only for other users) -->
        <div v-if="!isMine" class="sender-name">
          {{ comment.user.employee_name || comment.user.username }}
        </div>

        <!-- Message text -->
        <p class="message-text">{{ comment.message }}</p>

        <!-- Timestamp and edited indicator -->
        <div class="message-footer">
          <span class="timestamp">{{ formatTime(comment.created_at) }}</span>
          <span v-if="comment.updated_at !== comment.created_at" class="edited-badge">
            (edited)
          </span>
        </div>
      </div>

      <!-- Action buttons (edit/delete) -->
      <div v-if="canEdit || canDelete" class="message-actions">
        <button
          v-if="canEdit"
          @click="$emit('edit', comment)"
          class="action-btn edit-btn"
          title="Edit message"
        >
          <ion-icon :icon="createOutline"></ion-icon>
        </button>
        <button
          v-if="canDelete"
          @click="$emit('delete', comment)"
          class="action-btn delete-btn"
          title="Delete message"
        >
          <ion-icon :icon="trashOutline"></ion-icon>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { IonIcon } from '@ionic/vue'
import { createOutline, trashOutline } from 'ionicons/icons'
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

const isMine = computed(() => {
  return props.comment.user_id === authStore.user?.id
})

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
  
  return then.toLocaleString('en-US', { 
    month: 'short', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    year: then.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
  })
}
</script>

<style scoped>
.comment-bubble-wrapper {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
  animation: slideIn 0.3s ease-out;
}

/* Left-aligned (other users) */
.comment-bubble-wrapper.theirs {
  justify-content: flex-start;
}

/* Right-aligned (my messages) */
.comment-bubble-wrapper.mine {
  justify-content: flex-end;
}

/* Avatar styling */
.avatar-wrapper {
  flex-shrink: 0;
}

.avatar {
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  background: linear-gradient(135deg, #14B8A6, #3B82F6);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.75rem;
  font-weight: 700;
}

/* Bubble container */
.bubble-container {
  display: flex;
  flex-direction: column;
  max-width: 70%;
  position: relative;
}

.mine .bubble-container {
  align-items: flex-end;
}

.theirs .bubble-container {
  align-items: flex-start;
}

/* Message bubble */
.message-bubble {
  padding: 0.75rem 1rem;
  border-radius: 1.125rem;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
  word-wrap: break-word;
  transition: transform 0.2s ease;
}

.message-bubble:hover {
  transform: scale(1.02);
}

/* My messages - right side, gradient */
.bubble-mine {
  background: linear-gradient(135deg, #14B8A6, #0D9488);
  color: white;
  border-bottom-right-radius: 0.25rem;
}

/* Other users' messages - left side, light gray */
.bubble-theirs {
  background: #F3F4F6;
  color: #1F2937;
  border-bottom-left-radius: 0.25rem;
}

/* Sender name */
.sender-name {
  font-size: 0.75rem;
  font-weight: 600;
  color: #6B7280;
  margin-bottom: 0.25rem;
}

/* Message text */
.message-text {
  margin: 0;
  line-height: 1.5;
  font-size: 0.9375rem;
  white-space: pre-wrap;
}

.bubble-mine .message-text {
  color: white;
}

.bubble-theirs .message-text {
  color: #1F2937;
}

/* Message footer */
.message-footer {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.25rem;
  font-size: 0.6875rem;
}

.timestamp {
  opacity: 0.7;
}

.bubble-mine .timestamp {
  color: rgba(255, 255, 255, 0.9);
}

.bubble-theirs .timestamp {
  color: #6B7280;
}

.edited-badge {
  font-style: italic;
  opacity: 0.6;
}

/* Action buttons */
.message-actions {
  display: flex;
  gap: 0.25rem;
  margin-top: 0.25rem;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.comment-bubble-wrapper:hover .message-actions {
  opacity: 1;
}

.action-btn {
  background: transparent;
  border: none;
  padding: 0.25rem;
  cursor: pointer;
  color: #6B7280;
  font-size: 1rem;
  transition: color 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.action-btn:hover {
  color: #1F2937;
}

.edit-btn:hover {
  color: #3B82F6;
}

.delete-btn:hover {
  color: #EF4444;
}

/* Animation */
@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive */
@media (max-width: 640px) {
  .bubble-container {
    max-width: 85%;
  }
}
</style>
