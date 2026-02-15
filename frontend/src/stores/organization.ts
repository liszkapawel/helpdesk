import { ref } from 'vue'
import { defineStore } from 'pinia'
import api from '@/services/api'

export interface OrgInfo {
  id: number
  name: string
  slug: string
  description: string | null
  primaryColor: string | null
  logoPath: string | null
}

export const useOrganizationStore = defineStore('organization', () => {
  const org = ref<OrgInfo | null>(null)
  const loading = ref(false)

  async function fetchOrg(slug: string) {
    loading.value = true
    try {
      const { data } = await api.get(`/public/org/${slug}`)
      org.value = data
    } finally {
      loading.value = false
    }
  }

  return { org, loading, fetchOrg }
})
