import { test, expect } from '@playwright/test'
import { loginAs, TEST_ADMIN, TEST_USER } from '../helpers/auth'

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
    await page.waitForLoadState('networkidle')
    // At least one accordion header should be visible
    await expect(page.locator('[data-pc-name="accordionheader"]').first()).toBeVisible({ timeout: 10000 })
  })

  test('FAQ article can be expanded', async ({ page }) => {
    await page.goto(`${PORTAL_URL}/faq`)
    await page.waitForLoadState('networkidle')
    // Click the first accordion header and verify content expands
    await page.locator('[data-pc-name="accordionheader"]').first().click()
    await expect(page.locator('[data-pc-name="accordioncontent"]').first()).toBeVisible({ timeout: 5000 })
  })

  test('track ticket by email', async ({ page }) => {
    await page.goto(`${PORTAL_URL}/track`)
    await expect(page.getByPlaceholder('jan@example.com')).toBeVisible()
  })

  test('track with unknown email shows empty state', async ({ page }) => {
    await page.goto(`${PORTAL_URL}/track`)
    await page.getByPlaceholder('jan@example.com').fill('nieznany@example.com')
    await page.getByPlaceholder('np. 42').fill('99999')
    await page.getByRole('button', { name: 'Sprawdź' }).click()
    await expect(page.locator('.bg-surface-0').getByText('Nie znaleziono zgłoszenia o podanych danych')).toBeVisible({ timeout: 5000 })
  })

  test('portal login page accessible', async ({ page }) => {
    await page.goto(`${PORTAL_URL}/login`)
    await expect(page.getByPlaceholder('jan@firma.pl')).toBeVisible()
    await expect(page.getByPlaceholder('Twoje hasło')).toBeVisible()
  })

  test('authenticated user sees portal dashboard', async ({ page }) => {
    await loginAs(page, TEST_USER.email, TEST_USER.password, PORTAL_URL)

    await expect(page).toHaveURL(/\/dashboard/)
    await expect(page.getByText('Wszystkie tickety')).toBeVisible({ timeout: 10000 })
  })

  test('authenticated user can view their tickets on portal', async ({ page }) => {
    await loginAs(page, TEST_USER.email, TEST_USER.password, PORTAL_URL)

    await page.goto(`${PORTAL_URL}/tickets`)
    await page.waitForLoadState('networkidle')

    await expect(page.locator('tbody tr, .ticket-row').first()).toBeVisible({ timeout: 10000 })
  })

  test('admin can access portal admin panel', async ({ page }) => {
    await loginAs(page, TEST_ADMIN.email, TEST_ADMIN.password, PORTAL_URL)

    await page.goto(`${PORTAL_URL}/admin/users`)
    await expect(page.getByText('anna.nowak@techvision.pl')).toBeVisible({ timeout: 10000 })
  })
})
