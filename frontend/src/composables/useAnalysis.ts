import { ref, shallowRef } from 'vue'
import { loadAnalysis } from '../api/client'
import { taxonomyView, type Analysis } from '../domain/taxonomy'
import { emptyFilters, type SimulationFilters } from '../domain/simulation'

/** Commit an entire snapshot, never partial counters/filters or a stale response. */
export function useAnalysis() {
  const data = shallowRef<Analysis | null>(null)
  const busy = ref(false)
  const error = ref('')
  const requested = ref<SimulationFilters>(emptyFilters())
  let sequence = 0
  let controller: AbortController | undefined
  async function load(filters: SimulationFilters = emptyFilters()): Promise<boolean> {
    const request = ++sequence
    controller?.abort()
    controller = new AbortController()
    requested.value = { excluded_task_ids: [...filters.excluded_task_ids], excluded_employee_ids: [...filters.excluded_employee_ids] }
    busy.value = true; error.value = ''
    try {
      const result = await loadAnalysis(requested.value, controller.signal)
      if (request !== sequence) return false
      taxonomyView(result, false, null)
      data.value = result
      return true
    } catch (cause) {
      if (request === sequence) error.value = cause instanceof Error ? cause.message : 'Die Analyse konnte nicht geladen werden.'
      return false
    } finally { if (request === sequence) busy.value = false }
  }
  return { data, busy, error, requested, load }
}
