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

// Edit state
const editing = ref(false)
const editForm = ref({ title: '', description: '', status: '', priority: '', category: null as number | null, assignedTo: null as number | null })
const categories = ref<any[]>([])
const agents = ref<any[]>([])
const editLoading = ref(false)
const deleteDialog = ref(false)

const statuses = [
  { label: 'New', value: 'new' },
  { label: 'Open', value: 'open' },
  { label: 'In Progress', value: 'in_progress' },
  { label: 'Resolved', value: 'resolved' },
  { label: 'Closed', value: 'closed' },
]

const priorities = [
  { label: 'Low', value: 'low' },
  { label: 'Medium', value: 'medium' },
  { label: 'High', value: 'high' },
  { label: 'Critical', value: 'critical' },
]

const statusSeverity: Record<string, string> = {
  new: 'info',
  open: 'warn',
  in_progress: 'warn',
  resolved: 'success',
  closed: 'success',
}

const prioritySeverity: Record<string, string> = {
  low: 'info',
  medium: 'warn',
  high: 'danger',
  critical: 'danger',
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
    toast.add({ severity: 'error', summary: 'Error', detail: 'Ticket not found', life: 3000 })
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
  } catch {
    // ignore if not authorized
  }
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
    toast.add({ severity: 'success', summary: 'Updated', detail: 'Ticket updated', life: 3000 })
  } catch (err: any) {
    const errors = err.response?.data?.errors
    const detail = errors ? Object.values(errors).join(', ') : 'Failed to update'
    toast.add({ severity: 'error', summary: 'Error', detail, life: 5000 })
  } finally {
    editLoading.value = false
  }
}

async function deleteTicket() {
  try {
    await api.delete(`/tickets/${ticket.value.id}`)
    toast.add({ severity: 'success', summary: 'Deleted', detail: 'Ticket deleted', life: 3000 })
    router.push('/tickets')
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete', life: 3000 })
  }
}

async function addComment() {
  if (!commentContent.value.trim()) return
  commentLoading.value = true
  try {
    const { data } = await api.post(`/tickets/${ticket.value.id}/comments`, {
      content: commentContent.value,
    })
    ticket.value.comments.push(data)
    commentContent.value = ''
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to add comment', life: 3000 })
  } finally {
    commentLoading.value = false
  }
}

async function deleteComment(commentId: number) {
  try {
    await api.delete(`/tickets/${ticket.value.id}/comments/${commentId}`)
    ticket.value.comments = ticket.value.comments.filter((c: any) => c.id !== commentId)
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete comment', life: 3000 })
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
  <div v-if="loading" class="flex justify-center p-8">
    <i class="pi pi-spin pi-spinner text-4xl"></i>
  </div>

  <div v-else-if="ticket">
    <!-- Header -->
    <div class="flex justify-between items-start mb-4">
      <div>
        <Button icon="pi pi-arrow-left" text severity="secondary" @click="router.push('/tickets')" class="mb-2" />
        <h1 class="text-2xl font-bold">{{ ticket.title }}</h1>
        <div class="flex gap-2 mt-2">
          <Tag :value="ticket.status" :severity="(statusSeverity[ticket.status] as any) || 'secondary'" />
          <Tag :value="ticket.priority" :severity="(prioritySeverity[ticket.priority] as any) || 'secondary'" />
          <Tag v-if="ticket.category" :value="ticket.category.name" severity="secondary" />
        </div>
      </div>
      <div class="flex gap-2">
        <Button v-if="canEdit" label="Edit" icon="pi pi-pencil" severity="secondary" @click="startEdit" />
        <Button v-if="canDelete" label="Delete" icon="pi pi-trash" severity="danger" @click="deleteDialog = true" />
      </div>
    </div>

    <!-- Edit form -->
    <div v-if="editing" class="border rounded p-4 mb-4">
      <h2 class="text-lg font-bold mb-3">Edit Ticket</h2>
      <form class="flex flex-col gap-3" @submit.prevent="saveEdit">
        <div class="flex flex-col gap-1">
          <label>Title</label>
          <InputText v-model="editForm.title" required />
        </div>
        <div class="flex flex-col gap-1">
          <label>Description</label>
          <Textarea v-model="editForm.description" rows="4" required />
        </div>
        <div class="flex gap-4">
          <div class="flex flex-col gap-1 flex-1">
            <label>Status</label>
            <Select v-model="editForm.status" :options="statuses" option-label="label" option-value="value" />
          </div>
          <div class="flex flex-col gap-1 flex-1">
            <label>Priority</label>
            <Select v-model="editForm.priority" :options="priorities" option-label="label" option-value="value" />
          </div>
          <div class="flex flex-col gap-1 flex-1">
            <label>Category</label>
            <Select v-model="editForm.category" :options="categories" option-label="name" option-value="id" placeholder="None" show-clear />
          </div>
        </div>
        <div v-if="canAssign" class="flex flex-col gap-1">
          <label>Assign To</label>
          <Select v-model="editForm.assignedTo" :options="agents" :option-label="(a: any) => `${a.firstName} ${a.lastName}`" option-value="id" placeholder="Unassigned" show-clear />
        </div>
        <div class="flex gap-2">
          <Button type="submit" label="Save" :loading="editLoading" />
          <Button type="button" label="Cancel" severity="secondary" text @click="editing = false" />
        </div>
      </form>
    </div>

    <!-- Description -->
    <div v-if="!editing" class="mb-6">
      <h2 class="text-lg font-semibold mb-2">Description</h2>
      <p class="whitespace-pre-wrap">{{ ticket.description }}</p>
      <div class="text-sm text-surface-500 mt-2">
        Created by {{ ticket.createdBy?.firstName }} {{ ticket.createdBy?.lastName }}
        on {{ new Date(ticket.createdAt).toLocaleString() }}
        <span v-if="ticket.assignedTo">
          · Assigned to {{ ticket.assignedTo.firstName }} {{ ticket.assignedTo.lastName }}
        </span>
      </div>
    </div>

    <!-- Attachments -->
    <div v-if="!editing" class="mb-6">
      <div class="flex justify-between items-center mb-2">
        <h2 class="text-lg font-semibold">Attachments ({{ ticket.attachments?.length || 0 }})</h2>
        <FileUpload :upload-url="`/attachments/ticket/${ticket.id}`" @uploaded="onAttachmentUploaded" />
      </div>
      <AttachmentList :attachments="ticket.attachments || []" @deleted="onAttachmentDeleted" />
      <div v-if="!ticket.attachments?.length" class="text-surface-500 text-sm">No attachments.</div>
    </div>

    <!-- Comments -->
    <div>
      <h2 class="text-lg font-semibold mb-3">Comments ({{ ticket.comments?.length || 0 }})</h2>

      <div v-for="comment in ticket.comments" :key="comment.id" class="border rounded p-3 mb-2">
        <div class="flex justify-between items-start">
          <div>
            <span class="font-semibold">{{ comment.author?.firstName }} {{ comment.author?.lastName }}</span>
            <span class="text-sm text-surface-500 ml-2">{{ new Date(comment.createdAt).toLocaleString() }}</span>
          </div>
          <Button
            v-if="canDeleteComment(comment)"
            icon="pi pi-trash"
            text
            severity="danger"
            size="small"
            @click="deleteComment(comment.id)"
          />
        </div>
        <p class="mt-1 whitespace-pre-wrap">{{ comment.content }}</p>
      </div>

      <div v-if="!ticket.comments?.length" class="text-surface-500 mb-3">No comments yet.</div>

      <!-- Add comment -->
      <form class="flex gap-2 mt-3" @submit.prevent="addComment">
        <Textarea v-model="commentContent" placeholder="Add a comment..." rows="2" class="flex-1" />
        <Button type="submit" label="Comment" :loading="commentLoading" :disabled="!commentContent.trim()" />
      </form>
    </div>

    <!-- History -->
    <div class="mt-6">
      <h2 class="text-lg font-semibold mb-3">History</h2>
      <TicketHistory :ticket-id="ticket.id" />
    </div>

    <!-- Delete dialog -->
    <Dialog v-model:visible="deleteDialog" header="Delete Ticket" :modal="true" :style="{ width: '400px' }">
      <p>Are you sure you want to delete this ticket? This action cannot be undone.</p>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="deleteDialog = false" />
        <Button label="Delete" severity="danger" @click="deleteTicket" />
      </template>
    </Dialog>
  </div>
</template>
