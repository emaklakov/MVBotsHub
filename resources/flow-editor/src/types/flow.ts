export interface BlockContent {
    translations?: Record<string, string>
    text?: string
    buttons?: string[]
}

export interface BlockConfig {
    variable?: string
    hint?: string
}

export interface FlowBlock {
    id: string
    type: 'text' | 'input' | 'button'
    content?: BlockContent
    config?: BlockConfig
    next_id?: string | null
}

export interface FlowSchema {
    start_block_id: string | null
    blocks: Record<string, FlowBlock>
    editor_positions?: Record<string, { x: number; y: number }>
}

export interface FlowVersion {
    id: number
    schema: FlowSchema
    status: string
    version_number: number
}
