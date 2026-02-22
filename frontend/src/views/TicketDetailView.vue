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
import Rating from 'primevue/rating'
import TicketTimeline from '@/components/TicketTimeline.vue'
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

// Satisfaction rating
const ratingValue = ref(0)
const ratingComment = ref('')
const ratingLoading = ref(false)

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

// SLA computed
const hasSla = computed(() => ticket.value?.slaResponseDeadline)
const slaResponseStatus = computed(() => {
  if (!hasSla.value) return null
  if (ticket.value.slaResponseBreached) return 'breached'
  const deadline = new Date(ticket.value.slaResponseDeadline).getTime()
  const created = new Date(ticket.value.createdAt).getTime()
  const now = Date.now()
  const total = deadline - created
  const elapsed = now - created
  if (elapsed / total > 0.75) return 'warning'
  return 'ok'
})
const slaResolutionStatus = computed(() => {
  if (!hasSla.value) return null
  if (ticket.value.slaResolutionBreached) return 'breached'
  const deadline = new Date(ticket.value.slaResolutionDeadline).getTime()
  const created = new Date(ticket.value.createdAt).getTime()
  const now = Date.now()
  const total = deadline - created
  const elapsed = now - created
  if (elapsed / total > 0.75) return 'warning'
  return 'ok'
})

function slaColor(status: string | null) {
  if (status === 'breached') return 'text-red-600 bg-red-50'
  if (status === 'warning') return 'text-amber-600 bg-amber-50'
  return 'text-green-600 bg-green-50'
}

function timeLeft(deadline: string) {
  const diff = new Date(deadline).getTime() - Date.now()
  if (diff <= 0) return 'Przekroczono'
  const hours = Math.floor(diff / 3600000)
  const minutes = Math.floor((diff % 3600000) / 60000)
  if (hours > 24) return `${Math.floor(hours / 24)}d ${hours % 24}h`
  return `${hours}h ${minutes}m`
}

// Satisfaction
const canRate = computed(() =>
  isOwner.value &&
  ['resolved', 'closed'].includes(ticket.value?.status) &&
  !ticket.value?.satisfactionRating
)

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

async function submitRating() {
  if (!ratingValue.value) return
  ratingLoading.value = true
  try {
    const { data } = await api.post(`/tickets/${ticket.value.id}/rate`, {
      rating: ratingValue.value,
      comment: ratingComment.value || undefined,
    })
    ticket.value.satisfactionRating = data
    toast.add({ severity: 'success', summary: 'Dziękujemy', detail: 'Ocena została zapisana', life: 3000 })
  } catch {
    toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się zapisać oceny', life: 3000 })
  } finally {
    ratingLoading.value = false
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

    <!-- SLA Indicators (agents/admins only) -->
    <div v-if="hasSla && auth.isAgentOrAdmin" class="bg-surface-0 rounded-xl border border-surface-200 p-4 mb-6">
      <h3 class="text-sm font-semibold mb-3 flex items-center gap-2">
        <i class="pi pi-clock text-surface-400"></i>
        SLA
      </h3>
      <div class="grid grid-cols-2 gap-4">
        <div class="rounded-lg p-3" :class="slaColor(slaResponseStatus)">
          <div class="text-xs font-medium mb-1">Czas reakcji</div>
          <div class="text-lg font-bold">{{ timeLeft(ticket.slaResponseDeadline) }}</div>
          <div class="text-xs mt-1">Limit: {{ ticket.slaResponseHours }}h</div>
        </div>
        <div class="rounded-lg p-3" :class="slaColor(slaResolutionStatus)">
          <div class="text-xs font-medium mb-1">Czas rozwiązania</div>
          <div class="text-lg font-bold">{{ timeLeft(ticket.slaResolutionDeadline) }}</div>
          <div class="text-xs mt-1">Limit: {{ ticket.slaResolutionHours }}h</div>
        </div>
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

    <!-- Satisfaction Rating - show form or existing rating -->
    <div v-if="canRate" class="bg-surface-0 rounded-xl border border-green-200 p-6 mb-6">
      <h2 class="font-semibold mb-3 flex items-center gap-2">
        <i class="pi pi-star text-amber-500"></i>
        Oceń obsługę
      </h2>
      <p class="text-sm text-surface-500 mb-4">Jak oceniasz jakość obsługi tego zgłoszenia?</p>
      <div class="flex flex-col gap-3">
        <Rating v-model="ratingValue" :cancel="false" />
        <Textarea v-model="ratingComment" placeholder="Opcjonalny komentarz..." rows="2" />
        <div>
          <Button
            label="Wyślij ocenę"
            icon="pi pi-check"
            :loading="ratingLoading"
            :disabled="!ratingValue"
            @click="submitRating"
          />
        </div>
      </div>
    </div>

    <div v-if="ticket.satisfactionRating" class="bg-surface-0 rounded-xl border border-surface-200 p-6 mb-6">
      <h2 class="font-semibold mb-3 flex items-center gap-2">
        <i class="pi pi-star-fill text-amber-500"></i>
        Ocena obsługi
      </h2>
      <Rating :model-value="ticket.satisfactionRating.rating" :cancel="false" readonly />
      <p v-if="ticket.satisfactionRating.comment" class="text-sm text-surface-600 mt-2">{{ ticket.satisfactionRating.comment }}</p>
    </div>

    <!-- Timeline (merged comments + history) -->
    <div class="bg-surface-0 rounded-xl border border-surface-200 p-6 mb-6">
      <h2 class="font-semibold mb-4">Oś czasu</h2>
      <TicketTimeline
        :ticket-id="ticket.id"
        :comments="ticket.comments || []"
        @delete-comment="deleteComment"
      />

      <form class="flex gap-3 mt-6 pt-4 border-t border-surface-200" @submit.prevent="addComment">
        <Textarea v-model="commentContent" placeholder="Napisz komentarz..." rows="2" class="flex-1" />
        <Button type="submit" label="Wyślij" icon="pi pi-send" :loading="commentLoading" :disabled="!commentContent.trim()" />
      </form>
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
