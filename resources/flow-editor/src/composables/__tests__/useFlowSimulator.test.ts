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
                content: { translations: { ru: 'Выбери язык' }, buttons: ['ru', 'en'] },
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
        expect(sim.state.waiting).toMatchObject({ kind: 'buttons', options: ['ru', 'en'], variable: 'lang' })
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
                content: { buttons: ['ru', 'en'] },
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
