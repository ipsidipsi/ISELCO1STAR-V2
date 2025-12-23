import { ref } from 'vue'
import api from '@/services/api'
import { useNotification } from '@/composables/useNotification'

export interface Comment {
    id: number
    ticket_id: number
    user_id: number
    message: string
    is_internal: boolean
    read_by: string
    created_at: string
    updated_at: string
    deleted_at: string | null
    user: {
        id: number
        username: string
        employee_name: string | null
    }
}

export function useComments(ticketId: number) {
    const { showError } = useNotification()

    const comments = ref<Comment[]>([])
    const loading = ref(false)
    const submitting = ref(false)

    /**
     * Load all comments for the ticket
     */
    async function loadComments() {
        loading.value = true
        try {
            const response = await api.get(`/tickets/${ticketId}/comments`)
            comments.value = response.data.data || response.data
        } catch (error: any) {
            await showError('Failed to Load Comments', error.message || 'Could not load comments')
            throw error
        } finally {
            loading.value = false
        }
    }

    /**
     * Add a new comment
     */
    async function addComment(message: string) {
        submitting.value = true
        try {
            const response = await api.post(`/tickets/${ticketId}/comments`, { message })
            const newComment = response.data

            // Add to local list immediately (optimistic update)
            comments.value.push(newComment)

            return newComment
        } catch (error: any) {
            await showError('Failed to Post Comment', error.message || 'Could not post your comment')
            throw error
        } finally {
            submitting.value = false
        }
    }

    /**
     * Update an existing comment
     */
    async function updateComment(commentId: number, message: string) {
        try {
            const response = await api.patch(`/comments/${commentId}`, { message })
            const updated = response.data

            // Update in local list
            const index = comments.value.findIndex(c => c.id === commentId)
            if (index !== -1) {
                comments.value[index] = updated
            }

            return updated
        } catch (error: any) {
            await showError('Failed to Update Comment', error.message || 'Could not update comment')
            throw error
        }
    }

    /**
     * Delete a comment
     */
    async function deleteComment(commentId: number) {
        try {
            await api.delete(`/comments/${commentId}`)

            // Remove from local list
            comments.value = comments.value.filter(c => c.id !== commentId)
        } catch (error: any) {
            await showError('Failed to Delete Comment', error.message || 'Could not delete comment')
            throw error
        }
    }

    /**
     * Add a comment from WebSocket event
     */
    function addCommentFromEvent(comment: Comment) {
        // Check if comment already exists (avoid duplicates)
        if (!comments.value.find(c => c.id === comment.id)) {
            comments.value.push(comment)
        }
    }

    /**
     * Update a comment from WebSocket event
     */
    function updateCommentFromEvent(comment: Comment) {
        const index = comments.value.findIndex(c => c.id === comment.id)
        if (index !== -1) {
            comments.value[index] = comment
        }
    }

    /**
     * Delete a comment from WebSocket event
     */
    function deleteCommentFromEvent(commentId: number) {
        comments.value = comments.value.filter(c => c.id !== commentId)
    }

    return {
        comments,
        loading,
        submitting,
        loadComments,
        addComment,
        updateComment,
        deleteComment,
        addCommentFromEvent,
        updateCommentFromEvent,
        deleteCommentFromEvent,
    }
}
