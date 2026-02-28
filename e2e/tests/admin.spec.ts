import { test, expect } from '@playwright/test'
import { loginAs, TEST_ADMIN } from '../helpers/auth'

test.describe('Admin', () => {
  test('admin can view user list', async ({ page }) => {
    await loginAs(page, TEST_ADMIN.email, TEST_ADMIN.password)

    await page.goto('/admin/users')
    await expect(page.getByText('anna.nowak@techvision.pl')).toBeVisible({ timeout: 10000 })
  })

  test('admin can change user role', async ({ page }) => {
    await loginAs(page, TEST_ADMIN.email, TEST_ADMIN.password)

    await page.goto('/admin/users')

    const agentRow = page.getByRole('row').filter({ hasText: 'anna.nowak@techvision.pl' })
    await expect(agentRow).toBeVisible({ timeout: 10000 })

    const roleSelect = agentRow.locator('.p-select').first()
    await roleSelect.click()

    const agentOption = page.getByRole('option', { name: 'Agent' })
    await expect(agentOption).toBeVisible()
    await agentOption.click()
  })

  test('admin can add category', async ({ page }) => {
    await loginAs(page, TEST_ADMIN.email, TEST_ADMIN.password)

    await page.goto('/admin/categories')
    await page.waitForLoadState('networkidle')

    const newCategoryName = 'E2E Kategoria ' + Date.now()
    await page.getByRole('button', { name: 'Nowa kategoria' }).click()

    const dialog = page.locator('.p-dialog')
    await expect(dialog).toBeVisible()
    await dialog.locator('input[type="text"]').first().fill(newCategoryName)
    await dialog.getByRole('button', { name: 'Utwórz' }).click()

    await expect(page.getByText(newCategoryName)).toBeVisible()
  })

  test('admin can view FAQ list', async ({ page }) => {
    await loginAs(page, TEST_ADMIN.email, TEST_ADMIN.password)

    await page.goto('/admin/faq')
    await page.waitForLoadState('networkidle')

    await expect(page.getByRole('button', { name: 'Nowy artykuł' })).toBeVisible({ timeout: 10000 })
    await expect(page.getByText('Jak połączyć się z VPN?')).toBeVisible()
  })

  test('admin can create FAQ article', async ({ page }) => {
    await loginAs(page, TEST_ADMIN.email, TEST_ADMIN.password)

    await page.goto('/admin/faq')
    await page.waitForLoadState('networkidle')

    await page.getByRole('button', { name: 'Nowy artykuł' }).click()

    const dialog = page.locator('.p-dialog')
    await expect(dialog).toBeVisible()

    const title = 'E2E FAQ ' + Date.now()
    await dialog.locator('input[type="text"]').first().fill(title)
    await dialog.locator('textarea').first().fill('Treść artykułu FAQ dodanego przez test automatyczny.')
    await dialog.getByRole('button', { name: 'Utwórz' }).click()

    await expect(page.getByText(title)).toBeVisible({ timeout: 5000 })
  })

  test('admin can edit FAQ article', async ({ page }) => {
    await loginAs(page, TEST_ADMIN.email, TEST_ADMIN.password)

    await page.goto('/admin/faq')
    await page.waitForLoadState('networkidle')

    // Click edit on first article
    await page.locator('[icon="pi pi-pencil"], button:has(.pi-pencil)').first().click()

    const dialog = page.locator('.p-dialog')
    await expect(dialog).toBeVisible()

    const titleInput = dialog.locator('input[type="text"]').first()
    await titleInput.clear()
    await titleInput.fill('Zaktualizowany tytuł E2E')
    await dialog.getByRole('button', { name: 'Zapisz' }).click()

    await expect(page.getByText('Zaktualizowany tytuł E2E')).toBeVisible({ timeout: 5000 })
  })

  test('admin can view SLA settings', async ({ page }) => {
    await loginAs(page, TEST_ADMIN.email, TEST_ADMIN.password)

    await page.goto('/admin/sla')
    await page.waitForLoadState('networkidle')

    await expect(page.getByText('Niski')).toBeVisible({ timeout: 10000 })
    await expect(page.getByText('Krytyczny')).toBeVisible()
    await expect(page.getByRole('button', { name: 'Zapisz' })).toBeVisible()
  })

  test('admin can view organization settings', async ({ page }) => {
    await loginAs(page, TEST_ADMIN.email, TEST_ADMIN.password)

    await page.goto('/settings')
    await page.waitForLoadState('networkidle')

    await expect(page.locator('input').first()).toHaveValue('TechVision', { timeout: 10000 })
  })

  test('admin can create invite', async ({ page }) => {
    await loginAs(page, TEST_ADMIN.email, TEST_ADMIN.password)

    await page.goto('/settings')
    await page.waitForLoadState('networkidle')

    await page.getByPlaceholder('Email (opcjonalnie)').fill(`e2e.test.${Date.now()}@example.com`)
    await page.getByRole('button', { name: 'Utwórz zaproszenie' }).click()

    await expect(page.getByText('Zaproszenie utworzone')).toBeVisible({ timeout: 5000 })
  })
})
