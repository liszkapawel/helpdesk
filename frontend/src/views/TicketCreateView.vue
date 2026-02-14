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
  { label: 'Low', value: 'low' },
  { label: 'Medium', value: 'medium' },
  { label: 'High', value: 'high' },
  { label: 'Critical', value: 'critical' },
]

async function loadCategories() {
  const { data } = await api.get('/categories')
  categories.value = data
}

async function submit() {
  loading.value = true
  try {
    const payload: any = {
      title: title.value,
      description: description.value,
      priority: priority.value,
    }
    if (categoryId.value) {
      payload.category = categoryId.value
    }
    const { data } = await api.post('/tickets', payload)
    toast.add({ severity: 'success', summary: 'Created', detail: 'Ticket created', life: 3000 })
    router.push(`/tickets/${data.id}`)
  } catch (err: any) {
    const errors = err.response?.data?.errors
    const detail = errors ? Object.values(errors).join(', ') : 'Failed to create ticket'
    toast.add({ severity: 'error', summary: 'Error', detail, life: 5000 })
  } finally {
    loading.value = false
  }
}

onMounted(loadCategories)
</script>

<template>
  <div class="max-w-2xl">
    <h1 class="text-2xl font-bold mb-4">New Ticket</h1>

    <form class="flex flex-col gap-4" @submit.prevent="submit">
      <div class="flex flex-col gap-1">
        <label for="title">Title</label>
        <InputText id="title" v-model="title" placeholder="Ticket title" required />
      </div>

      <div class="flex flex-col gap-1">
        <label for="description">Description</label>
        <Textarea id="description" v-model="description" placeholder="Describe the issue..." rows="6" required />
      </div>

      <div class="flex gap-4">
        <div class="flex flex-col gap-1 flex-1">
          <label for="priority">Priority</label>
          <Select
            id="priority"
            v-model="priority"
            :options="priorities"
            option-label="label"
            option-value="value"
            placeholder="Select priority"
          />
        </div>

        <div class="flex flex-col gap-1 flex-1">
          <label for="category">Category</label>
          <Select
            id="category"
            v-model="categoryId"
            :options="categories"
            option-label="name"
            option-value="id"
            placeholder="Select category"
            show-clear
          />
        </div>
      </div>

      <div class="flex gap-2">
        <Button type="submit" label="Create Ticket" :loading="loading" />
        <Button type="button" label="Cancel" severity="secondary" text @click="router.back()" />
      </div>
    </form>
  </div>
</template>
