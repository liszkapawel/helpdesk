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
  new: 'info',
  open: 'warn',
  in_progress: 'warn',
  resolved: 'success',
  closed: 'success',
}

const prioritySeverity: Record<string, string> = {
  low: 'info',
  medium: 'warn',
  high: 'danger',
  critical: 'danger',
}

async function loadTickets() {
  loading.value = true
  try {
    const { data } = await api.get('/tickets', {
      params: { page: page.value, limit: rows.value },
    })
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
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-2xl font-bold">Tickets</h1>
      <Button label="New Ticket" icon="pi pi-plus" @click="router.push('/tickets/new')" />
    </div>

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
      <Column field="id" header="ID" style="width: 60px" />
      <Column field="title" header="Title" />
      <Column field="status" header="Status" style="width: 120px">
        <template #body="{ data }">
          <Tag :value="data.status" :severity="(statusSeverity[data.status] as any) || 'secondary'" />
        </template>
      </Column>
      <Column field="priority" header="Priority" style="width: 120px">
        <template #body="{ data }">
          <Tag :value="data.priority" :severity="(prioritySeverity[data.priority] as any) || 'secondary'" />
        </template>
      </Column>
      <Column header="Category" style="width: 150px">
        <template #body="{ data }">
          {{ data.category?.name || '—' }}
        </template>
      </Column>
      <Column header="Assigned To" style="width: 180px">
        <template #body="{ data }">
          <span v-if="data.assignedTo">{{ data.assignedTo.firstName }} {{ data.assignedTo.lastName }}</span>
          <span v-else class="text-surface-400">—</span>
        </template>
      </Column>
      <Column header="Created By" style="width: 180px">
        <template #body="{ data }">
          {{ data.createdBy?.firstName }} {{ data.createdBy?.lastName }}
        </template>
      </Column>
      <Column field="createdAt" header="Created" style="width: 160px">
        <template #body="{ data }">
          {{ new Date(data.createdAt).toLocaleDateString() }}
        </template>
      </Column>
    </DataTable>
  </div>
</template>
