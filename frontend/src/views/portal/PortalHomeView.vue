<script setup lang="ts">
import Button from 'primevue/button'
import { useOrganizationStore } from '@/stores/organization'

const orgStore = useOrganizationStore()
</script>

<template>
  <div class="max-w-2xl mx-auto text-center py-16">
    <div class="mb-8">
      <img
        v-if="orgStore.org?.logoPath"
        :src="orgStore.org.logoPath"
        :alt="orgStore.org?.name"
        class="h-20 w-20 object-contain rounded-xl mx-auto mb-4"
      />
      <div
        v-else
        class="h-20 w-20 rounded-xl flex items-center justify-center text-white font-bold text-3xl mx-auto mb-4"
        :style="{ backgroundColor: orgStore.org?.primaryColor || '#3B82F6' }"
      >
        {{ orgStore.org?.name?.[0] || '?' }}
      </div>
      <h1 class="text-3xl font-bold tracking-tight mb-2">{{ orgStore.org?.name }}</h1>
      <p v-if="orgStore.org?.description" class="text-surface-500 text-lg">{{ orgStore.org.description }}</p>
    </div>

    <div class="flex gap-4 justify-center">
      <router-link to="/submit">
        <Button label="Zgłoś problem" icon="pi pi-plus-circle" size="large" />
      </router-link>
      <router-link to="/track">
        <Button label="Sprawdź status" icon="pi pi-search" size="large" severity="secondary" outlined />
      </router-link>
    </div>
  </div>
</template>
