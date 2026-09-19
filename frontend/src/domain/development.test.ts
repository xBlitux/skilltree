import { expect, it } from 'vitest'
import { developmentPath } from './development'
import type { Analysis } from './taxonomy'

function data(): Analysis {
  return {
    organisation: { id: 1, name: 'Test' }, summary: { task_count: 1, employee_count: 1, required_skill_count: 8, available_skill_count: 4, counts: { red: 4, yellow: 4, green: 0 } },
    skills: [], required_skill_ids: [1, 2, 3, 4, 5, 6, 7, 8],
    taxonomy: { groups: [], skills: Array.from({ length: 9 }, (_, index) => ({ id: index + 1, name: `Skill ${index + 1}`, skill_group_id: 1 })) },
    employees: [{ id: 1, first_name: 'Test', last_name: 'Schulze', available_skill_ids: [1, 2, 3, 4] }],
    development: { estimated_skill_ids: [1, 2, 3, 4, 5, 6, 7, 8], candidates: [], edges: [
      { source: 1, target: 4 }, { source: 2, target: 4 }, { source: 3, target: 4 },
      { source: 4, target: 6 }, { source: 5, target: 7 }, { source: 6, target: 8 }, { source: 7, target: 8 },
    ] },
  }
}
it('S-05 acceptance: remaining A,B,C,D,E; full additionally X,Y,Z; distance stays 4', () => {
  const remaining = developmentPath(data(), 8, 1, false)!
  expect(remaining.nodes.map(node => node.id).sort()).toEqual([4, 5, 6, 7, 8])
  expect(remaining.distance).toBe(4)
  const full = developmentPath(data(), 8, 1, true)!
  expect(full.nodes).toHaveLength(8)
  expect(full.distance).toBe(4)
  expect(full.nodes.filter(node => node.owned)).toHaveLength(4)
})
it('retains an owned prerequisite if needed as a boundary on another branch', () => {
  const source = data(); source.development.edges.push({ source: 1, target: 7 })
  const path = developmentPath(source, 8, 1, false)!
  expect(path.nodes.map(node => node.id).sort()).toEqual([1, 4, 5, 6, 7, 8])
  expect(path.edges).not.toContainEqual({ source: 1, target: 4 })
  expect(path.edges).toContainEqual({ source: 1, target: 7 })
  expect(path.distance).toBe(4)
})
it('general path is full and neutral regardless of current demand', () => {
  const source = data(); source.required_skill_ids = []
  const path = developmentPath(source, 8, null, false)!
  expect(path.nodes).toHaveLength(8)
  expect(path.nodes.every(node => node.owned === null)).toBe(true)
  expect(path.distance).toBeNull()
  for (const edge of path.edges) expect(path.nodes.find(node => node.id === edge.source)!.x).toBeLessThan(path.nodes.find(node => node.id === edge.target)!.x)
})
it('owned target gives distance zero and a single green target in remaining view', () => {
  const path = developmentPath(data(), 4, 1, false)!
  expect(path.distance).toBe(0); expect(path.nodes).toHaveLength(1)
  expect(path.nodes[0]?.owned).toBe(true)
})
it('estimated root opens as one node; unestimated catalog skill has no path', () => {
  expect(developmentPath(data(), 1, null, true)?.nodes).toHaveLength(1)
  expect(developmentPath(data(), 9, null, true)).toBeNull()
})
it('no possession counts shared prerequisite only once, including the target', () => {
  const source = data(); source.employees[0]!.available_skill_ids = []
  source.development.edges.push({ source: 4, target: 7 })
  expect(developmentPath(source, 8, 1, false)?.distance).toBe(8)
})
