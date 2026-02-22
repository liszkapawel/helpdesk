<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import Button from 'primevue/button'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const props = defineProps<{
  ticketId: number
  comments: any[]
}>()

const emit = defineEmits<{
  (e: 'deleteComment', commentId: number): void
}>()

const auth = useAuthStore()
const history = ref<any[]>([])
const loading = ref(true)

async function loadHistory() {
  loading.value = true
  try {
    const { data } = await api.get(`/tickets/${props.ticketId}/history`)
    history.value = data
  } finally {
    loading.value = false
  }
}

interface TimelineItem {
  type: 'comment' | 'created' | 'status' | 'assignment' | 'priority' | 'update'
  date: string
  icon: string
  color: string
  title: string
  detail?: string
  user?: string
  commentId?: number
  commentAuthor?: any
  commentContent?: string
}

const timelineItems = computed<TimelineItem[]>(() => {
  const items: TimelineItem[] = []

  // Add audit log entries
  for (const entry of history.value) {
    const user = entry.performedBy
      ? `${entry.performedBy.firstName} ${entry.performedBy.lastName}`
      : 'System'

    if (entry.action === 'created') {
      items.push({
        type: 'created',
        date: entry.createdAt,
        icon: 'pi pi-plus-circle',
        color: '#3B82F6',
        title: 'Ticket utworzony',
        detail: entry.changes?.priority ? `Priorytet: ${entry.changes.priority}` : undefined,
        user,
      })
    } else if (entry.action === 'updated') {
      const changes = entry.changes || {}
      if (changes.status) {
        items.push({
          type: 'status',
          date: entry.createdAt,
          icon: 'pi pi-sync',
          color: '#F97316',
          title: 'Zmiana statusu',
          detail: `${changes.status.from} → ${changes.status.to}`,
          user,
        })
      }
      if (changes.assignedTo) {
        items.push({
          type: 'assignment',
          date: entry.createdAt,
          icon: 'pi pi-user-edit',
          color: '#8B5CF6',
          title: 'Zmiana przypisania',
          detail: `${changes.assignedTo.from || 'Brak'} → ${changes.assignedTo.to || 'Brak'}`,
          user,
        })
      }
      if (changes.priority) {
        items.push({
          type: 'priority',
          date: entry.createdAt,
          icon: 'pi pi-exclamation-triangle',
          color: '#EF4444',
          title: 'Zmiana priorytetu',
          detail: `${changes.priority.from} → ${changes.priority.to}`,
          user,
        })
      }
      // Other changes (title, description, category)
      const otherKeys = Object.keys(changes).filter(k => !['status', 'assignedTo', 'priority'].includes(k))
      if (otherKeys.length > 0 && !changes.status && !changes.assignedTo && !changes.priority) {
        items.push({
          type: 'update',
          date: entry.createdAt,
          icon: 'pi pi-pencil',
          color: '#6B7280',
          title: 'Aktualizacja',
          detail: otherKeys.join(', '),
          user,
        })
      }
    } else if (entry.action === 'comment_added') {
      // Skip - we'll add comments from props.comments
    }
  }

  // Add comments
  for (const comment of props.comments) {
    items.push({
      type: 'comment',
      date: comment.createdAt,
      icon: 'pi pi-comment',
      color: '#10B981',
      title: 'Komentarz',
      user: `${comment.author?.firstName} ${comment.author?.lastName}`,
      commentId: comment.id,
      commentAuthor: comment.author,
      commentContent: comment.content,
    })
  }

  // Sort by date ascending
  items.sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime())

  return items
})

const canDeleteComment = (comment: any) => comment?.id === auth.user?.id || auth.isAgentOrAdmin

onMounted(loadHistory)
</script>

<template>
  <div>
    <div v-if="loading" class="flex justify-center py-4">
      <i class="pi pi-spin pi-spinner text-surface-400"></i>
    </div>

    <div v-else-if="!timelineItems.length" class="text-surface-400 text-sm">Brak aktywności</div>

    <div v-else class="relative">
      <!-- Timeline line -->
      <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-surface-200"></div>

      <div v-for="(item, index) in timelineItems" :key="index" class="relative pl-11 pb-6 last:pb-0">
        <!-- Dot -->
        <div
          class="absolute left-2 w-5 h-5 rounded-full flex items-center justify-center"
          :style="{ backgroundColor: item.color + '20' }"
        >
          <i :class="item.icon" class="text-xs" :style="{ color: item.color }"></i>
        </div>

        <!-- Content -->
        <div v-if="item.type === 'comment'" class="border border-surface-200 rounded-lg p-4">
          <div class="flex justify-between items-start">
            <div class="flex items-center gap-2">
              <div class="w-7 h-7 rounded-full bg-surface-100 flex items-center justify-center">
                <span class="text-xs font-semibold text-surface-500">{{ item.commentAuthor?.firstName?.[0] }}{{ item.commentAuthor?.lastName?.[0] }}</span>
              </div>
              <span class="font-medium text-sm">{{ item.user }}</span>
              <span class="text-xs text-surface-400">{{ new Date(item.date).toLocaleString('pl-PL') }}</span>
            </div>
            <Button
              v-if="canDeleteComment(item.commentAuthor)"
              icon="pi pi-trash"
              text
              severity="danger"
              size="small"
              @click="emit('deleteComment', item.commentId!)"
            />
          </div>
          <p class="mt-2 whitespace-pre-wrap text-sm text-surface-700">{{ item.commentContent }}</p>
        </div>

        <div v-else>
          <div class="flex items-center gap-2">
            <span class="font-medium text-sm">{{ item.title }}</span>
            <span class="text-xs text-surface-400">{{ new Date(item.date).toLocaleString('pl-PL') }}</span>
          </div>
          <div class="text-xs text-surface-500 mt-0.5">
            <span v-if="item.detail">{{ item.detail }} &middot; </span>
            <span>{{ item.user }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
