<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'
import api from '@/services/api'

const toast = useToast()
const loading = ref(true)
const saving = ref(false)

const priorities = [
  { key: 'low', label: 'Niski' },
  { key: 'medium', label: 'Średni' },
  { key: 'high', label: 'Wysoki' },
  { key: 'critical', label: 'Krytyczny' },
]

const form = ref<Record<string, { responseHours: number; resolutionHours: number }>>({
  low: { responseHours: 48, resolutionHours: 168 },
  medium: { responseHours: 24, resolutionHours: 72 },
  high: { responseHours: 8, resolutionHours: 24 },
  critical: { responseHours: 2, resolutionHours: 8 },
})

async function loadPolicies() {
  loading.value = true
  try {
    const { data } = await api.get('/sla')
    for (const policy of data) {
      if (form.value[policy.priority]) {
        form.value[policy.priority].responseHours = policy.responseHours
        form.value[policy.priority].resolutionHours = policy.resolutionHours
      }
    }
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  try {
    const payload = priorities.map(p => ({
      priority: p.key,
      responseHours: form.value[p.key].responseHours,
      resolutionHours: form.value[p.key].resolutionHours,
    }))
    await api.put('/sla', payload)
    toast.add({ severity: 'success', summary: 'Zapisano', detail: 'Polityki SLA zaktualizowane', life: 3000 })
  } catch {
    toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się zapisać', life: 3000 })
  } finally {
    saving.value = false
  }
}

onMounted(loadPolicies)
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold tracking-tight">Polityki SLA</h1>
      <p class="text-surface-500 text-sm mt-1">Ustaw limity czasu reakcji i rozwiązania per priorytet</p>
    </div>

    <div v-if="loading" class="flex justify-center py-16">
      <i class="pi pi-spin pi-spinner text-4xl text-surface-400"></i>
    </div>

    <div v-else class="bg-surface-0 rounded-xl border border-surface-200 p-6">
      <div class="grid grid-cols-[1fr_1fr_1fr] gap-4 mb-2 px-1">
        <div class="text-sm font-semibold text-surface-500">Priorytet</div>
        <div class="text-sm font-semibold text-surface-500">Czas reakcji (godziny)</div>
        <div class="text-sm font-semibold text-surface-500">Czas rozwiązania (godziny)</div>
      </div>

      <div v-for="p in priorities" :key="p.key" class="grid grid-cols-[1fr_1fr_1fr] gap-4 items-center py-3 border-t border-surface-100">
        <div class="font-medium">{{ p.label }}</div>
        <InputNumber v-model="form[p.key].responseHours" :min="1" :max="720" suffix="h" class="w-full" />
        <InputNumber v-model="form[p.key].resolutionHours" :min="1" :max="720" suffix="h" class="w-full" />
      </div>

      <div class="mt-6 pt-4 border-t border-surface-200">
        <Button label="Zapisz" icon="pi pi-check" :loading="saving" @click="save" />
      </div>
    </div>
  </div>
</template>
