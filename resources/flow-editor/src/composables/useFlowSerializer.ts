import type { FlowSchema, FlowGroup, FlowBlock, FlowBlockType } from '@/types/flow'
import type { Node, Edge } from '@vue-flow/core'

function defaultGroupTitle(type: FlowBlockType): string {
    switch (type) {
        case 'text':
            return 'Сообщение'
        case 'input':
            return 'Вопрос'
        case 'button':
            return 'Кнопки'
        default:
            return 'Группа'
    }
}

export function useFlowSerializer() {
    /**
     * Схема -> VueFlow. Пока у каждой группы ровно один блок, поэтому
     * каждая группа рендерится как одна нода холста с типом этого блока
     * (полноценный контейнер с несколькими блоками — Фаза 2).
     * id блока хранится в data.blockId, чтобы toSchema() мог собрать
     * структуру обратно, не потеряв идентификатор блока.
     */
    const toVueFlow = (schema: FlowSchema): { nodes: Node[]; edges: Edge[] } => {
        const nodes: Node[] = []
        const edges: Edge[] = []

        for (const group of Object.values(schema.groups)) {
            const blockId = group.block_ids[0]
            const block = blockId ? schema.blocks[blockId] : undefined
            if (!block) continue

            nodes.push({
                id: group.id,
                type: block.type,
                position: group.position,
                data: {
                    blockId: block.id,
                    content: block.content,
                    config: block.config,
                },
            })
        }

        for (const edge of Object.values(schema.edges)) {
            const sourceBlock = schema.blocks[edge.source_block_id]
            if (!sourceBlock) continue

            edges.push({
                id: edge.id,
                source: sourceBlock.group_id,
                target: edge.target_group_id,
            })
        }

        return { nodes, edges }
    }

    /**
     * VueFlow -> схема. node.id всегда используется как id группы.
     * node.data.blockId — id блока внутри неё; если его нет (нода только
     * что создана в addBlock и blockId ещё не проставлен), в качестве
     * запасного варианта используется node.id, чтобы не терять данные.
     */
    const toSchema = (nodes: Node[], edges: Edge[], startGroupId: string | null): FlowSchema => {
        const groups: Record<string, FlowGroup> = {}
        const blocks: Record<string, FlowBlock> = {}
        const schemaEdges: Record<string, { id: string; source_block_id: string; target_group_id: string }> = {}

        for (const node of nodes) {
            const blockId: string = node.data?.blockId || node.id
            const outgoingEdge = edges.find((e) => e.source === node.id)

            groups[node.id] = {
                id: node.id,
                title: defaultGroupTitle(node.type as FlowBlockType),
                position: { x: node.position.x, y: node.position.y },
                block_ids: [blockId],
            }

            blocks[blockId] = {
                id: blockId,
                group_id: node.id,
                type: node.type as FlowBlockType,
                content: node.data?.content,
                config: node.data?.config,
                outgoing_edge_id: outgoingEdge ? outgoingEdge.id : null,
            }
        }

        for (const edge of edges) {
            const sourceNode = nodes.find((n) => n.id === edge.source)
            const sourceBlockId: string = sourceNode?.data?.blockId || edge.source

            schemaEdges[edge.id] = {
                id: edge.id,
                source_block_id: sourceBlockId,
                target_group_id: edge.target,
            }
        }

        return {
            start_group_id: startGroupId || nodes[0]?.id || null,
            groups,
            blocks,
            edges: schemaEdges,
        }
    }

    return { toVueFlow, toSchema }
}
