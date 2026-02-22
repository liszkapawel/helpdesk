<script setup lang="ts">
import { ref, reactive, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const router = useRouter()
const auth = useAuthStore()

const tickets = ref<any[]>([])
const totalRecords = ref(0)
const loading = ref(false)
const page = ref(1)
const rows = ref(20)

const categories = ref<any[]>([])
const agents = ref<any[]>([])
const showFilters = ref(false)

const filters = reactive({
  search: '',
  status: null as string | null,
  priority: null as string | null,
  category: null as number | null,
  assignedTo: null as string | null,
  dateFrom: null as Date | null,
  dateTo: null as Date | null,
  sort: 'createdAt',
  order: 'desc',
})

const statusOptions = [
  { label: 'Wszystkie', value: null },
  { label: 'Nowy', value: 'new' },
  { label: 'Otwarty', value: 'open' },
  { label: 'W toku', value: 'in_progress' },
  { label: 'Rozwiązany', value: 'resolved' },
  { label: 'Zamknięty', value: 'closed' },
]

const priorityOptions = [
  { label: 'Wszystkie', value: null },
  { label: 'Niski', value: 'low' },
  { label: 'Średni', value: 'medium' },
  { label: 'Wysoki', value: 'high' },
  { label: 'Krytyczny', value: 'critical' },
]

const sortOptions = [
  { label: 'Data utworzenia', value: 'createdAt' },
  { label: 'Tytuł', value: 'title' },
  { label: 'Status', value: 'status' },
  { label: 'Priorytet', value: 'priority' },
]

const orderOptions = [
  { label: 'Malejąco', value: 'desc' },
  { label: 'Rosnąco', value: 'asc' },
]

const statusSeverity: Record<string, string> = {
  new: 'info', open: 'warn', in_progress: 'warn', resolved: 'success', closed: 'secondary',
}
const prioritySeverity: Record<string, string> = {
  low: 'info', medium: 'warn', high: 'danger', critical: 'danger',
}
const statusLabels: Record<string, string> = {
  new: 'Nowy', open: 'Otwarty', in_progress: 'W toku', resolved: 'Rozwiązany', closed: 'Zamknięty',
}
const priorityLabels: Record<string, string> = {
  low: 'Niski', medium: 'Średni', high: 'Wysoki', critical: 'Krytyczny',
}

function formatDate(d: Date | null): string | undefined {
  if (!d) return undefined
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

async function loadTickets() {
  loading.value = true
  try {
    const params: Record<string, any> = {
      page: page.value,
      limit: rows.value,
    }
    if (filters.search) params.search = filters.search
    if (filters.status) params.status = filters.status
    if (filters.priority) params.priority = filters.priority
    if (filters.category) params.category = filters.category
    if (filters.assignedTo) params.assignedTo = filters.assignedTo
    if (filters.dateFrom) params.dateFrom = formatDate(filters.dateFrom)
    if (filters.dateTo) params.dateTo = formatDate(filters.dateTo)
    if (filters.sort) params.sort = filters.sort
    if (filters.order) params.order = filters.order

    const { data } = await api.get('/tickets', { params })
    tickets.value = data.data
    totalRecords.value = data.meta.total
  } finally {
    loading.value = false
  }
}

let searchTimeout: ReturnType<typeof setTimeout> | null = null
watch(() => filters.search, () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1
    loadTickets()
  }, 350)
})

watch([() => filters.status, () => filters.priority, () => filters.category, () => filters.assignedTo, () => filters.dateFrom, () => filters.dateTo, () => filters.sort, () => filters.order], () => {
  page.value = 1
  loadTickets()
})

function clearFilters() {
  filters.search = ''
  filters.status = null
  filters.priority = null
  filters.category = null
  filters.assignedTo = null
  filters.dateFrom = null
  filters.dateTo = null
  filters.sort = 'createdAt'
  filters.order = 'desc'
}

const hasActiveFilters = () => {
  return filters.status || filters.priority || filters.category || filters.assignedTo || filters.dateFrom || filters.dateTo
}

function onPage(event: any) {
  page.value = event.page + 1
  rows.value = event.rows
  loadTickets()
}

function openTicket(event: any) {
  router.push(`/tickets/${event.data.id}`)
}

async function loadFilterOptions() {
  try {
    const [catRes, agentRes] = await Promise.all([
      api.get('/categories'),
      auth.isAgentOrAdmin ? api.get('/admin/agents') : Promise.resolve({ data: [] }),
    ])
    categories.value = catRes.data
    agents.value = agentRes.data
  } catch {
    // ignore
  }
}

onMounted(() => {
  loadTickets()
  loadFilterOptions()
})
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Tickety</h1>
        <p class="text-surface-500 text-sm mt-1">Lista wszystkich zgłoszeń</p>
      </div>
      <Button label="Nowy ticket" icon="pi pi-plus" @click="router.push('/tickets/new')" />
    </div>

    <!-- Search + filter toggle -->
    <div class="flex items-center gap-3 mb-4">
      <div class="relative flex-1">
        <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-surface-400 text-sm"></i>
        <InputText
          v-model="filters.search"
          placeholder="Szukaj ticketów..."
          class="w-full pl-9"
        />
      </div>
      <Button
        :label="showFilters ? 'Ukryj filtry' : 'Filtry'"
        :icon="showFilters ? 'pi pi-filter-slash' : 'pi pi-filter'"
        :severity="hasActiveFilters() ? 'primary' : 'secondary'"
        :outlined="!hasActiveFilters()"
        @click="showFilters = !showFilters"
      />
      <Button
        v-if="hasActiveFilters()"
        label="Wyczyść"
        icon="pi pi-times"
        severity="secondary"
        text
        @click="clearFilters"
      />
    </div>

    <!-- Filter panel -->
    <div v-if="showFilters" class="bg-surface-0 rounded-xl border border-surface-200 p-4 mb-4">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-medium text-surface-500">Status</label>
          <Select
            v-model="filters.status"
            :options="statusOptions"
            option-label="label"
            option-value="value"
            placeholder="Wszystkie"
          />
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-medium text-surface-500">Priorytet</label>
          <Select
            v-model="filters.priority"
            :options="priorityOptions"
            option-label="label"
            option-value="value"
            placeholder="Wszystkie"
          />
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-medium text-surface-500">Kategoria</label>
          <Select
            v-model="filters.category"
            :options="[{ name: 'Wszystkie', id: null }, ...categories]"
            option-label="name"
            option-value="id"
            placeholder="Wszystkie"
          />
        </div>

        <div v-if="auth.isAgentOrAdmin" class="flex flex-col gap-1.5">
          <label class="text-xs font-medium text-surface-500">Przypisany do</label>
          <Select
            v-model="filters.assignedTo"
            :options="[
              { label: 'Wszyscy', value: null },
              { label: 'Nieprzypisane', value: 'unassigned' },
              ...agents.map(a => ({ label: `${a.firstName} ${a.lastName}`, value: String(a.id) })),
            ]"
            option-label="label"
            option-value="value"
            placeholder="Wszyscy"
          />
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-medium text-surface-500">Data od</label>
          <DatePicker v-model="filters.dateFrom" date-format="yy-mm-dd" placeholder="Wybierz datę" show-icon />
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-medium text-surface-500">Data do</label>
          <DatePicker v-model="filters.dateTo" date-format="yy-mm-dd" placeholder="Wybierz datę" show-icon />
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-medium text-surface-500">Sortowanie</label>
          <Select v-model="filters.sort" :options="sortOptions" option-label="label" option-value="value" />
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-medium text-surface-500">Kolejność</label>
          <Select v-model="filters.order" :options="orderOptions" option-label="label" option-value="value" />
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-surface-0 rounded-xl border border-surface-200">
      <DataTable
        :value="tickets"
        :loading="loading"
        :paginator="true"
        :rows="rows"
        :total-records="totalRecords"
        :lazy="true"
        :rows-per-page-options="[10, 20, 50]"
        @page="onPage"
        @row-click="openTicket"
        striped-rows
        hover
        class="cursor-pointer"
      >
        <template #empty>
          <div class="text-center py-8 text-surface-400">
            <i class="pi pi-inbox text-3xl mb-2"></i>
            <p>Brak ticketów spełniających kryteria</p>
          </div>
        </template>
        <Column field="id" header="#" style="width: 80px">
          <template #body="{ data }">
            <div class="flex items-center gap-1.5">
              <span>{{ data.id }}</span>
              <i
                v-if="data.slaResponseBreached || data.slaResolutionBreached"
                class="pi pi-exclamation-circle text-red-500 text-xs"
                v-tooltip.top="'SLA przekroczone'"
              ></i>
            </div>
          </template>
        </Column>
        <Column field="title" header="Tytuł" />
        <Column field="status" header="Status" style="width: 130px">
          <template #body="{ data }">
            <Tag :value="statusLabels[data.status] || data.status" :severity="(statusSeverity[data.status] as any) || 'secondary'" />
          </template>
        </Column>
        <Column field="priority" header="Priorytet" style="width: 120px">
          <template #body="{ data }">
            <Tag :value="priorityLabels[data.priority] || data.priority" :severity="(prioritySeverity[data.priority] as any) || 'secondary'" />
          </template>
        </Column>
        <Column header="Kategoria" style="width: 150px">
          <template #body="{ data }">
            {{ data.category?.name || '-' }}
          </template>
        </Column>
        <Column header="Przypisany" style="width: 180px">
          <template #body="{ data }">
            <span v-if="data.assignedTo">{{ data.assignedTo.firstName }} {{ data.assignedTo.lastName }}</span>
            <span v-else class="text-surface-400">-</span>
          </template>
        </Column>
        <Column field="createdAt" header="Utworzony" style="width: 130px">
          <template #body="{ data }">
            {{ new Date(data.createdAt).toLocaleDateString('pl-PL') }}
          </template>
        </Column>
      </DataTable>
    </div>
  </div>
</template>
