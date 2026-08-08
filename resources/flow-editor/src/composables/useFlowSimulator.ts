import { reactive } from 'vue'
import type { FlowSchema, FlowEdge, FlowBlockType, BlockContent, BlockConfig, ButtonItem, ConditionOperator } from '@/types/flow'

/** Кнопка/вариант опроса в представлении симулятора: label — то, что
 * показываем в UI, value — то, что реально уходит в переменную/echo
 * (callback_data кнопки, если задан, иначе label — см. ButtonItem в
 * types/flow.ts). У опроса value всегда равен label, т.к. callback_data
 * у вариантов sendPoll не бывает. */
export interface SimButtonOption {
    label: string
    value: string
}

function toSimOptions(buttons: ButtonItem[] | undefined): SimButtonOption[] {
    return (buttons ?? []).map((b) => ({ label: b.label, value: b.callbackData || b.label }))
}

export interface SimMessage {
    id: string
    role: 'bot' | 'user' | 'system'
    kind: 'text' | 'buttons' | 'note' | 'media' | 'poll'
    text: string
    /** Для kind: 'buttons' — доступные варианты; для kind: 'poll'
     * (Фаза 2) — варианты ответа опроса (статичный список, без выбора —
     * см. комментарий у 'poll' в blocks/registry.ts). */
    options?: SimButtonOption[]
    /** Только для kind: 'media' (Фаза 1 — image/video/audio/file). */
    mediaType?: 'image' | 'video' | 'audio' | 'file'
    mediaUrl?: string
    mediaFileName?: string
}

export type SimWaiting =
    | {
          kind: 'input'
          blockId: string
          variable: string | null
          hint?: string
          /** Какой конкретно тип блока ждёт ответа — нужно для валидации
           * формата (Фаза 2, см. validateInputValue). У обычного 'input'
           * валидации нет, у number/email/phone/date — есть. */
          blockType: FlowBlockType
          config?: BlockConfig
      }
    | { kind: 'buttons'; blockId: string; variable: string | null; options: SimButtonOption[] }
    | {
          /** Запрос через нативную кнопку Telegram (Фаза 2) —
           * геолокация/контакт. См. submitRequest ниже: это
           * dev-заглушка для превью, не настоящий доступ к геолокации. */
          kind: 'request'
          blockId: string
          variable: string | null
          requestType: 'geolocation' | 'contact'
      }
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

// 'input' и его валидируемые варианты (Фаза 2) ведут себя в симуляторе
// одинаково (вопрос → ждём текстовый ответ → переменная), различие
// только в проверке формата ответа — см. validateInputValue.
const TEXT_INPUT_BLOCK_TYPES: ReadonlySet<FlowBlockType> = new Set(['input', 'number', 'email', 'phone', 'date'])

const REQUEST_BLOCK_TYPES: ReadonlySet<FlowBlockType> = new Set(['geolocation', 'contact'])

/**
 * Валидация ответа для number/email/phone/date (Фаза 2). У обычного
 * 'input' формата нет — всегда null. Возвращает текст ошибки для показа
 * пользователю, либо null, если ответ принят.
 */
function validateInputValue(type: FlowBlockType, value: string, config: BlockConfig | undefined): string | null {
    switch (type) {
        case 'number': {
            if (value === '' || Number.isNaN(Number(value))) return 'Это не похоже на число — попробуйте ещё раз.'
            const num = Number(value)
            const { min, max } = config?.validation ?? {}
            if (min !== undefined && num < min) return `Значение должно быть не меньше ${min}.`
            if (max !== undefined && num > max) return `Значение должно быть не больше ${max}.`
            return null
        }
        case 'email':
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) ? null : 'Похоже, это не email — проверьте формат (name@example.com).'
        case 'phone':
            return /^\+?[0-9][0-9\s\-()]{5,}$/.test(value)
                ? null
                : 'Похоже, это не номер телефона — укажите в международном формате, например +79991234567.'
        case 'date':
            return /^\d{4}-\d{2}-\d{2}$/.test(value) && !Number.isNaN(Date.parse(value))
                ? null
                : 'Укажите дату в формате ГГГГ-ММ-ДД, например 2000-01-31.'
        default:
            return null
    }
}

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

            if (TEXT_INPUT_BLOCK_TYPES.has(block.type)) {
                pushMessage({ role: 'bot', kind: 'text', text: interpolate(pickTranslation(block.content), state.variables) })
                state.waiting = {
                    kind: 'input',
                    blockId,
                    variable: block.config?.variable || null,
                    hint: block.config?.hint,
                    blockType: block.type,
                    config: block.config,
                }
                return
            }

            if (block.type === 'button') {
                const options = toSimOptions(block.content?.buttons)
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

            if (REQUEST_BLOCK_TYPES.has(block.type)) {
                pushMessage({ role: 'bot', kind: 'text', text: interpolate(pickTranslation(block.content), state.variables) })
                state.waiting = {
                    kind: 'request',
                    blockId,
                    variable: block.config?.variable || null,
                    requestType: block.type as 'geolocation' | 'contact',
                }
                return
            }

            if (block.type === 'poll') {
                // Опрос не ждёт ответа в рамках диалога (см. комментарий
                // у 'poll' в blocks/registry.ts) — отправили и продолжили.
                pushMessage({
                    role: 'bot',
                    kind: 'poll',
                    text: interpolate(pickTranslation(block.content), state.variables),
                    options: toSimOptions(block.content?.buttons),
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
        const { variable, blockType, config } = state.waiting
        const trimmed = value.trim()

        const error = validateInputValue(blockType, trimmed, config)
        if (error) {
            // Невалидный ответ: показываем причину и остаёмся в режиме
            // ожидания — как реальный бот переспросил бы, а не завершаем
            // диалог/переходим дальше с мусорным значением.
            pushMessage({ role: 'system', kind: 'note', text: error })
            return
        }

        pushMessage({ role: 'user', kind: 'text', text: trimmed })
        state.waiting = null
        if (variable) state.variables[variable] = trimmed
        advance()
    }

    /**
     * `value` — то же, что SimButtonOption.value (callback_data кнопки,
     * если задан, иначе label). Ищем совпадающий option в текущем
     * ожидании, чтобы показать в эхо-сообщении пользователя человекочитаемый
     * label, а не сырой callback_data — так же, как настоящий Telegram-клиент
     * показывает текст кнопки, а не то, что реально уходит боту.
     */
    const submitChoice = (value: string) => {
        if (!state.waiting || state.waiting.kind !== 'buttons') return
        const { variable, options } = state.waiting
        const matched = options.find((o) => o.value === value)
        const label = matched?.label ?? value

        pushMessage({ role: 'user', kind: 'text', text: label })
        state.waiting = null
        if (variable) state.variables[variable] = value
        advance()
    }

    // Дев-заглушки для превью: у редактора флоу нет доступа к реальной
    // геолокации браузера или контактам пользователя (да это и не имело
    // бы смысла — тестируется сценарий, а не устройство разработчика).
    // В реальном Telegram эти значения приходят от нативной кнопки
    // "Отправить геолокацию"/"Отправить контакт".
    const REQUEST_STUB_VALUES: Record<'geolocation' | 'contact', string> = {
        geolocation: '55.751244,37.618423',
        contact: '+79990001122',
    }

    const submitRequest = () => {
        if (!state.waiting || state.waiting.kind !== 'request') return
        const { variable, requestType } = state.waiting
        const value = REQUEST_STUB_VALUES[requestType]
        const label = requestType === 'geolocation' ? `📍 Геолокация отправлена (${value})` : `☎️ Контакт отправлен (${value})`

        pushMessage({ role: 'user', kind: 'text', text: label })
        state.waiting = null
        if (variable) state.variables[variable] = value
        advance()
    }

    return { state, start, submitText, submitChoice, submitRequest }
}
