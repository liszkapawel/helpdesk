<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import api from '@/services/api'

const toast = useToast()

const orgName = ref('')
const orgLoading = ref(false)
const invites = ref<any[]>([])
const inviteLoading = ref(false)
const inviteEmail = ref('')

async function loadOrg() {
  const { data } = await api.get('/organization')
  orgName.value = data.name
}

async function saveOrg() {
  orgLoading.value = true
  try {
    await api.put('/organization', { name: orgName.value })
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Organization updated', life: 3000 })
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to update', life: 3000 })
  } finally {
    orgLoading.value = false
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
    toast.add({ severity: 'success', summary: 'Created', detail: 'Invite created', life: 3000 })
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to create invite', life: 3000 })
  } finally {
    inviteLoading.value = false
  }
}

async function deleteInvite(id: number) {
  try {
    await api.delete(`/invites/${id}`)
    invites.value = invites.value.filter(i => i.id !== id)
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete invite', life: 3000 })
  }
}

function copyCode(code: string) {
  navigator.clipboard.writeText(code)
  toast.add({ severity: 'info', summary: 'Copied', detail: 'Invite code copied to clipboard', life: 2000 })
}

onMounted(() => {
  loadOrg()
  loadInvites()
})
</script>

<template>
  <div class="max-w-3xl">
    <h1 class="text-2xl font-bold mb-6">Organization Settings</h1>

    <!-- Org Name -->
    <div class="border rounded p-4 mb-6">
      <h2 class="text-lg font-semibold mb-3">Organization Name</h2>
      <form class="flex gap-2" @submit.prevent="saveOrg">
        <InputText v-model="orgName" class="flex-1" />
        <Button type="submit" label="Save" :loading="orgLoading" />
      </form>
    </div>

    <!-- Invites -->
    <div class="border rounded p-4">
      <h2 class="text-lg font-semibold mb-3">Invite Codes</h2>

      <form class="flex gap-2 mb-4" @submit.prevent="createInvite">
        <InputText v-model="inviteEmail" placeholder="Email (optional)" class="flex-1" />
        <Button type="submit" label="Create Invite" icon="pi pi-plus" :loading="inviteLoading" />
      </form>

      <DataTable :value="invites" striped-rows>
        <Column header="Code" style="width: 280px">
          <template #body="{ data }">
            <code class="text-sm">{{ data.code }}</code>
            <Button icon="pi pi-copy" text size="small" @click="copyCode(data.code)" class="ml-1" />
          </template>
        </Column>
        <Column field="email" header="Email">
          <template #body="{ data }">
            {{ data.email || '—' }}
          </template>
        </Column>
        <Column header="Status" style="width: 120px">
          <template #body="{ data }">
            <span v-if="data.usedBy" class="text-green-500">Used</span>
            <span v-else-if="new Date(data.expiresAt) < new Date()" class="text-red-500">Expired</span>
            <span v-else class="text-blue-500">Active</span>
          </template>
        </Column>
        <Column header="Expires" style="width: 160px">
          <template #body="{ data }">
            {{ new Date(data.expiresAt).toLocaleDateString() }}
          </template>
        </Column>
        <Column style="width: 60px">
          <template #body="{ data }">
            <Button icon="pi pi-trash" text severity="danger" size="small" @click="deleteInvite(data.id)" />
          </template>
        </Column>
      </DataTable>
    </div>
  </div>
</template>
