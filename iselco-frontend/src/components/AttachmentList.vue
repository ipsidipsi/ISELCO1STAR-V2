<template>
  <div class="attachment-list">
    <!-- Loading State -->
    <div v-if="loading" class="text-center py-4">
      <ion-spinner name="crescent" class="text-teal"></ion-spinner>
    </div>

    <!-- Empty State -->
    <div v-else-if="attachments.length === 0 && showEmptyState" class="empty-state">
      <ion-icon :icon="documentAttachOutline" class="empty-icon"></ion-icon>
      <p class="empty-text">No attachments</p>
    </div>

    <!-- Attachments Grid/List -->
    <div v-else :class="layout === 'grid' ? 'attachments-grid' : 'attachments-list'">
      <div
        v-for="attachment in attachments"
        :key="attachment.id"
        class="attachment-item"
      >
        <!-- File Icon & Info -->
        <div class="attachment-info">
          <ion-icon :icon="getIcon(attachment.mime_type)" class="file-icon"></ion-icon>
          <div class="file-details">
            <p class="file-name" :title="attachment.file_name">{{ attachment.file_name }}</p>
            <p class="file-meta">
              {{ formatSize(attachment.file_size) }}
              <span v-if="attachment.uploader" class="uploader">
                • {{ attachment.uploader.employee_name || attachment.uploader.username }}
              </span>
            </p>
          </div>
        </div>

        <!-- Actions -->
        <div class="attachment-actions">
          <!-- Preview (images only) -->
          <button
            v-if="isImageFile(attachment.mime_type)"
            @click="$emit('preview', attachment)"
            class="action-btn preview-btn"
            title="Preview"
          >
            <ion-icon :icon="eyeOutline"></ion-icon>
          </button>

          <!-- Download -->
          <button
            @click="$emit('download', attachment)"
            class="action-btn download-btn"
            title="Download"
          >
            <ion-icon :icon="downloadOutline"></ion-icon>
          </button>

          <!-- Delete (only for uploader or admin) -->
          <button
            v-if="canDelete(attachment)"
            @click="$emit('delete', attachment)"
            class="action-btn delete-btn"
            title="Delete"
          >
            <ion-icon :icon="trashOutline"></ion-icon>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { IonIcon, IonSpinner } from '@ionic/vue'
import {
  documentAttachOutline,
  imageOutline,
  documentTextOutline,
  documentOutline,
  gridOutline,
  archiveOutline,
  eyeOutline,
  downloadOutline,
  trashOutline,
} from 'ionicons/icons'
import { useAttachments, type Attachment } from '@/composables/useAttachments'
import { useAuthStore } from '@/stores/auth'

const props = defineProps<{
  attachments: Attachment[]
  loading?: boolean
  showEmptyState?: boolean
  layout?: 'grid' | 'list'
  allowDelete?: boolean
}>()

defineEmits<{
  preview: [attachment: Attachment]
  download: [attachment: Attachment]
  delete: [attachment: Attachment]
}>()

const { getFileIcon, formatFileSize, isImage } = useAttachments()
const authStore = useAuthStore()

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

function isImageFile(mimeType: string) {
  return isImage(mimeType)
}

function canDelete(attachment: Attachment): boolean {
  if (!props.allowDelete) return false
  const user = authStore.user
  if (!user) return false
  const isSuperadmin = user.roles?.some((r: any) => r.name === 'superadmin') || false
  return user.id === attachment.uploaded_by || isSuperadmin
}
</script>

<style scoped>
.attachment-list {
  width: 100%;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  color: #9CA3AF;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 0.5rem;
  opacity: 0.5;
}

.empty-text {
  font-size: 0.875rem;
  margin: 0;
}

/* List Layout */
.attachments-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

/* Grid Layout */
.attachments-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1rem;
}

.attachment-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem;
  background: rgba(255, 255, 255, 0.5);
  border: 1px solid rgba(0, 0, 0, 0.05);
  border-radius: 0.5rem;
  transition: all 0.2s ease;
}

.attachment-item:hover {
  background: rgba(255, 255, 255, 0.8);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.attachment-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
  min-width: 0; /* Allow text truncation */
}

.file-icon {
  font-size: 2rem;
  color: #14B8A6;
  flex-shrink: 0;
}

.file-details {
  flex: 1;
  min-width: 0;
}

.file-name {
  margin: 0;
  font-size: 0.875rem;
  font-weight: 500;
  color: #1F2937;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.file-meta {
  margin: 0.25rem 0 0;
  font-size: 0.75rem;
  color: #6B7280;
}

.uploader {
  color: #9CA3AF;
}

.attachment-actions {
  display: flex;
  gap: 0.25rem;
  flex-shrink: 0;
}

.action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border: none;
  background: transparent;
  border-radius: 0.375rem;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 1.25rem;
}

.preview-btn {
  color: #3B82F6;
}

.preview-btn:hover {
  background: rgba(59, 130, 246, 0.1);
}

.download-btn {
  color: #14B8A6;
}

.download-btn:hover {
  background: rgba(20, 184, 166, 0.1);
}

.delete-btn {
  color: #EF4444;
}

.delete-btn:hover {
  background: rgba(239, 68, 68, 0.1);
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .attachment-item {
    background: rgba(31, 41, 55, 0.5);
    border-color: rgba(255, 255, 255, 0.1);
  }

  .attachment-item:hover {
    background: rgba(31, 41, 55, 0.8);
  }

  .file-name {
    color: #F9FAFB;
  }

  .file-meta {
    color: #9CA3AF;
  }
}
</style>
