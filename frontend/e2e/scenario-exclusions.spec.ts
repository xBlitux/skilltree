import { expect, test } from '@playwright/test'

test('scenarios replace previous exclusions, preserve employees and restore non-listed tasks', async ({ page }) => {
  // Fully synthetic API responses: no real database required or changed.
  const tasks = [1, 2, 3, 4].map(id => ({ id, name: `Testaufgabe ${id}` }))
  const employees = [{ id: 1, first_name: 'Test', last_name: 'Person' }]
  const scenarios = [
    { id: 1, name: 'A', task_ids: [2] },
    { id: 2, name: 'B', task_ids: [1, 3] },
    { id: 3, name: 'Leer', task_ids: [] },
  ]
  let lastTasks: number[] = [], lastEmployees: number[] = []
  await page.route('**/api/analysis*', async route => {
    const query = new URL(route.request().url()).searchParams
    const ids = (key: string) => (query.get(key) ?? '').split(',').filter(Boolean).map(Number)
    lastTasks = ids('excluded_tasks'); lastEmployees = ids('excluded_employees')
    await route.fulfill({ json: {
      organisation: { id: 1, name: 'Synthetische Organisation' },
      summary: { task_count: 4 - lastTasks.length, employee_count: 1 - lastEmployees.length, required_skill_count: 0, available_skill_count: 0, counts: { red: 0, yellow: 0, green: 0 } },
      skills: [], employees: employees.filter(e => !lastEmployees.includes(e.id)).map(e => ({ ...e, available_skill_ids: [] })), required_skill_ids: [],
      taxonomy: { groups: [], skills: [] }, development: { estimated_skill_ids: [], edges: [], candidates: [] },
      simulation: { tasks, employees, scenarios, excluded_task_ids: lastTasks, excluded_employee_ids: lastEmployees },
    } })
  })
  await page.goto('./')
  await page.getByRole('button', { name: 'Simulation', exact: true }).click()
  const dialog = page.getByRole('dialog')
  await dialog.getByRole('checkbox', { name: 'Test Person' }).uncheck()
  await expect(dialog.locator('fieldset')).toBeEnabled()
  await dialog.getByRole('checkbox', { name: 'Testaufgabe 4' }).uncheck()
  await expect(dialog.locator('fieldset')).toBeEnabled()
  for (const scenario of [scenarios[0]!, scenarios[1]!, scenarios[0]!, scenarios[2]!]) {
    const button = dialog.getByRole('button', { name: `Szenario "${scenario.name}" aktivieren`, exact: true })
    await expect(button).toHaveAttribute('title', `Szenario "${scenario.name}" aktivieren`)
    const response = page.waitForResponse(r => r.url().includes('/api/analysis'))
    await button.click()
    await response
    await expect(dialog.locator('fieldset')).toBeEnabled()
    expect(lastTasks).toEqual(scenario.task_ids)
    expect(lastEmployees).toEqual([1])
    for (const task of tasks) await expect(dialog.getByRole('checkbox', { name: task.name })).toBeChecked({ checked: !scenario.task_ids.includes(task.id) })
    await expect(dialog.getByRole('checkbox', { name: 'Test Person' })).not.toBeChecked()
  }
})
