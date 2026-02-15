<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Button from 'primevue/button'
import api from '@/services/api'

const router = useRouter()
const toast = useToast()

const title = ref('')
const description = ref('')
const priority = ref('medium')
const categoryId = ref<number | null>(null)
const categories = ref<any[]>([])
const loading = ref(false)

const priorities = [
  { label: 'Niski', value: 'low' },
  { label: 'Średni', value: 'medium' },
  { label: 'Wysoki', value: 'high' },
  { label: 'Krytyczny', value: 'critical' },
]

async function loadCategories() {
  const { data } = await api.get('/categories')
  categories.value = data
}

async function submit() {
  loading.value = true
  try {
    const payload: any = { title: title.value, description: description.value, priority: priority.value }
    if (categoryId.value) payload.category = categoryId.value
    const { data } = await api.post('/tickets', payload)
    toast.add({ severity: 'success', summary: 'Sukces', detail: 'Ticket utworzony', life: 3000 })
    router.push(`/tickets/${data.id}`)
  } catch (err: any) {
    const errors = err.response?.data?.errors
    const detail = errors ? Object.values(errors).join(', ') : 'Nie udało się utworzyć ticketa'
    toast.add({ severity: 'error', summary: 'Błąd', detail, life: 5000 })
  } finally {
    loading.value = false
  }
}

onMounted(loadCategories)
</script>

<template>
  <div class="max-w-2xl">
    <div class="mb-6">
      <Button icon="pi pi-arrow-left" text severity="secondary" size="small" @click="router.push('/tickets')" label="Wróć do listy" />
    </div>

    <div class="bg-surface-0 rounded-xl border border-surface-200 p-6">
      <h1 class="text-xl font-bold mb-6">Nowy ticket</h1>

      <form class="flex flex-col gap-5" @submit.prevent="submit">
        <div class="flex flex-col gap-1.5">
          <label for="title" class="text-sm font-medium">Tytuł</label>
          <InputText id="title" v-model="title" placeholder="Krótki opis problemu" required />
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="description" class="text-sm font-medium">Opis</label>
          <Textarea id="description" v-model="description" placeholder="Opisz problem szczegółowo..." rows="6" required />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="flex flex-col gap-1.5">
            <label for="priority" class="text-sm font-medium">Priorytet</label>
            <Select id="priority" v-model="priority" :options="priorities" option-label="label" option-value="value" placeholder="Wybierz priorytet" />
          </div>
          <div class="flex flex-col gap-1.5">
            <label for="category" class="text-sm font-medium">Kategoria</label>
            <Select id="category" v-model="categoryId" :options="categories" option-label="name" option-value="id" placeholder="Wybierz kategorię" show-clear />
          </div>
        </div>

        <div class="flex gap-3 pt-2">
          <Button type="submit" label="Utwórz ticket" icon="pi pi-check" :loading="loading" />
          <Button type="button" label="Anuluj" severity="secondary" outlined @click="router.back()" />
        </div>
      </form>
    </div>
  </div>
</template>
