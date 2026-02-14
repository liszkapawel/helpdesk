<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import Popover from 'primevue/popover'
import api from '@/services/api'

const router = useRouter()
const notifications = ref<any[]>([])
const unreadCount = ref(0)
const popover = ref()
let pollInterval: ReturnType<typeof setInterval> | null = null

async function loadNotifications() {
  try {
    const { data } = await api.get('/notifications')
    notifications.value = data.data
    unreadCount.value = data.unreadCount
  } catch {
    // ignore
  }
}

function toggle(event: Event) {
  popover.value.toggle(event)
}

async function markRead(id: number) {
  await api.put(`/notifications/${id}/read`)
  const n = notifications.value.find(n => n.id === id)
  if (n) {
    n.isRead = true
    unreadCount.value = Math.max(0, unreadCount.value - 1)
  }
}

async function markAllRead() {
  await api.put('/notifications/read-all')
  notifications.value.forEach(n => n.isRead = true)
  unreadCount.value = 0
}

function goTo(notification: any) {
  if (!notification.isRead) {
    markRead(notification.id)
  }
  if (notification.link) {
    router.push(notification.link)
  }
  popover.value.hide()
}

onMounted(() => {
  loadNotifications()
  pollInterval = setInterval(loadNotifications, 30000)
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})
</script>

<template>
  <div class="relative">
    <Button icon="pi pi-bell" text rounded severity="secondary" @click="toggle" class="relative">
      <template #icon>
        <i class="pi pi-bell text-lg"></i>
        <Badge v-if="unreadCount > 0" :value="unreadCount" severity="danger" class="absolute -top-1 -right-1" style="min-width: 18px; height: 18px; font-size: 0.7rem;" />
      </template>
    </Button>

    <Popover ref="popover" class="w-80">
      <div class="flex justify-between items-center mb-2 px-1">
        <span class="font-semibold">Notifications</span>
        <Button v-if="unreadCount > 0" label="Mark all read" text size="small" @click="markAllRead" />
      </div>

      <div v-if="!notifications.length" class="text-surface-500 text-sm text-center py-4">
        No notifications
      </div>

      <div class="max-h-80 overflow-y-auto">
        <div
          v-for="n in notifications"
          :key="n.id"
          class="px-2 py-2 cursor-pointer rounded hover:bg-surface-100 dark:hover:bg-surface-700 transition-colors"
          :class="{ 'opacity-60': n.isRead }"
          @click="goTo(n)"
        >
          <div class="text-sm">{{ n.message }}</div>
          <div class="text-xs text-surface-400 mt-1">{{ new Date(n.createdAt).toLocaleString() }}</div>
        </div>
      </div>
    </Popover>
  </div>
</template>
