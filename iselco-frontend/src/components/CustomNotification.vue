<template>
  <!-- Toast Notification -->
  <Transition name="toast-slide">
    <div
      v-if="visible && type === 'toast'"
      class="custom-toast"
      :class="[`toast-${variant}`, position]"
    >
      <div class="toast-content">
        <ion-icon :icon="icon" class="toast-icon"></ion-icon>
        <div class="toast-text">
          <h3 v-if="title" class="toast-title">{{ title }}</h3>
          <p v-if="message" class="toast-message">{{ message }}</p>
        </div>
        <ion-icon 
          v-if="showClose"
          :icon="closeOutline" 
          class="toast-close" 
          @click="close"
        ></ion-icon>
      </div>
      <div v-if="timer" class="toast-progress" :style="{ width: progress + '%' }"></div>
    </div>
  </Transition>

  <!-- Modal Notification -->
  <Transition name="modal-fade">
    <div v-if="visible && type === 'modal'" class="custom-modal-backdrop" @click="handleBackdropClick">
      <div class="custom-modal" :class="`modal-${variant}`" @click.stop>
        <div class="modal-header">
          <ion-icon :icon="icon" class="modal-icon"></ion-icon>
        </div>
        <div class="modal-body">
          <h2 v-if="title" class="modal-title">{{ title }}</h2>
          <p v-if="message" class="modal-message">{{ message }}</p>
          <textarea
            v-if="inputType === 'textarea'"
            v-model="inputValue"
            :placeholder="inputPlaceholder"
            class="modal-input"
            rows="4"
          ></textarea>
        </div>
        <div class="modal-footer">
          <button
            v-if="showCancel"
            class="modal-btn modal-btn-cancel"
            @click="cancel"
          >
            {{ cancelText }}
          </button>
          <button
            class="modal-btn modal-btn-confirm"
            :class="`btn-${variant}`"
            @click="confirm"
          >
            {{ confirmText }}
          </button>
        </div>
      </div>
    </div>
  </Transition>

  <!-- Loading Overlay -->
  <Transition name="modal-fade">
    <div v-if="visible && type === 'loading'" class="custom-modal-backdrop">
      <div class="custom-loading">
        <ion-spinner name="crescent" class="loading-spinner"></ion-spinner>
        <h3 v-if="title" class="loading-title">{{ title }}</h3>
        <p v-if="message" class="loading-message">{{ message }}</p>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue'
import { IonIcon, IonSpinner } from '@ionic/vue'
import {
  checkmarkCircleOutline,
  alertCircleOutline,
  warningOutline,
  informationCircleOutline,
  closeOutline,
} from 'ionicons/icons'

interface Props {
  visible?: boolean
  type?: 'toast' | 'modal' | 'loading'
  variant?: 'success' | 'error' | 'warning' | 'info'
  title?: string
  message?: string
  position?: 'top-right' | 'top-center' | 'bottom-right' | 'bottom-center'
  timer?: number
  showClose?: boolean
  showCancel?: boolean
  confirmText?: string
  cancelText?: string
  inputType?: 'textarea' | null
  inputPlaceholder?: string
}

const props = withDefaults(defineProps<Props>(), {
  visible: false,
  type: 'toast',
  variant: 'info',
  position: 'top-right',
  timer: 3000,
  showClose: true,
  showCancel: false,
  confirmText: 'OK',
  cancelText: 'Cancel',
  inputType: null,
  inputPlaceholder: '',
})

const emit = defineEmits<{
  close: []
  confirm: [value?: string]
  cancel: []
}>()

const progress = ref(100)
const inputValue = ref('')

const icon = computed(() => {
  const icons = {
    success: checkmarkCircleOutline,
    error: alertCircleOutline,
    warning: warningOutline,
    info: informationCircleOutline,
  }
  return icons[props.variant]
})

let progressInterval: NodeJS.Timeout | null = null

watch(() => props.visible, (newVal) => {
  if (newVal && props.type === 'toast' && props.timer) {
    startProgress()
  } else {
    stopProgress()
  }
})

onMounted(() => {
  if (props.visible && props.type === 'toast' && props.timer) {
    startProgress()
  }
})

function startProgress() {
  progress.value = 100
  const interval = 50
  const decrement = (100 / props.timer) * interval
  
  progressInterval = setInterval(() => {
    progress.value -= decrement
    if (progress.value <= 0) {
      stopProgress()
      close()
    }
  }, interval)
}

function stopProgress() {
  if (progressInterval) {
    clearInterval(progressInterval)
    progressInterval = null
  }
}

function close() {
  stopProgress()
  emit('close')
}

function confirm() {
  emit('confirm', inputValue.value)
}

function cancel() {
  emit('cancel')
}

function handleBackdropClick() {
  if (props.type === 'modal' && !props.showCancel) {
    // Allow closing by backdrop click if no cancel button
    close()
  }
}
</script>

<style scoped>
/* Toast Styles */
.custom-toast {
  position: fixed;
  z-index: 99999;
  min-width: 300px;
  max-width: 500px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  overflow: hidden;
}

.custom-toast.top-right {
  top: 20px;
  right: 20px;
}

.custom-toast.top-center {
  top: 20px;
  left: 50%;
  transform: translateX(-50%);
}

.custom-toast.bottom-right {
  bottom: 20px;
  right: 20px;
}

.custom-toast.bottom-center {
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
}

.toast-content {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
}

.toast-icon {
  font-size: 28px;
  flex-shrink: 0;
}

.toast-text {
  flex: 1;
}

.toast-title {
  font-size: 16px;
  font-weight: 600;
  margin: 0 0 4px 0;
  color: var(--ion-color-dark);
}

.toast-message {
  font-size: 14px;
  margin: 0;
  color: var(--ion-color-medium);
}

.toast-close {
  font-size: 20px;
  color: var(--ion-color-medium);
  cursor: pointer;
  flex-shrink: 0;
}

.toast-close:hover {
  color: var(--ion-color-dark);
}

.toast-progress {
  height: 3px;
  background: currentColor;
  transition: width 50ms linear;
}

/* Toast Variants */
.toast-success {
  border-left: 4px solid #10b981;
}

.toast-success .toast-icon {
  color: #10b981;
}

.toast-success .toast-progress {
  background: #10b981;
}

.toast-error {
  border-left: 4px solid #ef4444;
}

.toast-error .toast-icon {
  color: #ef4444;
}

.toast-error .toast-progress {
  background: #ef4444;
}

.toast-warning {
  border-left: 4px solid #f59e0b;
}

.toast-warning .toast-icon {
  color: #f59e0b;
}

.toast-warning .toast-progress {
  background: #f59e0b;
}

.toast-info {
  border-left: 4px solid #3b82f6;
}

.toast-info .toast-icon {
  color: #3b82f6;
}

.toast-info .toast-progress {
  background: #3b82f6;
}

/* Modal Backdrop */
.custom-modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 99998;
  padding: 20px;
}

/* Modal */
.custom-modal {
  background: white;
  border-radius: 16px;
  max-width: 500px;
  width: 100%;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
  overflow: hidden;
}

.modal-header {
  padding: 24px 24px 16px;
  text-align: center;
}

.modal-icon {
  font-size: 64px;
}

.modal-success .modal-icon {
  color: #10b981;
}

.modal-error .modal-icon {
  color: #ef4444;
}

.modal-warning .modal-icon {
  color: #f59e0b;
}

.modal-info .modal-icon {
  color: #3b82f6;
}

.modal-body {
  padding: 0 24px 24px;
  text-align: center;
}

.modal-title {
  font-size: 24px;
  font-weight: 700;
  margin: 0 0 12px 0;
  color: var(--ion-color-dark);
}

.modal-message {
  font-size: 16px;
  margin: 0;
  color: var(--ion-color-medium);
  line-height: 1.5;
}

.modal-input {
  width: 100%;
  margin-top: 16px;
  padding: 12px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 14px;
  font-family: inherit;
  resize: vertical;
}

.modal-input:focus {
  outline: none;
  border-color: var(--ion-color-primary);
}

.modal-footer {
  display: flex;
  gap: 12px;
  padding: 16px 24px 24px;
}

.modal-btn {
  flex: 1;
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.modal-btn-cancel {
  background: #f3f4f6;
  color: #6b7280;
}

.modal-btn-cancel:hover {
  background: #e5e7eb;
}

.modal-btn-confirm {
  color: white;
}

.modal-btn-confirm.btn-success {
  background: #10b981;
}

.modal-btn-confirm.btn-success:hover {
  background: #059669;
}

.modal-btn-confirm.btn-error {
  background: #ef4444;
}

.modal-btn-confirm.btn-error:hover {
  background: #dc2626;
}

.modal-btn-confirm.btn-warning {
  background: #f59e0b;
}

.modal-btn-confirm.btn-warning:hover {
  background: #d97706;
}

.modal-btn-confirm.btn-info {
  background: #3b82f6;
}

.modal-btn-confirm.btn-info:hover {
  background: #2563eb;
}

/* Loading */
.custom-loading {
  background: white;
  border-radius: 16px;
  padding: 32px;
  text-align: center;
  max-width: 300px;
}

.loading-spinner {
  font-size: 48px;
  color: var(--ion-color-primary);
}

.loading-title {
  font-size: 18px;
  font-weight: 600;
  margin: 16px 0 8px;
  color: var(--ion-color-dark);
}

.loading-message {
  font-size: 14px;
  margin: 0;
  color: var(--ion-color-medium);
}

/* Transitions */
.toast-slide-enter-active,
.toast-slide-leave-active {
  transition: all 0.3s ease;
}

.toast-slide-enter-from {
  transform: translateX(100%);
  opacity: 0;
}

.toast-slide-leave-to {
  transform: translateX(100%);
  opacity: 0;
}

.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.modal-fade-enter-active .custom-modal,
.modal-fade-enter-active .custom-loading {
  animation: modalScale 0.3s ease;
}

@keyframes modalScale {
  from {
    transform: scale(0.9);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}
</style>
