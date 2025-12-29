<template>
  <ion-page>
    <ion-header>
      <ion-toolbar class="px-4">
        <ion-buttons slot="start">
          <ion-button @click="$router.back()">
            <ion-icon :icon="arrowBackOutline"></ion-icon>
          </ion-button>
        </ion-buttons>
        <ion-title class="text-xl font-bold">Ticket Details</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content class="ion-padding bg-gradient-to-br from-gray-50 to-blue-50">
      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center h-full">
        <ion-spinner name="crescent" class="text-teal text-4xl"></ion-spinner>
      </div>

      <!-- Ticket Content -->
      <div v-else-if="ticket" class="max-w-6xl mx-auto space-y-4">
        
        <!-- Ticket Header -->
        <div class="glass-card p-6">
          <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2">
                <h1 class="text-2xl font-extrabold text-navy-700">{{ ticket.ticket_number }}</h1>
                <span
                  class="px-3 py-1 rounded-full text-sm font-bold uppercase"
                  :class="getStatusClass(ticket.status)"
                >
                  {{ ticket.status.replace('_', ' ') }}
                  <span v-if="isNewlyAssigned(ticket)" class="ml-1">• NEW</span>
                </span>
                <span
                  class="px-3 py-1 rounded-full text-sm font-bold uppercase"
                  :class="getPriorityClass(ticket.priority?.name)"
                >
                  {{ ticket.priority?.name }}
                </span>
              </div>
              <h2 class="text-xl font-bold text-gray-900">{{ ticket.title }}</h2>
            </div>
          </div>

          <!-- Description -->
          <div class="mb-6">
            <p class="text-sm uppercase tracking-wide text-gray-600 font-semibold mb-2">Description</p>
            <p class="text-base text-gray-800 leading-relaxed">{{ ticket.description }}</p>
          </div>

          <!-- Ticket Info Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
              <p class="text-xs uppercase tracking-wide text-gray-600 font-semibold mb-1">Department</p>
              <p class="text-base font-bold text-navy-700">{{ ticket.department?.name }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-gray-600 font-semibold mb-1">Category</p>
              <p class="text-base font-bold text-navy-700">{{ ticket.category?.name }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-gray-600 font-semibold mb-1">Requestor</p>
              <p class="text-base font-bold text-navy-700">{{ ticket.requestor?.employee_name || ticket.requestor?.username }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-gray-600 font-semibold mb-1">Assigned To</p>
              <p class="text-base font-bold text-navy-700">
                {{ ticket.assigned_to?.employee_name || ticket.assigned_to?.username || 'Unassigned' }}
              </p>
            </div>
          </div>

          <!-- Dates -->
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 pt-4 border-t border-gray-200">
            <div>
              <p class="text-xs uppercase tracking-wide text-gray-600 font-semibold mb-1">Created</p>
              <p class="text-sm font-medium text-gray-700">{{ formatDate(ticket.created_at) }}</p>
            </div>
            <div>
              <p class="text-xs uppercase tracking-wide text-gray-600 font-semibold mb-1">Updated</p>
              <p class="text-sm font-medium text-gray-700">{{ formatDate(ticket.updated_at) }}</p>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="glass-card p-6" v-if="canPerformActions">
          <h3 class="text-lg font-bold text-navy-700 mb-4">Actions</h3>
          <div class="flex flex-wrap gap-3">
            <!-- Accept Button -->
            <button
              v-if="canAccept"
              @click="acceptTicket"
              class="px-6 py-3 bg-gradient-to-r from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 text-white rounded-lg font-semibold transition-all shadow-md hover:shadow-lg"
            >
              <ion-icon :icon="checkmarkCircleOutline" class="mr-2"></ion-icon>
              Accept Ticket
            </button>

            <!-- Start Work Button -->
            <button
              v-if="canStart"
              @click="startWork"
              class="px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg font-semibold transition-all shadow-md hover:shadow-lg"
            >
              <ion-icon :icon="playCircleOutline" class="mr-2"></ion-icon>
              Start Work
            </button>

            <!-- Resolve Button -->
            <button
              v-if="canResolve"
              @click="handleResolve"
              class="px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-lg font-semibold transition-all shadow-md hover:shadow-lg"
            >
              <ion-icon :icon="checkmarkDoneOutline" class="mr-2"></ion-icon>
              Mark Resolved
            </button>

            <!-- Verify Button -->
            <button
              v-if="canVerify"
              @click="verifyTicket"
              class="px-6 py-3 bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white rounded-lg font-semibold transition-all shadow-md hover:shadow-lg"
            >
              <ion-icon :icon="shieldCheckmarkOutline" class="mr-2"></ion-icon>
              Verify & Close
            </button>

            <!-- Reject Button -->
            <button
              v-if="canReject"
              @click="handleReject"
              class="px-6 py-3 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white rounded-lg font-semibold transition-all shadow-md hover:shadow-lg"
            >
              <ion-icon :icon="closeCircleOutline" class="mr-2"></ion-icon>
              Reject & Reopen
            </button>

            <!-- Reassign Button (Admin) -->
            <button
              v-if="canReassign"
              @click="showReassignModal = true"
              class="px-6 py-3 bg-gradient-to-r from-indigo-500 to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white rounded-lg font-semibold transition-all shadow-md hover:shadow-lg"
            >
              <ion-icon :icon="peopleOutline" class="mr-2"></ion-icon>
              Reassign
            </button>
          </div>
        </div>

        <!-- Timeline/Status History (Placeholder) -->
        <div class="glass-card p-6">
          <h3 class="text-lg font-bold text-navy-700 mb-4 flex items-center">
            <ion-icon :icon="timeOutline" class="mr-2 text-teal"></ion-icon>
            Timeline
          </h3>
          <div class="text-center py-8 text-gray-500">
            <ion-icon :icon="timeOutline" class="text-5xl mb-2 text-gray-300"></ion-icon>
            <p>Timeline feature coming soon</p>
          </div>
        </div>

        <!-- Comments Section -->
        <CommentSection :ticket-id="ticketId" />

        <!-- Attachments Section -->
        <div class="glass-card p-6">
          <h3 class="text-lg font-bold text-navy-700 mb-4 flex items-center">
            <ion-icon :icon="attachOutline" class="mr-2 text-teal"></ion-icon>
            Attachments
          </h3>
          <AttachmentList 
            v-if="ticket.attachments && ticket.attachments.length > 0"
            :attachments="ticket.attachments"
          />
          <div v-else class="text-center py-8 text-gray-500">
            <ion-icon :icon="attachOutline" class="text-5xl mb-2 text-gray-300"></ion-icon>
            <p>No attachments</p>
          </div>
        </div>
      </div>
    </ion-content>

    <!-- Reassign Modal (Placeholder) -->
    <ion-modal :is-open="showReassignModal" @didDismiss="showReassignModal = false">
      <ion-header>
        <ion-toolbar>
          <ion-title>Reassign Ticket</ion-title>
          <ion-buttons slot="end">
            <ion-button @click="showReassignModal = false">Close</ion-button>
          </ion-buttons>
        </ion-toolbar>
      </ion-header>
      <ion-content class="ion-padding">
        <p class="text-center text-gray-500 py-8">User selection feature coming soon</p>
      </ion-content>
    </ion-modal>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import {
  IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons,
  IonButton, IonIcon, IonSpinner, IonModal
} from '@ionic/vue'
import {
  arrowBackOutline, checkmarkCircleOutline, playCircleOutline, checkmarkDoneOutline,
  shieldCheckmarkOutline, closeCircleOutline, peopleOutline, timeOutline,
  chatbubblesOutline, attachOutline
} from 'ionicons/icons'
import { useTicketDetail } from '@/composables/useTicketDetail'
import { useAuthStore } from '@/stores/auth'
import CommentSection from '@/components/CommentSection.vue'
import AttachmentList from '@/components/AttachmentList.vue'
import Swal from 'sweetalert2'

const route = useRoute()
const authStore = useAuthStore()
const ticketId = Number(route.params.id)

const {
  ticket,
  loading,
  loadTicket,
  acceptTicket,
  startWork,
  resolveTicket,
  verifyTicket,
  rejectTicket,
  reassignTicket,
} = useTicketDetail(ticketId)

const showReassignModal = ref(false)

onMounted(async () => {
  await loadTicket()
})

// Permission checks
const isAdmin = computed(() => authStore.user?.roles?.some(r => r.slug === 'admin'))
const isRequestor = computed(() => ticket.value?.requestor_id === authStore.user?.id)
const isAssignee = computed(() => ticket.value?.assigned_to_id === authStore.user?.id)

const canPerformActions = computed(() => {
  // Show actions if user is admin, requestor, assignee, OR can accept unassigned tickets
  return isAdmin.value || isRequestor.value || isAssignee.value || !ticket.value?.assigned_to_id
})
const canAccept = computed(() => {
  // Show accept button for unassigned tickets (new, seen, or reopened) - but not for requestor
  return !ticket.value?.assigned_to_id && 
         ['new', 'seen', 'reopened'].includes(ticket.value?.status || '') && 
         !isRequestor.value
})
const canStart = computed(() => (isAdmin.value || isAssignee.value) && ticket.value?.status === 'assigned')
const canResolve = computed(() => (isAdmin.value || isAssignee.value) && ticket.value?.status === 'in_progress')
const canVerify = computed(() => (isAdmin.value || isRequestor.value) && ticket.value?.status === 'resolved')
const canReject = computed(() => (isAdmin.value || isRequestor.value) && ticket.value?.status === 'resolved')
const canReassign = computed(() => isAdmin.value && ticket.value?.status !== 'closed')

// UI Helpers
function getStatusClass(status: string) {
  const classes = {
    new: 'bg-blue-100 text-blue-700',
    seen: 'bg-gray-200 text-gray-700',
    assigned: 'bg-cyan-100 text-cyan-700',
    in_progress: 'bg-yellow-100 text-yellow-700',
    resolved: 'bg-purple-100 text-purple-700',
    closed: 'bg-green-100 text-green-700',
    reopened: 'bg-red-100 text-red-700',
  }
  return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-700'
}

function isNewlyAssigned(ticket: any): boolean {
  if (ticket.status !== 'assigned') return false
  if (!ticket.assigned_at) return true // If no timestamp, assume new
  
  const assignedTime = new Date(ticket.assigned_at).getTime()
  const now = Date.now()
  const hoursSinceAssigned = (now - assignedTime) / (1000 * 60 * 60)
  
  return hoursSinceAssigned < 24 // New if assigned within last 24 hours
}

function getPriorityClass(priority: string) {
  const classes = {
    Critical: 'bg-red-100 text-red-700',
    High: 'bg-orange-100 text-orange-700',
    Normal: 'bg-blue-100 text-blue-700',
    Low: 'bg-gray-100 text-gray-700',
  }
  return classes[priority as keyof typeof classes] || 'bg-gray-100 text-gray-700'
}

function formatDate(date: string) {
  return new Date(date).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

// Action handlers
async function handleResolve() {
  const { value: notes } = await Swal.fire({
    title: 'Mark as Resolved',
    input: 'textarea',
    inputLabel: 'Resolution Notes (Optional)',
    inputPlaceholder: 'Describe what was done to resolve this issue...',
    showCancelButton: true,
    confirmButtonText: 'Mark Resolved',
    confirmButtonColor: '#14B8A6',
    cancelButtonColor: '#6B7280',
  })

  if (notes !== undefined) {
    await resolveTicket(notes || '')
  }
}

async function handleReject() {
  const { value: reason } = await Swal.fire({
    title: 'Reject Solution',
    input: 'textarea',
    inputLabel: 'Reason for Rejection',
    inputPlaceholder: 'Explain why this solution is not acceptable...',
    showCancelButton: true,
    confirmButtonText: 'Reject & Reopen',
    confirmButtonColor: '#F59E0B',
    cancelButtonColor: '#6B7280',
    inputValidator: (value) => {
      if (!value) {
        return 'You must provide a reason for rejection'
      }
    }
  })

  if (reason) {
    await rejectTicket(reason)
  }
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
