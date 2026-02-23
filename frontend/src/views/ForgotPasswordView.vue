<script setup lang="ts">
import { ref } from 'vue'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import api from '@/services/api'

const email = ref('')
const loading = ref(false)
const sent = ref(false)

async function submit() {
  loading.value = true
  try {
    await api.post('/password-reset/request', { email: email.value })
    sent.value = true
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center p-6">
    <div class="w-full max-w-sm">
      <div class="flex items-center gap-2 mb-8">
        <i class="pi pi-shield text-primary text-xl"></i>
        <span class="font-bold text-lg">Ticketerr</span>
      </div>

      <div v-if="sent" class="text-center">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <i class="pi pi-check text-green-600 text-2xl"></i>
        </div>
        <h1 class="text-xl font-bold mb-2">Sprawdź skrzynkę</h1>
        <p class="text-surface-500 text-sm mb-6">
          Jeśli konto istnieje, wysłaliśmy link do resetowania hasła na podany adres email.
        </p>
        <router-link to="/login" class="text-primary font-medium hover:underline text-sm">
          Wróć do logowania
        </router-link>
      </div>

      <div v-else>
        <h1 class="text-2xl font-bold mb-1">Resetuj hasło</h1>
        <p class="text-surface-500 text-sm mb-8">Podaj adres email, wyślemy Ci link do resetu</p>

        <form class="flex flex-col gap-5" @submit.prevent="submit">
          <div class="flex flex-col gap-1.5">
            <label for="email" class="text-sm font-medium">Email</label>
            <InputText id="email" v-model="email" type="email" placeholder="jan@firma.pl" required />
          </div>
          <Button type="submit" label="Wyślij link" :loading="loading" />
          <p class="text-center text-sm text-surface-500">
            <router-link to="/login" class="text-primary font-medium hover:underline">Wróć do logowania</router-link>
          </p>
        </form>
      </div>
    </div>
  </div>
</template>
