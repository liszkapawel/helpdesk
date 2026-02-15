<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import api from '@/services/api'

const router = useRouter()

const tickets = ref<any[]>([])
const totalRecords = ref(0)
const loading = ref(false)
const page = ref(1)
const rows = ref(20)

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

async function loadTickets() {
  loading.value = true
  try {
    const { data } = await api.get('/tickets', { params: { page: page.value, limit: rows.value } })
    tickets.value = data.data
    totalRecords.value = data.meta.total
  } finally {
    loading.value = false
  }
}

function onPage(event: any) {
  page.value = event.page + 1
  rows.value = event.rows
  loadTickets()
}

function openTicket(event: any) {
  router.push(`/tickets/${event.data.id}`)
}

onMounted(loadTickets)
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
        <Column field="id" header="#" style="width: 60px" />
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
            {{ data.category?.name || '—' }}
          </template>
        </Column>
        <Column header="Przypisany" style="width: 180px">
          <template #body="{ data }">
            <span v-if="data.assignedTo">{{ data.assignedTo.firstName }} {{ data.assignedTo.lastName }}</span>
            <span v-else class="text-surface-400">—</span>
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
