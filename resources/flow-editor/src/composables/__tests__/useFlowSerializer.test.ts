import { describe, it, expect } from 'vitest'
import {
    useFlowSerializer,
    emptySchema,
    defaultGroupTitle,
    defaultBlockContent,
    defaultBlockConfig,
    collectVariables,
    type GroupNodeData,
} from '../useFlowSerializer'
import type { FlowSchema } from '@/types/flow'

const { toVueFlow, toSchema } = useFlowSerializer()

// Группа "welcome" содержит ДВА блока подряд (текст + кнопки выбора языка) —
// именно такой сценарий и был целью Фазы 2: несколько блоков в одной группе.
// Группа "ask_name" — один блок. Переход на "confirmation" идёт от
// последнего блока группы welcome (button), а не от первого (text).
const sampleSchema: FlowSchema = {
    start_group_id: 'group_welcome',
    groups: {
        group_welcome: {
            id: 'group_welcome',
            title: 'Приветствие',
            position: { x: 40, y: 80 },
            block_ids: ['block_welcome_text', 'block_welcome_lang'],
        },
        group_ask_name: {
            id: 'group_ask_name',
            title: 'Вопрос',
            position: { x: 40, y: 260 },
            block_ids: ['block_ask_name'],
        },
    },
    blocks: {
        block_welcome_text: {
            id: 'block_welcome_text',
            group_id: 'group_welcome',
            type: 'text',
            content: { translations: { ru: 'Привет!', en: 'Hi!' } },
            outgoing_edge_id: null,
        },
        block_welcome_lang: {
            id: 'block_welcome_lang',
            group_id: 'group_welcome',
            type: 'button',
            content: { buttons: ['Русский', 'English'] },
            outgoing_edge_id: 'edge_1',
        },
        block_ask_name: {
            id: 'block_ask_name',
            group_id: 'group_ask_name',
            type: 'input',
            content: { translations: { ru: 'Как тебя зовут?', en: "What's your name?" } },
            config: { variable: 'user_name' },
            outgoing_edge_id: null,
        },
    },
    edges: {
        edge_1: { id: 'edge_1', source_block_id: 'block_welcome_lang', target_group_id: 'group_ask_name' },
    },
}

describe('toVueFlow', () => {
    const { nodes, edges } = toVueFlow(sampleSchema)

    it('создаёт одну ноду типа group на каждую группу схемы', () => {
        expect(nodes).toHaveLength(2)
        expect(nodes.every((n) => n.type === 'group')).toBe(true)
    })

    it('кладёт все блоки группы в data.blocks в порядке block_ids', () => {
        const welcomeNode = nodes.find((n) => n.id === 'group_welcome')!
        const data = welcomeNode.data as GroupNodeData
        expect(data.title).toBe('Приветствие')
        expect(data.blocks.map((b) => b.id)).toEqual(['block_welcome_text', 'block_welcome_lang'])
        expect(data.blocks[1].content?.buttons).toEqual(['Русский', 'English'])
    })

    it('строит рёбра между группами (а не между блоками)', () => {
        expect(edges).toHaveLength(1)
        expect(edges[0]).toEqual({ id: 'edge_1', source: 'group_welcome', target: 'group_ask_name' })
    })
})

describe('toVueFlow — устойчивость к пустому/битому черновику', () => {
    // Регрессия: "Cannot convert undefined or null to object" при открытии
    // редактора для нового бота, у которого черновик ещё не был сохранён
    // и бэкенд вернул schema: null / {} вместо полной структуры.
    it('не падает, если schema === null', () => {
        const result = toVueFlow(null)
        expect(result.nodes).toEqual([])
        expect(result.edges).toEqual([])
    })

    it('не падает, если schema === undefined', () => {
        const result = toVueFlow(undefined)
        expect(result.nodes).toEqual([])
        expect(result.edges).toEqual([])
    })

    it('не падает, если schema === {} (нет ни groups, ни blocks, ни edges)', () => {
        const result = toVueFlow({})
        expect(result.nodes).toEqual([])
        expect(result.edges).toEqual([])
    })

    it('emptySchema() даёт валидную пустую схему для инициализации нового бота', () => {
        const result = toVueFlow(emptySchema())
        expect(result.nodes).toEqual([])
        expect(result.edges).toEqual([])
    })
})


describe('toSchema', () => {
    it('является обратной операцией к toVueFlow (round-trip) для группы с несколькими блоками', () => {
        const { nodes, edges } = toVueFlow(sampleSchema)
        const result = toSchema(nodes, edges, sampleSchema.start_group_id)

        expect(result.start_group_id).toBe('group_welcome')
        expect(result.groups['group_welcome'].block_ids).toEqual(['block_welcome_text', 'block_welcome_lang'])
        expect(result.blocks['block_welcome_lang'].content?.buttons).toEqual(['Русский', 'English'])
    })

    it('привязывает outgoing_edge_id ребра группы к ПОСЛЕДНЕМУ блоку группы', () => {
        const { nodes, edges } = toVueFlow(sampleSchema)
        const result = toSchema(nodes, edges, sampleSchema.start_group_id)

        expect(result.blocks['block_welcome_lang'].outgoing_edge_id).toBe('edge_1')
        expect(result.blocks['block_welcome_text'].outgoing_edge_id).toBeNull()
    })

    it('источник ребра в схеме указывает на последний блок исходной группы', () => {
        const { nodes, edges } = toVueFlow(sampleSchema)
        const result = toSchema(nodes, edges, sampleSchema.start_group_id)

        expect(result.edges['edge_1'].source_block_id).toBe('block_welcome_lang')
        expect(result.edges['edge_1'].target_group_id).toBe('group_ask_name')
    })

    it('если группа пуста (data.blocks == []), не падает и просто не создаёт блоков', () => {
        const emptyGroupNode = {
            id: 'group_empty',
            type: 'group',
            position: { x: 0, y: 0 },
            data: { title: 'Пустая', blocks: [] } as GroupNodeData,
        }
        const result = toSchema([emptyGroupNode as any], [], 'group_empty')

        expect(result.groups['group_empty'].block_ids).toEqual([])
        expect(Object.keys(result.blocks)).toHaveLength(0)
    })

    it('если start_group_id не передан, берёт первую ноду', () => {
        const { nodes, edges } = toVueFlow(sampleSchema)
        const result = toSchema(nodes, edges, null)
        expect(result.start_group_id).toBe(nodes[0].id)
    })
})

describe('defaultBlockContent / defaultBlockConfig', () => {
    it('text получает пустые переводы ru/en', () => {
        expect(defaultBlockContent('text')).toEqual({ translations: { ru: '', en: '' } })
        expect(defaultBlockConfig('text')).toEqual({})
    })

    it('input получает пустую переменную в config, content пустой', () => {
        expect(defaultBlockContent('input')).toEqual({})
        expect(defaultBlockConfig('input')).toEqual({ variable: '' })
    })

    it('button получает пустой список кнопок', () => {
        expect(defaultBlockContent('button')).toEqual({ buttons: [] })
        expect(defaultBlockConfig('button')).toEqual({})
    })

    it('condition получает пустой content и оператор "==" по умолчанию', () => {
        expect(defaultBlockContent('condition')).toEqual({})
        expect(defaultBlockConfig('condition')).toEqual({ conditionOperator: '==' })
    })
})

describe('defaultGroupTitle', () => {
    it('условию соответствует заголовок "Условие"', () => {
        expect(defaultGroupTitle('condition')).toBe('Условие')
    })
})

describe('condition-блок: два выхода (True/False) через source_handle', () => {
    // Группа заканчивается блоком-условием: True ведёт в group_yes,
    // False — в group_no. Оба ребра формально выходят из одного и того
    // же блока (block_condition), различаются только source_handle.
    const conditionSchema: FlowSchema = {
        start_group_id: 'group_check',
        groups: {
            group_check: {
                id: 'group_check',
                title: 'Условие',
                position: { x: 0, y: 0 },
                block_ids: ['block_condition'],
            },
            group_yes: { id: 'group_yes', title: 'Да', position: { x: 100, y: 100 }, block_ids: [] },
            group_no: { id: 'group_no', title: 'Нет', position: { x: -100, y: 100 }, block_ids: [] },
        },
        blocks: {
            block_condition: {
                id: 'block_condition',
                group_id: 'group_check',
                type: 'condition',
                config: { conditionVariable: 'user_language', conditionOperator: '==', conditionValue: 'ru' },
                outgoing_edge_id: null,
            },
        },
        edges: {
            edge_true: { id: 'edge_true', source_block_id: 'block_condition', target_group_id: 'group_yes', source_handle: 'true' },
            edge_false: { id: 'edge_false', source_block_id: 'block_condition', target_group_id: 'group_no', source_handle: 'false' },
        },
    }

    it('toVueFlow переносит source_handle в sourceHandle ребра VueFlow', () => {
        const { edges } = toVueFlow(conditionSchema)
        const trueEdge = edges.find((e) => e.id === 'edge_true')
        const falseEdge = edges.find((e) => e.id === 'edge_false')

        expect(trueEdge).toMatchObject({ source: 'group_check', target: 'group_yes', sourceHandle: 'true' })
        expect(falseEdge).toMatchObject({ source: 'group_check', target: 'group_no', sourceHandle: 'false' })
    })

    it('toSchema восстанавливает оба ребра с правильным source_handle (round-trip)', () => {
        const { nodes, edges } = toVueFlow(conditionSchema)
        const result = toSchema(nodes, edges, conditionSchema.start_group_id)

        expect(result.edges['edge_true']).toMatchObject({
            source_block_id: 'block_condition',
            target_group_id: 'group_yes',
            source_handle: 'true',
        })
        expect(result.edges['edge_false']).toMatchObject({
            source_block_id: 'block_condition',
            target_group_id: 'group_no',
            source_handle: 'false',
        })
    })

    it('condition-блок не получает outgoing_edge_id — у него два выхода, это поле рассчитано на один', () => {
        const { nodes, edges } = toVueFlow(conditionSchema)
        const result = toSchema(nodes, edges, conditionSchema.start_group_id)

        expect(result.blocks['block_condition'].outgoing_edge_id).toBeNull()
    })

    it('обычный (не condition) блок по-прежнему не получает source_handle', () => {
        const { nodes, edges } = toVueFlow(sampleSchema)
        const result = toSchema(nodes, edges, sampleSchema.start_group_id)

        expect(result.edges['edge_1'].source_handle).toBeNull()
    })
})

describe('collectVariables', () => {
    it('собирает переменные из всех input-блоков по всем группам', () => {
        const { nodes } = toVueFlow(sampleSchema)
        // sampleSchema: block_ask_name (input, variable: user_name) — единственный input
        expect(collectVariables(nodes)).toEqual(['user_name'])
    })

    it('собирает переменные и из button-блоков, если у них задана config.variable', () => {
        const nodesWithButtonVariable = [
            {
                id: 'g1',
                type: 'group',
                position: { x: 0, y: 0 },
                data: {
                    title: 'A',
                    blocks: [
                        { id: 'b1', type: 'input', config: { variable: 'user_name' } },
                        { id: 'b2', type: 'button', config: { variable: 'user_language' }, content: { buttons: ['ru', 'en'] } },
                    ],
                } as GroupNodeData,
            },
        ]
        expect(collectVariables(nodesWithButtonVariable as any)).toEqual(['user_name', 'user_language'])
    })

    it('не дублирует переменную, если она используется в нескольких input-блоках', () => {
        const nodesWithDuplicate = [
            {
                id: 'g1',
                type: 'group',
                position: { x: 0, y: 0 },
                data: {
                    title: 'A',
                    blocks: [{ id: 'b1', type: 'input', config: { variable: 'name' } }],
                } as GroupNodeData,
            },
            {
                id: 'g2',
                type: 'group',
                position: { x: 0, y: 0 },
                data: {
                    title: 'B',
                    blocks: [{ id: 'b2', type: 'input', config: { variable: 'name' } }],
                } as GroupNodeData,
            },
        ]
        expect(collectVariables(nodesWithDuplicate as any)).toEqual(['name'])
    })

    it('игнорирует input-блоки без указанной переменной и блоки других типов', () => {
        const mixedNodes = [
            {
                id: 'g1',
                type: 'group',
                position: { x: 0, y: 0 },
                data: {
                    title: 'A',
                    blocks: [
                        { id: 'b1', type: 'input', config: {} },
                        { id: 'b2', type: 'text', config: { variable: 'should_be_ignored' } },
                    ],
                } as GroupNodeData,
            },
        ]
        expect(collectVariables(mixedNodes as any)).toEqual([])
    })

    it('на пустом списке нод возвращает пустой массив', () => {
        expect(collectVariables([])).toEqual([])
    })
})
