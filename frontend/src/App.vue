<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute } from 'vue-router'
import Toast from 'primevue/toast'
import Button from 'primevue/button'
import AppLayout from '@/layouts/AppLayout.vue'
import PortalPublicLayout from '@/layouts/PortalPublicLayout.vue'
import PortalLayout from '@/layouts/PortalLayout.vue'
import { isOrgSubdomain, getOrgSlug } from '@/utils/subdomain'
import { useOrganizationStore } from '@/stores/organization'

const route = useRoute()
const orgStore = useOrganizationStore()
const isPortal = isOrgSubdomain()
const slug = getOrgSlug()

const baseDomain = import.meta.env.VITE_BASE_DOMAIN || 'helpdesk.local'

onMounted(async () => {
  if (slug) {
    await orgStore.fetchOrg(slug)
  }
})
</script>

<template>
  <Toast />
  <!-- Portal subdomain -->
  <template v-if="isPortal">
    <!-- Loading -->
    <div v-if="orgStore.loading" class="min-h-screen flex items-center justify-center">
      <i class="pi pi-spinner pi-spin text-4xl text-surface-400"></i>
    </div>
    <!-- Not found -->
    <div v-else-if="orgStore.notFound" class="min-h-screen flex items-center justify-center bg-surface-50">
      <div class="text-center max-w-md px-6">
        <div class="w-20 h-20 rounded-full bg-surface-200 flex items-center justify-center mx-auto mb-6">
          <i class="pi pi-building text-3xl text-surface-400"></i>
        </div>
        <h1 class="text-2xl font-bold mb-2">Organizacja nie znaleziona</h1>
        <p class="text-surface-500 mb-6">
          Nie istnieje organizacja o adresie <strong>{{ slug }}.{{ baseDomain }}</strong>.
          Sprawdź poprawność adresu lub przejdź do strony głównej.
        </p>
        <a :href="`//${baseDomain}`">
          <Button label="Strona główna" icon="pi pi-home" />
        </a>
      </div>
    </div>
    <!-- Portal loaded -->
    <template v-else>
      <PortalPublicLayout v-if="route.meta.public">
        <RouterView />
      </PortalPublicLayout>
      <PortalLayout v-else>
        <RouterView />
      </PortalLayout>
    </template>
  </template>
  <!-- Main domain -->
  <template v-else>
    <AppLayout v-if="!route.meta.public">
      <RouterView />
    </AppLayout>
    <RouterView v-else />
  </template>
</template>
