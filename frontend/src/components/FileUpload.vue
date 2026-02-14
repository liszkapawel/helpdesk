<script setup lang="ts">
import { ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import FileUploadPrime from 'primevue/fileupload'
import api from '@/services/api'

const props = defineProps<{
  uploadUrl: string
}>()

const emit = defineEmits<{
  uploaded: [attachment: any]
}>()

const toast = useToast()

async function onUpload(event: any) {
  const file = event.files[0]
  if (!file) return

  const formData = new FormData()
  formData.append('file', file)

  try {
    const { data } = await api.post(props.uploadUrl, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    emit('uploaded', data)
    toast.add({ severity: 'success', summary: 'Uploaded', detail: 'File uploaded', life: 3000 })
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Upload failed', life: 3000 })
  }
}
</script>

<template>
  <FileUploadPrime
    mode="basic"
    :auto="true"
    :custom-upload="true"
    @uploader="onUpload"
    choose-label="Attach File"
    class="p-button-sm"
  />
</template>
