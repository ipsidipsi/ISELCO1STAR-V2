<template>
  <ion-page>
    <ion-header>
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/dashboard"></ion-back-button>
        </ion-buttons>
        <ion-title>Reports & Analytics</ion-title>
        <ion-buttons slot="end">
            <!-- Mobile Date Filter Toggle -->
            <ion-button @click="showFilters = !showFilters" class="md:hidden">
                <ion-icon :icon="filterOutline"></ion-icon>
            </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content class="bg-gray-50">
      <div class="p-4 md:p-8 max-w-[1600px] mx-auto space-y-6">
        
        <!-- Filters Section -->
        <div class="glass-card p-4" :class="{ 'hidden md:block': !showFilters }">
           <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
             <div>
               <label class="text-xs font-bold text-gray-500 uppercase">Start Date</label>
               <input type="date" v-model="filters.start_date" class="w-full mt-1 p-2 border rounded-lg bg-gray-50">
             </div>
             <div>
               <label class="text-xs font-bold text-gray-500 uppercase">End Date</label>
               <input type="date" v-model="filters.end_date" class="w-full mt-1 p-2 border rounded-lg bg-gray-50">
             </div>
             <div>
               <label class="text-xs font-bold text-gray-500 uppercase">Department</label>
               <select v-model="filters.department_id" class="w-full mt-1 p-2 border rounded-lg bg-gray-50">
                   <option value="">All Departments</option>
                   <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
               </select>
             </div>
             <div class="flex gap-2">
                <button @click="loadData" class="flex-1 bg-teal-600 text-white py-2 rounded-lg font-bold hover:bg-teal-700 transition">
                    Apply Filters
                </button>
                <button @click="resetFilters" class="px-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    <ion-icon :icon="refreshOutline"></ion-icon>
                </button>
             </div>
           </div>
        </div>

        <!-- Metric Cards -->
        <div v-if="loading" class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div v-for="i in 4" :key="i" class="glass-card h-32 animate-pulse bg-gray-200"></div>
        </div>
        <div v-else class="grid grid-cols-2 lg:grid-cols-4 gap-4 overflow-x-auto pb-2 snap-x">
             <!-- Avg Response Time -->
             <div class="glass-card p-4 min-w-[160px] snap-center">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase">Avg Response</p>
                        <h3 class="text-2xl font-extrabold text-navy-800 mt-1">{{ metrics.avg_response }}h</h3>
                    </div>
                    <div class="p-2 bg-blue-100 rounded-lg text-blue-600">
                        <ion-icon :icon="timerOutline"></ion-icon>
                    </div>
                </div>
                <!-- <p class="text-xs text-green-600 mt-2 font-bold">In target</p> -->
             </div>

             <!-- Avg Resolution Time -->
             <div class="glass-card p-4 min-w-[160px] snap-center">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase">Avg Resolution</p>
                        <h3 class="text-2xl font-extrabold text-navy-800 mt-1">{{ metrics.avg_resolution }}h</h3>
                    </div>
                    <div class="p-2 bg-purple-100 rounded-lg text-purple-600">
                        <ion-icon :icon="checkmarkDoneCircleOutline"></ion-icon>
                    </div>
                </div>
             </div>
             
             <!-- Avg Turnaround -->
             <div class="glass-card p-4 min-w-[160px] snap-center">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase">Avg Turnaround</p>
                        <h3 class="text-2xl font-extrabold text-navy-800 mt-1">{{ metrics.avg_turnaround }}h</h3>
                    </div>
                    <div class="p-2 bg-teal-100 rounded-lg text-teal-600">
                        <ion-icon :icon="repeatOutline"></ion-icon>
                    </div>
                </div>
             </div>

             <!-- Total Tickets -->
            <div class="glass-card p-4 min-w-[160px] snap-center">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase">Total Tickets</p>
                        <h3 class="text-3xl font-extrabold text-navy-800 mt-1">{{ metrics.total_tickets }}</h3>
                        <p class="text-xs text-gray-500 mt-1">{{ metrics.open_tickets }} Open / {{ metrics.closed_tickets }} Closed</p>
                    </div>
                    <div class="p-2 bg-orange-100 rounded-lg text-orange-600">
                        <ion-icon :icon="documentsOutline"></ion-icon>
                    </div>
                </div>
             </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Volume Trend -->
            <div class="glass-card p-4">
                <h3 class="font-bold text-lg text-gray-700 mb-4">Ticket Volume Trend</h3>
                <div class="h-64 relative">
                     <Line v-if="chartData.trend" :data="chartData.trend" :options="chartOptions" />
                     <div v-else class="flex items-center justify-center h-full text-gray-400">No data available</div>
                </div>
            </div>

            <!-- Tickets by Dept -->
            <div class="glass-card p-4">
                <h3 class="font-bold text-lg text-gray-700 mb-4">Tickets by Department</h3>
                 <div class="h-64 relative">
                     <Bar v-if="chartData.department" :data="chartData.department" :options="chartOptions" />
                     <div v-else class="flex items-center justify-center h-full text-gray-400">No data available</div>
                </div>
            </div>
            
            <!-- Tickets by Category -->
             <div class="glass-card p-4">
                <h3 class="font-bold text-lg text-gray-700 mb-4">Tickets by Category</h3>
                 <div class="h-64 relative flex justify-center">
                     <Doughnut v-if="chartData.category" :data="chartData.category" :options="doughnutOptions" />
                     <div v-else class="flex items-center justify-center h-full text-gray-400">No data available</div>
                </div>
            </div>

             <!-- Top Requestors -->
            <div class="glass-card p-4">
                <h3 class="font-bold text-lg text-gray-700 mb-4">Top Requestors</h3>
                 <div class="h-64 relative">
                     <Bar v-if="chartData.requestors" :data="chartData.requestors" :options="{ ...chartOptions, indexAxis: 'y' }" />
                     <div v-else class="flex items-center justify-center h-full text-gray-400">No data available</div>
                </div>
            </div>
        </div>

        <!-- Detailed Report Table -->
        <div class="glass-card overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
                <h3 class="font-bold text-lg text-gray-700">Detailed Report</h3>
                <div class="flex gap-2 w-full md:w-auto">
                     <button @click="exportReport('csv')" class="flex-1 md:flex-none px-4 py-2 border rounded-lg hover:bg-gray-50 text-sm font-semibold flex items-center justify-center gap-2">
                        <ion-icon :icon="documentTextOutline" class="text-green-600"></ion-icon> CSV
                     </button>
                     <button @click="exportReport('excel')" class="flex-1 md:flex-none px-4 py-2 border rounded-lg hover:bg-gray-50 text-sm font-semibold flex items-center justify-center gap-2">
                        <ion-icon :icon="gridOutline" class="text-green-600"></ion-icon> Excel
                     </button>
                     <button @click="exportReport('pdf')" class="flex-1 md:flex-none px-4 py-2 border rounded-lg hover:bg-gray-50 text-sm font-semibold flex items-center justify-center gap-2">
                        <ion-icon :icon="printOutline" class="text-red-600"></ion-icon> PDF
                     </button>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Ticket #</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Requestor</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="tickets.length === 0">
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">No records found</td>
                        </tr>
                        <tr v-for="ticket in tickets" :key="ticket.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-mono text-teal-600">{{ ticket.ticket_number }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 truncate max-w-[200px]">{{ ticket.title }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-bold rounded-full uppercase" :class="getStatusClass(ticket.status)">
                                    {{ ticket.status.replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ ticket.department?.name }}</td>
                             <td class="px-6 py-4 text-sm text-gray-600">{{ ticket.requestor?.employee_name || ticket.requestor?.username }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ formatDate(ticket.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
             <!-- Pagination -->
            <div class="px-6 py-4 bg-gray-50 flex justify-between items-center" v-if="totalPages > 1">
                <button 
                  @click="page > 1 && (page--, loadData())" 
                  :disabled="page === 1"
                  class="px-3 py-1 rounded border bg-white disabled:opacity-50"
                >Prev</button>
                <span class="text-sm text-gray-600">Page {{ page }} of {{ totalPages }}</span>
                 <button 
                  @click="page < totalPages && (page++, loadData())" 
                  :disabled="page === totalPages"
                  class="px-3 py-1 rounded border bg-white disabled:opacity-50"
                >Next</button>
            </div>
        </div>

      </div>
    </ion-content>
  </ion-page>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import { IonPage, IonHeader, IonToolbar, IonButtons, IonBackButton, IonTitle, IonContent, IonIcon, IonButton } from '@ionic/vue'
import { 
    filterOutline, refreshOutline, timerOutline, checkmarkDoneCircleOutline, 
    repeatOutline, documentsOutline, documentTextOutline, gridOutline, printOutline 
} from 'ionicons/icons'
import api from '@/services/api'
import { useNotification } from '@/composables/useNotification'

// Chart.js imports
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement
} from 'chart.js'
import { Line, Bar, Doughnut } from 'vue-chartjs'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend
)

const { showError } = useNotification()

const loading = ref(true)
const showFilters = ref(false)
const tickets = ref<any[]>([])
const departments = ref<any[]>([])

const page = ref(1)
const totalPages = ref(1)

const filters = reactive({
    start_date: '',
    end_date: '',
    department_id: '',
    status: '',
})

const metrics = reactive({
    avg_response: 0,
    avg_resolution: 0,
    avg_turnaround: 0,
    total_tickets: 0,
    open_tickets: 0,
    closed_tickets: 0,
})

// Chart Data holders
const chartData = reactive<any>({
    trend: null,
    department: null,
    category: null,
    requestors: null
})

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false }
    }
}

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
}

onMounted(async () => {
    // Set default date range (last 30 days)
    const end = new Date()
    const start = new Date()
    start.setDate(start.getDate() - 30)
    
    filters.end_date = end.toISOString().split('T')[0]
    filters.start_date = start.toISOString().split('T')[0]

    await loadDepartments()
    await loadData()
})

async function loadDepartments() {
    try {
        // Request tailored list for reporting access (respecting assigned depts)
        const res = await api.get('/departments?scope=assigned')
        departments.value = res.data
    } catch (e) { console.error(e) }
}

async function loadData() {
    loading.value = true
    try {
        // Load Analytics
        const analyticsRes = await api.get('/reports/analytics', { params: filters })
        processAnalytics(analyticsRes.data)

        // Load Table Data
        const listRes = await api.get('/reports', { 
            params: { ...filters, page: page.value } 
        })
        tickets.value = listRes.data.data
        totalPages.value = listRes.data.last_page
        
    } catch (error: any) {
        showError('Failed to load reports', error.message)
    } finally {
        loading.value = false
        showFilters.value = false // Hide filters on mobile after load
    }
}

function processAnalytics(data: any) {
    // Metrics
    Object.assign(metrics, data.metrics)

    // 1. Trend Chart
    if (data.trend && data.trend.length) {
        chartData.trend = {
            labels: data.trend.map((d: any) => new Date(d.date).toLocaleDateString(undefined, { month: 'short', day: 'numeric'})),
            datasets: [{
                label: 'Tickets',
                backgroundColor: '#3B82F6',
                borderColor: '#3B82F6',
                data: data.trend.map((d: any) => d.count),
                tension: 0.4
            }]
        }
    }

    // 2. Department Chart
    if (data.by_department && data.by_department.length) {
        chartData.department = {
            labels: data.by_department.map((d: any) => d.name),
            datasets: [{
                label: 'Tickets',
                backgroundColor: ['#14B8A6', '#8B5CF6', '#F59E0B', '#ec4899'],
                data: data.by_department.map((d: any) => d.count),
                borderRadius: 4
            }]
        }
    }
    
    // 3. Category Chart
    if (data.by_category && data.by_category.length) {
        chartData.category = {
            labels: data.by_category.map((d: any) => d.name),
            datasets: [{
                backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'],
                data: data.by_category.map((d: any) => d.count),
            }]
        }
    }

    // 4. Requestors Chart
    if (data.top_requestors && data.top_requestors.length) {
        chartData.requestors = {
            labels: data.top_requestors.map((d: any) => d.name),
            datasets: [{
                label: 'Tickets',
                backgroundColor: '#6366F1',
                data: data.top_requestors.map((d: any) => d.count),
                borderRadius: 4
            }]
        }
    }
}

function resetFilters() {
    filters.department_id = ''
    filters.status = ''
    // Reset dates to default
    const end = new Date()
    const start = new Date()
    start.setDate(start.getDate() - 30)
    filters.end_date = end.toISOString().split('T')[0]
    filters.start_date = start.toISOString().split('T')[0]
    
    loadData()
}

async function exportReport(type: 'pdf'|'excel'|'csv') {
    try {
        const response = await api.get('/reports/export', {
            params: { ...filters, type },
            responseType: 'blob' // Important for file download
        })
        
        // Create download link
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `report-${type}-${Date.now()}.${type === 'excel' ? 'xlsx' : type}`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        
    } catch (error) {
        showError('Export Failed', 'Could not download the report')
    }
}

function getStatusClass(status: string) {
  const classes: Record<string, string> = {
    'new': 'bg-blue-100 text-blue-700',
    'assigned': 'bg-cyan-100 text-cyan-700',
    'in_progress': 'bg-yellow-100 text-yellow-700',
    'resolved': 'bg-green-100 text-green-700',
    'closed': 'bg-gray-200 text-gray-700',
    'reopened': 'bg-red-100 text-red-700',
  }
  return classes[status] || 'bg-gray-100 text-gray-700'
}

function formatDate(date: string) {
    if (!date) return '-'
    return new Date(date).toLocaleDateString()
}
</script>

<style scoped>
.glass-card {
  background: white;
  border-radius: 1rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  border: 1px solid #f3f4f6;
}
</style>
