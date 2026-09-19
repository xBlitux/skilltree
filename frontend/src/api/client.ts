import type { Analysis } from '../domain/taxonomy'

export const analysisEndpoint = 'api/analysis'
export async function loadAnalysis(): Promise<Analysis> {
  const response = await fetch(analysisEndpoint, { cache: 'no-store', headers: { Accept: 'application/json' } })
  if (!response.ok) throw new Error(response.status === 409
    ? 'Die vorbereiteten Daten sind nicht konsistent. Bitte den Datenbestand prüfen.'
    : 'Die Analyse ist momentan nicht erreichbar. Bitte Apache und Datenbank prüfen.')
  return await response.json() as Analysis
}
