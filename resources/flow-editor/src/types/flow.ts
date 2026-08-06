export interface BlockContent {
    translations?: Record<string, string>
    text?: string
    buttons?: string[]
}

export type ConditionOperator = '==' | '!=' | 'contains' | 'is_set' | 'is_empty'

export interface BlockConfig {
    variable?: string
    hint?: string
    /** Только для type: 'button'. Inline-кнопки под сообщением или reply-клавиатура. */
    keyboardMode?: 'inline' | 'reply'
    /** Только для type: 'condition'. */
    conditionVariable?: string
    conditionOperator?: ConditionOperator
    conditionValue?: string
}

export type FlowBlockType = 'text' | 'input' | 'button' | 'condition'

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
    /**
     * Ссылка на исходящее ребро этого блока, если оно есть.
     * ВАЖНО: для блоков с несколькими выходами (condition — True/False,
     * и любой другой тип блока, у которого в реестре блоков задано поле
     * outputs — см. src/blocks/registry.ts) это поле всегда null, потому
     * что оно рассчитано на один выход. Для таких блоков источник истины
     * по рёбрам — это `schema.edges`, отфильтрованные по
     * `source_block_id` этого блока (их может быть несколько, различаются
     * по `source_handle`).
     */
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
    /**
     * Для блоков с несколькими выходами (сейчас — только 'condition'):
     * 'true' | 'false' говорит, какая это ветка. Для обычных блоков
     * (один выход) — null.
     */
    source_handle?: string | null
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
