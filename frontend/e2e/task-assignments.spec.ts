import { expect, test } from '@playwright/test'

test('direct and implicit assignments retain excluded tasks and follow organisation context', async ({ page }) => {
  // Synthetic data only. Skill 1 is also an implicit prerequisite of skill 2.
  const skills = [1, 2, 3].map(id => ({ id, name: `Testskill ${id}`, skill_group_id: 1 }))
  let longList = false
  await page.route('**/api/analysis*', async route => {
    const query = new URL(route.request().url()).searchParams
    const organisation = Number(query.get('organisation') || 1)
    const excluded = (query.get('excluded_tasks') || '').split(',').filter(Boolean).map(Number)
    const tasks = organisation === 1 ? [
      { id: 1, name: 'Direkte Aufgabe', direct_skill_ids: [1], required_skill_ids: [1] },
      { id: 2, name: 'Nur indirekte Aufgabe', direct_skill_ids: [2], required_skill_ids: [1, 2] },
      { id: 3, name: '', direct_skill_ids: [1, 2], required_skill_ids: [1, 2] },
    ] : [{ id: 4, name: 'Andere Organisation Aufgabe', direct_skill_ids: [1], required_skill_ids: [1] }]
    if (longList) {
      tasks.push(...Array.from({ length: 60 }, (_, n) => ({ id: n + 100, name: `Lange Testaufgabe ${n}`, direct_skill_ids: [1], required_skill_ids: [1] })))
    }
    const employees = [{ id: 1, first_name: 'Test', last_name: 'Person', available_skill_ids: [] }]
    await route.fulfill({ json: {
      organisation: { id: organisation, name: `Testorganisation ${organisation}` },
      organisations: [1, 2].map(id => ({ id, name: `Testorganisation ${id}` })),
      summary: { task_count: tasks.length - excluded.length, employee_count: 1, required_skill_count: 3, available_skill_count: 0, counts: { red: 0, yellow: 0, green: 3 } },
      required_skill_ids: [1, 2, 3], employees,
      skills: skills.map(s => ({ ...s, status: 'green', carrier_count: 0, employee_ids: [] })),
      taxonomy: { groups: [{ id: 1, name: 'Testgruppe', parent_skill_group_id: null }], skills },
      development: { estimated_skill_ids: [1, 2, 3], edges: [{ source: 1, target: 2 }], candidates: [] },
      simulation: { tasks, employees, scenarios: [], excluded_task_ids: excluded, excluded_employee_ids: [] },
    } })
  })
  await page.goto('./')
  const openPath = async (id: number) => {
    await page.getByRole('button', { name: 'Unkritisch: 3 Skills anzeigen' }).click()
    await page.getByRole('button', { name: `Testskill ${id}`, exact: true }).click()
  }
  await openPath(1)
  const button = page.getByRole('button', { name: 'Aufgabenzuordnung', exact: true })
  const dialog = page.getByRole('dialog', { name: 'Aufgabenzuordnung', exact: true })
  await button.click()
  await expect(dialog).toContainText('Testskill 1')
  await expect(dialog.locator('li')).toHaveText(['Aufgabe 1 • Direkte Aufgabe', 'Aufgabe 2 • Nur indirekte Aufgabe', 'Aufgabe 3 • kein Aufgabenname hinterlegt'])
  await page.keyboard.press('Escape')
  await expect(button).toBeFocused()
  await page.getByRole('combobox', { name: 'Person im Entwicklungspfad' }).selectOption('1')
  await button.click()
  await expect(dialog.locator('li')).toHaveCount(3)
  await dialog.getByRole('button', { name: 'Schließen' }).click()
  await page.getByRole('button', { name: 'Simulation', exact: true }).click()
  const simulation = page.getByRole('dialog', { name: 'Ausfall simulieren' })
  await simulation.getByRole('checkbox', { name: 'Direkte Aufgabe', exact: true }).uncheck()
  await expect(simulation.locator('fieldset')).toBeEnabled()
  await page.keyboard.press('Escape')
  await button.click()
  await expect(dialog.locator('li').first()).toHaveText('Aufgabe 1 • Direkte Aufgabein Simulation ausgeschlossen')
  await expect(dialog.locator('li')).toHaveCount(3)
  await page.mouse.click(5, 5)
  await expect(dialog).toHaveCount(0)
  await expect(page.getByRole('combobox', { name: 'Person im Entwicklungspfad' })).toHaveValue('1')
  await page.getByRole('combobox', { name: 'Organisationseinheit', exact: true }).selectOption('2')
  await openPath(1)
  await button.click()
  await expect(dialog.locator('li')).toHaveText(['Aufgabe 4 • Andere Organisation Aufgabe'])
  await dialog.getByRole('button', { name: 'Schließen' }).click()
  await page.getByRole('button', { name: /Zurück/ }).click()
  await page.getByRole('button', { name: 'Testskill 3', exact: true }).click()
  await button.click()
  await expect(dialog).toContainText('keine Aufgaben direkt oder implizit zugeordnet')
  await dialog.getByRole('button', { name: 'Schließen' }).click()
  await page.setViewportSize({ width: 390, height: 844 })
  await button.click()
  await expect(dialog).toBeVisible()
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true)
  await page.screenshot({ path: 'test-results/task-assignments-mobile.png' })
  await dialog.getByRole('button', { name: 'Schließen' }).click()
  // Supply a long list through the same synthetic endpoint, without touching the database.
  longList = true
  await page.getByRole('combobox', { name: 'Organisationseinheit', exact: true }).selectOption('1')
  await openPath(1)
  await button.click()
  for (const width of [390, 1440]) {
    await page.setViewportSize({ width, height: 844 })
    const content = dialog.getByRole('region', { name: 'Zugeordnete Aufgaben' })
    await expect(dialog.locator('li')).toHaveCount(63)
    expect(await content.evaluate(el => el.scrollHeight > el.clientHeight)).toBe(true)
    await content.focus()
    await page.keyboard.press('Control+End')
    await expect(dialog.locator('li').last()).toBeInViewport()
    await expect(dialog.getByRole('button', { name: 'Schließen' })).toBeInViewport()
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true)
    await page.screenshot({ path: `test-results/task-assignments-scroll-${width}.png` })
  }
})
