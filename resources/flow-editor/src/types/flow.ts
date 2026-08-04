export interface BlockContent {
    translations?: Record<string, string>
    text?: string
    buttons?: string[]
}

export interface BlockConfig {
    variable?: string
    hint?: string
}

export type FlowBlockType = 'text' | 'input' | 'button'

/**
 * Группа — нода на холсте. Содержит один или несколько блоков,
 * идущих друг за другом сверху вниз (в MVP — ровно один, полноценный
 * контейнер с несколькими блоками появится в Фазе 2, когда будет готов
 * визуальный компонент группы на канвасе).
 */
export interface FlowGroup {
    id: string
    title: string
    position: { x: number; y: number }
    /** Порядок блоков внутри группы, сверху вниз. */
    block_ids: string[]
}

export interface FlowBlock {
    id: string
    group_id: string
    type: FlowBlockType
    content?: BlockContent
    config?: BlockConfig
    /** Ссылка на исходящее ребро этого блока, если оно есть. */
    outgoing_edge_id?: string | null
}

/**
 * Ребро идёт от конкретного блока к следующей группе (а не от группы
 * к группе) — это нужно для блоков с несколькими выходами, например
 * условий (Фаза 5).
 */
export interface FlowEdge {
    id: string
    source_block_id: string
    target_group_id: string
}

export interface FlowSchema {
    start_group_id: string | null
    groups: Record<string, FlowGroup>
    blocks: Record<string, FlowBlock>
    edges: Record<string, FlowEdge>
}

export interface FlowVersion {
    id: number
    schema: FlowSchema
    status: string
    version_number: number
}
