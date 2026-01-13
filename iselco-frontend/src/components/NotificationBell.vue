<template>
  <div class="relative inline-block">
    <ion-button fill="clear" id="notification-trigger" class="relative">
      <ion-icon 
        :icon="preferences.is_muted ? notificationsOffOutline : notificationsOutline" 
        class="text-2xl" 
        :class="preferences.is_muted ? 'text-gray-400 dark:text-gray-600' : 'text-gray-600 dark:text-gray-300'"
      ></ion-icon>
      <span 
        v-if="unreadCount > 0"
        class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white shadow-sm ring-1 ring-white"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </ion-button>

    <ion-popover trigger="notification-trigger" show-backdrop="false" class="notification-popover">
      <div class="w-80 max-h-[500px] flex flex-col bg-white dark:bg-gray-800 rounded-lg shadow-xl overflow-hidden">
        
        <!-- Header -->
        <div class="p-3 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-700/50">
          <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Notifications</h3>
          <div class="flex gap-2 items-center">
            <button 
              v-if="notifications.length > 0"
              @click="confirmDeleteAll"
              class="text-xs text-red-600 dark:text-red-400 hover:text-red-800 font-medium"
            >
              Delete All
            </button>
            <button 
              @click="toggleMute"
              class="text-lg hover:bg-gray-200 dark:hover:bg-gray-600 rounded p-1 transition"
              :title="preferences.is_muted ? 'Unmute notifications' : 'Mute notifications'"
            >
              <ion-icon :icon="preferences.is_muted ? volumeMuteOutline : volumeHighOutline"></ion-icon>
            </button>
            <button 
              v-if="hasUnread"
              @click="markAllRead"
              class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 font-medium"
            >
              Mark all read
            </button>
          </div>
        </div>

        <!-- List -->
        <div class="overflow-y-auto flex-1 p-0">
          <div v-if="loading && notifications.length === 0" class="p-4 text-center">
            <ion-spinner name="dots" class="h-4 w-4"></ion-spinner>
          </div>

          <div v-else-if="notifications.length === 0" class="p-8 text-center text-gray-500 flex flex-col items-center">
            <ion-icon :icon="notificationsOffOutline" class="text-4xl mb-2 opacity-50"></ion-icon>
            <p class="text-sm">No notifications</p>
          </div>

          <div v-else class="divide-y divide-gray-50 dark:divide-gray-700">
            <div 
              v-for="notif in notifications" 
              :key="notif.id"
              class="p-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer relative group"
              :class="{'bg-blue-50/50 dark:bg-blue-900/10': !notif.read_at}"
              @click="handleNotificationClick(notif)"
            >
              <div class="flex gap-3">
                <!-- Icon based on type -->
                <div class="mt-1 flex-shrink-0">
                  <div 
                    class="h-8 w-8 rounded-full flex items-center justify-center"
                    :class="getIconColor(notif.data.action_type)"
                  >
                    <ion-icon :icon="getIcon(notif.data.action_type)" class="text-sm text-white"></ion-icon>
                  </div>
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                  <p class="text-xs text-gray-500 mb-0.5 flex justify-between">
                    <span>{{ notif.data.ticket_number }}</span>
                    <span>{{ formatTime(notif.created_at) }}</span>
                  </p>
                  <p class="text-sm text-gray-800 dark:text-gray-200 font-medium truncate">
                    {{ notif.data.message }}
                  </p>
                  <p class="text-xs text-gray-500 mt-0.5 truncate">
                    by {{ notif.data.actor_name }}
                  </p>
                </div>
                
                <!-- Unread Indicator -->
                <div v-if="!notif.read_at" class="mt-2">
                  <span class="block h-2 w-2 rounded-full bg-blue-600 ring-2 ring-white"></span>
                </div>
              </div>
            </div>
            
            <!-- Load More Trigger -->
             <div v-if="hasMore" class="p-2 text-center">
                <button @click.stop="loadMore" class="text-xs text-blue-500">Load More</button>
             </div>
          </div>
        </div>
      </div>
    </ion-popover>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { IonButton, IonIcon, IonPopover, IonSpinner } from '@ionic/vue'
import { 
  notificationsOutline, notificationsOffOutline, 
  chatbubbleOutline, checkmarkCircleOutline, personOutline, 
  swapHorizontalOutline, alertCircleOutline, refreshOutline,
  volumeMuteOutline, volumeHighOutline
} from 'ionicons/icons'
import { useNotificationStore } from '@/stores/notifications'
import { storeToRefs } from 'pinia'
import { useCustomNotification } from '@/composables/useCustomNotification'

// Props & Emit not needed as it's self-contained with store

const router = useRouter()
const notifStore = useNotificationStore()
const { notifications, unreadCount, loading, hasMore, preferences } = storeToRefs(notifStore)
const { showConfirm, showSuccess } = useCustomNotification()

const hasUnread = computed(() => {
  return unreadCount.value > 0 || notifications.value.some(n => !n.read_at)
})

onMounted(() => {
  notifStore.fetchNotifications(true)
  notifStore.fetchUnreadCount()
  // Note: initializeListener and fetchPreferences are called in DashboardPage onMounted
})

onUnmounted(() => {
    // Don't stop listener here - it's managed by DashboardPage
})

async function markAllRead() {
  await notifStore.markAllAsRead()
}

async function confirmDeleteAll() {
  if (confirm('Are you sure you want to delete all notifications? This cannot be undone.')) {
    await notifStore.deleteAllNotifications()
  }
}

async function toggleMute() {
  await notifStore.updatePreferences({
    is_muted: !notifStore.preferences.is_muted
  })
}

async function loadMore() {
    await notifStore.fetchNotifications(false)
}

function handleNotificationClick(notif: any) {
  // Mark as read - REMOVED to prevent race condition/connection refused
  // The TicketDetailPage handles marking notifications as read via useTicketDetail -> markTicketAsRead
  // if (!notif.read_at) {
  //   notifStore.markAsRead(notif.id)
  // }
  
  // Navigate
  // Close popover logic implicitly stored in UI state or framework
  const popover = document.querySelector('ion-popover.notification-popover') as any
  if (popover) popover.dismiss()

  router.push(`/tickets/${notif.data.ticket_id}`)
}

function formatTime(dateStr: string) {
  const date = new Date(dateStr)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.round(diffMs / 60000)
  const diffHours = Math.round(diffMins / 60)
  const diffDays = Math.round(diffHours / 24)

  if (diffMins < 1) return 'Just now'
  if (diffMins < 60) return `${diffMins}m ago`
  if (diffHours < 24) return `${diffHours}h ago`
  if (diffDays < 7) return `${diffDays}d ago`
  return date.toLocaleDateString()
}

function getIcon(type: string) {
  switch (type) {
    case 'comment_added': return chatbubbleOutline
    case 'resolved': return checkmarkCircleOutline
    case 'assigned': return personOutline
    case 'reassigned': return swapHorizontalOutline
    case 'status_changed': return refreshOutline
    case 'reopened': return alertCircleOutline
    default: return notificationsOutline
  }
}

function getIconColor(type: string) {
  switch (type) {
    case 'comment_added': return 'bg-blue-500'
    case 'resolved': return 'bg-green-500'
    case 'assigned': return 'bg-orange-500'
    case 'reassigned': return 'bg-purple-500' // Fixed duplicate key in spirit
    case 'reopened': return 'bg-red-500'
    case 'status_changed': return 'bg-gray-500'
    default: return 'bg-gray-400'
  }
}
</script>

<style scoped>
/* Custom Popover styling override attempts */
ion-popover.notification-popover {
  --width: 320px;
  --max-height: 500px;
}
</style>
