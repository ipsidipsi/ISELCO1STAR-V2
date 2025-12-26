<template>
  <ion-page>
    <!-- Header -->
    <ion-header class="bg-white shadow-sm">
      <ion-toolbar class="px-4">
        <div class="flex items-center justify-between py-2">
          <div class="flex items-center space-x-3">
            <ion-button fill="clear" @click="goBack">
              <ion-icon :icon="arrowBackOutline" class="text-navy-700"></ion-icon>
            </ion-button>
            <h1 class="text-xl font-bold text-navy-700">Tickets</h1>
          </div>
          
          <ion-button fill="solid" color="success" @click="createTicket" class="bg-teal">
            <ion-icon :icon="addOutline" slot="start"></ion-icon>
            New Ticket
          </ion-button>
        </div>
      </ion-toolbar>
    </ion-header>

    <!-- Content -->
    <ion-content :fullscreen="true" class="bg-gray-50">
      <div class="p-4 max-w-7xl mx-auto">
        
        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Search -->
            <div>
              <ion-searchbar
                v-model="searchQuery"
                placeholder="Search tickets..."
                @ionInput="handleSearch"
                class="p-0"
              ></ion-searchbar>
            </div>
            
            <!-- Status Filter -->
            <select 
              v-model="statusFilter"
              @change="handleFilter"
              class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal focus:border-transparent"
            >
              <option value="">All Status</option>
              <option value="new">New</option>
              <option value="assigned">Assigned</option>
              <option value="in_progress">In Progress</option>
              <option value="resolved">Resolved</option>
              <option value="closed">Closed</option>
              <option value="reopened">Reopened</option>
            </select>
            
            <!-- Sort -->
            <select 
              v-model="sortBy"
              @change="handleFilter"
              class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal focus:border-transparent"
            >
              <option value="newest">Newest First</option>
              <option value="oldest">Oldest First</option>
              <option value="priority">Priority</option>
            </select>
          </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-20">
          <ion-spinner name="crescent" class="text-teal"></ion-spinner>
        </div>

        <!-- Tickets List -->
        <div v-else-if="tickets.length > 0" class="space-y-3">
          <div
            v-for="ticket in tickets"
            :key="ticket.id"
            @click="viewTicket(ticket.id)"
            class="bg-white rounded-xl shadow-sm p-6 hover:shadow-lg hover:border-2 hover:border-teal transition-all cursor-pointer"
          >
            <!-- Header -->
            <div class="flex items-start justify-between mb-3">
              <div class="flex-1">
                <div class="flex items-center flex-wrap gap-2 mb-3">
                  <span class="text-base font-mono font-bold text-navy-700">
                    {{ ticket.ticket_number }}
                  </span>
                  <span 
                    class="px-3 py-1.5 text-xs font-bold uppercase tracking-wide rounded-full"
                    :class="getStatusClass(ticket.status)"
                  >
                    {{ formatStatus(ticket.status) }}
                    <span v-if="isNewlyAssigned(ticket)" class="ml-1">• NEW</span>
                  </span>
                  <span 
                    v-if="ticket.priority"
                    class="px-3 py-1.5 text-xs font-bold uppercase tracking-wide rounded-full"
                    :class="getPriorityClass(ticket.priority.level)"
                  >
                    {{ ticket.priority.name }}
                  </span>
                </div>
                <h3 class="text-xl font-bold text-navy-700 mb-2 leading-snug">
                  {{ ticket.title }}
                </h3>
                <p class="text-base text-gray-700 leading-relaxed line-clamp-2">
                  {{ ticket.description }}
                </p>
              </div>
              <ion-icon :icon="chevronForwardOutline" class="text-teal text-2xl ml-4"></ion-icon>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between text-sm text-gray-600 mt-4 pt-4 border-t border-gray-200">
              <div class="flex items-center gap-4">
                <span class="font-medium">
                  <ion-icon :icon="personOutline" class="align-middle text-base"></ion-icon>
                  {{ ticket.requestor?.employee_name || 'Unknown' }}
                </span>
                <span v-if="ticket.department" class="font-medium">
                  <ion-icon :icon="businessOutline" class="align-middle text-base"></ion-icon>
                  {{ ticket.department.name }}
                </span>
              </div>
              <span class="font-semibold">{{ formatDate(ticket.created_at) }}</span>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-white rounded-xl shadow-sm p-12 text-center">
          <ion-icon :icon="documentTextOutline" class="text-6xl text-gray-300 mb-4"></ion-icon>
          <h3 class="text-lg font-semibold text-gray-700 mb-2">No Tickets Found</h3>
          <p class="text-gray-500 mb-6">Create your first ticket to get started</p>
          <ion-button @click="createTicket" class="bg-teal">
            <ion-icon :icon="addOutline" slot="start"></ion-icon>
            Create Ticket
          </ion-button>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  IonPage, IonHeader, IonToolbar, IonContent, IonButton,
  IonIcon, IonSpinner, IonSearchbar
} from '@ionic/vue'
import {
  arrowBackOutline, addOutline, chevronForwardOutline,
  personOutline, businessOutline, documentTextOutline
} from 'ionicons/icons'
import { useTickets } from '@/composables/useTickets'

const router = useRouter()
const { tickets, loading, loadTickets } = useTickets()

const searchQuery = ref('')
const statusFilter = ref('')
const sortBy = ref('newest')

onMounted(async () => {
  await loadTickets()
})

function goBack() {
  router.push('/dashboard')
}

function createTicket() {
  // TODO: Open create ticket modal
  console.log('Create ticket')
}

function viewTicket(id: number) {
  router.push(`/tickets/${id}`)
}

async function handleSearch() {
  await loadTickets({ search: searchQuery.value })
}

async function handleFilter() {
  const filters: any = {}
  if (statusFilter.value) filters.status = statusFilter.value
  if (searchQuery.value) filters.search = searchQuery.value
  await loadTickets(filters)
}

function getStatusClass(status: string) {
  const classes: Record<string, string> = {
    'new': 'bg-blue-100 text-blue-700',
    'seen': 'bg-gray-200 text-gray-700',
    'assigned': 'bg-cyan-100 text-cyan-700',
    'in_progress': 'bg-yellow-100 text-yellow-700',
    'resolved': 'bg-purple-100 text-purple-700',
    'closed': 'bg-green-100 text-green-700',
    'reopened': 'bg-red-100 text-red-700',
  }
  return classes[status] || 'bg-gray-100 text-gray-700'
}

function getPriorityClass(level: number) {
  if (level >= 4) return 'bg-red-100 text-red-700'
  if (level === 3) return 'bg-orange-100 text-orange-700'
  if (level === 2) return 'bg-yellow-100 text-yellow-700'
  return 'bg-blue-100 text-blue-700'
}

function formatStatus(status: string) {
  return status.replace(/_/g, ' ').toUpperCase()
}

function isNewlyAssigned(ticket: any): boolean {
  if (ticket.status !== 'assigned') return false
  if (!ticket.assigned_at) return true // If no timestamp, assume new
  
  const assignedTime = new Date(ticket.assigned_at).getTime()
  const now = Date.now()
  const hoursSinceAssigned = (now - assignedTime) / (1000 * 60 * 60)
  
  return hoursSinceAssigned < 24 // New if assigned within last 24 hours
}

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>
