import { expect, test } from '@playwright/test'

test('dashboard supports four-digit counts at desktop and mobile widths', async ({ page, request }) => {
  const data = await (await request.get('api/analysis')).json()
  // Rendering-only fixture, not fabricated database results.
  data.summary.counts = { red: 1234, yellow: 5678, green: 9012 }
  await page.route('**/api/analysis', route => route.fulfill({ json: data }))
  await page.goto('./')
  await expect(page.locator('.metric-number')).toHaveText(['1.234', '5.678', '9.012'])
  for (const width of [1440, 390]) {
    await page.setViewportSize({ width, height: 900 })
    for (const counter of await page.locator('.metric-number').all()) {
      expect(await counter.evaluate(element => element.scrollWidth <= element.clientWidth)).toBe(true)
    }
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true)
  }
})

test('a newly opened page starts without the simulation of another page', async ({ page, context }) => {
  await page.goto('./')
  await page.getByRole('button', { name: 'Simulation', exact: true }).click()
  await page.getByRole('checkbox', { name: 'Anna Adler' }).uncheck()
  await expect(page.locator('.simulation-fields')).toBeEnabled()
  await page.getByRole('dialog').getByRole('button', { name: 'Schließen', exact: true }).click()
  await expect(page.locator('.metric-number')).toHaveText(['6', '4', '16'])
  const fresh = await context.newPage()
  await fresh.goto('./')
  await expect(fresh.locator('.metric-number')).toHaveText(['3', '7', '16'])
  await expect(fresh.getByRole('button', { name: 'Simulation', exact: true })).toHaveAttribute('aria-pressed', 'false')
  await expect(page.locator('.metric-number')).toHaveText(['6', '4', '16'])
  await fresh.close()
})
