import { afterEach, describe, expect, it, vi } from 'vitest'

import { analysisEndpoint, loadAnalysis } from './client'

afterEach(() => vi.unstubAllGlobals())

describe('API client configuration', () => {
  it('uses the XAMPP-relative API path', () => {
    expect(analysisEndpoint).toBe('api/analysis')
  })
  it('encodes exclusions and propagates cancellation without persisting them', async () => {
    const fetch = vi.fn().mockResolvedValue({ ok: true, json: async () => ({}) })
    vi.stubGlobal('fetch', fetch)
    const controller = new AbortController()
    await loadAnalysis({ excluded_task_ids: [1, 2], excluded_employee_ids: [3] }, controller.signal)
    expect(fetch).toHaveBeenCalledWith('api/analysis?excluded_tasks=1%2C2&excluded_employees=3', expect.objectContaining({ signal: controller.signal }))
  })
  it('requests one uncached analysis snapshot', async () => {
    const fetch = vi.fn().mockResolvedValue({ ok: true, json: async () => ({ taxonomy: {} }) })
    vi.stubGlobal('fetch', fetch)
    expect(await loadAnalysis()).toEqual({ taxonomy: {} })
    expect(fetch).toHaveBeenCalledWith('api/analysis', { cache: 'no-store', headers: { Accept: 'application/json' } })
  })
  it.each([409, 503])('does not expose server internals on HTTP %s', async status => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: false, status, json: async () => ({ secret: 'password' }) }))
    await expect(loadAnalysis()).rejects.toThrow(status === 409 ? /nicht konsistent/ : /nicht erreichbar/)
  })
})
