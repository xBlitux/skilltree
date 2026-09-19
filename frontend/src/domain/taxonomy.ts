import type { Development } from './development'
export type Status = 'red' | 'yellow' | 'green'
export type Color = Status | 'neutral'
export interface Skill { id: number; name: string; skill_group_id: number }
export interface Group { id: number; name: string; parent_skill_group_id: number | null }
export interface RatedSkill extends Skill { status: Status; carrier_count: number; employee_ids: number[] }
export interface Employee { id: number; first_name: string; last_name: string; available_skill_ids: number[] }
export interface Analysis {
  development: Development
  organisation: { id: number; name: string }
  summary: { task_count: number; employee_count: number; required_skill_count: number; available_skill_count: number; counts: Record<Status, number> }
  required_skill_ids: number[]
  skills: RatedSkill[]
  employees: Employee[]
  taxonomy: { groups: Group[]; skills: Skill[] }
}
export interface TreeNode extends Group { children: TreeNode[]; skills: Skill[]; count: number; color: Color }
export const labels: Record<Color, string> = { red: 'Kritisch', yellow: 'Handlungsbedarf', green: 'Unkritisch', neutral: 'Ohne Soll-Bewertung' }
const weight: Record<Color, number> = { neutral: 0, green: 1, yellow: 2, red: 3 }
export function taxonomyView(data: Analysis, mask: boolean, employeeId: number | null) {
  const ratings = new Map(data.skills.map(skill => [skill.id, skill.status]))
  const required = new Set(data.required_skill_ids)
  const person = data.employees.find(employee => employee.id === employeeId)
  const owned = new Set(person?.available_skill_ids ?? [])
  const color = (id: number): Color => person ? (owned.has(id) ? 'green' : 'red') : (ratings.get(id) ?? 'neutral')
  const visible = data.taxonomy.skills.filter(skill => !mask || required.size === 0 || required.has(skill.id))
  const nodes = new Map<number, TreeNode>(data.taxonomy.groups.map(group => [group.id, { ...group, children: [], skills: [], count: 0, color: 'neutral' }]))
  const roots: TreeNode[] = []
  for (const node of nodes.values()) {
    if (node.parent_skill_group_id === null) roots.push(node)
    else {
      const parent = nodes.get(node.parent_skill_group_id)
      if (!parent) throw new Error('Ungültige Taxonomie: übergeordnete Gruppe fehlt.')
      parent.children.push(node)
    }
  }
  for (const skill of visible) {
    const node = nodes.get(skill.skill_group_id)
    if (!node) throw new Error('Ungültige Taxonomie: Skillgruppe fehlt.')
    node.skills.push(skill)
  }
  // Iterative postorder, independent of the UI expansion state.
  const order: TreeNode[] = []
  const pending = [...roots]
  while (pending.length) { const node = pending.pop()!; order.push(node); pending.push(...node.children) }
  if (order.length !== nodes.size) throw new Error('Ungültige Taxonomie: zyklische Gruppenhierarchie.')
  for (const node of order.reverse()) {
    node.skills.sort((a, b) => a.name.localeCompare(b.name, 'de'))
    node.count = node.skills.length + node.children.reduce((sum, child) => sum + child.count, 0)
    const colors = [...node.skills.map(skill => color(skill.id)), ...node.children.map(child => child.color)]
    node.color = colors.reduce<Color>((worst, value) => weight[value] > weight[worst] ? value : worst, 'neutral')
    if (mask && required.size) node.children = node.children.filter(child => child.count > 0)
    node.children.sort((a, b) => a.name.localeCompare(b.name, 'de'))
  }
  roots.sort((a, b) => a.name.localeCompare(b.name, 'de'))
  return { roots, nodes, color, personal: !!person }
}
