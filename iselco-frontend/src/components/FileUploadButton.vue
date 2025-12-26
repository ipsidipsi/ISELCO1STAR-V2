<template>
  <div class="file-upload-button">
    <!-- Hidden file input -->
    <input
      ref="fileInput"
      type="file"
      :accept="acceptedTypes"
      :multiple="multiple"
      @change="handleFileSelect"
      style="display: none"
    />

    <!-- Camera input (for mobile devices) -->
    <input
      ref="cameraInput"
      type="file"
      accept="image/*"
      capture="environment"
      @change="handleCameraCapture"
      style="display: none"
    />

    <!-- Button Group -->
    <div class="button-group">
      <!-- File Upload Button -->
      <button
        @click="openFilePicker"
        :disabled="disabled || uploading"
        class="upload-btn file-btn"
        :title="buttonTitle"
      >
        <ion-icon :icon="disabled ? closeCircleOutline : attachOutline"></ion-icon>
        <span v-if="showLabel" class="btn-label">{{ label }}</span>
      </button>

      <!-- Camera Button -->
      <button
        v-if="showCameraButton"
        @click="openCamera"
        :disabled="disabled || uploading"
        class="upload-btn camera-btn"
        title="Take Photo"
      >
        <ion-icon :icon="cameraOutline"></ion-icon>
      </button>
    </div>

    <!-- Upload Progress -->
    <div v-if="uploading" class="upload-progress">
      <div class="progress-bar">
        <div class="progress-fill" :style="{ width: uploadProgress + '%' }"></div>
      </div>
      <span class="progress-text">{{ uploadProgress }}%</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { IonIcon } from '@ionic/vue'
import { attachOutline, cameraOutline, closeCircleOutline } from 'ionicons/icons'
import { useAttachments } from '@/composables/useAttachments'
import { useNotification } from '@/composables/useNotification'

const props = defineProps<{
  label?: string
  showLabel?: boolean
  multiple?: boolean
  disabled?: boolean
  showCameraButton?: boolean
  uploading?: boolean
  uploadProgress?: number
}>()

const emit = defineEmits<{
  filesSelected: [files: File[]]
}>()

const { validateFile, captureFromCamera } = useAttachments()
const { showError } = useNotification()

const fileInput = ref<HTMLInputElement | null>(null)
const cameraInput = ref<HTMLInputElement | null>(null)

const acceptedTypes = computed(() => {
  return 'image/jpeg,image/png,image/gif,image/bmp,image/webp,application/pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar'
})

const buttonTitle = computed(() => {
  return props.disabled ? 'File upload disabled' : (props.multiple ? 'Attach files' : 'Attach file')
})

function openFilePicker() {
  fileInput.value?.click()
}

function openCamera() {
  // Check if device supports camera
  if ('mediaDevices' in navigator && 'getUserMedia' in navigator.mediaDevices) {
    // Use HTML5 camera input for better mobile support
    cameraInput.value?.click()
  } else {
    showError('Camera Not Available', 'Your device does not support camera access')
  }
}

async function handleFileSelect(event: Event) {
  const target = event.target as HTMLInputElement
  const files = Array.from(target.files || [])

  if (files.length === 0) return

  // Validate each file
  const validFiles: File[] = []
  for (const file of files) {
    const validation = validateFile(file)
    if (validation.valid) {
      validFiles.push(file)
    } else {
      await showError('Invalid File', validation.error || 'File validation failed')
    }
  }

  if (validFiles.length > 0) {
    emit('filesSelected', validFiles)
  }

  // Reset input
  target.value = ''
}

async function handleCameraCapture(event: Event) {
  const target = event.target as HTMLInputElement
  const files = Array.from(target.files || [])

  if (files.length > 0) {
    emit('filesSelected', files)
  }

  // Reset input
  target.value = ''
}

// Expose method for programmatic camera capture (advanced mode)
async function capturePhoto() {
  const file = await captureFromCamera()
  if (file) {
    emit('filesSelected', [file])
  }
}

defineExpose({ capturePhoto })
</script>

<style scoped>
.file-upload-button {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.button-group {
  display: flex;
  gap: 0.5rem;
}

.upload-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 0.5rem;
  cursor: pointer;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.2s ease;
  background: linear-gradient(135deg, #14B8A6, #0D9488);
  color: white;
  box-shadow: 0 2px 8px rgba(20, 184, 166, 0.3);
}

.upload-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(20, 184, 166, 0.4);
}

.upload-btn:active:not(:disabled) {
  transform: translateY(0);
}

.upload-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  background: #9CA3AF;
}

.upload-btn ion-icon {
  font-size: 1.25rem;
}

.file-btn {
  flex: 1;
}

.camera-btn {
  background: linear-gradient(135deg, #3B82F6, #2563EB);
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.camera-btn:hover:not(:disabled) {
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.btn-label {
  white-space: nowrap;
}

.upload-progress {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.progress-bar {
  flex: 1;
  height: 0.5rem;
  background: rgba(156, 163, 175, 0.2);
  border-radius: 0.25rem;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(135deg, #14B8A6, #0D9488);
  transition: width 0.3s ease;
  border-radius: 0.25rem;
}

.progress-text {
  font-size: 0.75rem;
  font-weight: 600;
  color: #14B8A6;
  min-width: 3rem;
  text-align: right;
}

/* Responsive */
@media (max-width: 640px) {
  .btn-label {
    display: none;
  }

  .upload-btn {
    padding: 0.5rem;
    justify-content: center;
  }
}
</style>
