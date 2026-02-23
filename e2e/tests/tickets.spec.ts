import { test, expect } from '@playwright/test'
import { loginAs, TEST_ADMIN, TEST_USER } from '../helpers/auth'

test.describe('Tickets', () => {
  test('user can create a ticket', async ({ page }) => {
    await loginAs(page, TEST_USER.email, TEST_USER.password)

    await page.goto('/tickets/new')
    await page.getByLabel('Tytuł').fill('E2E test ticket')
    await page.getByLabel('Opis').fill('Ticket created by Playwright test')
    await page.getByRole('button', { name: 'Utwórz zgłoszenie' }).click()

    await expect(page.getByText('E2E test ticket')).toBeVisible()
  })

  test('ticket list loads with data', async ({ page }) => {
    await loginAs(page, TEST_ADMIN.email, TEST_ADMIN.password)

    await page.goto('/tickets')
    await expect(page.locator('table, [data-pc-name="datatable"], .ticket-row').first()).toBeVisible({ timeout: 10000 })
  })

  test('can open ticket detail', async ({ page }) => {
    await loginAs(page, TEST_ADMIN.email, TEST_ADMIN.password)

    await page.goto('/tickets')
    // Click first ticket in list
    const firstTicket = page.locator('tbody tr, .ticket-row').first()
    await firstTicket.click()

    await expect(page.getByText('Opis')).toBeVisible()
    await expect(page.getByText('Oś czasu')).toBeVisible()
  })

  test('agent can change ticket status', async ({ page }) => {
    await loginAs(page, TEST_ADMIN.email, TEST_ADMIN.password)

    await page.goto('/tickets')
    await page.locator('tbody tr').first().click()

    await page.getByRole('button', { name: 'Edytuj' }).click()

    // Select resolved status in PrimeVue Select
    const statusSelect = page.locator('.p-select').filter({ hasText: /Nowy|Otwarty|W toku|Rozwiązany|Zamknięty/ }).first()
    await statusSelect.click()
    await page.getByRole('option', { name: 'Rozwiązany' }).click()

    await page.getByRole('button', { name: 'Zapisz' }).click()
    await expect(page.getByText('Zaktualizowano')).toBeVisible()
  })

  test('can add comment to ticket', async ({ page }) => {
    await loginAs(page, TEST_USER.email, TEST_USER.password)

    await page.goto('/tickets')
    await page.locator('tbody tr').first().click()

    const commentText = 'Playwright comment ' + Date.now()
    await page.getByPlaceholder('Napisz komentarz...').fill(commentText)
    await page.getByRole('button', { name: 'Wyślij' }).click()

    await expect(page.getByText(commentText)).toBeVisible()
  })
})
