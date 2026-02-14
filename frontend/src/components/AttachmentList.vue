<script setup lang="ts">
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const props = defineProps<{
  attachments: any[]
}>()

const emit = defineEmits<{
  deleted: [id: number]
}>()

const toast = useToast()
const auth = useAuthStore()

function isImage(mimeType: string): boolean {
  return mimeType.startsWith('image/')
}

async function download(attachment: any) {
  try {
    const response = await api.get(`/attachments/${attachment.id}/download`, {
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', attachment.originalName)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Download failed', life: 3000 })
  }
}

async function remove(id: number) {
  try {
    await api.delete(`/attachments/${id}`)
    emit('deleted', id)
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete', life: 3000 })
  }
}

function canDelete(attachment: any): boolean {
  return attachment.uploadedBy?.id === auth.user?.id || auth.isAdmin
}

function formatSize(bytes: number): string {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / 1048576).toFixed(1) + ' MB'
}
</script>

<template>
  <div v-if="attachments?.length" class="flex flex-col gap-2">
    <div v-for="att in attachments" :key="att.id" class="flex items-center gap-2 border rounded p-2">
      <i :class="isImage(att.mimeType) ? 'pi pi-image' : 'pi pi-file'" class="text-lg"></i>
      <div class="flex-1 min-w-0">
        <div class="text-sm truncate">{{ att.originalName }}</div>
        <div class="text-xs text-surface-400">{{ formatSize(att.size) }}</div>
      </div>
      <Button icon="pi pi-download" text size="small" @click="download(att)" />
      <Button v-if="canDelete(att)" icon="pi pi-trash" text severity="danger" size="small" @click="remove(att.id)" />
    </div>
  </div>
</template>
