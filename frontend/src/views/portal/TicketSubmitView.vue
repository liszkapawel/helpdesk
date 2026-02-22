<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Button from 'primevue/button'
import FileUploadPrime from 'primevue/fileupload'
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
const pendingFiles = ref<File[]>([])

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

function onSelectFiles(event: any) {
  pendingFiles.value = [...pendingFiles.value, ...event.files]
}

function removeFile(index: number) {
  pendingFiles.value.splice(index, 1)
}

function formatSize(bytes: number) {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / 1048576).toFixed(1) + ' MB'
}

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

    // Upload pending files using tracking token
    for (const file of pendingFiles.value) {
      const formData = new FormData()
      formData.append('file', file)
      try {
        await api.post(`/public/tickets/${data.ticketId}/attachments`, formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
            'X-Tracking-Token': data.trackingToken,
          },
        })
      } catch {
        toast.add({ severity: 'warn', summary: 'Uwaga', detail: `Nie udało się przesłać: ${file.name}`, life: 5000 })
      }
    }

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

        <!-- Attachments -->
        <div class="flex flex-col gap-2">
          <label class="text-sm font-medium">Załączniki</label>
          <FileUploadPrime
            mode="basic"
            :auto="false"
            :multiple="true"
            :custom-upload="true"
            @select="onSelectFiles"
            choose-label="Dodaj pliki"
            choose-icon="pi pi-paperclip"
            class="p-button-sm p-button-outlined p-button-secondary"
          />
          <div v-if="pendingFiles.length" class="flex flex-col gap-2 mt-1">
            <div
              v-for="(file, index) in pendingFiles"
              :key="index"
              class="flex items-center justify-between border border-surface-200 rounded-lg px-3 py-2"
            >
              <div class="flex items-center gap-2 min-w-0">
                <i class="pi pi-file text-surface-400 text-sm"></i>
                <span class="text-sm truncate">{{ file.name }}</span>
                <span class="text-xs text-surface-400">({{ formatSize(file.size) }})</span>
              </div>
              <Button icon="pi pi-times" text severity="danger" size="small" @click="removeFile(index)" />
            </div>
          </div>
        </div>
      </div>

      <Button type="submit" label="Wyślij zgłoszenie" icon="pi pi-send" :loading="loading" />
    </form>
  </div>
</template>
