<template>
  <ion-page>
    <!-- Header with subtle glass effect -->
    <ion-header class="glass-header">
      <ion-toolbar class="px-4 bg-transparent">
        <div class="flex items-center justify-between py-2">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-br from-teal to-teal-600 rounded-full flex items-center justify-center shadow-lg">
              <ion-icon :icon="ticketOutline" class="text-xl text-white"></ion-icon>
            </div>
            <div>
              <h1 class="text-xl font-bold text-navy-700">Dashboard</h1>
              <p class="text-sm text-gray-600">Welcome, {{ user?.employee_name || user?.username }}</p>
            </div>
          </div>
          
          <ion-button fill="clear" @click="handleLogout" class="glass-button">
            <ion-icon :icon="logOutOutline" class="text-gray-700"></ion-icon>
          </ion-button>
        </div>
      </ion-toolbar>
    </ion-header>

    <!-- Content with gradient background -->
    <ion-content :fullscreen="true" class="gradient-bg">
      <div class="p-4 max-w-7xl mx-auto">
        
        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center items-center py-20">
          <ion-spinner name="crescent" class="text-teal"></ion-spinner>
        </div>

        <template v-else>
          <!-- Statistics Cards with glass effect -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total Tickets -->
            <div class="glass-card card-blue">
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <p class="text-xs uppercase tracking-wide text-gray-600 font-semibold mb-2">Total Tickets</p>
                  <p class="text-5xl font-extrabold text-navy-700 leading-none">{{ stats.total }}</p>
                </div>
                <div class="icon-glow icon-blue">
                  <ion-icon :icon="documentsOutline" class="text-2xl text-blue-600"></ion-icon>
                </div>
              </div>
            </div>

            <!-- Assigned to Me -->
            <div class="glass-card card-teal">
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <p class="text-xs uppercase tracking-wide text-gray-600 font-semibold mb-2">Assigned to Me</p>
                  <p class="text-5xl font-extrabold text-navy-700 leading-none">{{ stats.my_assigned }}</p>
                </div>
                <div class="icon-glow icon-teal">
                  <ion-icon :icon="personOutline" class="text-2xl text-teal-600"></ion-icon>
                </div>
              </div>
            </div>

            <!-- In Progress -->
            <div class="glass-card card-yellow">
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <p class="text-xs uppercase tracking-wide text-gray-600 font-semibold mb-2">In Progress</p>
                  <p class="text-5xl font-extrabold text-navy-700 leading-none">{{ stats.in_progress }}</p>
                </div>
                <div class="icon-glow icon-yellow">
                  <ion-icon :icon="timeOutline" class="text-2xl text-yellow-600"></ion-icon>
                </div>
              </div>
            </div>

            <!-- Resolved -->
            <div class="glass-card card-green">
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <p class="text-xs uppercase tracking-wide text-gray-600 font-semibold mb-2">Resolved</p>
                  <p class="text-5xl font-extrabold text-navy-700 leading-none">{{ stats.resolved }}</p>
                </div>
                <div class="icon-glow icon-green">
                  <ion-icon :icon="checkmarkCircleOutline" class="text-2xl text-green-600"></ion-icon>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Actions with glass effect -->
          <div class="glass-card mb-6">
            <h2 class="text-lg font-semibold text-navy-700 mb-4">Quick Actions</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <button @click="openCreateTicket" class="action-button action-teal">
                <ion-icon :icon="addCircleOutline" class="text-3xl mb-2"></ion-icon>
                <span class="text-sm font-medium">New Ticket</span>
              </button>
              
              <button @click="goToTickets" class="action-button action-blue">
                <ion-icon :icon="listOutline" class="text-3xl mb-2"></ion-icon>
                <span class="text-sm font-medium">View All</span>
              </button>
              
              <button @click="goToReports" class="action-button action-purple">
                <ion-icon :icon="statsChartOutline" class="text-3xl mb-2"></ion-icon>
                <span class="text-sm font-medium">Reports</span>
              </button>
              
              <button @click="goToSettings" class="action-button action-gray">
                <ion-icon :icon="settingsOutline" class="text-3xl mb-2"></ion-icon>
                <span class="text-sm font-medium">Settings</span>
              </button>
            </div>
          </div>

          <!-- Recent Activity -->
          <div class="glass-card">
            <h2 class="text-lg font-semibold text-navy-700 mb-4">Recent Tickets</h2>
            
            <div v-if="tickets.length === 0" class="text-center py-8 text-gray-500">
              No tickets found. Create your first ticket!
            </div>

            <div v-else class="space-y-3">
              <div 
                v-for="ticket in tickets.slice(0, 5)" 
                :key="ticket.id"
                class="ticket-item cursor-pointer"
                @click="goToTicketDetail(ticket.id)"
              >
                <div class="flex-1">
                  <div class="flex items-center space-x-2">
                    <span class="text-sm font-mono font-semibold text-teal-600">{{ ticket.ticket_number }}</span>
                    <span 
                      class="px-2 py-1 text-xs font-medium rounded-full"
                      :class="getStatusClass(ticket.status)"
                    >
                      {{ ticket.status.toUpperCase() }}
                    </span>
                  </div>
                  <h3 class="font-medium text-navy-700 mt-1">{{ ticket.title }}</h3>
                  <p class="text-sm text-gray-600 mt-1">
                    {{ formatDate(ticket.created_at) }}
                  </p>
                </div>
                <ion-icon :icon="chevronForwardOutline" class="text-gray-400"></ion-icon>
              </div>
            </div>
          </div>
        </template>
      </div>
    </ion-content>

    <!-- Create Ticket Modal -->
    <CreateTicketModal
      :is-open="showCreateModal"
      @close="showCreateModal = false"
      @created="handleTicketCreated"
    />
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { 
  IonPage, IonHeader, IonToolbar, IonContent, IonButton, 
  IonIcon, IonSpinner 
} from '@ionic/vue'
import {
  ticketOutline, logOutOutline, documentsOutline, personOutline,
  timeOutline, checkmarkCircleOutline, addCircleOutline, listOutline,
  statsChartOutline, settingsOutline, chevronForwardOutline
} from 'ionicons/icons'
import { useAuthStore } from '@/stores/auth'
import { useTickets } from '@/composables/useTickets'
import CreateTicketModal from '@/components/CreateTicketModal.vue'

const router = useRouter()
const authStore = useAuthStore()
const { stats, tickets, loading, loadStats, loadTickets } = useTickets()

const user = computed(() => authStore.user)
const showCreateModal = ref(false)

onMounted(async () => {
  await Promise.all([
    loadStats(),
    loadTickets({ limit: 5 })
  ])
})

async function handleLogout() {
  await authStore.logout()
}

function openCreateTicket() {
  showCreateModal.value = true
}

async function handleTicketCreated() {
  // Reload stats and tickets after creating
  await Promise.all([
    loadStats(),
    loadTickets({ limit: 5 })
  ])
}

function goToTickets() {
  router.push('/tickets')
}

function goToTicketDetail(id: number) {
  router.push(`/tickets/${id}`)
}

function goToReports() {
  // TODO: Navigate to reports page
  console.log('Navigate to reports')
}

function goToSettings() {
  // TODO: Navigate to settings page
  console.log('Navigate to settings')
}

function getStatusClass(status: string) {
  const classes: Record<string, string> = {
    'new': 'bg-gray-200 text-gray-700',
    'assigned': 'bg-blue-100 text-blue-700',
    'in_progress': 'bg-yellow-100 text-yellow-700',
    'resolved': 'bg-green-100 text-green-700',
    'closed': 'bg-gray-300 text-gray-800',
    'reopened': 'bg-red-100 text-red-700',
  }
  return classes[status] || 'bg-gray-100 text-gray-700'
}

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<style scoped>
/* Gradient Background */
.gradient-bg {
  --background: linear-gradient(135deg, #f5f7fa 0%, #e6eef5 100%);
}

/* Glass Header */
.glass-header {
  --background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

/* Glass Cards - Subtle Effect */
.glass-card {
  background: rgba(255, 255, 255, 0.75);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border-radius: 1rem;
  padding: 1.5rem;
  border: 1px solid rgba(255, 255, 255, 0.3);
  box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
  transition: all 0.3s ease;
}

.glass-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.25);
}

/* Stat Card Gradients */
.card-blue {
  border-left: 4px solid #3B82F6;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(255, 255, 255, 0.75) 100%);
}

.card-teal {
  border-left: 4px solid #14B8A6;
  background: linear-gradient(135deg, rgba(20, 184, 166, 0.1) 0%, rgba(255, 255, 255, 0.75) 100%);
}

.card-yellow {
  border-left: 4px solid #F59E0B;
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(255, 255, 255, 0.75) 100%);
}

.card-green {
  border-left: 4px solid #10B981;
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(255, 255, 255, 0.75) 100%);
}

/* Icon Glow Effect */
.icon-glow {
  width: 3rem;
  height: 3rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(5px);
  -webkit-backdrop-filter: blur(5px);
  transition: all 0.3s ease;
}

.icon-blue {
  background: rgba(59, 130, 246, 0.15);
  box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
}

.icon-teal {
  background: rgba(20, 184, 166, 0.15);
  box-shadow: 0 4px 15px rgba(20, 184, 166, 0.3);
}

.icon-yellow {
  background: rgba(245, 158, 11, 0.15);
  box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
}

.icon-green {
  background: rgba(16, 185, 129, 0.15);
  box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}

.icon-glow:hover {
  transform: scale(1.1);
}

/* Action Buttons */
.action-button {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1rem;
  border-radius: 0.75rem;
  border: 1px solid rgba(255, 255, 255, 0.3);
  backdrop-filter: blur(5px);
  -webkit-backdrop-filter: blur(5px);
  transition: all 0.3s ease;
  cursor: pointer;
}

.action-teal {
  background: linear-gradient(135deg, rgba(20, 184, 166, 0.2) 0%, rgba(20, 184, 166, 0.05) 100%);
  color: #0D9488;
}

.action-blue {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(59, 130, 246, 0.05) 100%);
  color: #2563EB;
}

.action-purple {
  background: linear-gradient(135deg, rgba(147, 51, 234, 0.2) 0%, rgba(147, 51, 234, 0.05) 100%);
  color: #7C3AED;
}

.action-gray {
  background: linear-gradient(135deg, rgba(107, 114, 128, 0.2) 0%, rgba(107, 114, 128, 0.05) 100%);
  color: #4B5563;
}

.action-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

/* Ticket Items */
.ticket-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  background: rgba(249, 250, 251, 0.5);
  backdrop-filter: blur(5px);
  -webkit-backdrop-filter: blur(5px);
  border-radius: 0.75rem;
  border: 1px solid rgba(255, 255, 255, 0.3);
  transition: all 0.3s ease;
  cursor: pointer;
}

.ticket-item:hover {
  background: rgba(243, 244, 246, 0.8);
  transform: translateX(4px);
}

/* Glass Button */
.glass-button {
  background: rgba(255, 255, 255, 0.3);
  backdrop-filter: blur(5px);
  -webkit-backdrop-filter: blur(5px);
  border-radius: 0.5rem;
}

/* Fallback for browsers without backdrop-filter */
@supports not (backdrop-filter: blur(10px)) {
  .glass-card,
  .glass-header {
    background: rgba(255, 255, 255, 0.95);
  }
}
</style>
