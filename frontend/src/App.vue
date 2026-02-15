<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute } from 'vue-router'
import Toast from 'primevue/toast'
import AppLayout from '@/layouts/AppLayout.vue'
import PortalPublicLayout from '@/layouts/PortalPublicLayout.vue'
import PortalLayout from '@/layouts/PortalLayout.vue'
import { isOrgSubdomain, getOrgSlug } from '@/utils/subdomain'
import { useOrganizationStore } from '@/stores/organization'

const route = useRoute()
const orgStore = useOrganizationStore()
const isPortal = isOrgSubdomain()

onMounted(async () => {
  const slug = getOrgSlug()
  if (slug) {
    await orgStore.fetchOrg(slug)
  }
})
</script>

<template>
  <Toast />
  <!-- Portal subdomain -->
  <template v-if="isPortal">
    <PortalPublicLayout v-if="route.meta.public">
      <RouterView />
    </PortalPublicLayout>
    <PortalLayout v-else>
      <RouterView />
    </PortalLayout>
  </template>
  <!-- Main domain -->
  <template v-else>
    <AppLayout v-if="!route.meta.public">
      <RouterView />
    </AppLayout>
    <RouterView v-else />
  </template>
</template>
