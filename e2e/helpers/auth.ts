import { Page } from '@playwright/test'

export async function loginAs(page: Page, email: string, password: string): Promise<void> {
  const response = await page.request.post('http://helpdesk.local/api/login', {
    data: { email, password },
  })

  const body = await response.json()
  const token: string = body.token

  await page.goto('http://helpdesk.local')
  await page.evaluate((t) => localStorage.setItem('token', t), token)
  await page.goto('http://helpdesk.local/dashboard')
}

export const TEST_ADMIN = { email: 'admin@techvision.pl', password: 'admin123' }
export const TEST_AGENT = { email: 'anna.nowak@techvision.pl', password: 'agent123' }
export const TEST_USER = { email: 'jan.zielinski@techvision.pl', password: 'user123' }
