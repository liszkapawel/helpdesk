<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Button from 'primevue/button'
import api from '@/services/api'
import { getOrgSlug } from '@/utils/subdomain'

const router = useRouter()
const toast = useToast()

const name = ref('')
const email = ref('')
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

onMounted(async () => {
  const slug = getOrgSlug()
  if (slug) {
    try {
      const { data } = await api.get(`/public/org/${slug}/categories`)
      categories.value = data
    } catch {
      // ignore
    }
  }
})

async function submit() {
  if (!name.value || !email.value || !title.value || !description.value) {
    toast.add({ severity: 'warn', summary: 'Uwaga', detail: 'Wypełnij wszystkie wymagane pola', life: 3000 })
    return
  }
  loading.value = true
  try {
    const { data } = await api.post('/public/tickets', {
      name: name.value,
      email: email.value,
      title: title.value,
      description: description.value,
      priority: priority.value,
      category: categoryId.value,
    })
    router.push({
      path: '/submit/success',
      query: { ticketId: data.ticketId, token: data.trackingToken },
    })
  } catch {
    toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się wysłać zgłoszenia', life: 3000 })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="max-w-xl mx-auto">
    <h1 class="text-2xl font-bold tracking-tight mb-1">Zgłoś problem</h1>
    <p class="text-surface-500 text-sm mb-6">Wypełnij formularz, aby zgłosić problem</p>

    <form class="flex flex-col gap-4" @submit.prevent="submit">
      <div class="bg-surface-0 rounded-xl border border-surface-200 p-6 flex flex-col gap-4">
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Imię i nazwisko *</label>
          <InputText v-model="name" placeholder="Jan Kowalski" />
        </div>

        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Email *</label>
          <InputText v-model="email" type="email" placeholder="jan@example.com" />
        </div>

        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Tytuł zgłoszenia *</label>
          <InputText v-model="title" placeholder="Krótki opis problemu" />
        </div>

        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Opis *</label>
          <Textarea v-model="description" rows="5" placeholder="Opisz problem szczegółowo..." />
        </div>

        <div class="flex gap-4">
          <div class="flex flex-col gap-1 flex-1">
            <label class="text-sm font-medium">Priorytet</label>
            <Select v-model="priority" :options="priorities" optionLabel="label" optionValue="value" class="w-full" />
          </div>
          <div v-if="categories.length" class="flex flex-col gap-1 flex-1">
            <label class="text-sm font-medium">Kategoria</label>
            <Select v-model="categoryId" :options="categories" optionLabel="name" optionValue="id" placeholder="Wybierz..." class="w-full" />
          </div>
        </div>
      </div>

      <Button type="submit" label="Wyślij zgłoszenie" icon="pi pi-send" :loading="loading" />
    </form>
  </div>
</template>
