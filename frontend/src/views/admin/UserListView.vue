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
  { label: 'Użytkownik', value: 'ROLE_USER' },
  { label: 'Agent', value: 'ROLE_AGENT' },
  { label: 'Admin', value: 'ROLE_ADMIN' },
]

const roleSeverity: Record<string, string> = {
  ROLE_USER: 'info', ROLE_AGENT: 'warn', ROLE_ADMIN: 'danger',
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
    toast.add({ severity: 'success', summary: 'Zaktualizowano', detail: `Rola zmieniona na ${getRoleLabel(newRole)}`, life: 3000 })
  } catch {
    toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nie udało się zmienić roli', life: 3000 })
  }
}

onMounted(loadUsers)
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold tracking-tight">Użytkownicy</h1>
      <p class="text-surface-500 text-sm mt-1">Zarządzaj użytkownikami i ich rolami</p>
    </div>

    <div class="bg-surface-0 rounded-xl border border-surface-200">
      <DataTable :value="users" :loading="loading" striped-rows>
        <Column field="id" header="#" style="width: 60px" />
        <Column header="Użytkownik">
          <template #body="{ data }">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                <span class="text-primary text-xs font-semibold">{{ data.firstName?.[0] }}{{ data.lastName?.[0] }}</span>
              </div>
              <div>
                <div class="font-medium text-sm">{{ data.firstName }} {{ data.lastName }}</div>
                <div class="text-xs text-surface-400">{{ data.email }}</div>
              </div>
            </div>
          </template>
        </Column>
        <Column header="Rola" style="width: 200px">
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
  </div>
</template>
