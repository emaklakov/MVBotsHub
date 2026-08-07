import { reactive } from 'vue'
import type { FlowSchema, FlowEdge, FlowBlockType, BlockContent, BlockConfig, ConditionOperator } from '@/types/flow'

export interface SimMessage {
    id: string
    role: 'bot' | 'user' | 'system'
    kind: 'text' | 'buttons' | 'note' | 'media'
    text: string
    options?: string[]
    /** Только для kind: 'media' (Фаза 1 — image/video/audio/file). */
    mediaType?: 'image' | 'video' | 'audio' | 'file'
    mediaUrl?: string
    mediaFileName?: string
}

export type SimWaiting =
    | { kind: 'input'; blockId: string; variable: string | null; hint?: string }
    | { kind: 'buttons'; blockId: string; variable: string | null; options: string[] }
    | null

export interface SimState {
    messages: SimMessage[]
    variables: Record<string, string>
    waiting: SimWaiting
    finished: boolean
}

function interpolate(text: string, variables: Record<string, string>): string {
    return text.replace(/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/g, (_match, name: string) =>
        Object.prototype.hasOwnProperty.call(variables, name) ? variables[name] : `{{${name}}}`
    )
}

function pickTranslation(content: BlockContent | undefined): string {
    return content?.translations?.ru || ''
}

const MEDIA_BLOCK_TYPES: ReadonlySet<FlowBlockType> = new Set(['image', 'video', 'audio', 'file'])

const OPERATOR_LABELS: Record<ConditionOperator, string> = {
    '==': '=',
    '!=': '≠',
    contains: 'содержит',
    is_set: 'задана',
    is_empty: 'пустая',
}

function evaluateCondition(config: BlockConfig | undefined, variables: Record<string, string>): boolean {
    const variable = config?.conditionVariable
    const operator = config?.conditionOperator ?? '=='
    const expected = config?.conditionValue ?? ''
    const actual = variable ? variables[variable] : undefined

    switch (operator) {
        case 'is_set':
            return actual !== undefined && actual !== ''
        case 'is_empty':
            return actual === undefined || actual === ''
        case '!=':
            return (actual ?? '') !== expected
        case 'contains':
            return (actual ?? '').includes(expected)
        case '==':
        default:
            return (actual ?? '') === expected
    }
}

function describeCondition(config: BlockConfig | undefined, result: boolean): string {
    const variable = config?.conditionVariable || '?'
    const operator = config?.conditionOperator ?? '=='
    const needsValue = operator !== 'is_set' && operator !== 'is_empty'
    const expr = needsValue
        ? `${variable} ${OPERATOR_LABELS[operator]} «${config?.conditionValue ?? ''}»`
        : `${variable} ${OPERATOR_LABELS[operator]}`
    return `Условие: ${expr} → ${result ? 'True' : 'False'}`
}

function findOutgoingEdge(schema: FlowSchema, sourceBlockId: string, handle: 'true' | 'false' | null): FlowEdge | undefined {
    return Object.values(schema.edges).find((e) => e.source_block_id === sourceBlockId && (e.source_handle ?? null) === handle)
}

let messageCounter = 0
function nextMessageId(): string {
    messageCounter += 1
    return `sim_msg_${messageCounter}`
}

/**
 * Прогоняет флоу как чат-диалог: bubbles выводятся как сообщения бота,
 * inputs/buttons ставят симуляцию на паузу до ответа "пользователя" —
 * это позволяет проверить сценарий (включая ветвление по condition)
 * прямо в редакторе, без публикации бота и без реального Telegram.
 *
 * Специально не завязан на Vue-компонент/DOM — использует reactive()
 * только для удобства связывания с шаблоном ChatPreview.vue, вся логика
 * тестируется как обычная функция.
 */
export function createChatSimulator(schema: FlowSchema) {
    const state = reactive<SimState>({
        messages: [],
        variables: {},
        waiting: null,
        finished: false,
    })

    let currentGroupId: string | null = null
    let currentBlockIndex = 0

    const pushMessage = (msg: Omit<SimMessage, 'id'>) => {
        state.messages.push({ id: nextMessageId(), ...msg })
    }

    const advance = () => {
        if (state.finished || state.waiting) return

        while (true) {
            if (!currentGroupId) {
                state.finished = true
                return
            }

            const group = schema.groups[currentGroupId]
            if (!group) {
                state.finished = true
                return
            }

            if (currentBlockIndex >= group.block_ids.length) {
                // Блоки группы закончились — переходим по единственному
                // исходящему ребру ПОСЛЕДНЕГО блока группы (обычный, не
                // condition-переход — у condition своя ветка ниже).
                const lastBlockId = group.block_ids[group.block_ids.length - 1]
                const edge = lastBlockId ? findOutgoingEdge(schema, lastBlockId, null) : undefined
                if (!edge) {
                    state.finished = true
                    return
                }
                currentGroupId = edge.target_group_id
                currentBlockIndex = 0
                continue
            }

            const blockId = group.block_ids[currentBlockIndex]
            currentBlockIndex += 1
            const block = schema.blocks[blockId]
            if (!block) continue

            if (block.type === 'text') {
                pushMessage({ role: 'bot', kind: 'text', text: interpolate(pickTranslation(block.content), state.variables) })
                continue
            }

            if (block.type === 'input') {
                pushMessage({ role: 'bot', kind: 'text', text: interpolate(pickTranslation(block.content), state.variables) })
                state.waiting = { kind: 'input', blockId, variable: block.config?.variable || null, hint: block.config?.hint }
                return
            }

            if (block.type === 'button') {
                const options = block.content?.buttons ?? []
                pushMessage({
                    role: 'bot',
                    kind: 'buttons',
                    text: interpolate(pickTranslation(block.content), state.variables),
                    options,
                })
                state.waiting = { kind: 'buttons', blockId, variable: block.config?.variable || null, options }
                return
            }

            if (block.type === 'condition') {
                const result = evaluateCondition(block.config, state.variables)
                pushMessage({ role: 'system', kind: 'note', text: describeCondition(block.config, result) })
                const edge = findOutgoingEdge(schema, blockId, result ? 'true' : 'false')
                if (!edge) {
                    state.finished = true
                    return
                }
                currentGroupId = edge.target_group_id
                currentBlockIndex = 0
                continue
            }

            if (MEDIA_BLOCK_TYPES.has(block.type)) {
                pushMessage({
                    role: 'bot',
                    kind: 'media',
                    text: interpolate(pickTranslation(block.content), state.variables),
                    mediaType: block.type as 'image' | 'video' | 'audio' | 'file',
                    mediaUrl: block.content?.mediaUrl || '',
                    mediaFileName: block.content?.mediaFileName,
                })
                continue
            }
        }
    }

    /** Сбрасывает и запускает симуляцию заново с начала флоу. */
    const start = () => {
        state.messages = []
        state.variables = {}
        state.waiting = null
        state.finished = false
        currentGroupId = schema.start_group_id
        currentBlockIndex = 0

        if (!currentGroupId || !schema.groups[currentGroupId]) {
            state.finished = true
            pushMessage({ role: 'system', kind: 'note', text: 'Флоу пустой или не задан стартовый блок — нечего запускать.' })
            return
        }
        advance()
    }

    const submitText = (value: string) => {
        if (!state.waiting || state.waiting.kind !== 'input') return
        const variable = state.waiting.variable
        const trimmed = value.trim()

        pushMessage({ role: 'user', kind: 'text', text: trimmed })
        state.waiting = null
        if (variable) state.variables[variable] = trimmed
        advance()
    }

    const submitChoice = (option: string) => {
        if (!state.waiting || state.waiting.kind !== 'buttons') return
        const variable = state.waiting.variable

        pushMessage({ role: 'user', kind: 'text', text: option })
        state.waiting = null
        if (variable) state.variables[variable] = option
        advance()
    }

    return { state, start, submitText, submitChoice }
}
