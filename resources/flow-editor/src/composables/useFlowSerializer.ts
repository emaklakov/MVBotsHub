import type { FlowSchema, FlowBlock } from '@/types/flow'
import type { Node, Edge } from '@vue-flow/core'

export function useFlowSerializer() {
    const toVueFlow = (schema: FlowSchema): { nodes: Node[]; edges: Edge[] } => {
        const nodes: Node[] = []
        const edges: Edge[] = []
        const positions = schema.editor_positions || {}
        let index = 0

        for (const [id, block] of Object.entries(schema.blocks) as [string, FlowBlock][]) {
            const pos = positions[id] || { x: 100 + index * 250, y: 100 + (index % 2) * 150 }

            nodes.push({
                id,
                type: block.type,
                position: pos,
                data: {
                    content: block.content,
                    config: block.config,
                },
            })

            if (block.next_id) {
                edges.push({
                    id: `e-${id}-${block.next_id}`,
                    source: id,
                    target: block.next_id,
                })
            }

            index++
        }

        return { nodes, edges }
    }

    const toSchema = (nodes: Node[], edges: Edge[], startNodeId: string | null): FlowSchema => {
        const blocks: Record<string, FlowBlock> = {}
        const editor_positions: Record<string, { x: number; y: number }> = {}

        for (const node of nodes) {
            blocks[node.id] = {
                id: node.id,
                type: node.type as FlowBlock['type'],
                content: node.data?.content,
                config: node.data?.config,
                next_id: edges.find((e) => e.source === node.id)?.target || null,
            }

            editor_positions[node.id] = { x: node.position.x, y: node.position.y }
        }

        return {
            start_block_id: startNodeId || nodes[0]?.id || null,
            blocks,
            editor_positions,
        }
    }

    return { toVueFlow, toSchema }
}
