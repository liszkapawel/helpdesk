<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import Button from 'primevue/button'
import NotificationBell from '@/components/NotificationBell.vue'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()
const sidebarOpen = ref(true)

const navItems = computed(() => {
  const items = [
    { label: 'Dashboard', icon: 'pi pi-objects-column', to: '/dashboard' },
    { label: 'Tickety', icon: 'pi pi-ticket', to: '/tickets' },
    { label: 'Nowy ticket', icon: 'pi pi-plus-circle', to: '/tickets/new' },
  ]
  if (auth.isAdmin) {
    items.push({ label: 'Użytkownicy', icon: 'pi pi-users', to: '/admin/users' })
    items.push({ label: 'Kategorie', icon: 'pi pi-tags', to: '/admin/categories' })
    items.push({ label: 'SLA', icon: 'pi pi-clock', to: '/admin/sla' })
    items.push({ label: 'FAQ', icon: 'pi pi-book', to: '/admin/faq' })
    items.push({ label: 'Ustawienia', icon: 'pi pi-cog', to: '/settings' })
  }
  return items
})

function isActive(to: string) {
  return route.path === to
}

function logout() {
  auth.logout()
  router.push('/')
}
</script>

<template>
  <div class="flex h-screen bg-surface-50">
    <!-- Sidebar -->
    <aside
      class="flex flex-col border-r border-surface-200 bg-surface-0 transition-all duration-200"
      :class="sidebarOpen ? 'w-64' : 'w-16'"
    >
      <!-- Logo -->
      <div class="flex items-center gap-3 px-4 h-16 border-b border-surface-200">
        <i class="pi pi-shield text-primary text-xl"></i>
        <span v-if="sidebarOpen" class="font-bold text-lg tracking-tight">Ticketerr</span>
      </div>

      <!-- Nav -->
      <nav class="flex-1 py-3 px-2 flex flex-col gap-1">
        <router-link
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors no-underline"
          :class="isActive(item.to)
            ? 'bg-primary/10 text-primary'
            : 'text-surface-600 hover:bg-surface-100 hover:text-surface-900'"
        >
          <i :class="item.icon" class="text-base"></i>
          <span v-if="sidebarOpen">{{ item.label }}</span>
        </router-link>
      </nav>

      <!-- Sidebar footer -->
      <div class="border-t border-surface-200 p-3">
        <button
          class="flex items-center justify-center w-full p-2 rounded-lg hover:bg-surface-100 transition-colors"
          @click="sidebarOpen = !sidebarOpen"
        >
          <i :class="sidebarOpen ? 'pi pi-chevron-left' : 'pi pi-chevron-right'" class="text-surface-400 text-sm"></i>
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Top bar -->
      <header class="flex items-center justify-between h-16 px-6 border-b border-surface-200 bg-surface-0">
        <div></div>
        <div class="flex items-center gap-4">
          <NotificationBell />
          <div class="flex items-center gap-3">
            <div v-if="auth.user" class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                <span class="text-primary text-sm font-semibold">{{ auth.user.firstName?.[0] }}{{ auth.user.lastName?.[0] }}</span>
              </div>
              <span class="text-sm font-medium text-surface-700">{{ auth.user.firstName }} {{ auth.user.lastName }}</span>
            </div>
            <Button icon="pi pi-sign-out" severity="secondary" text rounded size="small" @click="logout" />
          </div>
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 overflow-y-auto p-6">
        <slot />
      </main>
    </div>
  </div>
</template>
