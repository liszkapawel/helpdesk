<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import SelectButton from 'primevue/selectbutton'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const firstName = ref('')
const lastName = ref('')
const loading = ref(false)

const modes = [
  { label: 'Nowa organizacja', value: 'create' },
  { label: 'Dołącz z zaproszeniem', value: 'join' },
]
const mode = ref('create')
const organizationName = ref('')
const inviteCode = ref((route.query.invite as string) || '')
const inviteOrgName = ref('')

watch(inviteCode, async (code) => {
  if (code.length >= 10) {
    try {
      const { data } = await api.get(`/invites/${code}/validate`)
      if (data.valid) {
        inviteOrgName.value = data.organizationName
      } else {
        inviteOrgName.value = ''
      }
    } catch {
      inviteOrgName.value = ''
    }
  } else {
    inviteOrgName.value = ''
  }
})

if (inviteCode.value) {
  mode.value = 'join'
}

async function submit() {
  loading.value = true
  try {
    await auth.register(
      email.value,
      password.value,
      firstName.value,
      lastName.value,
      mode.value === 'join' ? inviteCode.value : undefined,
      mode.value === 'create' ? organizationName.value : undefined,
    )
    toast.add({ severity: 'success', summary: 'Sukces', detail: 'Konto utworzone. Zaloguj się.', life: 3000 })
    router.push('/login')
  } catch (err: any) {
    const errors = err.response?.data?.errors
    const error = err.response?.data?.error
    const detail = errors ? Object.values(errors).join(', ') : error || 'Rejestracja nie powiodła się'
    toast.add({ severity: 'error', summary: 'Błąd', detail, life: 5000 })
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
          <span class="font-bold text-xl tracking-tight">Helpdesk</span>
        </div>
        <h2 class="text-3xl font-bold tracking-tight mb-4">Dołącz do Helpdesk</h2>
        <p class="text-surface-500 leading-relaxed">
          Stwórz organizację lub dołącz do istniejącej za pomocą kodu zaproszenia.
        </p>
      </div>
    </div>

    <!-- Right panel — form -->
    <div class="flex-1 flex items-center justify-center p-6">
      <div class="w-full max-w-sm">
        <div class="flex items-center gap-2 mb-8 lg:hidden">
          <i class="pi pi-shield text-primary text-xl"></i>
          <span class="font-bold text-lg">Helpdesk</span>
        </div>

        <h1 class="text-2xl font-bold mb-1">Zarejestruj się</h1>
        <p class="text-surface-500 text-sm mb-8">Utwórz konto, aby rozpocząć</p>

        <form class="flex flex-col gap-4" @submit.prevent="submit">
          <SelectButton v-model="mode" :options="modes" option-label="label" option-value="value" :allow-empty="false" />

          <div class="grid grid-cols-2 gap-3">
            <div class="flex flex-col gap-1.5">
              <label for="firstName" class="text-sm font-medium">Imię</label>
              <InputText id="firstName" v-model="firstName" placeholder="Jan" required />
            </div>
            <div class="flex flex-col gap-1.5">
              <label for="lastName" class="text-sm font-medium">Nazwisko</label>
              <InputText id="lastName" v-model="lastName" placeholder="Kowalski" required />
            </div>
          </div>

          <div class="flex flex-col gap-1.5">
            <label for="email" class="text-sm font-medium">Email</label>
            <InputText id="email" v-model="email" type="email" placeholder="jan@firma.pl" required />
          </div>

          <div class="flex flex-col gap-1.5">
            <label for="password" class="text-sm font-medium">Hasło</label>
            <Password id="password" v-model="password" placeholder="Min. 6 znaków" toggle-mask required fluid />
          </div>

          <div v-if="mode === 'create'" class="flex flex-col gap-1.5">
            <label for="orgName" class="text-sm font-medium">Nazwa organizacji</label>
            <InputText id="orgName" v-model="organizationName" placeholder="Nazwa firmy lub zespołu" required />
          </div>

          <div v-if="mode === 'join'" class="flex flex-col gap-1.5">
            <label for="inviteCode" class="text-sm font-medium">Kod zaproszenia</label>
            <InputText id="inviteCode" v-model="inviteCode" placeholder="Wklej kod zaproszenia" required />
            <small v-if="inviteOrgName" class="text-green-600 font-medium">Dołączasz do: {{ inviteOrgName }}</small>
          </div>

          <Button type="submit" label="Zarejestruj się" :loading="loading" class="mt-2" />
          <p class="text-center text-sm text-surface-500">
            Masz już konto?
            <router-link to="/login" class="text-primary font-medium hover:underline">Zaloguj się</router-link>
          </p>
        </form>
      </div>
    </div>
  </div>
</template>
