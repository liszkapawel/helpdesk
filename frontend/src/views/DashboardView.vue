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
import Card from 'primevue/card'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, PointElement, LineElement, Title, Tooltip, Legend)

const auth = useAuthStore()
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
  return {
    labels,
    datasets: [{ data, backgroundColor: colors }],
  }
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
  return {
    labels,
    datasets: [{ data, backgroundColor: colors }],
  }
})

const timeChartData = computed(() => {
  if (!stats.value?.overTime?.length) return null
  return {
    labels: stats.value.overTime.map((d: any) => d.date),
    datasets: [{
      label: 'Tickets',
      data: stats.value.overTime.map((d: any) => Number(d.count)),
      borderColor: '#3B82F6',
      backgroundColor: 'rgba(59, 130, 246, 0.1)',
      fill: true,
      tension: 0.3,
    }],
  }
})

const chartOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }

onMounted(loadStats)
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold mb-4">Dashboard</h1>

    <div v-if="loading" class="flex justify-center p-8">
      <i class="pi pi-spin pi-spinner text-4xl"></i>
    </div>

    <div v-else-if="stats">
      <!-- Metric Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <Card>
          <template #content>
            <div class="text-center">
              <div class="text-3xl font-bold">{{ stats.total }}</div>
              <div class="text-surface-500">Total Tickets</div>
            </div>
          </template>
        </Card>
        <Card>
          <template #content>
            <div class="text-center">
              <div class="text-3xl font-bold">
                {{ stats.byStatus?.find((s: any) => (s.status.value ?? s.status) === 'open')?.count || 0 }}
              </div>
              <div class="text-surface-500">Open Tickets</div>
            </div>
          </template>
        </Card>
        <Card>
          <template #content>
            <div class="text-center">
              <div class="text-3xl font-bold">{{ stats.avgResolutionHours ?? '—' }}h</div>
              <div class="text-surface-500">Avg Resolution Time</div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Charts -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <Card v-if="statusChartData">
          <template #title>By Status</template>
          <template #content>
            <div style="height: 250px">
              <Pie :data="statusChartData" :options="chartOptions" />
            </div>
          </template>
        </Card>

        <Card v-if="priorityChartData">
          <template #title>By Priority</template>
          <template #content>
            <div style="height: 250px">
              <Bar :data="priorityChartData" :options="chartOptions" />
            </div>
          </template>
        </Card>
      </div>

      <Card v-if="timeChartData" class="mb-6">
        <template #title>Tickets Over Time (Last 30 Days)</template>
        <template #content>
          <div style="height: 250px">
            <Line :data="timeChartData" :options="chartOptions" />
          </div>
        </template>
      </Card>

      <!-- Per Agent Table -->
      <Card v-if="stats.perAgent?.length" class="mb-6">
        <template #title>Tickets Per Agent</template>
        <template #content>
          <DataTable :value="stats.perAgent" striped-rows>
            <Column field="name" header="Agent" />
            <Column field="count" header="Assigned Tickets" style="width: 200px" />
          </DataTable>
        </template>
      </Card>
    </div>
  </div>
</template>
