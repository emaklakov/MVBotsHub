import type { FlowSchema, FlowGroup, FlowBlock, FlowEdge, FlowBlockType, BlockContent, BlockConfig, ButtonItem } from '@/types/flow'
import type { Node, Edge } from '@vue-flow/core'
import {
    defaultBlockTitle,
    defaultBlockContent as registryDefaultBlockContent,
    defaultBlockConfig as registryDefaultBlockConfig,
    getBlockOutputs,
    blockProducesVariable,
} from '@/blocks'

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

/** Заголовок по умолчанию для новой группы, создаваемой вместе с первым
 * блоком заданного типа. Делегирует в реестр блоков (src/blocks) —
 * см. его комментарии о том, почему тип блока больше не "размазан"
 * по нескольким файлам. */
export function defaultGroupTitle(type: FlowBlockType): string {
    return defaultBlockTitle(type)
}

/** Пустое содержимое блока при его создании (клик/drag из библиотеки блоков). */
export function defaultBlockContent(type: FlowBlockType): BlockContent {
    return registryDefaultBlockContent(type)
}

/** Пустые настройки блока при его создании. */
export function defaultBlockConfig(type: FlowBlockType): BlockConfig {
    return registryDefaultBlockConfig(type)
}

/**
 * Собирает имена всех переменных, которые где-либо во флоу заполняются
 * пользователем — из input-блоков (текстовый ответ) и button-блоков
 * (выбор варианта, если у блока задана config.variable). Используется,
 * чтобы предложить их для вставки в текст через TextBlockEditor и для
 * выбора в условии.
 *
 * Принимает минимальный тип (только `data`), а не полный `Node` из
 * @vue-flow/core: связка `computed(() => collectVariables(nodes.value))`
 * с полным generic-типом `Node` в вызывающем коде провоцирует ошибку
 * TS2589 (Type instantiation is excessively deep) из-за глубоких
 * generic-параметров этого типа.
 *
 * Какие типы блоков вообще способны дать переменную — определяется
 * реестром блоков (BlockDefinition.producesVariable), а не перечислением
 * типов здесь: новый input-подобный блок (Фазы 1-2) подключается к сбору
 * переменных простой пометкой в реестре.
 */
export function collectVariables(nodes: Array<{ data?: unknown }>): string[] {
    const names = new Set<string>()
    for (const node of nodes) {
        const data = node.data as GroupNodeData | undefined
        for (const block of data?.blocks ?? []) {
            if (blockProducesVariable(block.type) && block.config?.variable) {
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

/**
 * Приводит content.buttons к текущему формату (ButtonItem[]).
 *
 * До добавления callback_data кнопки/варианты опроса хранились как
 * простой string[] — уже сохранённые в БД флоу могут быть в этом старом
 * формате. Не мигрировать данные назад (это задача бэкенда/миграции БД),
 * а просто нормализовать на лету при загрузке в редактор — так старые
 * боты продолжают открываться без ручной миграции.
 */
function normalizeButtonContent(content: BlockContent | undefined): BlockContent | undefined {
    // content.buttons типизирован как ButtonItem[] (текущий формат), но
    // рантайм-данные из уже сохранённых флоу могут быть старым string[] —
    // расширяем тип явно, иначе TS считает typeof b === 'string' заведомо
    // невозможным сравнением (ButtonItem и string не пересекаются).
    const buttons = content?.buttons as Array<ButtonItem | string> | undefined
    if (!buttons?.length) return content
    const needsMigration = buttons.some((b) => typeof b === 'string')
    if (!needsMigration) return content
    return {
        ...content,
        buttons: buttons.map((b) => (typeof b === 'string' ? { label: b } : b)),
    }
}

export function useFlowSerializer() {
    /**
     * Схема -> VueFlow. Каждая группа схемы становится одной нодой холста
     * с типом 'group'. Порядок data.blocks совпадает с group.block_ids.
     *
     * Ребро схемы переносит source_handle 1-в-1 в VueFlow-ребро
     * (sourceHandle) — для condition-блока это 'true'/'false' и
     * указывает, из какого из двух хендлов группы оно выходит.
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
                .map((b) => ({ id: b.id, type: b.type, content: normalizeButtonContent(b.content), config: b.config }))

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
                sourceHandle: edge.source_handle ?? undefined,
            })
        }

        return { nodes, edges }
    }

    /**
     * VueFlow -> схема. node.data.blocks задаёт и содержимое блоков,
     * и их порядок внутри группы (group.block_ids).
     *
     * Исходящее ребро группы (edge.source === node.id) в схеме
     * привязывается к ПОСЛЕДНЕМУ блоку группы — именно от него логически
     * идёт переход к следующей группе. Остальные блоки группы
     * outgoing_edge_id не получают.
     *
     * Если последний блок — 'condition' (два возможных выхода, True и
     * False, различаются по edge.sourceHandle), то block.outgoing_edge_id
     * остаётся null: это поле рассчитано только на один выход. Источник
     * истины для condition-блоков — это schemaEdges, отфильтрованные по
     * source_block_id.
     */
    const toSchema = (
        nodes: Array<{ id: string; type?: string; position: { x: number; y: number }; data?: unknown }>,
        edges: Array<{ id: string; source: string; target: string; sourceHandle?: string | null }>,
        startGroupId: string | null
    ): FlowSchema => {
        const groups: Record<string, FlowGroup> = {}
        const blocks: Record<string, FlowBlock> = {}
        const schemaEdges: Record<string, FlowEdge> = {}

        for (const node of nodes) {
            const data = node.data as GroupNodeData
            const nodeBlocks = data?.blocks ?? []
            const lastBlock = nodeBlocks[nodeBlocks.length - 1]
            const lastBlockHasSingleOutput = !lastBlock || getBlockOutputs(lastBlock.type, lastBlock.config).length <= 1

            // У последнего блока с ровно одним выходом ищем среди рёбер
            // группы то, что без конкретного sourceHandle (обычный
            // "групповой" хендл). У блоков с несколькими выходами (см.
            // src/blocks/registry.ts, поле outputs) единого outgoing_edge_id
            // нет в принципе — источник истины для них это schemaEdges,
            // отфильтрованные по source_block_id (см. комментарий у
            // outgoing_edge_id в types/flow.ts).
            const outgoingEdge = lastBlockHasSingleOutput
                ? edges.find((e) => e.source === node.id && !e.sourceHandle)
                : undefined

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
                    outgoing_edge_id: block.id === lastBlock?.id ? (outgoingEdge?.id ?? null) : null,
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
                source_handle: edge.sourceHandle ?? null,
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
