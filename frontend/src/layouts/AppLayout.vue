<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import Menubar from 'primevue/menubar'
import Button from 'primevue/button'
import NotificationBell from '@/components/NotificationBell.vue'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const items = computed(() => {
  const menu = [
    {
      label: 'Dashboard',
      icon: 'pi pi-chart-bar',
      command: () => router.push('/dashboard'),
    },
    {
      label: 'Tickets',
      icon: 'pi pi-ticket',
      command: () => router.push('/tickets'),
    },
    {
      label: 'New Ticket',
      icon: 'pi pi-plus',
      command: () => router.push('/tickets/new'),
    },
  ]

  if (auth.isAdmin) {
    menu.push({
      label: 'Admin',
      icon: 'pi pi-users',
      command: () => router.push('/admin/users'),
    })
    menu.push({
      label: 'Settings',
      icon: 'pi pi-cog',
      command: () => router.push('/settings'),
    })
  }

  return menu
})

function logout() {
  auth.logout()
  router.push('/login')
}
</script>

<template>
  <div class="layout">
    <Menubar :model="items">
      <template #start>
        <span class="font-bold text-xl mr-4">Helpdesk</span>
      </template>
      <template #end>
        <div class="flex items-center gap-3">
          <NotificationBell />
          <span v-if="auth.user" class="text-sm">{{ auth.user.firstName }} {{ auth.user.lastName }}</span>
          <Button label="Logout" icon="pi pi-sign-out" severity="secondary" text @click="logout" />
        </div>
      </template>
    </Menubar>
    <main class="p-4">
      <slot />
    </main>
  </div>
</template>
