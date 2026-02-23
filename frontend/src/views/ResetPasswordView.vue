<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Password from 'primevue/password'
import Button from 'primevue/button'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const password = ref('')
const confirm = ref('')
const loading = ref(false)

const token = route.query.token as string

async function submit() {
  if (password.value !== confirm.value) {
    toast.add({ severity: 'error', summary: 'Błąd', detail: 'Hasła nie są identyczne', life: 3000 })
    return
  }
  loading.value = true
  try {
    await api.post('/password-reset/confirm', { token, password: password.value })
    toast.add({ severity: 'success', summary: 'Sukces', detail: 'Hasło zostało zmienione', life: 3000 })
    router.push('/login')
  } catch (err: any) {
    const msg = err.response?.data?.error || 'Token jest nieprawidłowy lub wygasł'
    toast.add({ severity: 'error', summary: 'Błąd', detail: msg, life: 5000 })
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

      <div v-if="!token" class="text-center text-surface-500">
        <p>Brak tokenu. Wróć do <router-link to="/forgot-password" class="text-primary hover:underline">strony resetowania</router-link>.</p>
      </div>

      <div v-else>
        <h1 class="text-2xl font-bold mb-1">Nowe hasło</h1>
        <p class="text-surface-500 text-sm mb-8">Ustaw nowe hasło dla swojego konta</p>

        <form class="flex flex-col gap-5" @submit.prevent="submit">
          <div class="flex flex-col gap-1.5">
            <label for="password" class="text-sm font-medium">Nowe hasło</label>
            <Password id="password" v-model="password" placeholder="Min. 6 znaków" :feedback="false" toggle-mask required fluid />
          </div>
          <div class="flex flex-col gap-1.5">
            <label for="confirm" class="text-sm font-medium">Potwierdź hasło</label>
            <Password id="confirm" v-model="confirm" placeholder="Powtórz hasło" :feedback="false" toggle-mask required fluid />
          </div>
          <Button type="submit" label="Zmień hasło" :loading="loading" />
        </form>
      </div>
    </div>
  </div>
</template>
