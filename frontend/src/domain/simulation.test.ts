import { expect, it } from 'vitest'
import { emptyFilters, toggleExclusion } from './simulation'

it('toggles one exclusion without changing the original snapshot or other filters', () => {
  const original = { excluded_task_ids: [7], excluded_employee_ids: [3] }
  const changed = toggleExclusion(original, 'employee', 1)
  expect(changed).toEqual({ excluded_task_ids: [7], excluded_employee_ids: [1, 3] })
  expect(toggleExclusion(changed, 'employee', 1)).toEqual(original)
  expect(original).toEqual({ excluded_task_ids: [7], excluded_employee_ids: [3] })
  expect(toggleExclusion(original, 'task', 7).excluded_task_ids).toEqual([])
  expect(emptyFilters()).toEqual({ excluded_task_ids: [], excluded_employee_ids: [] })
})
