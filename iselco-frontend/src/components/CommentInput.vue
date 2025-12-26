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

        <!-- Selected Files Preview -->
        <div v-if="selectedFiles.length > 0" class="selected-files">
          <div
            v-for="(fileItem, index) in selectedFiles"
            :key="fileItem.id"
            class="file-chip-container"
          >
            <!-- Remove button (shows on hover when ready or failed) -->
            <button
              v-if="(fileItem.ready || fileItem.failed) && !fileItem.uploading && !fileItem.uploaded"
              @click="removeFile(index)"
              class="file-remove-btn"
              type="button"
              title="Remove file"
            >
              <ion-icon :icon="closeOutline"></ion-icon>
            </button>

            <div class="file-chip">
              <ion-icon 
                :icon="getFileIconForType(fileItem.file.type)" 
                class="file-chip-icon"
              ></ion-icon>
              <div class="file-info">
                <span class="file-chip-name">{{ truncateFileName(fileItem.file.name) }}</span>
                <span class="file-chip-size">{{ formatBytes(fileItem.file.size) }}</span>
                
                <!-- Progress bar (only during upload) -->
                <div v-if="fileItem.uploading" class="file-progress-bar">
                  <div 
                    class="file-progress-fill" 
                    :style="{ width: fileItem.progress + '%' }"
                  ></div>
                </div>
                
                <!-- Upload status text -->
                <span v-if="fileItem.uploading" class="upload-status">
                  Uploading {{ fileItem.progress }}%
                </span>
                <span v-else-if="fileItem.failed" class="upload-status failed">
                  Upload failed
                </span>
                <span v-else-if="fileItem.uploaded" class="upload-status sent">
                  Sent
                </span>
                <span v-else-if="fileItem.ready" class="upload-status ready">
                  Ready to send
                </span>
              </div>

              <!-- Status badges (bottom-right corner) -->
              
              <!-- Green checkmark when ready to send -->
              <div v-if="fileItem.ready && !fileItem.uploading && !fileItem.uploaded" class="status-badge ready">
                <ion-icon :icon="checkmarkCircleOutline"></ion-icon>
              </div>
              
              <!-- Blue checkmark when sent successfully -->
              <div v-if="fileItem.uploaded" class="status-badge sent">
                <ion-icon :icon="checkmarkCircleOutline"></ion-icon>
              </div>
              
              <!-- Red X when failed -->
              <div v-if="fileItem.failed" class="status-badge error">
                <ion-icon :icon="closeCircleOutline"></ion-icon>
              </div>
            </div>
          </div>
        </div>

        <!-- Character Counter & Actions -->
        <div class="flex items-center justify-between mt-2">
          <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500">
              {{ message.length }} / 5000 characters
              <span v-if="!editMode" class="ml-2 text-gray-400">Ctrl+Enter to send</span>
            </span>
            
            <!-- File Upload & Camera Buttons (not in edit mode) -->
            <div v-if="!editMode" class="upload-buttons">
              <FileUploadButton
                :multiple="true"
                :show-camera-button="true"
                :uploading="uploading"
                :upload-progress="uploadProgress"
                @files-selected="handleFilesSelected"
              />
            </div>
          </div>

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
import { IonSpinner, IonIcon } from '@ionic/vue'
import { 
  closeCircleOutline, 
  closeOutline,
  checkmarkCircleOutline,
  imageOutline, 
  documentTextOutline, 
  documentOutline, 
  archiveOutline, 
  documentAttachOutline 
} from 'ionicons/icons'
import { useAuthStore } from '@/stores/auth'
import FileUploadButton from './FileUploadButton.vue'

interface SelectedFileItem {
  file: File
  id: string
  preview?: string
  ready?: boolean      // File is loaded locally, ready to send
  uploading?: boolean  // Currently uploading to server
  uploaded?: boolean   // Successfully sent to server
  failed?: boolean     // Upload failed
  progress?: number
}

const props = defineProps<{
  submitting: boolean
  editMode: boolean
  editText?: string
}>()

const emit = defineEmits<{
  (e: 'submit', data: { message: string; files: SelectedFileItem[] }): void
  (e: 'cancel'): void
}>()

const authStore = useAuthStore()

const message = ref('')
const selectedFiles = ref<SelectedFileItem[]>([])
const uploading = ref(false)
const uploadProgress = ref(0)

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

watch(message, (newValue) => {
  // If user starts typing and there are uploaded files, clear them
  if (newValue && selectedFiles.value.some(f => f.uploaded)) {
    clearFiles()
  }
})

const canSubmit = computed(() => {
  return (message.value.trim().length > 0 || selectedFiles.value.length > 0) &&
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
    selectedFiles.value = []
  }
})

function handleFilesSelected(files: File[]) {
  for (const file of files) {
    selectedFiles.value.push({
      file,
      id: Math.random().toString(36).substring(7),
      preview: file.type.startsWith('image/') ? URL.createObjectURL(file) : undefined,
      ready: true,        // ✅ File loaded locally, ready to send
      uploading: false,
      uploaded: false,
      failed: false,
      progress: 0
    })
  }
}

function removeFile(index: number) {
  const file = selectedFiles.value[index]
  if (file.preview) {
    URL.revokeObjectURL(file.preview)
  }
  selectedFiles.value.splice(index, 1)
}

function formatBytes(bytes: number): string {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 10) / 10 + ' ' + sizes[i]
}

function truncateFileName(name: string, maxLength: number = 20): string {
  if (name.length <= maxLength) return name
  const ext = name.split('.').pop() || ''
  const nameWithoutExt = name.substring(0, name.length - ext.length - 1)
  const truncated = nameWithoutExt.substring(0, maxLength - ext.length - 4)
  return `${truncated}...${ext}`
}

function getFileIconForType(mimeType: string) {
  if (mimeType.startsWith('image/')) return imageOutline
  if (mimeType === 'application/pdf') return documentTextOutline
  if (mimeType.includes('word') || mimeType.includes('document')) return documentOutline
  if (mimeType.includes('zip') || mimeType.includes('rar')) return archiveOutline
  return documentAttachOutline
}

function submit() {
  if (canSubmit.value) {
    emit('submit', { 
      message: message.value.trim(), 
      files: selectedFiles.value
    })
    
    if (!props.editMode) {
      message.value = ''
      // Don't clear files immediately - let parent handle it after upload completes
      // This allows users to see the upload progress and completion checkmarks
    }
  }
}

// Expose method to clear files from parent
function clearFiles() {
  selectedFiles.value.forEach(f => {
    if (f.preview) URL.revokeObjectURL(f.preview)
  })
  selectedFiles.value = []
}

defineExpose({ clearFiles })
</script>

<style scoped>
.selected-files {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-top: 0.75rem;
  padding: 0.75rem;
  background: rgba(249, 250, 251, 0.5);
  border-radius: 0.5rem;
}

.file-chip-container {
  position: relative;
  min-width: 120px;
}

.file-chip {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem;
  background: white;
  border: 2px solid #E5E7EB;
  border-radius: 0.75rem;
  transition: all 0.2s ease;
}

.file-chip:hover {
  border-color: #14B8A6;
  box-shadow: 0 2px 8px rgba(20, 184, 166, 0.2);
}

.file-chip-icon {
  color: #14B8A6;
  font-size: 2.5rem;
}

.file-info {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  width: 100%;
}

.file-chip-name {
  color: #1F2937;
  font-weight: 500;
  font-size: 0.8125rem;
  text-align: center;
  word-break: break-word;
}

.file-chip-size {
  color: #6B7280;
  font-size: 0.6875rem;
}

/* Remove button (top-right corner, shows on hover) */
.file-remove-btn {
  position: absolute;
  top: -8px;
  right: -8px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  background: #6B7280;
  border: 2px solid white;
  border-radius: 50%;
  color: white;
  cursor: pointer;
  font-size: 1rem;
  z-index: 10;
  transition: all 0.2s ease;
  padding: 0;
  opacity: 0;
}

.file-chip-container:hover .file-remove-btn {
  opacity: 1;
}

.file-remove-btn:hover {
  transform: scale(1.15);
  background: #4B5563;
}

/* Status badges (bottom-right corner) */
.status-badge {
  position: absolute;
  bottom: 8px;
  right: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border: 2px solid white;
  border-radius: 50%;
  font-size: 1rem;
  z-index: 10;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.status-badge.ready {
  background: #10B981;
  color: white;
}

.status-badge.sent {
  background: #3B82F6;
  color: white;
}

.status-badge.error {
  background: #EF4444;
  color: white;
}

/* Progress bar below file icon */
.file-progress-bar {
  width: 100%;
  height: 4px;
  background: #E5E7EB;
  border-radius: 2px;
  overflow: hidden;
  margin-top: 0.25rem;
}

.file-progress-fill {
  height: 100%;
  background: linear-gradient(135deg, #14B8A6, #0D9488);
  transition: width 0.3s ease;
  border-radius: 2px;
}

/* Upload status text */
.upload-status {
  font-size: 0.6875rem;
  color: #6B7280;
  margin-top: 0.25rem;
}

.upload-status.ready {
  color: #10B981;
  font-weight: 600;
}

.upload-status.sent {
  color: #3B82F6;
  font-weight: 600;
}

.upload-status.failed {
  color: #EF4444;
  font-weight: 600;
}

.upload-buttons {
  display: flex;
  gap: 0.5rem;
}
</style>
