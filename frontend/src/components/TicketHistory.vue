<script setup lang="ts">
import { ref, onMounted } from 'vue'
import Timeline from 'primevue/timeline'
import api from '@/services/api'

const props = defineProps<{ ticketId: number }>()

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

function formatChanges(changes: any): string {
  if (!changes) return ''
  return Object.entries(changes)
    .map(([key, val]: [string, any]) => {
      if (val.from && val.to) return `${key}: ${val.from} → ${val.to}`
      if (val.to) return `${key}: ${val.to}`
      return `${key}`
    })
    .join(', ')
}

const actionLabels: Record<string, string> = {
  created: 'Created',
  updated: 'Updated',
  comment_added: 'Comment added',
  comment_deleted: 'Comment deleted',
}

onMounted(loadHistory)
</script>

<template>
  <div>
    <div v-if="loading" class="flex justify-center py-4">
      <i class="pi pi-spin pi-spinner"></i>
    </div>

    <div v-else-if="!history.length" class="text-surface-500 text-sm">No history yet.</div>

    <Timeline v-else :value="history" align="left">
      <template #content="{ item }">
        <div class="text-sm">
          <span class="font-semibold">{{ actionLabels[item.action] || item.action }}</span>
          <span class="text-surface-500 ml-2">
            by {{ item.performedBy?.firstName }} {{ item.performedBy?.lastName }}
          </span>
        </div>
        <div v-if="item.changes" class="text-xs text-surface-400 mt-1">
          {{ formatChanges(item.changes) }}
        </div>
      </template>
      <template #opposite="{ item }">
        <small class="text-surface-400">{{ new Date(item.createdAt).toLocaleString() }}</small>
      </template>
    </Timeline>
  </div>
</template>
