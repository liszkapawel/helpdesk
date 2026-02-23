import { test, expect } from '@playwright/test'
import { loginAs, TEST_ADMIN, TEST_USER } from '../helpers/auth'

const PORTAL_URL = 'http://techvision.localhost'

test.describe('Authentication', () => {
  test('login success redirects to dashboard', async ({ page }) => {
    await page.goto(`${PORTAL_URL}/login`)
    await page.getByPlaceholder('jan@firma.pl').fill(TEST_ADMIN.email)
    await page.getByPlaceholder('Twoje hasło').fill(TEST_ADMIN.password)
    await page.getByRole('button', { name: 'Zaloguj się' }).click()

    await expect(page).toHaveURL(/\/dashboard/)
  })

  test('login with wrong credentials shows error', async ({ page }) => {
    await page.goto(`${PORTAL_URL}/login`)
    await page.getByPlaceholder('jan@firma.pl').fill('wrong@email.com')
    await page.getByPlaceholder('Twoje hasło').fill('wrongpassword')
    await page.getByRole('button', { name: 'Zaloguj się' }).click()

    await expect(page.getByText('Nieprawidłowe dane logowania')).toBeVisible()
  })

  test('unauthenticated user redirected to login', async ({ page }) => {
    await page.goto(`${PORTAL_URL}/dashboard`)
    await expect(page).toHaveURL(/\/login/)
  })

  test('logout clears session', async ({ page }) => {
    await loginAs(page, TEST_USER.email, TEST_USER.password, PORTAL_URL)
    await expect(page).toHaveURL(/\/dashboard/)

    // Clear token to simulate logout
    await page.evaluate(() => localStorage.removeItem('token'))
    await page.goto(`${PORTAL_URL}/dashboard`)

    await expect(page).toHaveURL(/\/login/)
  })

  test('forgot password link visible on login page', async ({ page }) => {
    await page.goto(`${PORTAL_URL}/login`)
    await expect(page.getByText('Zapomniałeś hasła?')).toBeVisible()
  })
})
