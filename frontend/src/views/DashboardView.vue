<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Bar, Pie, Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  ArcElement,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
} from 'chart.js'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import api from '@/services/api'

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, PointElement, LineElement, Title, Tooltip, Legend)

const stats = ref<any>(null)
const loading = ref(true)

async function loadStats() {
  loading.value = true
  try {
    const { data } = await api.get('/dashboard/stats')
    stats.value = data
  } finally {
    loading.value = false
  }
}

const statusChartData = computed(() => {
  if (!stats.value?.byStatus) return null
  const labels = stats.value.byStatus.map((s: any) => s.status.value ?? s.status)
  const data = stats.value.byStatus.map((s: any) => Number(s.count))
  const colors = labels.map((l: string) => {
    const map: Record<string, string> = {
      new: '#3B82F6', open: '#F59E0B', in_progress: '#F97316', resolved: '#10B981', closed: '#6B7280'
    }
    return map[l] || '#9CA3AF'
  })
  return { labels, datasets: [{ data, backgroundColor: colors }] }
})

const priorityChartData = computed(() => {
  if (!stats.value?.byPriority) return null
  const labels = stats.value.byPriority.map((p: any) => p.priority.value ?? p.priority)
  const data = stats.value.byPriority.map((p: any) => Number(p.count))
  const colors = labels.map((l: string) => {
    const map: Record<string, string> = {
      low: '#3B82F6', medium: '#F59E0B', high: '#F97316', critical: '#EF4444'
    }
    return map[l] || '#9CA3AF'
  })
  return { labels, datasets: [{ data, backgroundColor: colors }] }
})

const timeChartData = computed(() => {
  if (!stats.value?.overTime?.length) return null
  return {
    labels: stats.value.overTime.map((d: any) => d.date),
    datasets: [{
      label: 'Tickety',
      data: stats.value.overTime.map((d: any) => Number(d.count)),
      borderColor: '#3B82F6',
      backgroundColor: 'rgba(59, 130, 246, 0.1)',
      fill: true,
      tension: 0.3,
    }],
  }
})

const chartOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }

const openCount = computed(() => {
  if (!stats.value?.byStatus) return 0
  return stats.value.byStatus.find((s: any) => (s.status.value ?? s.status) === 'open')?.count || 0
})

onMounted(loadStats)
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold tracking-tight">Dashboard</h1>
      <p class="text-surface-500 text-sm mt-1">Przegląd zgłoszeń i statystyk</p>
    </div>

    <div v-if="loading" class="flex justify-center py-16">
      <i class="pi pi-spin pi-spinner text-4xl text-surface-400"></i>
    </div>

    <div v-else-if="stats">
      <!-- Metric Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-surface-0 rounded-xl border border-surface-200 p-5">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
              <i class="pi pi-ticket text-blue-500"></i>
            </div>
            <div>
              <div class="text-2xl font-bold">{{ stats.total }}</div>
              <div class="text-surface-500 text-sm">Wszystkie tickety</div>
            </div>
          </div>
        </div>
        <div class="bg-surface-0 rounded-xl border border-surface-200 p-5">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center">
              <i class="pi pi-exclamation-circle text-amber-500"></i>
            </div>
            <div>
              <div class="text-2xl font-bold">{{ openCount }}</div>
              <div class="text-surface-500 text-sm">Otwarte</div>
            </div>
          </div>
        </div>
        <div class="bg-surface-0 rounded-xl border border-surface-200 p-5">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center">
              <i class="pi pi-clock text-green-500"></i>
            </div>
            <div>
              <div class="text-2xl font-bold">{{ stats.avgResolutionHours ?? '-' }}h</div>
              <div class="text-surface-500 text-sm">Śr. czas rozwiązania</div>
            </div>
          </div>
        </div>
        <div class="bg-surface-0 rounded-xl border border-surface-200 p-5">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center">
              <i class="pi pi-star-fill text-amber-500"></i>
            </div>
            <div>
              <div class="text-2xl font-bold">{{ stats.avgSatisfaction ?? '-' }}<span v-if="stats.avgSatisfaction" class="text-sm font-normal text-surface-400">/5</span></div>
              <div class="text-surface-500 text-sm">Średnia ocena</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div v-if="statusChartData" class="bg-surface-0 rounded-xl border border-surface-200 p-5">
          <h3 class="font-semibold mb-4">Wg statusu</h3>
          <div class="h-64">
            <Pie :data="statusChartData" :options="chartOptions" />
          </div>
        </div>

        <div v-if="priorityChartData" class="bg-surface-0 rounded-xl border border-surface-200 p-5">
          <h3 class="font-semibold mb-4">Wg priorytetu</h3>
          <div class="h-64">
            <Bar :data="priorityChartData" :options="chartOptions" />
          </div>
        </div>
      </div>

      <div v-if="timeChartData" class="bg-surface-0 rounded-xl border border-surface-200 p-5 mb-8">
        <h3 class="font-semibold mb-4">Tickety w ostatnich 30 dniach</h3>
        <div class="h-64">
          <Line :data="timeChartData" :options="chartOptions" />
        </div>
      </div>

      <!-- Per Agent -->
      <div v-if="stats.perAgent?.length" class="bg-surface-0 rounded-xl border border-surface-200 p-5">
        <h3 class="font-semibold mb-4">Tickety per agent</h3>
        <DataTable :value="stats.perAgent" striped-rows>
          <Column field="name" header="Agent" />
          <Column field="count" header="Przypisane tickety" style="width: 200px" />
        </DataTable>
      </div>
    </div>
  </div>
</template>
