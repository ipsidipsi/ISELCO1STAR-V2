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
        
        <!-- Collapsible Sections with Ionic Accordion -->
        <ion-accordion-group :value="['details', 'timeline', 'comments']" :multiple="true">
          
          <!-- Ticket Details Section -->
          <ion-accordion value="details" class="glass-card mb-4">
            <ion-item slot="header">
              <ion-label>
                <div class="flex items-center gap-2 mb-2">
                  <h3 class="text-3xl font-extrabold text-navy-800">{{ ticket.ticket_number }}</h3>
                  <span
                    class="px-3 py-1 rounded-full text-xs font-bold uppercase"
                    :class="getStatusClass(ticket.status)"
                  >
                    {{ ticket.status.replace('_', ' ') }}
                  </span>
                  <span
                    class="px-2 py-1 rounded-full text-xs font-bold uppercase"
                    :class="getPriorityClass(ticket.priority?.name)"
                  >
                    {{ ticket.priority?.name }}
                  </span>
                </div>
                <!-- Custom class to force size override -->
                <h4 class="ticket-title-custom mt-2">{{ ticket.title }}</h4>
              </ion-label>
            </ion-item>
            <div slot="content" class="p-6">
              <!-- Description -->
              <div class="mb-6">
                <p class="text-sm uppercase tracking-wide text-gray-600 font-semibold mb-2">Description</p>
                <p class="text-base text-gray-800 leading-relaxed">{{ ticket.description }}</p>
              </div>

              <!-- Ticket Info Grid -->
              <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-4">
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
          </ion-accordion>
        
        </ion-accordion-group>

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

             <!-- Reset Password Button (Admin for Password Reset tickets) -->
            <button
              v-if="canResetPassword"
              @click="resetPassword"
              class="px-6 py-3 bg-gradient-to-r from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 text-white rounded-lg font-semibold transition-all shadow-md hover:shadow-lg"
            >
              <ion-icon :icon="keyOutline" class="mr-2"></ion-icon>
              Reset Password (1234)
            </button>
          </div>
        </div>

        <!-- Collapsible Sections with Ionic Accordion -->
        <ion-accordion-group :value="['timeline', 'comments']" :multiple="true">

          
          <!-- Timeline Section -->
          <ion-accordion value="timeline" class="glass-card mb-4">
            <ion-item slot="header">
              <ion-icon :icon="timeOutline" class="mr-3 text-teal text-2xl" slot="start"></ion-icon>
              <ion-label>
                <h3 class="text-2xl font-extrabold text-navy-800">Timeline</h3>
                <p class="text-sm text-gray-600 font-medium">Activity history</p>
              </ion-label>
            </ion-item>
            <div slot="content" class="p-4">
              <TimelineList :ticket-id="ticketId" />
            </div>
          </ion-accordion>

          <!-- Attachments Section -->
          <ion-accordion value="attachments" class="glass-card mb-4">
            <ion-item slot="header">
              <ion-icon :icon="attachOutline" class="mr-3 text-teal text-2xl" slot="start"></ion-icon>
              <ion-label>
                <h3 class="text-2xl font-extrabold text-navy-800">Attachments</h3>
                <p class="text-sm text-gray-600 font-medium">
                  {{ ticket.attachments && ticket.attachments.length > 0 
                    ? `${ticket.attachments.length} file(s)` 
                    : 'No attachments' 
                  }}
                </p>
              </ion-label>
            </ion-item>
            <div slot="content" class="p-4">
              <AttachmentList 
                v-if="ticket.attachments && ticket.attachments.length > 0"
                :attachments="ticket.attachments"
              />
              <div v-else class="text-center py-8 text-gray-500">
                <ion-icon :icon="attachOutline" class="text-5xl mb-2 text-gray-300"></ion-icon>
                <p>No attachments</p>
              </div>
            </div>
          </ion-accordion>

          <!-- Comments Section -->
          <ion-accordion value="comments" class="glass-card">
            <ion-item slot="header">
              <ion-icon :icon="chatbubblesOutline" class="mr-3 text-teal text-2xl" slot="start"></ion-icon>
              <ion-label>
                <h3 class="text-2xl font-extrabold text-navy-800">Comments</h3>
                <p class="text-sm text-gray-600 font-medium">Discussion & updates</p>
              </ion-label>
            </ion-item>
            <div slot="content">
              <CommentSection :ticket-id="ticketId" />
            </div>
          </ion-accordion>

        </ion-accordion-group>
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
  IonButton, IonIcon, IonSpinner, IonModal, IonAccordionGroup, 
  IonAccordion, IonItem, IonLabel, alertController
} from '@ionic/vue'
import {
  arrowBackOutline, checkmarkCircleOutline, playCircleOutline, checkmarkDoneOutline,
  shieldCheckmarkOutline, closeCircleOutline, peopleOutline, timeOutline,
  chatbubblesOutline, attachOutline, chevronDownOutline, chevronUpOutline, keyOutline
} from 'ionicons/icons'
import { useTicketDetail } from '@/composables/useTicketDetail'
import { useAuthStore } from '@/stores/auth'
import CommentSection from '@/components/CommentSection.vue'
import AttachmentList from '@/components/AttachmentList.vue'
import TimelineList from '@/components/TimelineList.vue'

const route = useRoute()
const authStore = useAuthStore()

const ticketId = Number(route.params.id)
const showReassignModal = ref(false)

const {
  ticket,
  loading,
  loadTicket,
  acceptTicket,
  startWork,
  resolveTicket,
  verifyTicket,
  rejectTicket,
  resetPassword,
} = useTicketDetail(ticketId)

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
const canStart = computed(() => {
  // Can start if assigned or reopened (and is admin/assignee)
  return (isAdmin.value || isAssignee.value) && 
         (ticket.value?.status === 'assigned' || ticket.value?.status === 'reopened')
})
const canResolve = computed(() => (isAdmin.value || isAssignee.value) && ticket.value?.status === 'in_progress')
const canVerify = computed(() => (isAdmin.value || isRequestor.value) && ticket.value?.status === 'resolved')
const canReject = computed(() => (isAdmin.value || isRequestor.value) && ticket.value?.status === 'resolved')
const canReassign = computed(() => isAdmin.value && ticket.value?.status !== 'closed')
const canResetPassword = computed(() => {
  return isAdmin.value && 
         ticket.value?.category?.name === 'Forgot Password' &&
         ticket.value?.status !== 'closed'
})

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
  const alert = await alertController.create({
    header: 'Mark as Resolved',
    message: 'Please provide resolution notes (optional)',
    inputs: [
      {
        name: 'notes',
        type: 'textarea',
        placeholder: 'Describe what was done to resolve this issue...',
        attributes: {
          rows: 4,
        },
      },
    ],
    buttons: [
      {
        text: 'Cancel',
        role: 'cancel',
      },
      {
        text: 'Mark Resolved',
        role: 'confirm',
      },
    ],
  })

  await alert.present()
  const { data, role } = await alert.onDidDismiss()

  if (role === 'confirm') {
    await resolveTicket(data?.values?.notes || '')
  }
}

async function handleReject() {
  const alert = await alertController.create({
    header: 'Reject Solution',
    message: 'Please explain why this solution is not acceptable',
    inputs: [
      {
        name: 'reason',
        type: 'textarea',
        placeholder: 'Explain why this solution is not acceptable...',
        attributes: {
          rows: 4,
          required: true,
        },
      },
    ],
    buttons: [
      {
        text: 'Cancel',
        role: 'cancel',
      },
      {
        text: 'Reject & Reopen',
        role: 'confirm',
      },
    ],
  })

  await alert.present()
  const { data, role } = await alert.onDidDismiss()

  if (role === 'confirm' && data?.values?.reason) {
    await rejectTicket(data.values.reason)
  } else if (role === 'confirm' && !data?.values?.reason) {
    // Show error if no reason provided
    const errorAlert = await alertController.create({
      header: 'Error',
      message: 'You must provide a reason for rejection',
      buttons: ['OK'],
    })
    await errorAlert.present()
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

/* Force larger titles in accordion headers */
ion-accordion h3.text-3xl {
  font-size: 1.875rem !important; /* 30px */
  line-height: 2.25rem !important;
}

ion-accordion h3.text-2xl {
  font-size: 1.5rem !important; /* 24px */
  line-height: 2rem !important;
}

/* Ensure label allows wrapping for large text */
ion-accordion ion-item ion-label {
  white-space: normal !important;
  overflow: visible !important;
}

/* Specific override for ticket title inside accordion */
.ticket-title-custom {
  font-size: 1.5rem !important; /* 24px */
  font-weight: 800 !important;
  color: #1a202c !important; /* gray-900 */
  margin-top: 0.5rem !important;
  line-height: 1.4 !important;
  display: block !important;
}
</style>
