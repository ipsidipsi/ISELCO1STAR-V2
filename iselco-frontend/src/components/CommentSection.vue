<template>
  <div class="glass-card p-6">
    <h3 class="text-lg font-bold text-navy-700 mb-4 flex items-center">
      <ion-icon :icon="chatbubblesOutline" class="mr-2 text-teal"></ion-icon>
      Comments
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
      <p class="text-gray-500 mb-4">No comments yet</p>
      <p class="text-sm text-gray-400">Be the first to comment on this ticket</p>
    </div>

    <!-- Comments List -->
    <div v-else class="space-y-4 mb-6 max-h-96 overflow-y-auto">
      <CommentItem
        v-for="comment in comments"
        :key="comment.id"
        :comment="comment"
        @edit="handleEdit"
        @delete="handleDelete"
      />
    </div>

    <!-- Comment Input -->
    <CommentInput
      :submitting="submitting"
      :edit-mode="editMode"
      :edit-text="editText"
      @submit="handleSubmit"
      @cancel="cancelEdit"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { IonIcon, IonSpinner } from '@ionic/vue'
import { chatbubblesOutline } from 'ionicons/icons'
import { useComments, type Comment } from '@/composables/useComments'
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
} = useComments(props.ticketId)

const editMode = ref(false)
const editingCommentId = ref<number | null>(null)
const editText = ref('')

onMounted(async () => {
  await loadComments()
})

async function handleSubmit(message: string) {
  if (editMode.value && editingCommentId.value) {
    // Update existing comment
    await updateComment(editingCommentId.value, message)
    await showSuccess('Comment Updated', 'Your comment has been updated')
    cancelEdit()
  } else {
    // Add new comment
    await addComment(message)
  }
}

function handleEdit(comment: Comment) {
  editMode.value = true
  editingCommentId.value = comment.id
  editText.value = comment.message
}

async function handleDelete(comment: Comment) {
  const result = await showConfirm(
    'Delete Comment?',
    'This action cannot be undone.',
    'Delete',
    'Cancel'
  )

  if (result.isConfirmed) {
    await deleteComment(comment.id)
    await showSuccess('Comment Deleted', 'Your comment has been removed')
  }
}

function cancelEdit() {
  editMode.value = false
  editingCommentId.value = null
  editText.value = ''
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
</style>
