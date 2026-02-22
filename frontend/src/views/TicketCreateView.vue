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

const router = useRouter()
const toast = useToast()

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

async function loadCategories() {
  const { data } = await api.get('/categories')
  categories.value = data
}

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
  loading.value = true
  try {
    const payload: any = { title: title.value, description: description.value, priority: priority.value }
    if (categoryId.value) payload.category = categoryId.value
    const { data } = await api.post('/tickets', payload)
    const ticketId = data.id

    // Upload pending files
    for (const file of pendingFiles.value) {
      const formData = new FormData()
      formData.append('file', file)
      try {
        await api.post(`/attachments/ticket/${ticketId}`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
      } catch {
        toast.add({ severity: 'warn', summary: 'Uwaga', detail: `Nie udało się przesłać: ${file.name}`, life: 5000 })
      }
    }

    toast.add({ severity: 'success', summary: 'Sukces', detail: 'Ticket utworzony', life: 3000 })
    router.push(`/tickets/${ticketId}`)
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

        <div class="flex gap-3 pt-2">
          <Button type="submit" label="Utwórz ticket" icon="pi pi-check" :loading="loading" />
          <Button type="button" label="Anuluj" severity="secondary" outlined @click="router.back()" />
        </div>
      </form>
    </div>
  </div>
</template>
