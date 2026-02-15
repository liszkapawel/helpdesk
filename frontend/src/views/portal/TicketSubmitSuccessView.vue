<script setup lang="ts">
import { useRoute } from 'vue-router'
import Button from 'primevue/button'
import { useToast } from 'primevue/usetoast'

const route = useRoute()
const toast = useToast()

const ticketId = route.query.ticketId
const token = route.query.token

function copyToken() {
  if (token) {
    navigator.clipboard.writeText(String(token))
    toast.add({ severity: 'info', summary: 'Skopiowano', detail: 'Token skopiowany do schowka', life: 2000 })
  }
}
</script>

<template>
  <div class="max-w-lg mx-auto text-center py-16">
    <div class="mb-6">
      <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
        <i class="pi pi-check text-green-600 text-2xl"></i>
      </div>
      <h1 class="text-2xl font-bold tracking-tight mb-2">Zgłoszenie wysłane!</h1>
      <p class="text-surface-500">Twoje zgłoszenie zostało przyjęte</p>
    </div>

    <div class="bg-surface-0 rounded-xl border border-surface-200 p-6 mb-6 text-left">
      <div class="flex flex-col gap-3">
        <div>
          <span class="text-sm text-surface-500">Numer zgłoszenia</span>
          <p class="font-bold text-lg">#{{ ticketId }}</p>
        </div>
        <div>
          <span class="text-sm text-surface-500">Token śledzenia</span>
          <div class="flex items-center gap-2">
            <code class="text-xs bg-surface-100 px-2 py-1 rounded flex-1 break-all">{{ token }}</code>
            <Button icon="pi pi-copy" text size="small" @click="copyToken" />
          </div>
        </div>
      </div>
    </div>

    <p class="text-sm text-surface-500 mb-4">
      Możesz sprawdzić status zgłoszenia podając swój email i numer zgłoszenia.
    </p>

    <div class="flex gap-3 justify-center">
      <router-link to="/track">
        <Button label="Sprawdź status" icon="pi pi-search" severity="secondary" outlined />
      </router-link>
      <router-link to="/">
        <Button label="Strona główna" icon="pi pi-home" />
      </router-link>
    </div>
  </div>
</template>
