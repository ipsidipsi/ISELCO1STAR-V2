<template>
  <div class="glass-card p-6">
    <h3 class="text-lg font-bold text-navy-700 mb-4 flex items-center">
      <ion-icon :icon="chatbubblesOutline" class="mr-2 text-teal"></ion-icon>
      Chat
      <span v-if="comments.length > 0" class="ml-2 px-2 py-0.5 bg-teal/10 text-teal text-sm font-semibold rounded-full">
        {{ comments.length }}
      </span>
    </h3>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-8">
      <ion-spinner name="crescent" class="text-teal"></ion-spinner>
    </div>

    <!-- Empty State -->
    <div v-else-if="comments.length === 0" class="text-center py-8">
      <ion-icon :icon="chatbubblesOutline" class="text-5xl mb-2 text-gray-300"></ion-icon>
      <p class="text-gray-500 mb-4">No messages yet</p>
      <p class="text-sm text-gray-400">Start the conversation</p>
    </div>

    <!-- Chat Messages Container -->
    <div v-else class="comments-container">
      <div ref="messagesContainer" class="messages-list">
        <CommentItem
          v-for="comment in comments"
          :key="comment.id"
          :comment="comment"
          @edit="handleEdit"
          @delete="handleDelete"
        />
      </div>

      <!-- Scroll to Bottom Button -->
      <button
        v-if="showScrollButton"
        @click="scrollToBottom"
        class="scroll-to-bottom"
        title="Scroll to bottom"
      >
        <ion-icon :icon="arrowDownOutline"></ion-icon>
      </button>
    </div>

    <!-- Comment Input -->
    <CommentInput
      ref="commentInputRef"
      :submitting="submitting"
      :edit-mode="editMode"
      :edit-text="editText"
      @submit="handleSubmit"
      @cancel="cancelEdit"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { IonIcon, IonSpinner } from '@ionic/vue'
import { chatbubblesOutline, arrowDownOutline } from 'ionicons/icons'
import { useComments, type Comment } from '@/composables/useComments'
import { useAttachments } from '@/composables/useAttachments'
import { useNotification } from '@/composables/useNotification'
import CommentItem from './CommentItem.vue'
import CommentInput from './CommentInput.vue'

const props = defineProps<{
  ticketId: number
}>()

const { showSuccess, showConfirm } = useNotification()

const {
  comments,
  loading,
  submitting,
  loadComments,
  addComment,
  updateComment,
  deleteComment,
  subscribeToTicket,
  unsubscribeFromTicket,
} = useComments(props.ticketId)

const editMode = ref(false)
const editingCommentId = ref<number | null>(null)
const editText = ref('')
const messagesContainer = ref<HTMLElement | null>(null)
const showScrollButton = ref(false)
const commentInputRef = ref<{ clearFiles: () => void } | null>(null)

onMounted(async () => {
  await loadComments()
  await nextTick()
  scrollToBottom(true) // Scroll to bottom on initial load
  
  // Subscribe to real-time updates
  subscribeToTicket()
  
  // Detect scroll position
  if (messagesContainer.value) {
    messagesContainer.value.addEventListener('scroll', handleScroll)
  }
})

onUnmounted(() => {
  // Clean up WebSocket subscription
  unsubscribeFromTicket()
  
  // Remove scroll listener
  if (messagesContainer.value) {
    messagesContainer.value.removeEventListener('scroll', handleScroll)
  }
})

// Auto-scroll to bottom when new messages arrive
watch(() => comments.value.length, async (newLength, oldLength) => {
  if (newLength > oldLength) {
    await nextTick()
    // Always auto-scroll to bottom for new messages
    scrollToBottom()
    // Hide scroll button when auto-scrolling
    showScrollButton.value = false
  }
})

const uploadingFiles = ref(false)
const uploadProgress = ref(0)

async function handleSubmit(data: { message: string; files: any[] }) {
  if (editMode.value && editingCommentId.value) {
    // Update existing comment (files not supported in edit mode)
    await updateComment(editingCommentId.value, data.message)
    await showSuccess('Message Updated', 'Your message has been updated')
    cancelEdit()
  } else {
    try {
      // Send message (can be empty if there are files)
      const comment = await addComment(data.message || '')
      
      // Upload files if any
      if (data.files.length > 0 && comment) {
        const { uploadFile } = useAttachments()
        
        // Upload each file and track progress
        for (let i = 0; i < data.files.length; i++) {
          const fileItem = data.files[i]
          
          try {
            // Mark as uploading (hide ready checkmark, show progress)
            fileItem.ready = false
            fileItem.uploading = true
            fileItem.progress = 0
            fileItem.failed = false
            
            // Upload the file
            const result = await uploadFile(fileItem.file, 'App\\Models\\Comment', comment.id)
            
            if (result) {
              // Mark as uploaded with progress
              fileItem.progress = 100
              fileItem.uploading = false
              fileItem.uploaded = true
            } else {
              // Upload returned null (validation failed)
              fileItem.uploading = false
              fileItem.failed = true
            }
          } catch (error) {
            // Upload threw an error
            fileItem.uploading = false
            fileItem.failed = true
          }
        }
        
        // Files stay visible with checkmarks - user can see completion status
        // They'll be cleared when user starts typing a new message
      }
      
      // Reload comments to get updated attachments
      // This ensures the current user sees the attachments
      await loadComments()
      
      await nextTick()
      scrollToBottom() // Auto-scroll after sending
    } catch (error) {
      uploadingFiles.value = false
      uploadProgress.value = 0
      throw error
    }
  }
}

function handleEdit(comment: Comment) {
  editMode.value = true
  editingCommentId.value = comment.id
  editText.value = comment.message
}

async function handleDelete(comment: Comment) {
  const result = await showConfirm(
    'Delete Message?',
    'This action cannot be undone.',
    'Delete',
    'Cancel'
  )

  if (result.isConfirmed) {
    await deleteComment(comment.id)
    await showSuccess('Message Deleted', 'Your message has been removed')
  }
}

function cancelEdit() {
  editMode.value = false
  editingCommentId.value = null
  editText.value = ''
}

function scrollToBottom(instant = false) {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTo({
      top: messagesContainer.value.scrollHeight,
      behavior: instant ? 'auto' : 'smooth'
    })
  }
}

function handleScroll() {
  if (messagesContainer.value) {
    showScrollButton.value = !isNearBottom()
  }
}

function isNearBottom() {
  if (!messagesContainer.value) return true
  const { scrollTop, scrollHeight, clientHeight } = messagesContainer.value
  return scrollHeight - scrollTop - clientHeight < 100
}
</script>

<style scoped>
.glass-card {
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(10px);
  border-radius: 1rem;
  border: 1px solid rgba(255, 255, 255, 0.3);
  box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
}

.comments-container {
  position: relative;
  margin-bottom: 1rem;
}

.messages-list {
  max-height: 500px;
  overflow-y: auto;
  padding: 0.5rem;
  background: rgba(249, 250, 251, 0.5);
  border-radius: 0.75rem;
  scroll-behavior: smooth;
}

/* Custom scrollbar */
.messages-list::-webkit-scrollbar {
  width: 6px;
}

.messages-list::-webkit-scrollbar-track {
  background: rgba(243, 244, 246, 0.5);
  border-radius: 10px;
}

.messages-list::-webkit-scrollbar-thumb {
  background: rgba(156, 163, 175, 0.5);
  border-radius: 10px;
}

.messages-list::-webkit-scrollbar-thumb:hover {
  background: rgba(107, 114, 128, 0.7);
}

/* Scroll to bottom button */
.scroll-to-bottom {
  position: absolute;
  bottom: 0.5rem;
  right: 1rem;
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  background: linear-gradient(135deg, #14B8A6, #0D9488);
  color: white;
  border: none;
  box-shadow: 0 4px 12px rgba(20, 184, 166, 0.4);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  transition: all 0.2s ease;
  z-index: 10;
}

.scroll-to-bottom:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 16px rgba(20, 184, 166, 0.5);
}

.scroll-to-bottom:active {
  transform: scale(0.95);
}
</style>
