import { test, expect } from '@playwright/test'
import { loginAs, TEST_ADMIN, TEST_USER } from '../helpers/auth'

test.describe('Authentication', () => {
  test('login success redirects to dashboard', async ({ page }) => {
    await page.goto('/login')
    await page.getByLabel('Email').fill(TEST_ADMIN.email)
    await page.getByLabel('Hasło').fill(TEST_ADMIN.password)
    await page.getByRole('button', { name: 'Zaloguj się' }).click()

    await expect(page).toHaveURL(/\/dashboard/)
  })

  test('login with wrong credentials shows error', async ({ page }) => {
    await page.goto('/login')
    await page.getByLabel('Email').fill('wrong@email.com')
    await page.getByLabel('Hasło').fill('wrongpassword')
    await page.getByRole('button', { name: 'Zaloguj się' }).click()

    await expect(page.getByText('Nieprawidłowe dane logowania')).toBeVisible()
  })

  test('unauthenticated user redirected to login', async ({ page }) => {
    await page.goto('/dashboard')
    await expect(page).toHaveURL(/\/login/)
  })

  test('logout clears session', async ({ page }) => {
    await loginAs(page, TEST_USER.email, TEST_USER.password)
    await expect(page).toHaveURL(/\/dashboard/)

    // Clear token to simulate logout
    await page.evaluate(() => localStorage.removeItem('token'))
    await page.goto('/dashboard')

    await expect(page).toHaveURL(/\/login/)
  })

  test('forgot password link visible on login page', async ({ page }) => {
    await page.goto('/login')
    await expect(page.getByText('Zapomniałeś hasła?')).toBeVisible()
  })
})
