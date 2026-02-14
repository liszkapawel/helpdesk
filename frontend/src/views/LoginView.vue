<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Card from 'primevue/card'
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
    toast.add({ severity: 'error', summary: 'Error', detail: 'Invalid credentials', life: 3000 })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex items-center justify-center min-h-screen">
    <Card class="w-full max-w-md">
      <template #title>Login</template>
      <template #content>
        <form class="flex flex-col gap-4" @submit.prevent="submit">
          <div class="flex flex-col gap-1">
            <label for="email">Email</label>
            <InputText id="email" v-model="email" type="email" placeholder="Email" required />
          </div>
          <div class="flex flex-col gap-1">
            <label for="password">Password</label>
            <Password id="password" v-model="password" placeholder="Password" :feedback="false" toggle-mask required fluid />
          </div>
          <Button type="submit" label="Login" :loading="loading" />
          <p class="text-center text-sm">
            Don't have an account?
            <router-link to="/register" class="text-primary">Register</router-link>
          </p>
        </form>
      </template>
    </Card>
  </div>
</template>
