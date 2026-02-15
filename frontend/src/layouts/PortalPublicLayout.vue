<script setup lang="ts">
import { useOrganizationStore } from '@/stores/organization'

const orgStore = useOrganizationStore()
</script>

<template>
  <div class="min-h-screen bg-surface-50">
    <!-- Navbar -->
    <header
      class="h-16 border-b border-surface-200 bg-surface-0 flex items-center justify-between px-6"
    >
      <div class="flex items-center gap-3">
        <img
          v-if="orgStore.org?.logoPath"
          :src="orgStore.org.logoPath"
          :alt="orgStore.org.name"
          class="h-8 w-8 object-contain rounded"
        />
        <div
          v-else
          class="h-8 w-8 rounded flex items-center justify-center text-white font-bold text-sm"
          :style="{ backgroundColor: orgStore.org?.primaryColor || '#3B82F6' }"
        >
          {{ orgStore.org?.name?.[0] || '?' }}
        </div>
        <span class="font-bold text-lg tracking-tight">{{ orgStore.org?.name }}</span>
      </div>
      <nav class="flex items-center gap-4">
        <router-link to="/" class="text-sm text-surface-600 hover:text-surface-900 no-underline">Strona</router-link>
        <router-link to="/submit" class="text-sm text-surface-600 hover:text-surface-900 no-underline">Zgłoś problem</router-link>
        <router-link to="/track" class="text-sm text-surface-600 hover:text-surface-900 no-underline">Sprawdź status</router-link>
        <router-link to="/login" class="text-sm text-surface-600 hover:text-surface-900 no-underline">Zaloguj się</router-link>
      </nav>
    </header>

    <!-- Content -->
    <main class="p-6">
      <slot />
    </main>
  </div>
</template>
