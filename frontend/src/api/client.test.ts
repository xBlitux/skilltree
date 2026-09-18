import { describe, expect, it } from 'vitest'

import { healthEndpoint } from './client'

describe('API client configuration', () => {
  it('uses the XAMPP-relative API path', () => {
    expect(healthEndpoint).toBe('api/health')
  })
})
