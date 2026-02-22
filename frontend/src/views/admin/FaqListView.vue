<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import InputSwitch from 'primevue/inputswitch'
import Tag from 'primevue/tag'
import api from '@/services/api'

const toast = useToast()
const articles = ref<any[]>([])
const loading = ref(true)

const dialog = ref(false)
const editingArticle = ref<any>(null)
const form = ref({ title: '', content: '', isPublished: true, position: 0 })
const saving = ref(false)
const deleteDialog = ref(false)
const deletingId = ref<number | null>(null)

async function loadArticles() {
  loading.value = true
  try {
    const { data } = await api.get('/faq')
    articles.value = data
  } finally {
    loading.value = false
  }
}

function openNew() {
  editingArticle.value = null
  form.value = { title: '', content: '', isPublished: true, position: articles.value.length }
  dialog.value = true
}

function openEdit(article: any) {
  editingArticle.value = article
  form.value = {
    title: article.title,
    content: article.content,
    isPublished: article.isPublished,
    position: article.position,
  }
  dialog.value = true
}

async function save() {
  saving.value = true
  try {
    if (editingArticle.value) {
      await api.put(`/faq/${editingArticle.value.id}`, form.value)
    } else {
      await api.post('/faq', form.value)
    }
    dialog.value = false
    toast.add({ severity: 'success', summary: 'Zapisano', detail: 'Artykuł FAQ zapisany', life: 3000 })
    loadArticles()
  } catch {
    toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się zapisać', life: 3000 })
  } finally {
    saving.value = false
  }
}

function confirmDelete(id: number) {
  deletingId.value = id
  deleteDialog.value = true
}

async function deleteArticle() {
  if (!deletingId.value) return
  try {
    await api.delete(`/faq/${deletingId.value}`)
    deleteDialog.value = false
    toast.add({ severity: 'success', summary: 'Usunięto', detail: 'Artykuł usunięty', life: 3000 })
    loadArticles()
  } catch {
    toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się usunąć', life: 3000 })
  }
}

onMounted(loadArticles)
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">FAQ / Baza wiedzy</h1>
        <p class="text-surface-500 text-sm mt-1">Zarządzaj artykułami FAQ</p>
      </div>
      <Button label="Nowy artykuł" icon="pi pi-plus" @click="openNew" />
    </div>

    <div class="bg-surface-0 rounded-xl border border-surface-200">
      <DataTable :value="articles" :loading="loading" striped-rows>
        <template #empty>
          <div class="text-center py-8 text-surface-400">
            <i class="pi pi-book text-3xl mb-2"></i>
            <p>Brak artykułów FAQ</p>
          </div>
        </template>
        <Column field="position" header="#" style="width: 60px" />
        <Column field="title" header="Tytuł" />
        <Column header="Status" style="width: 120px">
          <template #body="{ data }">
            <Tag :value="data.isPublished ? 'Opublikowany' : 'Ukryty'" :severity="data.isPublished ? 'success' : 'secondary'" />
          </template>
        </Column>
        <Column header="Akcje" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-pencil" text severity="secondary" size="small" @click="openEdit(data)" />
              <Button icon="pi pi-trash" text severity="danger" size="small" @click="confirmDelete(data.id)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Edit/Create dialog -->
    <Dialog v-model:visible="dialog" :header="editingArticle ? 'Edytuj artykuł' : 'Nowy artykuł'" :modal="true" :style="{ width: '600px' }">
      <form class="flex flex-col gap-4" @submit.prevent="save">
        <div class="flex flex-col gap-1.5">
          <label class="text-sm font-medium">Tytuł</label>
          <InputText v-model="form.title" required />
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-sm font-medium">Treść</label>
          <Textarea v-model="form.content" rows="6" required />
        </div>
        <div class="flex items-center gap-3">
          <InputSwitch v-model="form.isPublished" />
          <span class="text-sm">Opublikowany</span>
        </div>
      </form>
      <template #footer>
        <Button label="Anuluj" severity="secondary" text @click="dialog = false" />
        <Button :label="editingArticle ? 'Zapisz' : 'Utwórz'" icon="pi pi-check" :loading="saving" @click="save" />
      </template>
    </Dialog>

    <!-- Delete dialog -->
    <Dialog v-model:visible="deleteDialog" header="Usuń artykuł" :modal="true" :style="{ width: '400px' }">
      <p>Czy na pewno chcesz usunąć ten artykuł?</p>
      <template #footer>
        <Button label="Anuluj" severity="secondary" text @click="deleteDialog = false" />
        <Button label="Usuń" severity="danger" @click="deleteArticle" />
      </template>
    </Dialog>
  </div>
</template>
