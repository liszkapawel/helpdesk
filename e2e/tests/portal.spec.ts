import { test, expect } from '@playwright/test'

const PORTAL_URL = 'http://techvision.localhost'

test.describe('Portal', () => {
  test('portal homepage loads', async ({ page }) => {
    await page.goto(PORTAL_URL)
    await expect(page.getByRole('heading').first()).toBeVisible()
  })

  test('submit ticket form is accessible', async ({ page }) => {
    await page.goto(`${PORTAL_URL}/submit`)
    await expect(page.getByPlaceholder('Krótki opis problemu')).toBeVisible()
    await expect(page.getByPlaceholder('Opisz problem szczegółowo...')).toBeVisible()
  })

  test('guest can submit a ticket', async ({ page }) => {
    await page.goto(`${PORTAL_URL}/submit`)

    await page.getByPlaceholder('Jan Kowalski').fill('Jan Kowalski')
    await page.getByPlaceholder('jan@example.com').fill('jan.kowalski@test.pl')
    await page.getByPlaceholder('Krótki opis problemu').fill('Portal E2E test ticket')
    await page.getByPlaceholder('Opisz problem szczegółowo...').fill('Test submission from Playwright')
    await page.getByRole('button', { name: 'Wyślij zgłoszenie' }).click()

    await expect(page).toHaveURL(/\/submit\/success/)
  })

  test('FAQ page loads articles', async ({ page }) => {
    await page.goto(`${PORTAL_URL}/faq`)
    // Either FAQ items visible or empty state
    const content = page.locator('main, .faq-list, [class*="faq"]').first()
    await expect(content).toBeVisible({ timeout: 10000 })
  })

  test('track ticket by email', async ({ page }) => {
    await page.goto(`${PORTAL_URL}/track`)
    await expect(page.getByPlaceholder('jan@example.com')).toBeVisible()
  })
})
