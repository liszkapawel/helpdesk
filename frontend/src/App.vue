<script setup lang="ts">
import { useRoute } from 'vue-router'
import Toast from 'primevue/toast'
import AppLayout from '@/layouts/AppLayout.vue'

const route = useRoute()
</script>

<template>
  <Toast />
  <AppLayout v-if="!route.meta.public">
    <RouterView />
  </AppLayout>
  <RouterView v-else />
</template>
