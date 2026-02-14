<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import api from '@/services/api'

const toast = useToast()
const users = ref<any[]>([])
const loading = ref(false)

const roleOptions = [
  { label: 'User', value: 'ROLE_USER' },
  { label: 'Agent', value: 'ROLE_AGENT' },
  { label: 'Admin', value: 'ROLE_ADMIN' },
]

const roleSeverity: Record<string, string> = {
  ROLE_USER: 'info',
  ROLE_AGENT: 'warn',
  ROLE_ADMIN: 'danger',
}

function getHighestRole(roles: string[]): string {
  if (roles.includes('ROLE_ADMIN')) return 'ROLE_ADMIN'
  if (roles.includes('ROLE_AGENT')) return 'ROLE_AGENT'
  return 'ROLE_USER'
}

function getRoleLabel(role: string): string {
  return roleOptions.find(r => r.value === role)?.label || role
}

async function loadUsers() {
  loading.value = true
  try {
    const { data } = await api.get('/admin/users')
    users.value = data
  } finally {
    loading.value = false
  }
}

async function changeRole(user: any, newRole: string) {
  try {
    const { data } = await api.put(`/admin/users/${user.id}/role`, { role: newRole })
    const idx = users.value.findIndex(u => u.id === user.id)
    if (idx !== -1) users.value[idx] = data
    toast.add({ severity: 'success', summary: 'Updated', detail: `Role changed to ${getRoleLabel(newRole)}`, life: 3000 })
  } catch {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to update role', life: 3000 })
  }
}

onMounted(loadUsers)
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold mb-4">User Management</h1>

    <DataTable :value="users" :loading="loading" striped-rows>
      <Column field="id" header="ID" style="width: 60px" />
      <Column header="Name">
        <template #body="{ data }">
          {{ data.firstName }} {{ data.lastName }}
        </template>
      </Column>
      <Column field="email" header="Email" />
      <Column header="Role" style="width: 200px">
        <template #body="{ data }">
          <Select
            :model-value="getHighestRole(data.roles)"
            :options="roleOptions"
            option-label="label"
            option-value="value"
            @update:model-value="(val: string) => changeRole(data, val)"
            class="w-full"
          />
        </template>
      </Column>
    </DataTable>
  </div>
</template>
