<script setup lang="ts">
import { ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import api from '@/services/api'

const toast = useToast()
const email = ref('')
const ticketId = ref('')
const loading = ref(false)
const result = ref<any>(null)
const searched = ref(false)

const statusMap: Record<string, { label: string; severity: string }> = {
  new: { label: 'Nowy', severity: 'info' },
  open: { label: 'Otwarty', severity: 'info' },
  in_progress: { label: 'W trakcie', severity: 'warn' },
  resolved: { label: 'Rozwiązany', severity: 'success' },
  closed: { label: 'Zamknięty', severity: 'secondary' },
}

const priorityMap: Record<string, { label: string; severity: string }> = {
  low: { label: 'Niski', severity: 'secondary' },
  medium: { label: 'Średni', severity: 'info' },
  high: { label: 'Wysoki', severity: 'warn' },
  critical: { label: 'Krytyczny', severity: 'danger' },
}

async function track() {
  if (!email.value || !ticketId.value) {
    toast.add({ severity: 'warn', summary: 'Uwaga', detail: 'Podaj email i numer zgłoszenia', life: 3000 })
    return
  }
  loading.value = true
  searched.value = true
  result.value = null
  try {
    const { data } = await api.post('/public/tickets/track', {
      email: email.value,
      ticketId: parseInt(ticketId.value),
    })
    result.value = data
  } catch {
    toast.add({ severity: 'error', summary: 'Nie znaleziono', detail: 'Nie znaleziono zgłoszenia o podanych danych', life: 3000 })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="max-w-lg mx-auto">
    <h1 class="text-2xl font-bold tracking-tight mb-1">Sprawdź status zgłoszenia</h1>
    <p class="text-surface-500 text-sm mb-6">Podaj swój email i numer zgłoszenia</p>

    <form class="flex flex-col gap-4" @submit.prevent="track">
      <div class="bg-surface-0 rounded-xl border border-surface-200 p-6 flex flex-col gap-4">
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Email</label>
          <InputText v-model="email" type="email" placeholder="jan@example.com" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Numer zgłoszenia</label>
          <InputText v-model="ticketId" placeholder="np. 42" />
        </div>
      </div>

      <Button type="submit" label="Sprawdź" icon="pi pi-search" :loading="loading" />
    </form>

    <!-- Result -->
    <div v-if="result" class="bg-surface-0 rounded-xl border border-surface-200 p-6 mt-6">
      <h2 class="font-semibold mb-4">Zgłoszenie #{{ result.id }}</h2>
      <div class="flex flex-col gap-3">
        <div>
          <span class="text-sm text-surface-500">Tytuł</span>
          <p class="font-medium">{{ result.title }}</p>
        </div>
        <div class="flex gap-4">
          <div>
            <span class="text-sm text-surface-500">Status</span>
            <div class="mt-1">
              <Tag :value="statusMap[result.status]?.label || result.status" :severity="statusMap[result.status]?.severity as any" />
            </div>
          </div>
          <div>
            <span class="text-sm text-surface-500">Priorytet</span>
            <div class="mt-1">
              <Tag :value="priorityMap[result.priority]?.label || result.priority" :severity="priorityMap[result.priority]?.severity as any" />
            </div>
          </div>
        </div>
        <div>
          <span class="text-sm text-surface-500">Data utworzenia</span>
          <p>{{ new Date(result.createdAt).toLocaleDateString('pl-PL', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}</p>
        </div>
        <div v-if="result.updatedAt">
          <span class="text-sm text-surface-500">Ostatnia aktualizacja</span>
          <p>{{ new Date(result.updatedAt).toLocaleDateString('pl-PL', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}</p>
        </div>
      </div>
    </div>

    <div v-else-if="searched && !loading" class="bg-surface-0 rounded-xl border border-surface-200 p-6 mt-6 text-center text-surface-500">
      Nie znaleziono zgłoszenia o podanych danych
    </div>
  </div>
</template>
