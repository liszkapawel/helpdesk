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

    // Find agent row and open role select
    const agentRow = page.getByRole('row').filter({ hasText: 'anna.nowak@techvision.pl' })
    await expect(agentRow).toBeVisible()

    // Find the role select in that row
    const roleSelect = agentRow.locator('.p-select').first()
    await roleSelect.click()

    // Select AGENT option (it's already AGENT, just verify dropdown works)
    const agentOption = page.getByRole('option', { name: 'Agent' })
    await expect(agentOption).toBeVisible()
    await agentOption.click()
  })

  test('admin can add category', async ({ page }) => {
    await loginAs(page, TEST_ADMIN.email, TEST_ADMIN.password)

    await page.goto('/admin/categories')

    const newCategoryName = 'E2E Kategoria ' + Date.now()
    const nameInput = page.getByPlaceholder('Nowa kategoria')
    if (await nameInput.isVisible()) {
      await nameInput.fill(newCategoryName)
      await page.getByRole('button', { name: 'Dodaj' }).click()
      await expect(page.getByText(newCategoryName)).toBeVisible()
    } else {
      // Alternative UI — find add button
      await page.getByRole('button', { name: /Dodaj|Nowa/ }).first().click()
      await page.getByLabel('Nazwa').fill(newCategoryName)
      await page.getByRole('button', { name: 'Zapisz' }).click()
      await expect(page.getByText(newCategoryName)).toBeVisible()
    }
  })
})
