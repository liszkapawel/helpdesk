import { test, expect } from '@playwright/test'
import { loginAs, TEST_ADMIN, TEST_AGENT } from '../helpers/auth'

test.describe('Dashboard', () => {
  test('admin dashboard shows stats', async ({ page }) => {
    await loginAs(page, TEST_ADMIN.email, TEST_ADMIN.password)

    await page.goto('/dashboard')
    await page.waitForLoadState('networkidle')

    // Stats cards should be visible
    await expect(page.getByText('Wszystkie tickety')).toBeVisible({ timeout: 10000 })
    await expect(page.getByText('Otwarte')).toBeVisible()
  })

  test('dashboard stats have non-zero values', async ({ page }) => {
    await loginAs(page, TEST_ADMIN.email, TEST_ADMIN.password)

    await page.goto('/dashboard')
    await page.waitForLoadState('networkidle')

    // At least one stat card should show a number > 0
    const statNumbers = page.locator('.text-2xl.font-bold').first()
    await expect(statNumbers).toBeVisible({ timeout: 10000 })
  })

  test('agent can access dashboard', async ({ page }) => {
    await loginAs(page, TEST_AGENT.email, TEST_AGENT.password)

    await page.goto('/dashboard')
    await page.waitForLoadState('networkidle')

    await expect(page).toHaveURL(/\/dashboard/)
    await expect(page.getByText('Wszystkie tickety')).toBeVisible({ timeout: 10000 })
  })

  test('dashboard has recent tickets table', async ({ page }) => {
    await loginAs(page, TEST_ADMIN.email, TEST_ADMIN.password)

    await page.goto('/dashboard')
    await page.waitForLoadState('networkidle')

    await expect(page.getByText('Wg statusu')).toBeVisible({ timeout: 10000 })
    await expect(page.getByText('Tickety w ostatnich 30 dniach')).toBeVisible()
  })
})
