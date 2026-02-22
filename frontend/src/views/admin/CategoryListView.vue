<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import api from '@/services/api'

const toast = useToast()

const categories = ref<any[]>([])
const dialogVisible = ref(false)
const editingId = ref<number | null>(null)
const form = ref({ name: '', description: '' })
const saving = ref(false)

async function load() {
  const { data } = await api.get('/categories')
  categories.value = data
}

function openCreate() {
  editingId.value = null
  form.value = { name: '', description: '' }
  dialogVisible.value = true
}

function openEdit(cat: any) {
  editingId.value = cat.id
  form.value = { name: cat.name, description: cat.description || '' }
  dialogVisible.value = true
}

async function save() {
  if (!form.value.name.trim()) {
    toast.add({ severity: 'warn', summary: 'Uwaga', detail: 'Nazwa jest wymagana', life: 3000 })
    return
  }
  saving.value = true
  try {
    if (editingId.value) {
      await api.put(`/categories/${editingId.value}`, form.value)
    } else {
      await api.post('/categories', form.value)
    }
    dialogVisible.value = false
    await load()
    toast.add({ severity: 'success', summary: 'Zapisano', detail: editingId.value ? 'Kategoria zaktualizowana' : 'Kategoria utworzona', life: 3000 })
  } catch {
    toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się zapisać', life: 3000 })
  } finally {
    saving.value = false
  }
}

async function remove(id: number) {
  try {
    await api.delete(`/categories/${id}`)
    categories.value = categories.value.filter(c => c.id !== id)
    toast.add({ severity: 'success', summary: 'Usunięto', detail: 'Kategoria usunięta', life: 3000 })
  } catch {
    toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się usunąć', life: 3000 })
  }
}

onMounted(load)
</script>

<template>
  <div class="max-w-3xl">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Kategorie</h1>
        <p class="text-surface-500 text-sm mt-1">Zarządzaj kategoriami zgłoszeń</p>
      </div>
      <Button label="Nowa kategoria" icon="pi pi-plus" @click="openCreate" />
    </div>

    <div class="bg-surface-0 rounded-xl border border-surface-200">
      <DataTable :value="categories" striped-rows>
        <Column field="name" header="Nazwa" />
        <Column field="description" header="Opis">
          <template #body="{ data }">
            {{ data.description || '-' }}
          </template>
        </Column>
        <Column header="Akcje" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" text size="small" @click="openEdit(data)" />
              <Button icon="pi pi-trash" text severity="danger" size="small" @click="remove(data.id)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <Dialog v-model:visible="dialogVisible" :header="editingId ? 'Edytuj kategorię' : 'Nowa kategoria'" modal class="w-[28rem]">
      <form class="flex flex-col gap-4" @submit.prevent="save">
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Nazwa *</label>
          <InputText v-model="form.name" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Opis</label>
          <Textarea v-model="form.description" rows="3" />
        </div>
        <div class="flex justify-end gap-2">
          <Button label="Anuluj" severity="secondary" text @click="dialogVisible = false" />
          <Button type="submit" :label="editingId ? 'Zapisz' : 'Utwórz'" :loading="saving" />
        </div>
      </form>
    </Dialog>
  </div>
</template>
