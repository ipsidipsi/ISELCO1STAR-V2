<template>
  <ion-page>
    <!-- Header -->
    <ion-header class="bg-white shadow-sm">
      <ion-toolbar class="px-4">
        <div class="flex items-center justify-between py-2">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-teal rounded-full flex items-center justify-center">
              <ion-icon :icon="ticketOutline" class="text-xl text-white"></ion-icon>
            </div>
            <div>
              <h1 class="text-xl font-bold text-navy-700">Dashboard</h1>
              <p class="text-sm text-gray-600">Welcome, {{ user?.employee_name || user?.username }}</p>
            </div>
          </div>
          
          <ion-button fill="clear" @click="handleLogout">
            <ion-icon :icon="logOutOutline" class="text-gray-600"></ion-icon>
          </ion-button>
        </div>
      </ion-toolbar>
    </ion-header>

    <!-- Content -->
    <ion-content :fullscreen="true" class="bg-gray-50">
      <div class="p-4 max-w-7xl mx-auto">
        
        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center items-center py-20">
          <ion-spinner name="crescent" class="text-teal"></ion-spinner>
        </div>

        <template v-else>
          <!-- Statistics Cards -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total Tickets -->
            <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-lg text-gray-600 font-medium">Total Tickets</p>
                  <p class="text-3xl font-bold text-navy-700 mt-2">{{ stats.total }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                  <ion-icon :icon="documentsOutline" class="text-2xl text-blue-600"></ion-icon>
                </div>
              </div>
            </div>

            <!-- Assigned to Me -->
            <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-teal">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm text-gray-600 font-medium">Assigned to Me</p>
                  <p class="text-3xl font-bold text-navy-700 mt-2">{{ stats.my_assigned }}</p>
                </div>
                <div class="w-12 h-12 bg-teal-100 rounded-full flex items-center justify-center">
                  <ion-icon :icon="personOutline" class="text-2xl text-teal-600"></ion-icon>
                </div>
              </div>
            </div>

            <!-- In Progress -->
            <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-yellow-500">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm text-gray-600 font-medium">In Progress</p>
                  <p class="text-3xl font-bold text-navy-700 mt-2">{{ stats.in_progress }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                  <ion-icon :icon="timeOutline" class="text-2xl text-yellow-600"></ion-icon>
                </div>
              </div>
            </div>

            <!-- Resolved -->
            <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm text-gray-600 font-medium">Resolved</p>
                  <p class="text-3xl font-bold text-navy-700 mt-2">{{ stats.resolved }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                  <ion-icon :icon="checkmarkCircleOutline" class="text-2xl text-green-600"></ion-icon>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-navy-700 mb-4">Quick Actions</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <button class="flex flex-col items-center p-4 bg-teal-50 hover:bg-teal-100 rounded-lg transition-colors">
                <ion-icon :icon="addCircleOutline" class="text-3xl text-teal mb-2"></ion-icon>
                <span class="text-sm font-medium text-navy-700">New Ticket</span>
              </button>
              
              <button class="flex flex-col items-center p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                <ion-icon :icon="listOutline" class="text-3xl text-blue-600 mb-2"></ion-icon>
                <span class="text-sm font-medium text-navy-700">View All</span>
              </button>
              
              <button class="flex flex-col items-center p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors">
                <ion-icon :icon="statsChartOutline" class="text-3xl text-purple-600 mb-2"></ion-icon>
                <span class="text-sm font-medium text-navy-700">Reports</span>
              </button>
              
              <button class="flex flex-col items-center p-4 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                <ion-icon :icon="settingsOutline" class="text-3xl text-gray-600 mb-2"></ion-icon>
                <span class="text-sm font-medium text-navy-700">Settings</span>
              </button>
            </div>
          </div>

          <!-- Recent Activity -->
          <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-navy-700 mb-4">Recent Tickets</h2>
            
            <div v-if="tickets.length === 0" class="text-center py-8 text-gray-500">
              No tickets found. Create your first ticket!
            </div>

            <div v-else class="space-y-3">
              <div 
                v-for="ticket in tickets.slice(0, 5)" 
                :key="ticket.id"
                class="flex items-center justify-between p-4 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors cursor-pointer"
              >
                <div class="flex-1">
                  <div class="flex items-center space-x-2">
                    <span class="text-sm font-mono text-gray-600">{{ ticket.ticket_number }}</span>
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
  </ion-page>
</template>

<script setup lang="ts">
import { onMounted, computed } from 'vue'
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

const authStore = useAuthStore()
const { stats, tickets, loading, loadStats, loadTickets } = useTickets()

const user = computed(() => authStore.user)

onMounted(async () => {
  await Promise.all([
    loadStats(),
    loadTickets({ limit: 5 })
  ])
})

async function handleLogout() {
  await authStore.logout()
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
