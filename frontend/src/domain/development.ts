import type { Analysis, Skill } from './taxonomy'

export interface Edge { source: number; target: number }
export interface Candidate { employee_id: number; first_name: string; last_name: string; distance: number }
export interface CandidateSet {
  skill_id: number
  slots: (Candidate | null)[]
  minimum_distance: number | null
  recommendations: { distance_threshold: boolean; unfilled_slots: boolean }
}
export interface Development { estimated_skill_ids: number[]; edges: Edge[]; candidates: CandidateSet[]; maximum_distance?: number }
export interface PathNode extends Skill { owned: boolean | null; target: boolean; x: number; y: number }
export interface DevelopmentPath { nodes: PathNode[]; edges: Edge[]; distance: number | null }

/** S-01/S-02/S-05: full closure for distance; projection only for remaining view. */
export function developmentPath(data: Analysis, target: number, person: number | null, full: boolean): DevelopmentPath | null {
  if (!data.development.estimated_skill_ids.includes(target)) return null
  const skills = new Map(data.taxonomy.skills.map(skill => [skill.id, skill]))
  const prerequisites = new Map<number, number[]>()
  for (const edge of data.development.edges) {
    if (!prerequisites.has(edge.target)) prerequisites.set(edge.target, [])
    prerequisites.get(edge.target)!.push(edge.source)
  }
  const closure = new Set<number>()
  const stack = [target]
  while (stack.length) {
    const id = stack.pop()!
    if (closure.has(id)) continue
    if (!skills.has(id)) throw new Error('Unbekannter Skill im Entwicklungspfad.')
    closure.add(id); stack.push(...(prerequisites.get(id) ?? []))
  }
  const employee = data.employees.find(item => item.id === person)
  const owned = new Set(employee?.available_skill_ids ?? [])
  const missing = new Set([...closure].filter(id => !owned.has(id)))
  const visible = new Set<number>(employee && !full ? missing : closure)
  if (employee && !full) {
    for (const id of missing) for (const prerequisite of prerequisites.get(id) ?? []) visible.add(prerequisite)
    visible.add(target) // Distanz 0: one green target remains as an explicit result.
  }
  const edges = data.development.edges.filter(edge => visible.has(edge.source) && visible.has(edge.target)
    && (!employee || full || !owned.has(edge.target)))
  // Longest-path levels guarantee all arrows run left -> right, including diamonds.
  const degree = new Map([...visible].map(id => [id, 0]))
  const successors = new Map<number, number[]>()
  const level = new Map([...visible].map(id => [id, 0]))
  for (const edge of edges) {
    degree.set(edge.target, degree.get(edge.target)! + 1)
    if (!successors.has(edge.source)) successors.set(edge.source, [])
    successors.get(edge.source)!.push(edge.target)
  }
  const ready = [...visible].filter(id => degree.get(id) === 0)
  let processed = 0
  while (ready.length) {
    const id = ready.pop()!; processed++
    for (const next of successors.get(id) ?? []) {
      level.set(next, Math.max(level.get(next)!, level.get(id)! + 1))
      degree.set(next, degree.get(next)! - 1)
      if (degree.get(next) === 0) ready.push(next)
    }
  }
  if (processed !== visible.size) throw new Error('Zyklus im Entwicklungspfad.')
  // Move short branches next to their dependent instead of spanning intervening nodes.
  for (const id of [...visible].sort((a, b) => level.get(b)! - level.get(a)!)) {
    const next = successors.get(id) ?? []
    if (next.length) level.set(id, Math.min(...next.map(child => level.get(child)!)) - 1)
  }
  const levels = new Map<number, number[]>()
  for (const id of [...visible].sort((a, b) => a - b)) {
    const column = level.get(id)!
    if (!levels.has(column)) levels.set(column, [])
    levels.get(column)!.push(id)
  }
  const nodes: PathNode[] = []
  for (const [column, ids] of levels) ids.forEach((id, index) => nodes.push({
    ...skills.get(id)!, owned: employee ? owned.has(id) : null, target: id === target,
    x: column * 270, y: (index - (ids.length - 1) / 2) * 100,
  }))
  return { nodes, edges, distance: employee ? missing.size : null }
}
