<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const toast = useToast()
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const loading = ref(false)

async function submit() {
  loading.value = true
  try {
    await auth.login(email.value, password.value)
    router.push('/dashboard')
  } catch {
    toast.add({ severity: 'error', summary: 'Błąd', detail: 'Nieprawidłowe dane logowania', life: 3000 })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen">
    <!-- Left panel -->
    <div class="hidden lg:flex lg:w-1/2 bg-primary/5 items-center justify-center p-12">
      <div class="max-w-md">
        <div class="flex items-center gap-2 mb-8">
          <i class="pi pi-shield text-primary text-2xl"></i>
          <span class="font-bold text-xl tracking-tight">Ticketerr</span>
        </div>
        <h2 class="text-3xl font-bold tracking-tight mb-4">Zarządzaj zgłoszeniami w jednym miejscu</h2>
        <p class="text-surface-500 leading-relaxed">
          Twórz tickety, przypisuj je do zespołu i śledź postępy w czasie rzeczywistym.
        </p>
      </div>
    </div>

    <!-- Right panel - form -->
    <div class="flex-1 flex items-center justify-center p-6">
      <div class="w-full max-w-sm">
        <div class="flex items-center gap-2 mb-8 lg:hidden">
          <i class="pi pi-shield text-primary text-xl"></i>
          <span class="font-bold text-lg">Ticketerr</span>
        </div>

        <h1 class="text-2xl font-bold mb-1">Zaloguj się</h1>
        <p class="text-surface-500 text-sm mb-8">Wprowadź dane, aby przejść do panelu</p>

        <form class="flex flex-col gap-5" @submit.prevent="submit">
          <div class="flex flex-col gap-1.5">
            <label for="email" class="text-sm font-medium">Email</label>
            <InputText id="email" v-model="email" type="email" placeholder="jan@firma.pl" required />
          </div>
          <div class="flex flex-col gap-1.5">
            <div class="flex justify-between items-center">
              <label for="password" class="text-sm font-medium">Hasło</label>
              <router-link to="/forgot-password" class="text-xs text-primary hover:underline">Zapomniałeś hasła?</router-link>
            </div>
            <Password id="password" v-model="password" placeholder="Twoje hasło" :feedback="false" toggle-mask required fluid />
          </div>
          <Button type="submit" label="Zaloguj się" :loading="loading" class="mt-2" />
          <p class="text-center text-sm text-surface-500">
            Nie masz konta?
            <router-link to="/register" class="text-primary font-medium hover:underline">Zarejestruj się</router-link>
          </p>
        </form>
      </div>
    </div>
  </div>
</template>
