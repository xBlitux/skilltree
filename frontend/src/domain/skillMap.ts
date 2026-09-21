import { taxonomyView, type Analysis } from './taxonomy'

export function skillMap(data: Analysis, mask: boolean) {
  const tree = taxonomyView(data, mask, null)
  const required = new Set(data.required_skill_ids)
  const owners = new Map(data.employees.map(employee => [employee.id, new Set(employee.available_skill_ids)]))
  const estimated = new Set(data.development.estimated_skill_ids)
  const prerequisites = new Map<number, number[]>()
  for (const edge of data.development.edges) prerequisites.set(edge.target, [...(prerequisites.get(edge.target) ?? []), edge.source])
  const pathLength = (id: number) => {
    if (!estimated.has(id)) return null
    const visited = new Set<number>(), pending = [id]
    while (pending.length) {
      const current = pending.pop()!
      if (visited.has(current)) continue
      visited.add(current); pending.push(...(prerequisites.get(current) ?? []))
    }
    return visited.size
  }
  return [...tree.nodes.values()].filter(group => group.skills.length).sort((a, b) => a.name.localeCompare(b.name, 'de')).map(group => ({
    id: group.id, name: group.name,
    rows: group.skills.map(skill => {
      const possession = data.employees.map(employee => ({ employee, owned: owners.get(employee.id)!.has(skill.id) }))
      const available = possession.filter(cell => cell.owned).length
      const needed = required.has(skill.id) ? 2 : 0
      return { skill, color: tree.color(skill.id), possession, available, needed, gap: Math.max(0, needed - available), pathLength: pathLength(skill.id) }
    }),
  }))
}
