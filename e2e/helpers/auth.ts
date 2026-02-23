import { Page } from '@playwright/test'

export async function loginAs(page: Page, email: string, password: string, baseUrl = 'http://localhost'): Promise<void> {
  const response = await page.request.post('http://localhost/api/login', {
    data: { email, password },
  })

  const body = await response.json()
  const token: string = body.token

  await page.goto(baseUrl)
  await page.evaluate((t) => localStorage.setItem('token', t), token)
  await page.goto(`${baseUrl}/dashboard`)
}

export const TEST_ADMIN = { email: 'admin@techvision.pl', password: 'admin123' }
export const TEST_AGENT = { email: 'anna.nowak@techvision.pl', password: 'agent123' }
export const TEST_USER = { email: 'jan.zielinski@techvision.pl', password: 'user123' }
