import type { Analysis } from '../domain/taxonomy'
import { emptyFilters, type SimulationFilters } from '../domain/simulation'

export const analysisEndpoint = 'api/analysis'
export async function loadAnalysis(filters: SimulationFilters = emptyFilters(), signal?: AbortSignal): Promise<Analysis> {
  const query = new URLSearchParams()
  if (filters.excluded_task_ids.length) query.set('excluded_tasks', filters.excluded_task_ids.join(','))
  if (filters.excluded_employee_ids.length) query.set('excluded_employees', filters.excluded_employee_ids.join(','))
  const endpoint = query.size ? `${analysisEndpoint}?${query}` : analysisEndpoint
  const response = await fetch(endpoint, { cache: 'no-store', headers: { Accept: 'application/json' }, ...(signal ? { signal } : {}) })
  if (response.status === 400) throw new Error('Die Simulationsauswahl passt nicht mehr zum Datenbestand. Mit Start die Ausgangsdaten neu laden.')
  if (!response.ok) throw new Error(response.status === 409
    ? 'Die vorbereiteten Daten sind nicht konsistent. Bitte den Datenbestand prüfen.'
    : 'Die Analyse ist momentan nicht erreichbar. Bitte Apache und Datenbank prüfen.')
  return await response.json() as Analysis
}
