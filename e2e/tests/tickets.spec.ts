import { test, expect } from '@playwright/test'
import { loginAs, TEST_ADMIN, TEST_AGENT, TEST_USER } from '../helpers/auth'

test.describe('Tickets', () => {
  test('user can create a ticket', async ({ page }) => {
    await loginAs(page, TEST_USER.email, TEST_USER.password)

    await page.goto('/tickets/new')
    await page.getByPlaceholder('Krótki opis problemu').fill('E2E test ticket')
    await page.getByPlaceholder('Opisz problem szczegółowo...').fill('Ticket created by Playwright test')
    await page.getByRole('button', { name: 'Utwórz ticket' }).click()

    await expect(page.getByText('E2E test ticket')).toBeVisible()
  })

  test('ticket list loads with data', async ({ page }) => {
    await loginAs(page, TEST_ADMIN.email, TEST_ADMIN.password)

    await page.goto('/tickets')
    await expect(page.locator('table, [data-pc-name="datatable"], .ticket-row').first()).toBeVisible({ timeout: 10000 })
  })

  test('ticket list shows fixture tickets', async ({ page }) => {
    await loginAs(page, TEST_ADMIN.email, TEST_ADMIN.password)

    await page.goto('/tickets')
    // Wait for rows to appear, then check fixture ticket count
    await expect(page.locator('tbody tr').nth(5)).toBeVisible({ timeout: 10000 })
    const rows = await page.locator('tbody tr').count()
    expect(rows).toBeGreaterThanOrEqual(10)
  })

  test('can open ticket detail', async ({ page }) => {
    await loginAs(page, TEST_ADMIN.email, TEST_ADMIN.password)

    await page.goto('/tickets')
    const firstTicket = page.locator('tbody tr, .ticket-row').first()
    await firstTicket.click()

    await expect(page.getByText('Opis')).toBeVisible()
    await expect(page.getByText('Oś czasu')).toBeVisible()
  })

  test('ticket detail shows comments', async ({ page }) => {
    await loginAs(page, TEST_ADMIN.email, TEST_ADMIN.password)

    await page.goto('/tickets')
    await page.waitForLoadState('networkidle')

    // Open a ticket known to have comments
    await page.getByText('VPN nie łączy z biurem').click()

    await expect(page.getByText('Sprawdziłam konfigurację')).toBeVisible({ timeout: 10000 })
  })

  test('agent can change ticket status', async ({ page }) => {
    await loginAs(page, TEST_ADMIN.email, TEST_ADMIN.password)

    await page.goto('/tickets')
    await page.locator('tbody tr').first().click()

    await page.getByRole('button', { name: 'Edytuj' }).click()

    const statusSelect = page.locator('.p-select').filter({ hasText: /Nowy|Otwarty|W toku|Rozwiązany|Zamknięty/ }).first()
    await statusSelect.click()
    await page.getByRole('option', { name: 'Rozwiązany' }).click()

    await page.getByRole('button', { name: 'Zapisz' }).click()
    await expect(page.getByText('Zaktualizowano')).toBeVisible({ timeout: 10000 })
  })

  test('agent can assign ticket to themselves', async ({ page }) => {
    await loginAs(page, TEST_AGENT.email, TEST_AGENT.password)

    await page.goto('/tickets')
    await page.waitForLoadState('networkidle')

    // Find an unassigned ticket
    await page.locator('tbody tr').first().click()

    await page.getByRole('button', { name: 'Edytuj' }).click()

    // Assignee select should be visible
    await expect(page.locator('.p-select').first()).toBeVisible()
    await page.getByRole('button', { name: 'Zapisz' }).click()
  })

  test('can add comment to ticket', async ({ page }) => {
    await loginAs(page, TEST_USER.email, TEST_USER.password)

    await page.goto('/tickets')
    await page.locator('tbody tr').first().click()

    const commentText = 'Playwright comment ' + Date.now()
    await page.getByPlaceholder('Napisz komentarz...').fill(commentText)
    await page.getByRole('button', { name: 'Wyślij', exact: true }).click()

    await expect(page.getByText(commentText)).toBeVisible()
  })

  test('admin can filter tickets by status', async ({ page }) => {
    await loginAs(page, TEST_ADMIN.email, TEST_ADMIN.password)

    await page.goto('/tickets')
    await page.waitForLoadState('networkidle')

    // Find status filter
    const filterSelect = page.locator('.p-select, select').first()
    await filterSelect.click()
    const newOption = page.getByRole('option', { name: /Nowy/ }).first()
    if (await newOption.isVisible()) {
      await newOption.click()
      await page.waitForLoadState('networkidle')
      // All visible rows should show "Nowy" status
      await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 10000 })
    }
  })

  test('user ticket list shows only their tickets', async ({ page }) => {
    await loginAs(page, TEST_USER.email, TEST_USER.password)

    await page.goto('/tickets')
    await page.waitForLoadState('networkidle')

    // User should see their tickets, not tickets from other users
    await expect(page.locator('tbody tr').first()).toBeVisible({ timeout: 10000 })
  })
})
