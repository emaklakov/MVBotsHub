import { describe, it, expect } from 'vitest'
import { useFlowSerializer } from '../useFlowSerializer'
import type { FlowSchema } from '@/types/flow'

const { toVueFlow, toSchema } = useFlowSerializer()

// Реалистичный сценарий: приветствие -> вопрос про имя -> подтверждение.
const sampleSchema: FlowSchema = {
    start_group_id: 'group_welcome',
    groups: {
        group_welcome: {
            id: 'group_welcome',
            title: 'Сообщение',
            position: { x: 40, y: 80 },
            block_ids: ['block_welcome'],
        },
        group_ask_name: {
            id: 'group_ask_name',
            title: 'Вопрос',
            position: { x: 40, y: 260 },
            block_ids: ['block_ask_name'],
        },
        group_confirmation: {
            id: 'group_confirmation',
            title: 'Сообщение',
            position: { x: 40, y: 440 },
            block_ids: ['block_confirmation'],
        },
    },
    blocks: {
        block_welcome: {
            id: 'block_welcome',
            group_id: 'group_welcome',
            type: 'text',
            content: { translations: { ru: 'Привет!', en: 'Hi!' } },
            outgoing_edge_id: 'edge_1',
        },
        block_ask_name: {
            id: 'block_ask_name',
            group_id: 'group_ask_name',
            type: 'input',
            content: { translations: { ru: 'Как тебя зовут?', en: "What's your name?" } },
            config: { variable: 'user_name' },
            outgoing_edge_id: 'edge_2',
        },
        block_confirmation: {
            id: 'block_confirmation',
            group_id: 'group_confirmation',
            type: 'text',
            content: { translations: { ru: 'Приятно познакомиться, {{user_name}}!', en: 'Nice to meet you, {{user_name}}!' } },
            outgoing_edge_id: null,
        },
    },
    edges: {
        edge_1: { id: 'edge_1', source_block_id: 'block_welcome', target_group_id: 'group_ask_name' },
        edge_2: { id: 'edge_2', source_block_id: 'block_ask_name', target_group_id: 'group_confirmation' },
    },
}

describe('toVueFlow', () => {
    const { nodes, edges } = toVueFlow(sampleSchema)

    it('создаёт одну ноду на группу с типом её единственного блока', () => {
        expect(nodes).toHaveLength(3)
        const welcomeNode = nodes.find((n) => n.id === 'group_welcome')
        expect(welcomeNode?.type).toBe('text')
        expect(welcomeNode?.position).toEqual({ x: 40, y: 80 })
    })

    it('прокидывает content/config и blockId в data ноды', () => {
        const nameNode = nodes.find((n) => n.id === 'group_ask_name')
        expect(nameNode?.data.blockId).toBe('block_ask_name')
        expect(nameNode?.data.content.translations.ru).toBe('Как тебя зовут?')
        expect(nameNode?.data.config.variable).toBe('user_name')
    })

    it('строит рёбра VueFlow между группами (source/target групп, не блоков)', () => {
        expect(edges).toHaveLength(2)
        const edge1 = edges.find((e) => e.id === 'edge_1')
        expect(edge1).toEqual({ id: 'edge_1', source: 'group_welcome', target: 'group_ask_name' })
    })
})

describe('toSchema', () => {
    it('является обратной операцией к toVueFlow (round-trip)', () => {
        const { nodes, edges } = toVueFlow(sampleSchema)
        const result = toSchema(nodes, edges, sampleSchema.start_group_id)

        expect(result.start_group_id).toBe('group_welcome')
        expect(Object.keys(result.groups)).toHaveLength(3)
        expect(Object.keys(result.blocks)).toHaveLength(3)
        expect(result.blocks['block_ask_name'].content).toEqual(
            sampleSchema.blocks['block_ask_name'].content
        )
        expect(result.groups['group_welcome'].block_ids).toEqual(['block_welcome'])
    })

    it('проставляет outgoing_edge_id блоку-источнику по рёбрам VueFlow', () => {
        const { nodes, edges } = toVueFlow(sampleSchema)
        const result = toSchema(nodes, edges, sampleSchema.start_group_id)

        expect(result.blocks['block_welcome'].outgoing_edge_id).toBe('edge_1')
        expect(result.blocks['block_confirmation'].outgoing_edge_id).toBeNull()
    })

    it('если у ноды ещё нет blockId (только что добавлена), использует node.id как blockId', () => {
        const newNode = {
            id: 'group_new',
            type: 'text',
            position: { x: 10, y: 10 },
            data: { content: { translations: { ru: '', en: '' } }, config: {} },
        }
        const result = toSchema([newNode as any], [], 'group_new')

        expect(result.groups['group_new'].block_ids).toEqual(['group_new'])
        expect(result.blocks['group_new'].id).toBe('group_new')
    })

    it('если start_group_id не передан, берёт первую ноду', () => {
        const { nodes, edges } = toVueFlow(sampleSchema)
        const result = toSchema(nodes, edges, null)
        expect(result.start_group_id).toBe(nodes[0].id)
    })
})
