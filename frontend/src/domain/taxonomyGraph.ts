import type { TreeNode } from './taxonomy'

export interface GraphNode { group: TreeNode; x: number; y: number }
export function taxonomyGraph(roots: TreeNode[], expanded: Set<number>) {
  const nodes: GraphNode[] = []
  const edges: { source: GraphNode; target: GraphNode }[] = []
  const weights = new Map<number, number>()
  const order: TreeNode[] = []
  const pending = [...roots]
  while (pending.length) {
    const node = pending.pop()!
    order.push(node)
    if (expanded.has(node.id)) pending.push(...node.children)
  }
  for (const node of order.reverse()) weights.set(node.id, expanded.has(node.id) && node.children.length
    ? node.children.reduce((sum, child) => sum + weights.get(child.id)!, 0) : 1)
  const total = roots.reduce((sum, node) => sum + weights.get(node.id)!, 0)
  const step = Math.max(140, total * 28)
  const queue = roots.map((group, index) => ({ group, depth: roots.length === 1 ? 0 : 1,
    start: -Math.PI / 2 + roots.slice(0, index).reduce((sum, root) => sum + weights.get(root.id)!, 0) / total * Math.PI * 2,
    sweep: weights.get(group.id)! / total * Math.PI * 2, parent: undefined as GraphNode | undefined }))
  while (queue.length) {
    const item = queue.shift()!
    const angle = item.start + item.sweep / 2
    const node = { group: item.group, x: Math.cos(angle) * item.depth * step, y: Math.sin(angle) * item.depth * step }
    nodes.push(node)
    if (item.parent) edges.push({ source: item.parent, target: node })
    if (!expanded.has(item.group.id)) continue
    let start = item.start
    for (const child of item.group.children) {
      const sweep = item.sweep * weights.get(child.id)! / weights.get(item.group.id)!
      queue.push({ group: child, depth: item.depth + 1, start, sweep, parent: node })
      start += sweep
    }
  }
  return { nodes, edges }
}
