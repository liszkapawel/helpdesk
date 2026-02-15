<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import ColorPicker from 'primevue/colorpicker'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import FileUpload from 'primevue/fileupload'
import api from '@/services/api'

const toast = useToast()
const baseDomain = import.meta.env.VITE_BASE_DOMAIN || 'helpdesk.local'

const orgName = ref('')
const orgSlug = ref('')
const orgDescription = ref('')
const orgColor = ref('3B82F6')
const orgLogoPath = ref<string | null>(null)
const orgLoading = ref(false)
const invites = ref<any[]>([])
const inviteLoading = ref(false)
const inviteEmail = ref('')

async function loadOrg() {
  const { data } = await api.get('/organization')
  orgName.value = data.name
  orgSlug.value = data.slug
  orgDescription.value = data.description || ''
  orgColor.value = (data.primaryColor || '#3B82F6').replace('#', '')
  orgLogoPath.value = data.logoPath
}

async function saveOrg() {
  orgLoading.value = true
  try {
    await api.put('/organization', {
      name: orgName.value,
      description: orgDescription.value || null,
      primaryColor: '#' + orgColor.value,
    })
    toast.add({ severity: 'success', summary: 'Zapisano', detail: 'Organizacja zaktualizowana', life: 3000 })
  } catch {
    toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się zaktualizować', life: 3000 })
  } finally {
    orgLoading.value = false
  }
}

async function uploadLogo(event: any) {
  const file = event.files[0]
  if (!file) return

  const formData = new FormData()
  formData.append('logo', file)

  try {
    const { data } = await api.post('/organization/logo', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    orgLogoPath.value = data.logoPath
    toast.add({ severity: 'success', summary: 'Zapisano', detail: 'Logo zaktualizowane', life: 3000 })
  } catch {
    toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się przesłać logo', life: 3000 })
  }
}

async function loadInvites() {
  const { data } = await api.get('/invites')
  invites.value = data
}

async function createInvite() {
  inviteLoading.value = true
  try {
    await api.post('/invites', { email: inviteEmail.value || null })
    inviteEmail.value = ''
    await loadInvites()
    toast.add({ severity: 'success', summary: 'Utworzono', detail: 'Zaproszenie utworzone', life: 3000 })
  } catch {
    toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się utworzyć zaproszenia', life: 3000 })
  } finally {
    inviteLoading.value = false
  }
}

async function deleteInvite(id: number) {
  try {
    await api.delete(`/invites/${id}`)
    invites.value = invites.value.filter(i => i.id !== id)
  } catch {
    toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się usunąć zaproszenia', life: 3000 })
  }
}

function copyCode(code: string) {
  navigator.clipboard.writeText(code)
  toast.add({ severity: 'info', summary: 'Skopiowano', detail: 'Kod skopiowany do schowka', life: 2000 })
}

function getInviteStatus(invite: any): { label: string; severity: string } {
  if (invite.usedBy) return { label: 'Wykorzystane', severity: 'success' }
  if (new Date(invite.expiresAt) < new Date()) return { label: 'Wygasłe', severity: 'danger' }
  return { label: 'Aktywne', severity: 'info' }
}

onMounted(() => {
  loadOrg()
  loadInvites()
})
</script>

<template>
  <div class="max-w-3xl">
    <div class="mb-6">
      <h1 class="text-2xl font-bold tracking-tight">Ustawienia organizacji</h1>
      <p class="text-surface-500 text-sm mt-1">Zarządzaj organizacją, brandingiem i zaproszeniami</p>
    </div>

    <!-- Org settings -->
    <div class="bg-surface-0 rounded-xl border border-surface-200 p-6 mb-6">
      <h2 class="font-semibold mb-4">Dane organizacji</h2>
      <form class="flex flex-col gap-4" @submit.prevent="saveOrg">
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Nazwa</label>
          <InputText v-model="orgName" />
        </div>
        <div class="flex flex-col gap-1">
          <label class="text-sm font-medium">Opis</label>
          <Textarea v-model="orgDescription" rows="3" placeholder="Opis widoczny na portalu publicznym..." />
        </div>
        <div class="flex items-center gap-4">
          <div class="flex flex-col gap-1">
            <label class="text-sm font-medium">Kolor główny</label>
            <ColorPicker v-model="orgColor" />
          </div>
          <div class="flex flex-col gap-1 flex-1">
            <label class="text-sm font-medium">Podgląd</label>
            <div class="h-10 rounded-lg" :style="{ backgroundColor: '#' + orgColor }"></div>
          </div>
        </div>
        <Button type="submit" label="Zapisz" :loading="orgLoading" class="self-start" />
      </form>
    </div>

    <!-- Logo -->
    <div class="bg-surface-0 rounded-xl border border-surface-200 p-6 mb-6">
      <h2 class="font-semibold mb-4">Logo</h2>
      <div class="flex items-center gap-6">
        <div>
          <img v-if="orgLogoPath" :src="orgLogoPath" alt="Logo" class="h-16 w-16 object-contain rounded-lg border border-surface-200" />
          <div v-else class="h-16 w-16 rounded-lg bg-surface-100 flex items-center justify-center text-surface-400">
            <i class="pi pi-image text-2xl"></i>
          </div>
        </div>
        <FileUpload
          mode="basic"
          accept="image/*"
          :maxFileSize="2000000"
          chooseLabel="Zmień logo"
          auto
          customUpload
          @uploader="uploadLogo"
        />
      </div>
    </div>

    <!-- Portal URL -->
    <div class="bg-surface-0 rounded-xl border border-surface-200 p-6 mb-6">
      <h2 class="font-semibold mb-4">Portal publiczny</h2>
      <div class="flex items-center gap-2">
        <span class="text-sm text-surface-500">URL portalu:</span>
        <code class="text-sm bg-surface-100 px-2 py-1 rounded">http://{{ orgSlug }}.{{ baseDomain }}</code>
        <Button
          icon="pi pi-copy"
          text
          size="small"
          @click="copyCode(`http://${orgSlug}.${baseDomain}`)"
        />
      </div>
    </div>

    <!-- Invites -->
    <div class="bg-surface-0 rounded-xl border border-surface-200 p-6">
      <h2 class="font-semibold mb-4">Kody zaproszeń</h2>

      <form class="flex gap-3 mb-6" @submit.prevent="createInvite">
        <InputText v-model="inviteEmail" placeholder="Email (opcjonalnie)" class="flex-1" />
        <Button type="submit" label="Utwórz zaproszenie" icon="pi pi-plus" :loading="inviteLoading" />
      </form>

      <DataTable :value="invites" striped-rows>
        <Column header="Kod" style="width: 280px">
          <template #body="{ data }">
            <div class="flex items-center gap-1">
              <code class="text-xs bg-surface-100 px-2 py-1 rounded">{{ data.code }}</code>
              <Button icon="pi pi-copy" text size="small" @click="copyCode(data.code)" />
            </div>
          </template>
        </Column>
        <Column field="email" header="Email">
          <template #body="{ data }">
            {{ data.email || '—' }}
          </template>
        </Column>
        <Column header="Status" style="width: 130px">
          <template #body="{ data }">
            <Tag :value="getInviteStatus(data).label" :severity="getInviteStatus(data).severity as any" />
          </template>
        </Column>
        <Column header="Wygasa" style="width: 130px">
          <template #body="{ data }">
            {{ new Date(data.expiresAt).toLocaleDateString('pl-PL') }}
          </template>
        </Column>
        <Column style="width: 50px">
          <template #body="{ data }">
            <Button icon="pi pi-trash" text severity="danger" size="small" @click="deleteInvite(data.id)" />
          </template>
        </Column>
      </DataTable>
    </div>
  </div>
</template>
