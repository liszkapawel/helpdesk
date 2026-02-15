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

const baseDomain = import.meta.env.VITE_BASE_DOMAIN || 'helpdesk.local'

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
const organizationSlug = ref('')
const slugAvailable = ref<boolean | null>(null)
const slugChecking = ref(false)
const inviteCode = ref((route.query.invite as string) || '')
const inviteOrgName = ref('')

let slugTimeout: ReturnType<typeof setTimeout> | null = null

watch(organizationSlug, (val) => {
  // Auto-sanitize: remove invalid chars
  const sanitized = val.toLowerCase().replace(/[^a-z0-9\-]/g, '')
  if (sanitized !== val) {
    organizationSlug.value = sanitized
    return
  }

  slugAvailable.value = null
  if (slugTimeout) clearTimeout(slugTimeout)

  if (val.length < 3) return

  slugChecking.value = true
  slugTimeout = setTimeout(async () => {
    try {
      const { data } = await api.get(`/public/check-slug/${val}`)
      slugAvailable.value = data.available
    } catch {
      slugAvailable.value = null
    } finally {
      slugChecking.value = false
    }
  }, 400)
})

// Auto-generate slug from org name if slug is empty
watch(organizationName, (name) => {
  if (!organizationSlug.value || organizationSlug.value === slugFromName(organizationName.value)) {
    // Only auto-fill if user hasn't customized it
  }
  const generated = slugFromName(name)
  if (!organizationSlug.value || organizationSlug.value === previousAutoSlug) {
    organizationSlug.value = generated
  }
  previousAutoSlug = generated
})

let previousAutoSlug = ''

function slugFromName(name: string): string {
  return name
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/ł/g, 'l')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '')
    .slice(0, 48)
}

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
      mode.value === 'create' ? organizationSlug.value : undefined,
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
          <span class="font-bold text-xl tracking-tight">Ticketerr</span>
        </div>
        <h2 class="text-3xl font-bold tracking-tight mb-4">Dołącz do Ticketerr</h2>
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
          <span class="font-bold text-lg">Ticketerr</span>
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

          <template v-if="mode === 'create'">
            <div class="flex flex-col gap-1.5">
              <label for="orgName" class="text-sm font-medium">Nazwa organizacji</label>
              <InputText id="orgName" v-model="organizationName" placeholder="Nazwa firmy lub zespołu" required />
            </div>

            <div class="flex flex-col gap-1.5">
              <label for="orgSlug" class="text-sm font-medium">Nazwa skrótowa (subdomena)</label>
              <InputText
                id="orgSlug"
                v-model="organizationSlug"
                placeholder="moja-firma"
                required
                :class="{
                  'p-invalid': organizationSlug.length >= 3 && slugAvailable === false,
                }"
              />
              <div class="text-xs text-surface-400">
                Twój portal: <strong>{{ organizationSlug || '...' }}.{{ baseDomain }}</strong>
              </div>
              <small v-if="slugChecking" class="text-surface-400">
                <i class="pi pi-spinner pi-spin text-xs"></i> Sprawdzanie...
              </small>
              <small v-else-if="slugAvailable === true && organizationSlug.length >= 3" class="text-green-600 font-medium">
                <i class="pi pi-check text-xs"></i> Dostępna
              </small>
              <small v-else-if="slugAvailable === false && organizationSlug.length >= 3" class="text-red-500 font-medium">
                <i class="pi pi-times text-xs"></i> Już zajęta
              </small>
              <small v-else-if="organizationSlug.length > 0 && organizationSlug.length < 3" class="text-surface-400">
                Min. 3 znaki
              </small>
            </div>
          </template>

          <div v-if="mode === 'join'" class="flex flex-col gap-1.5">
            <label for="inviteCode" class="text-sm font-medium">Kod zaproszenia</label>
            <InputText id="inviteCode" v-model="inviteCode" placeholder="Wklej kod zaproszenia" required />
            <small v-if="inviteOrgName" class="text-green-600 font-medium">Dołączasz do: {{ inviteOrgName }}</small>
          </div>

          <Button
            type="submit"
            label="Zarejestruj się"
            :loading="loading"
            :disabled="mode === 'create' && (slugAvailable === false || organizationSlug.length < 3)"
            class="mt-2"
          />
          <p class="text-center text-sm text-surface-500">
            Masz już konto?
            <router-link to="/login" class="text-primary font-medium hover:underline">Zaloguj się</router-link>
          </p>
        </form>
      </div>
    </div>
  </div>
</template>
