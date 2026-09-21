export interface SimulationFilters { excluded_task_ids: number[]; excluded_employee_ids: number[]; organisation?: number; maximum_distance?: number }
export interface Simulation extends SimulationFilters {
  scenarios?: { id: number; name: string; task_ids: number[] }[]
  tasks: { id: number; name: string }[]
  employees: { id: number; first_name: string; last_name: string }[]
}
export const emptyFilters = (): SimulationFilters => ({ excluded_task_ids: [], excluded_employee_ids: [] })

export function toggleExclusion(filters: SimulationFilters, kind: 'task' | 'employee', id: number): SimulationFilters {
  const key = kind === 'task' ? 'excluded_task_ids' : 'excluded_employee_ids'
  const selected = new Set(filters[key])
  selected.has(id) ? selected.delete(id) : selected.add(id)
  return { excluded_task_ids: [...filters.excluded_task_ids], excluded_employee_ids: [...filters.excluded_employee_ids], [key]: [...selected].sort((a, b) => a - b) }
}
