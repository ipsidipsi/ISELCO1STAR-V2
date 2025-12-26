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
        <p v-if="comment.message" class="message-text">{{ comment.message }}</p>

        <!-- Attachments -->
        <div v-if="comment.attachments && comment.attachments.length > 0" class="attachments-container">
          <div
            v-for="attachment in comment.attachments"
            :key="attachment.id"
            class="attachment-item"
          >
            <ion-icon 
              :icon="getIcon(attachment.mime_type)" 
              class="attachment-icon"
            ></ion-icon>
            <div class="attachment-info">
              <span class="attachment-name" :title="attachment.file_name">
                {{ truncateFileName(attachment.file_name) }}
              </span>
              <span class="attachment-size">{{ formatSize(attachment.file_size) }}</span>
            </div>
            <div class="attachment-actions">
              <button
                v-if="isImage(attachment.mime_type)"
                @click="handlePreview(attachment)"
                class="attach-btn preview"
                title="Preview"
              >
                <ion-icon :icon="eyeOutline"></ion-icon>
              </button>
              <button
                @click="handleDownload(attachment)"
                class="attach-btn download"
                title="Download"
              >
                <ion-icon :icon="downloadOutline"></ion-icon>
              </button>
              <button
                v-if="canDeleteAttachment(attachment)"
                @click="handleDeleteAttachment(attachment)"
                class="attach-btn delete"
                title="Delete"
              >
                <ion-icon :icon="trashOutline"></ion-icon>
              </button>
            </div>
          </div>
        </div>

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
import { 
  createOutline, 
  trashOutline, 
  downloadOutline, 
  eyeOutline,
  imageOutline,
  documentTextOutline,
  documentOutline,
  gridOutline,
  archiveOutline,
  documentAttachOutline
} from 'ionicons/icons'
import { useAuthStore } from '@/stores/auth'
import { useAttachments } from '@/composables/useAttachments'
import { useNotification } from '@/composables/useNotification'
import type { Comment } from '@/composables/useComments'

const props = defineProps<{
  comment: Comment
}>()

defineEmits<{
  (e: 'edit', comment: Comment): void
  (e: 'delete', comment: Comment): void
}>()

const authStore = useAuthStore()
const { downloadAttachment, deleteAttachment, getFileIcon, formatFileSize, isImage } = useAttachments()
const { showConfirm } = useNotification()

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

function canDeleteAttachment(attachment: any): boolean {
  const isUploader = attachment.uploaded_by === authStore.user?.id
  const isSuperadmin = authStore.user?.roles?.some((r: any) => r.name === 'superadmin') || false
  return isUploader || isSuperadmin
}

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

function getIcon(mimeType: string) {
  const iconName = getFileIcon(mimeType)
  const icons: Record<string, any> = {
    'image-outline': imageOutline,
    'document-text-outline': documentTextOutline,
    'document-outline': documentOutline,
    'grid-outline': gridOutline,
    'archive-outline': archiveOutline,
    'document-attach-outline': documentAttachOutline,
  }
  return icons[iconName] || documentAttachOutline
}

function formatSize(bytes: number) {
  return formatFileSize(bytes)
}

function truncateFileName(name: string, maxLength: number = 25): string {
  if (name.length <= maxLength) return name
  const ext = name.split('.').pop() || ''
  const nameWithoutExt = name.substring(0, name.length - ext.length - 1)
  const truncated = nameWithoutExt.substring(0, maxLength - ext.length - 4)
  return `${truncated}...${ext}`
}

async function handleDownload(attachment: any) {
  await downloadAttachment(attachment)
}

function handlePreview(attachment: any) {
  // Open image in a new window/tab for preview
  const baseUrl = import.meta.env.VITE_API_URL?.replace('/api', '') || 'http://localhost:8000'
  const imageUrl = `${baseUrl}/storage/${attachment.file_path}`
  window.open(imageUrl, '_blank')
}

async function handleDeleteAttachment(attachment: any) {
  const result = await showConfirm(
    'Delete Attachment?',
    `Are you sure you want to delete "${attachment.file_name}"?`,
    'Delete',
    'Cancel'
  )
  
  if (result.isConfirmed) {
    await deleteAttachment(attachment.id)
    // Refresh the page or emit an event to reload comments
    window.location.reload()
  }
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

/* Attachments */
.attachments-container {
  margin-top: 0.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.attachment-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 0.5rem;
  transition: background 0.2s ease;
}

.bubble-theirs .attachment-item {
  background: rgba(0, 0, 0, 0.05);
}

.attachment-item:hover {
  background: rgba(255, 255, 255, 0.3);
}

.bubble-theirs .attachment-item:hover {
  background: rgba(0, 0, 0, 0.08);
}

.attachment-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.bubble-mine .attachment-icon {
  color: rgba(255, 255, 255, 0.9);
}

.bubble-theirs .attachment-icon {
  color: #14B8A6;
}

.attachment-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.attachment-name {
  font-size: 0.8125rem;
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.bubble-mine .attachment-name {
  color: white;
}

.bubble-theirs .attachment-name {
  color: #1F2937;
}

.attachment-size {
  font-size: 0.6875rem;
  opacity: 0.7;
}

.attachment-actions {
  display: flex;
  gap: 0.25rem;
  flex-shrink: 0;
}

.attach-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 1.75rem;
  height: 1.75rem;
  border: none;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 0.375rem;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 1rem;
}

.bubble-mine .attach-btn {
  color: white;
}

.bubble-theirs .attach-btn {
  color: #1F2937;
  background: rgba(0, 0, 0, 0.05);
}

.attach-btn:hover {
  transform: scale(1.1);
}

.attach-btn.preview:hover {
  background: rgba(59, 130, 246, 0.3);
}

.attach-btn.download:hover {
  background: rgba(20, 184, 166, 0.3);
}

.attach-btn.delete:hover {
  background: rgba(239, 68, 68, 0.3);
  color: #EF4444;
}

/* Responsive */
@media (max-width: 640px) {
  .bubble-container {
    max-width: 85%;
  }
}
</style>
