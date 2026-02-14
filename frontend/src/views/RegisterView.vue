<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Card from 'primevue/card'
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
  { label: 'Create Organization', value: 'create' },
  { label: 'Join with Invite', value: 'join' },
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

// If invite code in URL, switch to join mode
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
    toast.add({ severity: 'success', summary: 'Success', detail: 'Account created. Please login.', life: 3000 })
    router.push('/login')
  } catch (err: any) {
    const errors = err.response?.data?.errors
    const error = err.response?.data?.error
    const detail = errors ? Object.values(errors).join(', ') : error || 'Registration failed'
    toast.add({ severity: 'error', summary: 'Error', detail, life: 5000 })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex items-center justify-center min-h-screen">
    <Card class="w-full max-w-md">
      <template #title>Register</template>
      <template #content>
        <form class="flex flex-col gap-4" @submit.prevent="submit">
          <SelectButton v-model="mode" :options="modes" option-label="label" option-value="value" class="w-full" />

          <div class="flex flex-col gap-1">
            <label for="firstName">First Name</label>
            <InputText id="firstName" v-model="firstName" placeholder="First Name" required />
          </div>
          <div class="flex flex-col gap-1">
            <label for="lastName">Last Name</label>
            <InputText id="lastName" v-model="lastName" placeholder="Last Name" required />
          </div>
          <div class="flex flex-col gap-1">
            <label for="email">Email</label>
            <InputText id="email" v-model="email" type="email" placeholder="Email" required />
          </div>
          <div class="flex flex-col gap-1">
            <label for="password">Password</label>
            <Password id="password" v-model="password" placeholder="Password (min 6 characters)" toggle-mask required fluid />
          </div>

          <div v-if="mode === 'create'" class="flex flex-col gap-1">
            <label for="orgName">Organization Name</label>
            <InputText id="orgName" v-model="organizationName" placeholder="Your company or team name" required />
          </div>

          <div v-if="mode === 'join'" class="flex flex-col gap-1">
            <label for="inviteCode">Invite Code</label>
            <InputText id="inviteCode" v-model="inviteCode" placeholder="Paste your invite code" required />
            <small v-if="inviteOrgName" class="text-green-500">Joining: {{ inviteOrgName }}</small>
          </div>

          <Button type="submit" label="Register" :loading="loading" />
          <p class="text-center text-sm">
            Already have an account?
            <router-link to="/login" class="text-primary">Login</router-link>
          </p>
        </form>
      </template>
    </Card>
  </div>
</template>
