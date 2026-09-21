import { describe, expect, it } from 'vitest'
import { taxonomyView, type Analysis } from './taxonomy'

export function fixture(): Analysis {
  return {
    simulation: { excluded_task_ids: [], excluded_employee_ids: [], tasks: [], employees: [] },
    development: { estimated_skill_ids: [1, 2, 3], edges: [], candidates: [] },
    organisation: { id: 1, name: 'Testorganisation' },
    summary: { task_count: 1, employee_count: 1, required_skill_count: 3, available_skill_count: 2, counts: { red: 1, yellow: 1, green: 1 } },
    required_skill_ids: [1, 2, 3],
    skills: [
      { id: 1, name: 'Rot', skill_group_id: 3, status: 'red', carrier_count: 0, employee_ids: [] },
      { id: 2, name: 'Gelb', skill_group_id: 2, status: 'yellow', carrier_count: 1, employee_ids: [1] },
      { id: 3, name: 'Grün', skill_group_id: 2, status: 'green', carrier_count: 2, employee_ids: [1, 2] },
    ],
    employees: [{ id: 1, first_name: 'Test', last_name: 'Person', available_skill_ids: [2, 3, 4] }],
    taxonomy: {
      groups: [{ id: 1, name: 'Wurzel', parent_skill_group_id: null }, { id: 2, name: 'Untergruppe', parent_skill_group_id: 1 }, { id: 3, name: 'Tief', parent_skill_group_id: 2 }, { id: 4, name: 'Zusatz', parent_skill_group_id: 1 }],
      skills: [{ id: 1, name: 'Rot', skill_group_id: 3 }, { id: 2, name: 'Gelb', skill_group_id: 2 }, { id: 3, name: 'Grün', skill_group_id: 2 }, { id: 4, name: 'Extra', skill_group_id: 4 }],
    },
  }
}
describe('F-01..F-11 taxonomy projection', () => {
  it('inherits worst status over every ancestor without counting ancestors as skills', () => {
    const tree = taxonomyView(fixture(), true, null)
    expect(tree.roots[0]?.color).toBe('red')
    expect(tree.nodes.get(2)?.color).toBe('red')
    expect(tree.roots[0]?.count).toBe(3)
    expect(tree.roots[0]?.children.map(group => group.id)).toEqual([2])
  })
  it('shows outside-Soll skills only when unmasked, neutrally rated', () => {
    const tree = taxonomyView(fixture(), false, null)
    expect(tree.roots[0]?.count).toBe(4)
    expect(tree.nodes.get(4)?.color).toBe('neutral')
  })
  it('uses binary personal possession without expanding the masked Soll', () => {
    const data = fixture()
    const before = JSON.stringify(data)
    const tree = taxonomyView(data, true, 1)
    expect(tree.color(2)).toBe('green')
    expect(tree.color(1)).toBe('red')
    expect(tree.roots[0]?.count).toBe(3)
    expect(taxonomyView(data, false, 1).nodes.get(4)?.color).toBe('green')
    expect(JSON.stringify(data)).toBe(before)
  })
  it('keeps the complete catalog accessible without Soll', () => {
    const data = fixture(); data.skills = []; data.required_skill_ids = []
    expect(taxonomyView(data, true, null).roots[0]?.count).toBe(4)
    expect(taxonomyView(data, true, null).roots[0]?.color).toBe('neutral')
  })
  it('hides empty top-level groups with and without mask and supports an empty catalog', () => {
    const data = fixture(); data.taxonomy.skills = []
    expect(taxonomyView(data, true, null).roots).toEqual([])
    expect(taxonomyView(data, false, null).roots).toEqual([])
    data.taxonomy.groups = []
    expect(taxonomyView(data, true, null).roots).toEqual([])
  })
  it('rejects missing parents, orphan skills and disconnected cycles', () => {
    const data = fixture(); data.taxonomy.groups[0]!.parent_skill_group_id = 3
    expect(() => taxonomyView(data, true, null)).toThrow(/zyklische/)
    data.taxonomy.groups[0]!.parent_skill_group_id = 99
    expect(() => taxonomyView(data, true, null)).toThrow(/übergeordnete/)
    data.taxonomy.groups[0]!.parent_skill_group_id = null
    data.taxonomy.skills[0]!.skill_group_id = 99
    expect(() => taxonomyView(data, true, null)).toThrow(/Skillgruppe/)
  })
})
