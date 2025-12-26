import { ref } from 'vue'
import api from '@/services/api'
import { useNotification } from '@/composables/useNotification'

export interface Attachment {
    id: number
    attachable_type: string
    attachable_id: number
    file_name: string
    file_path: string
    file_size: number
    mime_type: string
    uploaded_by: number
    created_at: string
    updated_at: string
    uploader?: {
        id: number
        username: string
        employee_name: string | null
    }
}

export interface SelectedFile {
    file: File
    preview?: string
    id?: string
}

export function useAttachments() {
    const { showError, showSuccess } = useNotification()
    const uploading = ref(false)
    const uploadProgress = ref(0)

    /**
     * Validate file before upload
     */
    function validateFile(file: File): { valid: boolean; error?: string } {
        // Check file size (25MB max)
        const maxSize = 25 * 1024 * 1024 // 25MB in bytes
        if (file.size > maxSize) {
            return {
                valid: false,
                error: `File "${file.name}" is too large. Maximum size is 25MB.`
            }
        }

        // Check file type
        const allowedTypes = [
            // Images
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp', 'image/webp',
            // Documents
            'application/pdf',
            'application/msword', // .doc
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
            'application/vnd.ms-excel', // .xls
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
            'text/plain', // .txt
            // Archives
            'application/zip', 'application/x-zip-compressed',
            'application/x-rar-compressed', 'application/vnd.rar'
        ]

        const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar']
        const fileExtension = file.name.split('.').pop()?.toLowerCase()

        if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExtension || '')) {
            return {
                valid: false,
                error: `File type not supported. Allowed: images, PDF, documents, ZIP, RAR`
            }
        }

        return { valid: true }
    }

    /**
     * Upload a single file
     */
    async function uploadFile(
        file: File,
        attachableType: string,
        attachableId: number
    ): Promise<Attachment | null> {
        // Validate file
        const validation = validateFile(file)
        if (!validation.valid) {
            await showError('Invalid File', validation.error || 'File validation failed')
            return null
        }

        const formData = new FormData()
        formData.append('file', file)
        formData.append('attachable_type', attachableType)
        formData.append('attachable_id', attachableId.toString())

        try {
            uploading.value = true
            uploadProgress.value = 0

            const response = await api.post('/attachments', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
                onUploadProgress: (progressEvent) => {
                    if (progressEvent.total) {
                        uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total)
                    }
                },
            })

            return response.data
        } catch (error: any) {
            await showError(
                'Upload Failed',
                error.response?.data?.message || `Failed to upload ${file.name}`
            )
            return null
        } finally {
            uploading.value = false
            uploadProgress.value = 0
        }
    }

    /**
     * Upload multiple files
     */
    async function uploadFiles(
        files: File[],
        attachableType: string,
        attachableId: number
    ): Promise<Attachment[]> {
        const attachments: Attachment[] = []

        for (const file of files) {
            const attachment = await uploadFile(file, attachableType, attachableId)
            if (attachment) {
                attachments.push(attachment)
            }
        }

        if (attachments.length > 0) {
            await showSuccess(
                'Upload Complete',
                `${attachments.length} file(s) uploaded successfully`
            )
        }

        return attachments
    }

    /**
     * Delete an attachment
     */
    async function deleteAttachment(attachmentId: number): Promise<boolean> {
        try {
            await api.delete(`/attachments/${attachmentId}`)
            await showSuccess('Deleted', 'Attachment deleted successfully')
            return true
        } catch (error: any) {
            await showError(
                'Delete Failed',
                error.response?.data?.message || 'Failed to delete attachment'
            )
            return false
        }
    }

    /**
     * Download an attachment
     */
    async function downloadAttachment(attachment: Attachment) {
        try {
            const response = await api.get(`/attachments/${attachment.id}/download`, {
                responseType: 'blob',
            })

            // Create download link
            const url = window.URL.createObjectURL(new Blob([response.data]))
            const link = document.createElement('a')
            link.href = url
            link.setAttribute('download', attachment.file_name)
            document.body.appendChild(link)
            link.click()
            link.remove()
            window.URL.revokeObjectURL(url)
        } catch (error: any) {
            await showError('Download Failed', 'Failed to download file')
        }
    }

    /**
     * Get file icon based on mime type
     */
    function getFileIcon(mimeType: string): string {
        if (mimeType.startsWith('image/')) return 'image-outline'
        if (mimeType === 'application/pdf') return 'document-text-outline'
        if (mimeType.includes('word') || mimeType.includes('document')) return 'document-outline'
        if (mimeType.includes('sheet') || mimeType.includes('excel')) return 'grid-outline'
        if (mimeType.includes('zip') || mimeType.includes('rar')) return 'archive-outline'
        return 'document-attach-outline'
    }

    /**
     * Format file size for display
     */
    function formatFileSize(bytes: number): string {
        if (bytes === 0) return '0 Bytes'
        const k = 1024
        const sizes = ['Bytes', 'KB', 'MB', 'GB']
        const i = Math.floor(Math.log(bytes) / Math.log(k))
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
    }

    /**
     * Check if file is an image
     */
    function isImage(mimeType: string): boolean {
        return mimeType.startsWith('image/')
    }

    /**
     * Capture photo from camera
     */
    async function captureFromCamera(): Promise<File | null> {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment' }
            })

            // Create video element
            const video = document.createElement('video')
            video.srcObject = stream
            video.play()

            // Wait for video to be ready
            await new Promise(resolve => {
                video.onloadedmetadata = resolve
            })

            // Create canvas and capture frame
            const canvas = document.createElement('canvas')
            canvas.width = video.videoWidth
            canvas.height = video.videoHeight
            const ctx = canvas.getContext('2d')
            ctx?.drawImage(video, 0, 0)

            // Stop the stream
            stream.getTracks().forEach(track => track.stop())

            // Convert canvas to blob then to File
            return new Promise<File>((resolve) => {
                canvas.toBlob((blob) => {
                    if (blob) {
                        const file = new File(
                            [blob],
                            `camera_${Date.now()}.jpg`,
                            { type: 'image/jpeg' }
                        )
                        resolve(file)
                    }
                }, 'image/jpeg', 0.95)
            })
        } catch (error: any) {
            await showError(
                'Camera Error',
                error.message || 'Failed to access camera'
            )
            return null
        }
    }

    /**
     * Create preview URL for a file
     */
    function createPreview(file: File): string | undefined {
        if (file.type.startsWith('image/')) {
            return URL.createObjectURL(file)
        }
        return undefined
    }

    return {
        uploading,
        uploadProgress,
        validateFile,
        uploadFile,
        uploadFiles,
        deleteAttachment,
        downloadAttachment,
        getFileIcon,
        formatFileSize,
        isImage,
        captureFromCamera,
        createPreview,
    }
}
