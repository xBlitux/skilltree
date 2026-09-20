import { afterEach, expect, it, vi } from 'vitest'
import { useAnalysis } from './useAnalysis'
import type { Analysis } from '../domain/taxonomy'

const result = (name: string): Analysis => ({ organisation: { id: 1, name }, taxonomy: { skills: [], groups: [] }, skills: [], required_skill_ids: [], employees: [] } as unknown as Analysis)
afterEach(() => vi.unstubAllGlobals())
it('ignores an older response even if the network did not honor cancellation', async () => {
  let finishOld!: (value: unknown) => void
  const fetch = vi.fn().mockImplementationOnce(() => new Promise(resolve => { finishOld = resolve }))
    .mockResolvedValueOnce({ ok: true, json: async () => result('New') })
  vi.stubGlobal('fetch', fetch)
  const state = useAnalysis()
  const old = state.load({ excluded_task_ids: [], excluded_employee_ids: [1] })
  await state.load()
  finishOld({ ok: true, json: async () => result('Old') })
  expect(await old).toBe(false)
  expect(state.data.value?.organisation.name).toBe('New')
  expect(state.busy.value).toBe(false)
  expect(fetch.mock.calls[0]?.[1].signal.aborted).toBe(true)
})
it('keeps the last complete snapshot on failure and clears the error on retry', async () => {
  vi.stubGlobal('fetch', vi.fn().mockResolvedValueOnce({ ok: true, json: async () => result('Base') })
    .mockResolvedValueOnce({ ok: false, status: 503 }).mockResolvedValueOnce({ ok: true, json: async () => result('Filtered') }))
  const state = useAnalysis()
  await state.load()
  await state.load({ excluded_task_ids: [1], excluded_employee_ids: [] })
  expect(state.error.value).toContain('nicht erreichbar')
  expect(state.data.value?.organisation.name).toBe('Base')
  await state.load(state.requested.value)
  expect(state.data.value?.organisation.name).toBe('Filtered')
  expect(state.error.value).toBe('')
})
