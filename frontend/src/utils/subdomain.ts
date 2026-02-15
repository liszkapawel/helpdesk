const BASE_DOMAIN = import.meta.env.VITE_BASE_DOMAIN || 'helpdesk.local'

export function getOrgSlug(): string | null {
  const hostname = window.location.hostname
  if (hostname === BASE_DOMAIN || hostname === `www.${BASE_DOMAIN}`) {
    return null
  }
  const suffix = `.${BASE_DOMAIN}`
  if (hostname.endsWith(suffix)) {
    return hostname.slice(0, -suffix.length)
  }
  return null
}

export function isOrgSubdomain(): boolean {
  return getOrgSlug() !== null
}
