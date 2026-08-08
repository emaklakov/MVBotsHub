import { describe, it, expect } from 'vitest'
import { createChatSimulator } from '../useFlowSimulator'
import type { FlowSchema } from '@/types/flow'

// Сценарий: приветствие -> вопрос про имя -> подтверждение с переменной.
const linearSchema: FlowSchema = {
    start_group_id: 'group_welcome',
    groups: {
        group_welcome: { id: 'group_welcome', title: 'Приветствие', position: { x: 0, y: 0 }, block_ids: ['block_welcome'] },
        group_ask_name: { id: 'group_ask_name', title: 'Вопрос', position: { x: 0, y: 100 }, block_ids: ['block_ask_name'] },
        group_confirm: { id: 'group_confirm', title: 'Подтверждение', position: { x: 0, y: 200 }, block_ids: ['block_confirm'] },
    },
    blocks: {
        block_welcome: {
            id: 'block_welcome',
            group_id: 'group_welcome',
            type: 'text',
            content: { translations: { ru: 'Привет!' } },
            outgoing_edge_id: 'edge_1',
        },
        block_ask_name: {
            id: 'block_ask_name',
            group_id: 'group_ask_name',
            type: 'input',
            content: { translations: { ru: 'Как тебя зовут?' } },
            config: { variable: 'user_name' },
            outgoing_edge_id: 'edge_2',
        },
        block_confirm: {
            id: 'block_confirm',
            group_id: 'group_confirm',
            type: 'text',
            content: { translations: { ru: 'Приятно познакомиться, {{user_name}}!' } },
            outgoing_edge_id: null,
        },
    },
    edges: {
        edge_1: { id: 'edge_1', source_block_id: 'block_welcome', target_group_id: 'group_ask_name' },
        edge_2: { id: 'edge_2', source_block_id: 'block_ask_name', target_group_id: 'group_confirm' },
    },
}

describe('createChatSimulator — линейный сценарий', () => {
    it('start() сразу выводит текстовые блоки и останавливается на первом input', () => {
        const sim = createChatSimulator(linearSchema)
        sim.start()

        expect(sim.state.messages).toHaveLength(2) // "Привет!" + вопрос "Как тебя зовут?"
        expect(sim.state.messages[0]).toMatchObject({ role: 'bot', kind: 'text', text: 'Привет!' })
        expect(sim.state.messages[1]).toMatchObject({ role: 'bot', kind: 'text', text: 'Как тебя зовут?' })
        expect(sim.state.waiting).toMatchObject({ kind: 'input', variable: 'user_name' })
        expect(sim.state.finished).toBe(false)
    })

    it('submitText() сохраняет ответ в переменную и продолжает диалог с интерполяцией', () => {
        const sim = createChatSimulator(linearSchema)
        sim.start()
        sim.submitText('Женя')

        expect(sim.state.variables.user_name).toBe('Женя')
        const lastMessage = sim.state.messages[sim.state.messages.length - 1]
        expect(lastMessage).toMatchObject({ role: 'bot', kind: 'text', text: 'Приятно познакомиться, Женя!' })
        expect(sim.state.finished).toBe(true)
        expect(sim.state.waiting).toBeNull()
    })

    it('submitText() до этого добавляет сообщение пользователя в историю', () => {
        const sim = createChatSimulator(linearSchema)
        sim.start()
        sim.submitText('Женя')

        const userMessage = sim.state.messages.find((m) => m.role === 'user')
        expect(userMessage?.text).toBe('Женя')
    })

    it('обрезает пробелы у введённого текста', () => {
        const sim = createChatSimulator(linearSchema)
        sim.start()
        sim.submitText('  Женя  ')
        expect(sim.state.variables.user_name).toBe('Женя')
    })

    it('start() можно вызвать повторно, чтобы перезапустить диалог с чистого листа', () => {
        const sim = createChatSimulator(linearSchema)
        sim.start()
        sim.submitText('Женя')
        expect(sim.state.finished).toBe(true)

        sim.start()
        expect(sim.state.finished).toBe(false)
        expect(sim.state.variables).toEqual({})
        expect(sim.state.messages).toHaveLength(2)
    })
})

describe('createChatSimulator — пустой/некорректный флоу', () => {
    it('если start_group_id не задан, сразу помечает диалог завершённым с системной пометкой', () => {
        const empty: FlowSchema = { start_group_id: null, groups: {}, blocks: {}, edges: {} }
        const sim = createChatSimulator(empty)
        sim.start()

        expect(sim.state.finished).toBe(true)
        expect(sim.state.messages[0]).toMatchObject({ role: 'system', kind: 'note' })
    })

    it('если start_group_id указывает на несуществующую группу, тоже не падает', () => {
        const broken: FlowSchema = { start_group_id: 'ghost', groups: {}, blocks: {}, edges: {} }
        const sim = createChatSimulator(broken)
        sim.start()

        expect(sim.state.finished).toBe(true)
    })

    it('если группа не имеет исходящего ребра, диалог просто завершается (не падает)', () => {
        const deadEnd: FlowSchema = {
            start_group_id: 'g1',
            groups: { g1: { id: 'g1', title: 'A', position: { x: 0, y: 0 }, block_ids: ['b1'] } },
            blocks: { b1: { id: 'b1', group_id: 'g1', type: 'text', content: { translations: { ru: 'Конец' } }, outgoing_edge_id: null } },
            edges: {},
        }
        const sim = createChatSimulator(deadEnd)
        sim.start()

        expect(sim.state.finished).toBe(true)
        expect(sim.state.messages).toHaveLength(1)
    })
})

describe('createChatSimulator — buttons и сохранение выбора в переменную', () => {
    const buttonSchema: FlowSchema = {
        start_group_id: 'group_lang',
        groups: {
            group_lang: { id: 'group_lang', title: 'Язык', position: { x: 0, y: 0 }, block_ids: ['block_lang'] },
            group_done: { id: 'group_done', title: 'Готово', position: { x: 0, y: 100 }, block_ids: ['block_done'] },
        },
        blocks: {
            block_lang: {
                id: 'block_lang',
                group_id: 'group_lang',
                type: 'button',
                content: { translations: { ru: 'Выбери язык' }, buttons: [{ label: 'ru' }, { label: 'en' }] },
                config: { variable: 'lang' },
                outgoing_edge_id: 'edge_1',
            },
            block_done: {
                id: 'block_done',
                group_id: 'group_done',
                type: 'text',
                content: { translations: { ru: 'Выбрано: {{lang}}' } },
                outgoing_edge_id: null,
            },
        },
        edges: { edge_1: { id: 'edge_1', source_block_id: 'block_lang', target_group_id: 'group_done' } },
    }

    it('start() останавливается на buttons-блоке со списком вариантов', () => {
        const sim = createChatSimulator(buttonSchema)
        sim.start()
        expect(sim.state.waiting).toMatchObject({
            kind: 'buttons',
            options: [
                { label: 'ru', value: 'ru' },
                { label: 'en', value: 'en' },
            ],
            variable: 'lang',
        })
    })

    it('submitChoice() сохраняет выбранный вариант в переменную и продолжает диалог', () => {
        const sim = createChatSimulator(buttonSchema)
        sim.start()
        sim.submitChoice('en')

        expect(sim.state.variables.lang).toBe('en')
        const lastMessage = sim.state.messages[sim.state.messages.length - 1]
        expect(lastMessage).toMatchObject({ text: 'Выбрано: en' })
        expect(sim.state.finished).toBe(true)
    })
})

describe('createChatSimulator — ветвление по condition (True/False)', () => {
    const conditionSchema: FlowSchema = {
        start_group_id: 'group_lang',
        groups: {
            group_lang: { id: 'group_lang', title: 'Язык', position: { x: 0, y: 0 }, block_ids: ['block_lang'] },
            group_check: { id: 'group_check', title: 'Проверка', position: { x: 0, y: 100 }, block_ids: ['block_check'] },
            group_ru: { id: 'group_ru', title: 'RU-ветка', position: { x: -100, y: 200 }, block_ids: ['block_ru'] },
            group_other: { id: 'group_other', title: 'Другая ветка', position: { x: 100, y: 200 }, block_ids: ['block_other'] },
        },
        blocks: {
            block_lang: {
                id: 'block_lang',
                group_id: 'group_lang',
                type: 'button',
                content: { buttons: [{ label: 'ru' }, { label: 'en' }] },
                config: { variable: 'lang' },
                outgoing_edge_id: 'edge_to_check',
            },
            block_check: {
                id: 'block_check',
                group_id: 'group_check',
                type: 'condition',
                config: { conditionVariable: 'lang', conditionOperator: '==', conditionValue: 'ru' },
                outgoing_edge_id: null,
            },
            block_ru: {
                id: 'block_ru',
                group_id: 'group_ru',
                type: 'text',
                content: { translations: { ru: 'Русская ветка' } },
                outgoing_edge_id: null,
            },
            block_other: {
                id: 'block_other',
                group_id: 'group_other',
                type: 'text',
                content: { translations: { ru: 'Другая ветка' } },
                outgoing_edge_id: null,
            },
        },
        edges: {
            edge_to_check: { id: 'edge_to_check', source_block_id: 'block_lang', target_group_id: 'group_check' },
            edge_true: { id: 'edge_true', source_block_id: 'block_check', target_group_id: 'group_ru', source_handle: 'true' },
            edge_false: { id: 'edge_false', source_block_id: 'block_check', target_group_id: 'group_other', source_handle: 'false' },
        },
    }

    it('идёт по ветке True, если условие выполняется, и добавляет системную пометку с результатом', () => {
        const sim = createChatSimulator(conditionSchema)
        sim.start()
        sim.submitChoice('ru')

        const note = sim.state.messages.find((m) => m.kind === 'note')
        expect(note?.text).toContain('True')

        const lastMessage = sim.state.messages[sim.state.messages.length - 1]
        expect(lastMessage).toMatchObject({ text: 'Русская ветка' })
    })

    it('идёт по ветке False, если условие не выполняется', () => {
        const sim = createChatSimulator(conditionSchema)
        sim.start()
        sim.submitChoice('en')

        const note = sim.state.messages.find((m) => m.kind === 'note')
        expect(note?.text).toContain('False')

        const lastMessage = sim.state.messages[sim.state.messages.length - 1]
        expect(lastMessage).toMatchObject({ text: 'Другая ветка' })
    })

    it('если для результата условия нет соответствующего ребра, диалог завершается без ошибки', () => {
        const schemaWithoutFalseEdge: FlowSchema = {
            ...conditionSchema,
            edges: {
                edge_to_check: conditionSchema.edges.edge_to_check,
                edge_true: conditionSchema.edges.edge_true,
                // edge_false намеренно отсутствует
            },
        }
        const sim = createChatSimulator(schemaWithoutFalseEdge)
        sim.start()
        sim.submitChoice('en') // lang=en -> условие false -> нет ребра

        expect(sim.state.finished).toBe(true)
    })
})

describe('createChatSimulator — интерполяция переменных', () => {
    it('оставляет плейсхолдер как есть, если переменная ещё не задана', () => {
        const schema: FlowSchema = {
            start_group_id: 'g1',
            groups: { g1: { id: 'g1', title: 'A', position: { x: 0, y: 0 }, block_ids: ['b1'] } },
            blocks: {
                b1: {
                    id: 'b1',
                    group_id: 'g1',
                    type: 'text',
                    content: { translations: { ru: 'Привет, {{unknown_var}}!' } },
                    outgoing_edge_id: null,
                },
            },
            edges: {},
        }
        const sim = createChatSimulator(schema)
        sim.start()
        expect(sim.state.messages[0].text).toBe('Привет, {{unknown_var}}!')
    })
})

describe('createChatSimulator — медиа-блоки (Фаза 1)', () => {
    const makeMediaSchema = (type: 'image' | 'video' | 'audio' | 'file'): FlowSchema => ({
        start_group_id: 'g1',
        groups: { g1: { id: 'g1', title: 'A', position: { x: 0, y: 0 }, block_ids: ['b1'] } },
        blocks: {
            b1: {
                id: 'b1',
                group_id: 'g1',
                type,
                content: {
                    mediaUrl: 'https://example.com/media.bin',
                    mediaFileName: 'document.pdf',
                    translations: { ru: 'Подпись к сообщению' },
                },
                outgoing_edge_id: null,
            },
        },
        edges: {},
    })

    it.each(['image', 'video', 'audio', 'file'] as const)(
        '%s-блок выводится как сообщение kind: media с подписью',
        (type) => {
            const sim = createChatSimulator(makeMediaSchema(type))
            sim.start()

            expect(sim.state.messages).toHaveLength(1)
            expect(sim.state.messages[0]).toMatchObject({
                role: 'bot',
                kind: 'media',
                mediaType: type,
                mediaUrl: 'https://example.com/media.bin',
                mediaFileName: 'document.pdf',
                text: 'Подпись к сообщению',
            })
            expect(sim.state.finished).toBe(true)
        }
    )

    it('пустой mediaUrl не ломает симуляцию — сообщение всё равно выводится', () => {
        const schema = makeMediaSchema('image')
        schema.blocks.b1.content = { translations: { ru: 'Без ссылки' } }
        const sim = createChatSimulator(schema)
        sim.start()

        expect(sim.state.messages[0]).toMatchObject({ kind: 'media', mediaType: 'image', mediaUrl: '' })
    })
})

describe('createChatSimulator — валидируемые input-блоки (Фаза 2: number/email/phone/date)', () => {
    const makeValidatedSchema = (type: 'number' | 'email' | 'phone' | 'date', validation?: { min?: number; max?: number }): FlowSchema => ({
        start_group_id: 'g1',
        groups: { g1: { id: 'g1', title: 'A', position: { x: 0, y: 0 }, block_ids: ['b1'] } },
        blocks: {
            b1: {
                id: 'b1',
                group_id: 'g1',
                type,
                content: { translations: { ru: 'Вопрос' } },
                config: { variable: 'answer', validation },
                outgoing_edge_id: null,
            },
        },
        edges: {},
    })

    it('number: невалидное значение не продвигает диалог и показывает note-сообщение об ошибке', () => {
        const sim = createChatSimulator(makeValidatedSchema('number'))
        sim.start()
        sim.submitText('не число')

        expect(sim.state.waiting?.kind).toBe('input')
        expect(sim.state.variables.answer).toBeUndefined()
        expect(sim.state.messages.at(-1)).toMatchObject({ role: 'system', kind: 'note' })
    })

    it('number: валидное значение сохраняется в переменную и диалог завершается', () => {
        const sim = createChatSimulator(makeValidatedSchema('number'))
        sim.start()
        sim.submitText('42')

        expect(sim.state.variables.answer).toBe('42')
        expect(sim.state.finished).toBe(true)
    })

    it('number: значение вне min/max отклоняется', () => {
        const sim = createChatSimulator(makeValidatedSchema('number', { min: 18, max: 99 }))
        sim.start()
        sim.submitText('10')

        expect(sim.state.waiting?.kind).toBe('input')
        expect(sim.state.variables.answer).toBeUndefined()

        sim.submitText('25')
        expect(sim.state.variables.answer).toBe('25')
    })

    it('email: отклоняет строку без @', () => {
        const sim = createChatSimulator(makeValidatedSchema('email'))
        sim.start()
        sim.submitText('не email')
        expect(sim.state.variables.answer).toBeUndefined()

        sim.submitText('user@example.com')
        expect(sim.state.variables.answer).toBe('user@example.com')
    })

    it('phone: отклоняет слишком короткую/нечисловую строку, принимает международный формат', () => {
        const sim = createChatSimulator(makeValidatedSchema('phone'))
        sim.start()
        sim.submitText('abc')
        expect(sim.state.variables.answer).toBeUndefined()

        sim.submitText('+79991234567')
        expect(sim.state.variables.answer).toBe('+79991234567')
    })

    it('date: отклоняет неверный формат, принимает ГГГГ-ММ-ДД', () => {
        const sim = createChatSimulator(makeValidatedSchema('date'))
        sim.start()
        sim.submitText('31.01.2000')
        expect(sim.state.variables.answer).toBeUndefined()

        sim.submitText('2000-01-31')
        expect(sim.state.variables.answer).toBe('2000-01-31')
    })

    it('обычный "input" (без валидации) принимает любой текст как раньше', () => {
        const schema: FlowSchema = {
            start_group_id: 'g1',
            groups: { g1: { id: 'g1', title: 'A', position: { x: 0, y: 0 }, block_ids: ['b1'] } },
            blocks: {
                b1: {
                    id: 'b1',
                    group_id: 'g1',
                    type: 'input',
                    content: { translations: { ru: 'Как тебя зовут?' } },
                    config: { variable: 'name' },
                    outgoing_edge_id: null,
                },
            },
            edges: {},
        }
        const sim = createChatSimulator(schema)
        sim.start()
        sim.submitText('что угодно, хоть 123 хоть @@@')

        expect(sim.state.variables.name).toBe('что угодно, хоть 123 хоть @@@')
        expect(sim.state.finished).toBe(true)
    })
})

describe('createChatSimulator — request-блоки (Фаза 2: geolocation/contact)', () => {
    const makeRequestSchema = (type: 'geolocation' | 'contact'): FlowSchema => ({
        start_group_id: 'g1',
        groups: { g1: { id: 'g1', title: 'A', position: { x: 0, y: 0 }, block_ids: ['b1'] } },
        blocks: {
            b1: {
                id: 'b1',
                group_id: 'g1',
                type,
                content: { translations: { ru: 'Поделись данными' } },
                config: { variable: 'answer' },
                outgoing_edge_id: null,
            },
        },
        edges: {},
    })

    it.each(['geolocation', 'contact'] as const)('%s: ставит waiting.kind = request и не продвигает диалог сам по себе', (type) => {
        const sim = createChatSimulator(makeRequestSchema(type))
        sim.start()

        expect(sim.state.waiting).toMatchObject({ kind: 'request', requestType: type })
        expect(sim.state.finished).toBe(false)
    })

    it('submitRequest сохраняет значение-заглушку в переменную и продвигает диалог', () => {
        const sim = createChatSimulator(makeRequestSchema('geolocation'))
        sim.start()
        sim.submitRequest()

        expect(sim.state.variables.answer).toBeTruthy()
        expect(sim.state.finished).toBe(true)
    })

    it('submitText не работает, пока ждём request (нужно звать именно submitRequest)', () => {
        const sim = createChatSimulator(makeRequestSchema('contact'))
        sim.start()
        sim.submitText('+79991234567')

        expect(sim.state.variables.answer).toBeUndefined()
        expect(sim.state.waiting?.kind).toBe('request')
    })
})

describe('createChatSimulator — poll (Фаза 2)', () => {
    const pollSchema: FlowSchema = {
        start_group_id: 'g1',
        groups: { g1: { id: 'g1', title: 'A', position: { x: 0, y: 0 }, block_ids: ['b1'] } },
        blocks: {
            b1: {
                id: 'b1',
                group_id: 'g1',
                type: 'poll',
                content: { translations: { ru: 'Что интереснее?' }, buttons: [{ label: 'Кино' }, { label: 'Книги' }] },
                outgoing_edge_id: null,
            },
        },
        edges: {},
    }

    it('poll не ждёт ответа — диалог сразу завершается (нет других блоков)', () => {
        const sim = createChatSimulator(pollSchema)
        sim.start()

        expect(sim.state.waiting).toBeNull()
        expect(sim.state.finished).toBe(true)
        expect(sim.state.messages[0]).toMatchObject({
            kind: 'poll',
            text: 'Что интереснее?',
            options: [
                { label: 'Кино', value: 'Кино' },
                { label: 'Книги', value: 'Книги' },
            ],
        })
    })
})

describe('createChatSimulator — callback_data у inline-кнопок', () => {
    const callbackSchema: FlowSchema = {
        start_group_id: 'group_lang',
        groups: {
            group_lang: { id: 'group_lang', title: 'Язык', position: { x: 0, y: 0 }, block_ids: ['block_lang'] },
        },
        blocks: {
            block_lang: {
                id: 'block_lang',
                group_id: 'group_lang',
                type: 'button',
                content: {
                    translations: { ru: 'Выбери язык' },
                    buttons: [
                        { label: '🇷🇺 Русский', callbackData: 'lang_ru' },
                        { label: '🇬🇧 English' }, // без callback_data — используется label
                    ],
                },
                config: { variable: 'lang' },
                outgoing_edge_id: null,
            },
        },
        edges: {},
    }

    it('options содержит и label (для отображения), и value = callback_data', () => {
        const sim = createChatSimulator(callbackSchema)
        sim.start()

        expect(sim.state.waiting).toMatchObject({
            kind: 'buttons',
            options: [
                { label: '🇷🇺 Русский', value: 'lang_ru' },
                { label: '🇬🇧 English', value: '🇬🇧 English' },
            ],
        })
    })

    it('submitChoice(callback_data) сохраняет callback_data в переменную, но эхо-сообщение показывает label', () => {
        const sim = createChatSimulator(callbackSchema)
        sim.start()
        sim.submitChoice('lang_ru')

        expect(sim.state.variables.lang).toBe('lang_ru')
        const echo = sim.state.messages.find((m) => m.role === 'user')
        expect(echo?.text).toBe('🇷🇺 Русский')
        expect(sim.state.finished).toBe(true)
    })

    it('кнопка без callback_data по-прежнему использует label как value (обратная совместимость)', () => {
        const sim = createChatSimulator(callbackSchema)
        sim.start()
        sim.submitChoice('🇬🇧 English')

        expect(sim.state.variables.lang).toBe('🇬🇧 English')
    })
})
