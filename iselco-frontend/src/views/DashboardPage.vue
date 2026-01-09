<template>
  <ion-page>
    <!-- Header with subtle glass effect -->
    <ion-header class="glass-header">
      <ion-toolbar class="dashboard-header">
          <div class="flex items-center justify-between px-4 py-2">
            <div class="flex items-center gap-3">
              <ion-icon :icon="ticketOutline" class="text-2xl text-teal-600"></ion-icon>
              <div>
                <h1 class="text-xl font-bold text-navy-700">Dashboard</h1>
                <p class="text-sm text-gray-600">Welcome, {{authStore.user?.employee_name || authStore.user?.username}}</p>
              </div>
            </div>
          
          <div class="flex items-center">
            <NotificationBell class="mr-2" />
            <ion-button fill="clear" @click="handleLogout" class="glass-button">
              <ion-icon :icon="logOutOutline" class="text-gray-700"></ion-icon>
            </ion-button>
          </div>
        </div>
      </ion-toolbar>
    </ion-header>

    <!-- Content with gradient background -->
    <ion-content :fullscreen="true" class="gradient-bg">
      <ion-refresher slot="fixed" @ionRefresh="handleRefresh($event)">
        <ion-refresher-content></ion-refresher-content>
      </ion-refresher>

      <div class="p-6 md:p-8 max-w-[1600px] mx-auto">
        
        <!-- Temporary Role Notification Banner -->
        <div v-if="hasTemporaryRoles" class="temp-role-notification mb-6">
            <div class="flex items-center p-4">
              <ion-icon :icon="shieldCheckmarkOutline" class="text-3xl mr-3 text-white"></ion-icon>
              <div class="flex-1">
                <h3 class="text-lg font-bold text-white mb-1">🔑 Temporary Admin Access Active</h3>
                <p class="text-sm text-orange-100">
                  You have been granted <strong>{{ tempRoleName }}</strong> privileges
                  <span v-if="tempRoleExpiry"> until {{ formatTempRoleExpiry }}</span>
                </p>
              </div>
              <ion-icon :icon="timeOutline" class="text-2xl text-white pulsing"></ion-icon>
            </div>
          </div>

          <!-- Statistics Cards with glass effect -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Pending Tickets -->
            <div @click="navigateToTickets('pending')" class="glass-card card-blue clickable relative">
              <!-- Loading overlay -->
              <div v-if="loading" class="absolute inset-0 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-2xl flex items-center justify-center z-10">
                <ion-spinner name="crescent" class="text-blue-600"></ion-spinner>
              </div>
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <p class="text-xs uppercase tracking-wide text-gray-600 font-semibold mb-2">Pending Tickets</p>
                  <p class="text-5xl font-extrabold text-navy-700 leading-none">{{ stats.pending }}</p>
                </div>
                <div class="icon-glow icon-blue">
                  <ion-icon :icon="documentsOutline" class="text-2xl text-blue-600"></ion-icon>
                </div>
              </div>
            </div>

            <!-- Assigned to Me -->
            <div @click="navigateToTickets('assigned')" class="glass-card card-teal clickable relative">
              <div v-if="loading" class="absolute inset-0 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-2xl flex items-center justify-center z-10">
                <ion-spinner name="crescent" class="text-teal-600"></ion-spinner>
              </div>
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <p class="text-xs uppercase tracking-wide text-gray-600 font-semibold mb-2">Assigned to Me</p>
                  <p class="text-5xl font-extrabold text-navy-700 leading-none">{{ stats.assigned_to_me }}</p>
                </div>
                <div class="icon-glow icon-teal">
                  <ion-icon :icon="personOutline" class="text-2xl text-teal-600"></ion-icon>
                </div>
              </div>
            </div>

            <!-- In Progress -->
            <div @click="navigateToTickets('in_progress')" class="glass-card card-yellow clickable relative">
              <div v-if="loading" class="absolute inset-0 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-2xl flex items-center justify-center z-10">
                <ion-spinner name="crescent" class="text-yellow-600"></ion-spinner>
              </div>
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

            <!-- Pending Verification Card -->
            <div @click="navigateToTickets('pending_verification')" class="glass-card card-purple clickable relative">
              <div v-if="loading" class="absolute inset-0 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-2xl flex items-center justify-center z-10">
                <ion-spinner name="crescent" class="text-purple-600"></ion-spinner>
              </div>
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <p class="text-xs uppercase tracking-wide text-gray-600 font-semibold mb-2">Pending Verification</p>
                  <p class="text-5xl font-extrabold text-navy-700 leading-none">{{ stats.pending_verification || 0 }}</p>
                  <p class="text-xs text-gray-600 mt-1">Waiting for approval</p>
                </div>
                <div class="icon-glow icon-purple">
                  <ion-icon :icon="timeOutline" class="text-2xl text-purple-600"></ion-icon>
                </div>
              </div>
            </div>

            <!-- Closed Card (Completed) -->
            <div @click="navigateToTickets('closed')" class="glass-card card-green clickable relative">
              <div v-if="loading" class="absolute inset-0 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-2xl flex items-center justify-center z-10">
                <ion-spinner name="crescent" class="text-green-600"></ion-spinner>
              </div>
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <p class="text-xs uppercase tracking-wide text-gray-600 font-semibold mb-2">Completed</p>
                  <p class="text-5xl font-extrabold text-navy-700 leading-none">{{ stats.closed || 0 }}</p>
                  <p class="text-xs text-gray-600 mt-1">Successfully closed</p>
                </div>
                <div class="icon-glow icon-green">
                   <ion-icon :icon="checkmarkDoneCircleOutline" class="text-2xl text-green-600"></ion-icon>
                </div>
              </div>
            </div>

            <!-- My Requests -->
            <div @click="navigateToTickets('my_requests')" class="glass-card card-purple clickable relative">
              <div v-if="loading" class="absolute inset-0 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm rounded-2xl flex items-center justify-center z-10">
                <ion-spinner name="crescent" class="text-purple-600"></ion-spinner>
              </div>
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <p class="text-xs uppercase tracking-wide text-gray-600 font-semibold mb-2">My Requests</p>
                  <p class="text-5xl font-extrabold text-navy-700 leading-none">{{ stats.my_requests || 0 }}</p>
                </div>
                <div class="icon-glow icon-purple">
                  <ion-icon :icon="createOutline" class="text-2xl text-purple-600"></ion-icon>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="glass-card mb-8">
            <h2 class="text-xl font-bold text-navy-700 mb-6">Quick Actions</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <button @click="openCreateTicket" class="action-button action-teal">
                <ion-icon :icon="addCircleOutline" class="text-3xl mb-2"></ion-icon>
                <span class="text-sm font-medium">New Ticket</span>
              </button>
              
              <button @click="goToTickets" class="action-button action-blue">
                <ion-icon :icon="listOutline" class="text-3xl mb-2"></ion-icon>
                <span class="text-sm font-medium">View All</span>
              </button>
              
              <!-- Users Management - Only for admins -->
              <button v-if="isAdmin" @click="goToUsers" class="action-button action-purple">
                <ion-icon :icon="peopleOutline" class="text-3xl mb-2"></ion-icon>
                <span class="text-sm font-medium">Users</span>
              </button>
              
              <!-- Category Management - Only for admins -->
              <button v-if="isAdmin" @click="goToCategories" class="action-button action-orange">
                <ion-icon :icon="settingsOutline" class="text-3xl mb-2"></ion-icon>
                <span class="text-sm font-medium">Categories</span>
              </button>

              <!-- Reports - Only for admins -->
              <button v-if="isAdmin" @click="goToReports" class="action-button action-teal">
                <ion-icon :icon="statsChartOutline" class="text-3xl mb-2"></ion-icon>
                <span class="text-sm font-medium">Reports</span>
              </button>
            </div>
          </div>

          <!-- Recent Activity -->
          <div class="glass-card">
            <h2 class="text-lg font-semibold text-navy-700 mb-4">Recent Tickets</h2>
            
            <div v-if="loading && tickets.length === 0" class="space-y-3">
              <!-- Skeleton loaders -->
              <div v-for="i in 3" :key="i" class="glass-card p-4">
                <div class="animate-pulse flex items-center gap-3">
                  <div class="h-10 w-10 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                  <div class="flex-1 space-y-2">
                    <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-3/4"></div>
                    <div class="h-3 bg-gray-300 dark:bg-gray-600 rounded w-1/2"></div>
                  </div>
                </div>
              </div>
            </div>

            <div v-else-if="tickets.length === 0" class="text-center py-12 glass-card">
              <p class="text-gray-500 dark:text-gray-400">No recent tickets</p>
            </div>

            <div v-else class="space-y-3">
              <div 
                v-for="ticket in tickets.slice(0, 5)" 
                :key="ticket.id"
                @click="viewTicket(ticket.id)"
                class="glass-card p-4 hover:shadow-lg transition-all duration-200 cursor-pointer group"
              >
                <div class="flex-1">
                  <div class="flex items-center space-x-2">
                    <span class="text-sm font-mono font-semibold text-teal-600">{{ ticket.ticket_number }}</span>
                    <span 
                      class="px-2 py-1 text-xs font-medium rounded-full"
                      :class="getStatusClass(ticket.status)"
                    >
                      {{ ticket.status.toUpperCase().replace('_', ' ') }}
                      <span v-if="isNewlyAssigned(ticket)" class="ml-1">• NEW</span>
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
import { ref, onMounted, computed, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { 
  IonPage, IonHeader, IonToolbar, IonContent, IonButton, 
  IonIcon, IonSpinner, IonTitle, IonRefresher, IonRefresherContent,
  IonChip, IonSearchbar, IonCard, IonCardHeader, IonCardTitle,
  IonCardContent, IonBadge, IonSkeletonText
} from '@ionic/vue'
import {
  ticketOutline, logOutOutline, documentsOutline, personOutline,
  timeOutline, checkmarkCircleOutline, addCircleOutline, listOutline,
  statsChartOutline, settingsOutline, chevronForwardOutline, peopleOutline,
  shieldCheckmarkOutline, createOutline, checkmarkDoneCircleOutline,
  hourglassOutline, alertCircleOutline, arrowForward, funnelOutline, searchOutline
} from 'ionicons/icons'
import { useAuthStore } from '@/stores/auth'
import { useTickets } from '@/composables/useTickets'
import CreateTicketModal from '@/components/CreateTicketModal.vue'

// Import Notification Bell
import NotificationBell from '@/components/NotificationBell.vue'
import { useNotification } from '@/composables/useNotification'
import { useNotificationStore } from '@/stores/notifications'

const router = useRouter()

const authStore = useAuthStore()
const { stats, tickets, loading, loadStats, loadTickets } = useTickets()
const { showInfo } = useNotification()
const notificationStore = useNotificationStore()

const showCreateModal = ref(false)

// Role checks
const isAdmin = computed(() => {
  return authStore.user?.roles?.some((role: any) => 
    role.slug === 'superadmin' || role.slug === 'department_admin'
  )
})

// Check if user has temporary roles
const hasTemporaryRoles = computed(() => {
  const userRoles = authStore.user?.roles || []
  // Check if any role has a pivot.expires_at (indicating temporary role)
  return userRoles.some((role: any) => role.pivot && role.pivot.expires_at)
})

const tempRoleName = computed(() => {
  const userRoles = authStore.user?.roles || []
  const tempRole = userRoles.find((role: any) => role.pivot && role.pivot.expires_at)
  return tempRole?.name || ''
})

const tempRoleExpiry = computed(() => {
  const userRoles = authStore.user?.roles || []
  const tempRole = userRoles.find((role: any) => role.pivot && role.pivot.expires_at)
  return tempRole?.pivot?.expires_at || null
})

const formatTempRoleExpiry = computed(() => {
  if (!tempRoleExpiry.value) return ''
  return new Date(tempRoleExpiry.value).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
})

// Listen for real-time dashboard updates
function subscribeToDashboardUpdates() {
    if (!authStore.user?.id) return

    // Listen to the user's private channel (same as notifications)
    // When a notification arrives (TicketUpdated), it affects stats/lists
    const channelName = `App.Models.User.${authStore.user.id}`
    
    console.log(`Dashboard subscribing to ${channelName}`)
    window.Echo.private(channelName)
        .notification((notification: any) => {
            console.log('Dashboard: Event received, refreshing stats...', notification)
            
            // Refresh data
            loadStats()
            loadTickets({ limit: 5 })
        })
}

function unsubscribeDashboard() {
    // Optional: Since same channel is used by NotificationStore, 
    // leaving it might affect the Bell if they share the specific listener instance.
    // Laravel Echo usually multiplexes, but to be safe, we can just leave it if we are sure.
    // Or better, just rely on the component unmount.

     if (!authStore.user?.id) return
     // If we leave, we might break the notification store listener if using same echo instance?
     // Echo multiplexes channels, so leaving here will stop ALL listeners on this channel.
     // BETTER STRATEGY: Do not leave channel globally if other components need it.
     // But typically we should cleanup. 
     // For now, let's just let it be or use a specific event listener removal if Echo supports it (Echo.leave stops all).
     
     // Actually, we can't easily remove *just* this callback without digging into Echo internals.
     // So we'll skip explicit unsubscribe here to avoid killing the Bell's connection.
}

onMounted(async () => {
  await Promise.all([
    loadStats(),
    loadTickets({ limit: 5 })
  ])
  
  // Watch for notification changes to auto-refresh dashboard
  watch(() => notificationStore.unreadCount, (newCount, oldCount) => {
    // Only refresh if count increased (new notification arrived)
    if (newCount > oldCount) {
      console.log('[Dashboard] New notification detected, refreshing stats...')
      
      // Fetch new stats (loading state is handled by the composable)
      loadStats()
      loadTickets({ limit: 5 })
    }
  })
})

onUnmounted(() => {
  notificationStore.stopListener()
})

async function handleRefresh(event: any) {
  try {
    await Promise.all([
      loadStats(),
      loadTickets({ limit: 5 })
    ])
  } finally {
    event.target.complete()
  }
}

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

function navigateToTickets(filterType: string) {
  const query: Record<string, string> = {}
  
  switch (filterType) {
    case 'pending':
      query.status = 'new,seen,reopened'
      break
    case 'assigned':
      query.assigned_to_me = 'true'
      query.exclude_status = 'in_progress'
      break
    case 'in_progress':
      query.assigned_to_me = 'true'
      query.status = 'in_progress'
      break
    case 'pending_verification':
      query.assigned_to_me = 'true'
      query.status = 'resolved'
      break
    case 'closed':
      query.assigned_to_me = 'true'
      query.status = 'closed'
      break
    case 'my_requests':
      query.requested_by_me = 'true'
      break
  }
  
  router.push({ path: '/tickets', query })
}

function goToUsers() {
  router.push('/admin/users')
}

function goToCategories() {
  router.push('/admin/categories')
}

// function goToSettings() {
//   router.push('/settings')
// }


function viewTicket(id: number) {
  router.push(`/tickets/${id}`)
}

function goToReports() {
  router.push('/admin/reports')
}



function getStatusClass(status: string) {
  const classes: Record<string, string> = {
    'new': 'bg-blue-100 text-blue-700',
    'seen': 'bg-gray-200 text-gray-700',
    'assigned': 'bg-blue-100 text-blue-700',
    'in_progress': 'bg-yellow-100 text-yellow-700',
    'resolved': 'bg-green-100 text-green-700',
    'closed': 'bg-gray-300 text-gray-800',
    'reopened': 'bg-red-100 text-red-700',
  }
  return classes[status] || 'bg-gray-100 text-gray-700'
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

/* Clickable Card Styles */
.glass-card.clickable {
  cursor: pointer;
}

.glass-card.clickable:hover {
  transform: translateY(-4px);
  box-shadow: 0 16px 48px 0 rgba(31, 38, 135, 0.3);
}

.glass-card.clickable:active {
  transform: translateY(-2px);
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

.card-purple {
  border-left: 4px solid #9333EA;
  background: linear-gradient(135deg, rgba(147, 51, 234, 0.1) 0%, rgba(255, 255, 255, 0.75) 100%);
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

.icon-purple {
  background: rgba(147, 51, 234, 0.15);
  box-shadow: 0 4px 15px rgba(147, 51, 234, 0.3);
}

.icon-glow:hover {
  transform: scale(1.1);
}

/* Action Buttons */
.action-button {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  border-radius: 0.75rem;
  border: 1px solid rgba(255, 255, 255, 0.3);
  backdrop-filter: blur(5px);
  -webkit-backdrop-filter: blur(5px);
  transition: all 0.3s ease;
  cursor: pointer;
  min-height: 120px;
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

.action-orange {
  background: linear-gradient(135deg, rgba(249, 115, 22, 0.2) 0%, rgba(249, 115, 22, 0.05) 100%);
  color: #EA580C;
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

/* Temporary Role Notification Banner */
.temp-role-notification {
  background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(245, 158, 11, 0.3);
  animation: slideInDown 0.5s ease-out;
}

.pulsing {
  animation: pulse 2s ease-in-out infinite;
}

@keyframes slideInDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.7;
    transform: scale(1.1);
  }
}
</style>
