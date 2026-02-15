<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Textarea from 'primevue/textarea'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Dialog from 'primevue/dialog'
import TicketHistory from '@/components/TicketHistory.vue'
import FileUpload from '@/components/FileUpload.vue'
import AttachmentList from '@/components/AttachmentList.vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const auth = useAuthStore()

const ticket = ref<any>(null)
const loading = ref(true)
const commentContent = ref('')
const commentLoading = ref(false)

const editing = ref(false)
const editForm = ref({ title: '', description: '', status: '', priority: '', category: null as number | null, assignedTo: null as number | null })
const categories = ref<any[]>([])
const agents = ref<any[]>([])
const editLoading = ref(false)
const deleteDialog = ref(false)

const statuses = [
  { label: 'Nowy', value: 'new' },
  { label: 'Otwarty', value: 'open' },
  { label: 'W toku', value: 'in_progress' },
  { label: 'Rozwiązany', value: 'resolved' },
  { label: 'Zamknięty', value: 'closed' },
]
const priorities = [
  { label: 'Niski', value: 'low' },
  { label: 'Średni', value: 'medium' },
  { label: 'Wysoki', value: 'high' },
  { label: 'Krytyczny', value: 'critical' },
]

const statusSeverity: Record<string, string> = {
  new: 'info', open: 'warn', in_progress: 'warn', resolved: 'success', closed: 'secondary',
}
const prioritySeverity: Record<string, string> = {
  low: 'info', medium: 'warn', high: 'danger', critical: 'danger',
}
const statusLabels: Record<string, string> = {
  new: 'Nowy', open: 'Otwarty', in_progress: 'W toku', resolved: 'Rozwiązany', closed: 'Zamknięty',
}
const priorityLabels: Record<string, string> = {
  low: 'Niski', medium: 'Średni', high: 'Wysoki', critical: 'Krytyczny',
}

const isOwner = computed(() => ticket.value?.createdBy?.id === auth.user?.id)
const canEdit = computed(() => isOwner.value || auth.isAgentOrAdmin)
const canDelete = computed(() => isOwner.value || auth.isAdmin)
const canAssign = computed(() => auth.isAgentOrAdmin)
const canDeleteComment = (comment: any) => comment.author?.id === auth.user?.id || auth.isAgentOrAdmin

async function loadTicket() {
  loading.value = true
  try {
    const { data } = await api.get(`/tickets/${route.params.id}`)
    ticket.value = data
  } catch {
    toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie znaleziono ticketa', life: 3000 })
    router.push('/tickets')
  } finally {
    loading.value = false
  }
}

async function loadCategories() {
  const { data } = await api.get('/categories')
  categories.value = data
}

async function loadAgents() {
  if (!auth.isAgentOrAdmin) return
  try {
    const { data } = await api.get('/admin/agents')
    agents.value = data
  } catch { /* ignore */ }
}

function startEdit() {
  editForm.value = {
    title: ticket.value.title,
    description: ticket.value.description,
    status: ticket.value.status,
    priority: ticket.value.priority,
    category: ticket.value.category?.id || null,
    assignedTo: ticket.value.assignedTo?.id || null,
  }
  editing.value = true
  loadCategories()
  loadAgents()
}

async function saveEdit() {
  editLoading.value = true
  try {
    const payload: any = { ...editForm.value }
    if (!payload.category) delete payload.category
    const { data } = await api.put(`/tickets/${ticket.value.id}`, payload)
    ticket.value = data
    editing.value = false
    toast.add({ severity: 'success', summary: 'Zaktualizowano', detail: 'Ticket zaktualizowany', life: 3000 })
  } catch (err: any) {
    const errors = err.response?.data?.errors
    const detail = errors ? Object.values(errors).join(', ') : 'Nie udało się zaktualizować'
    toast.add({ severity: 'error', summary: 'Błąd', detail, life: 5000 })
  } finally {
    editLoading.value = false
  }
}

async function deleteTicket() {
  try {
    await api.delete(`/tickets/${ticket.value.id}`)
    toast.add({ severity: 'success', summary: 'Usunięto', detail: 'Ticket usunięty', life: 3000 })
    router.push('/tickets')
  } catch {
    toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się usunąć', life: 3000 })
  }
}

async function addComment() {
  if (!commentContent.value.trim()) return
  commentLoading.value = true
  try {
    const { data } = await api.post(`/tickets/${ticket.value.id}/comments`, { content: commentContent.value })
    ticket.value.comments.push(data)
    commentContent.value = ''
  } catch {
    toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się dodać komentarza', life: 3000 })
  } finally {
    commentLoading.value = false
  }
}

async function deleteComment(commentId: number) {
  try {
    await api.delete(`/tickets/${ticket.value.id}/comments/${commentId}`)
    ticket.value.comments = ticket.value.comments.filter((c: any) => c.id !== commentId)
  } catch {
    toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się usunąć komentarza', life: 3000 })
  }
}

function onAttachmentUploaded(att: any) {
  if (!ticket.value.attachments) ticket.value.attachments = []
  ticket.value.attachments.push(att)
}

function onAttachmentDeleted(id: number) {
  ticket.value.attachments = ticket.value.attachments.filter((a: any) => a.id !== id)
}

onMounted(loadTicket)
</script>

<template>
  <div v-if="loading" class="flex justify-center py-16">
    <i class="pi pi-spin pi-spinner text-4xl text-surface-400"></i>
  </div>

  <div v-else-if="ticket" class="max-w-4xl">
    <!-- Back -->
    <div class="mb-4">
      <Button icon="pi pi-arrow-left" text severity="secondary" size="small" @click="router.push('/tickets')" label="Wróć do listy" />
    </div>

    <!-- Header -->
    <div class="bg-surface-0 rounded-xl border border-surface-200 p-6 mb-6">
      <div class="flex justify-between items-start">
        <div>
          <div class="flex items-center gap-2 mb-2">
            <span class="text-surface-400 text-sm font-mono">#{{ ticket.id }}</span>
            <Tag :value="statusLabels[ticket.status] || ticket.status" :severity="(statusSeverity[ticket.status] as any) || 'secondary'" />
            <Tag :value="priorityLabels[ticket.priority] || ticket.priority" :severity="(prioritySeverity[ticket.priority] as any) || 'secondary'" />
            <Tag v-if="ticket.category" :value="ticket.category.name" severity="contrast" />
          </div>
          <h1 class="text-xl font-bold">{{ ticket.title }}</h1>
        </div>
        <div class="flex gap-2">
          <Button v-if="canEdit" label="Edytuj" icon="pi pi-pencil" severity="secondary" outlined size="small" @click="startEdit" />
          <Button v-if="canDelete" icon="pi pi-trash" severity="danger" outlined size="small" @click="deleteDialog = true" />
        </div>
      </div>

      <div class="text-sm text-surface-500 mt-4 flex items-center gap-4">
        <span>
          <i class="pi pi-user text-xs mr-1"></i>
          {{ ticket.createdBy?.firstName }} {{ ticket.createdBy?.lastName }}
        </span>
        <span>
          <i class="pi pi-calendar text-xs mr-1"></i>
          {{ new Date(ticket.createdAt).toLocaleString('pl-PL') }}
        </span>
        <span v-if="ticket.assignedTo">
          <i class="pi pi-id-card text-xs mr-1"></i>
          Przypisany: {{ ticket.assignedTo.firstName }} {{ ticket.assignedTo.lastName }}
        </span>
      </div>
    </div>

    <!-- Edit form -->
    <div v-if="editing" class="bg-surface-0 rounded-xl border border-surface-200 p-6 mb-6">
      <h2 class="font-semibold mb-4">Edytuj ticket</h2>
      <form class="flex flex-col gap-4" @submit.prevent="saveEdit">
        <div class="flex flex-col gap-1.5">
          <label class="text-sm font-medium">Tytuł</label>
          <InputText v-model="editForm.title" required />
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-sm font-medium">Opis</label>
          <Textarea v-model="editForm.description" rows="4" required />
        </div>
        <div class="grid grid-cols-3 gap-4">
          <div class="flex flex-col gap-1.5">
            <label class="text-sm font-medium">Status</label>
            <Select v-model="editForm.status" :options="statuses" option-label="label" option-value="value" />
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-sm font-medium">Priorytet</label>
            <Select v-model="editForm.priority" :options="priorities" option-label="label" option-value="value" />
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-sm font-medium">Kategoria</label>
            <Select v-model="editForm.category" :options="categories" option-label="name" option-value="id" placeholder="Brak" show-clear />
          </div>
        </div>
        <div v-if="canAssign" class="flex flex-col gap-1.5">
          <label class="text-sm font-medium">Przypisz do</label>
          <Select v-model="editForm.assignedTo" :options="agents" :option-label="(a: any) => `${a.firstName} ${a.lastName}`" option-value="id" placeholder="Nieprzypisany" show-clear />
        </div>
        <div class="flex gap-3 pt-2">
          <Button type="submit" label="Zapisz" icon="pi pi-check" :loading="editLoading" />
          <Button type="button" label="Anuluj" severity="secondary" outlined @click="editing = false" />
        </div>
      </form>
    </div>

    <!-- Description -->
    <div v-if="!editing" class="bg-surface-0 rounded-xl border border-surface-200 p-6 mb-6">
      <h2 class="font-semibold mb-3">Opis</h2>
      <p class="whitespace-pre-wrap text-surface-700 leading-relaxed">{{ ticket.description }}</p>
    </div>

    <!-- Attachments -->
    <div v-if="!editing" class="bg-surface-0 rounded-xl border border-surface-200 p-6 mb-6">
      <div class="flex justify-between items-center mb-4">
        <h2 class="font-semibold">Załączniki ({{ ticket.attachments?.length || 0 }})</h2>
        <FileUpload :upload-url="`/attachments/ticket/${ticket.id}`" @uploaded="onAttachmentUploaded" />
      </div>
      <AttachmentList :attachments="ticket.attachments || []" @deleted="onAttachmentDeleted" />
      <div v-if="!ticket.attachments?.length" class="text-surface-400 text-sm">Brak załączników</div>
    </div>

    <!-- Comments -->
    <div class="bg-surface-0 rounded-xl border border-surface-200 p-6 mb-6">
      <h2 class="font-semibold mb-4">Komentarze ({{ ticket.comments?.length || 0 }})</h2>

      <div v-for="comment in ticket.comments" :key="comment.id" class="border border-surface-200 rounded-lg p-4 mb-3">
        <div class="flex justify-between items-start">
          <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-full bg-surface-100 flex items-center justify-center">
              <span class="text-xs font-semibold text-surface-500">{{ comment.author?.firstName?.[0] }}{{ comment.author?.lastName?.[0] }}</span>
            </div>
            <span class="font-medium text-sm">{{ comment.author?.firstName }} {{ comment.author?.lastName }}</span>
            <span class="text-xs text-surface-400">{{ new Date(comment.createdAt).toLocaleString('pl-PL') }}</span>
          </div>
          <Button v-if="canDeleteComment(comment)" icon="pi pi-trash" text severity="danger" size="small" @click="deleteComment(comment.id)" />
        </div>
        <p class="mt-2 whitespace-pre-wrap text-sm text-surface-700">{{ comment.content }}</p>
      </div>

      <div v-if="!ticket.comments?.length" class="text-surface-400 text-sm mb-4">Brak komentarzy</div>

      <form class="flex gap-3 mt-4" @submit.prevent="addComment">
        <Textarea v-model="commentContent" placeholder="Napisz komentarz..." rows="2" class="flex-1" />
        <Button type="submit" label="Wyślij" icon="pi pi-send" :loading="commentLoading" :disabled="!commentContent.trim()" />
      </form>
    </div>

    <!-- History -->
    <div class="bg-surface-0 rounded-xl border border-surface-200 p-6">
      <h2 class="font-semibold mb-4">Historia zmian</h2>
      <TicketHistory :ticket-id="ticket.id" />
    </div>

    <!-- Delete dialog -->
    <Dialog v-model:visible="deleteDialog" header="Usuń ticket" :modal="true" :style="{ width: '400px' }">
      <p>Czy na pewno chcesz usunąć ten ticket? Tej operacji nie można cofnąć.</p>
      <template #footer>
        <Button label="Anuluj" severity="secondary" text @click="deleteDialog = false" />
        <Button label="Usuń" severity="danger" @click="deleteTicket" />
      </template>
    </Dialog>
  </div>
</template>
