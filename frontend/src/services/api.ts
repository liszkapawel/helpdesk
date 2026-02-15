import axios from 'axios'
import router from '@/router'
import { getOrgSlug } from '@/utils/subdomain'

const api = axios.create({
  baseURL: '/api',
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  const slug = getOrgSlug()
  if (slug) {
    config.headers['X-Org-Slug'] = slug
  }
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      const current = router.currentRoute.value
      if (!current.meta.public) {
        router.push('/login')
      }
    }
    return Promise.reject(error)
  },
)

export default api
