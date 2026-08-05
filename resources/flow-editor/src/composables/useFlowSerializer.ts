import type { FlowSchema, FlowGroup, FlowBlock, FlowBlockType, BlockContent, BlockConfig } from '@/types/flow'
import type { Node, Edge } from '@vue-flow/core'

/** Блок в представлении холста — то, что реально нужно компонентам группы. */
export interface UiBlock {
    id: string
    type: FlowBlockType
    content?: BlockContent
    config?: BlockConfig
}

export interface GroupNodeData {
    title: string
    blocks: UiBlock[]
}

export function defaultGroupTitle(type: FlowBlockType): string {
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

/** Пустое содержимое блока при его создании (клик/drag из библиотеки блоков). */
export function defaultBlockContent(type: FlowBlockType): BlockContent {
    switch (type) {
        case 'text':
            return { translations: { ru: '', en: '' } }
        case 'button':
            return { buttons: [] }
        default:
            return {}
    }
}

/** Пустые настройки блока при его создании. */
export function defaultBlockConfig(type: FlowBlockType): BlockConfig {
    return type === 'input' ? { variable: '' } : {}
}

/**
 * Собирает имена всех переменных, которые где-либо во флоу заполняются
 * input-блоками (`config.variable`) — используется, чтобы предложить их
 * для вставки в текст через TextBlockEditor.
 *
 * Принимает минимальный тип (только `data`), а не полный `Node` из
 * @vue-flow/core: связка `computed(() => collectVariables(nodes.value))`
 * с полным generic-типом `Node` в вызывающем коде провоцирует ошибку
 * TS2589 (Type instantiation is excessively deep) из-за глубоких
 * generic-параметров этого типа.
 */
export function collectVariables(nodes: Array<{ data?: unknown }>): string[] {
    const names = new Set<string>()
    for (const node of nodes) {
        const data = node.data as GroupNodeData | undefined
        for (const block of data?.blocks ?? []) {
            if (block.type === 'input' && block.config?.variable) {
                names.add(block.config.variable)
            }
        }
    }
    return Array.from(names)
}

/**
 * Пустая схема — используется как безопасный дефолт, когда бэкенд
 * ещё не вернул валидную схему (новый бот без сохранённого черновика,
 * `schema: null` в БД и т.п.).
 */
export function emptySchema(): FlowSchema {
    return { start_group_id: null, groups: {}, blocks: {}, edges: {} }
}

export function useFlowSerializer() {
    /**
     * Схема -> VueFlow. Каждая группа схемы становится одной нодой холста
     * с типом 'group' (единственный тип ноды теперь, вне зависимости от
     * того, сколько и каких блоков внутри). Порядок data.blocks совпадает
     * с group.block_ids.
     *
     * Специально принимает `schema` как возможно неполную/отсутствующую —
     * для нового бота бэкенд может вернуть `null`/`{}` вместо валидной
     * схемы с group/blocks/edges, и это не должно ронять редактор.
     */
    const toVueFlow = (schema: Partial<FlowSchema> | null | undefined): { nodes: Node[]; edges: Edge[] } => {
        const groupsMap = schema?.groups ?? {}
        const blocksMap = schema?.blocks ?? {}
        const edgesMap = schema?.edges ?? {}

        const nodes: Node[] = []
        const edges: Edge[] = []

        for (const group of Object.values(groupsMap)) {
            const blocks: UiBlock[] = group.block_ids
                .map((blockId) => blocksMap[blockId])
                .filter((b): b is FlowBlock => Boolean(b))
                .map((b) => ({ id: b.id, type: b.type, content: b.content, config: b.config }))

            nodes.push({
                id: group.id,
                type: 'group',
                position: group.position,
                data: { title: group.title, blocks } satisfies GroupNodeData,
            })
        }

        for (const edge of Object.values(edgesMap)) {
            const sourceBlock = blocksMap[edge.source_block_id]
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
     * VueFlow -> схема. node.data.blocks задаёт и содержимое блоков,
     * и их порядок внутри группы (group.block_ids). Исходящее ребро
     * группы (edge.source === node.id) в схеме привязывается к
     * ПОСЛЕДНЕМУ блоку группы — именно от него логически идёт переход
     * к следующей группе. Остальные блоки группы outgoing_edge_id не
     * получают (у логических блоков с несколькими выходами это будет
     * устроено иначе — см. Фазу 5).
     */
    const toSchema = (nodes: Node[], edges: Edge[], startGroupId: string | null): FlowSchema => {
        const groups: Record<string, FlowGroup> = {}
        const blocks: Record<string, FlowBlock> = {}
        const schemaEdges: Record<string, { id: string; source_block_id: string; target_group_id: string }> = {}

        for (const node of nodes) {
            const data = node.data as GroupNodeData
            const nodeBlocks = data?.blocks ?? []
            const outgoingEdge = edges.find((e) => e.source === node.id)
            const lastBlockId = nodeBlocks[nodeBlocks.length - 1]?.id ?? null

            groups[node.id] = {
                id: node.id,
                title: data?.title || 'Группа',
                position: { x: node.position.x, y: node.position.y },
                block_ids: nodeBlocks.map((b) => b.id),
            }

            for (const block of nodeBlocks) {
                blocks[block.id] = {
                    id: block.id,
                    group_id: node.id,
                    type: block.type,
                    content: block.content,
                    config: block.config,
                    outgoing_edge_id: block.id === lastBlockId ? (outgoingEdge?.id ?? null) : null,
                }
            }
        }

        for (const edge of edges) {
            const sourceNode = nodes.find((n) => n.id === edge.source)
            const sourceBlocks = (sourceNode?.data as GroupNodeData | undefined)?.blocks ?? []
            const sourceBlockId = sourceBlocks[sourceBlocks.length - 1]?.id ?? edge.source

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
