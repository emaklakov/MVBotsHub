import { describe, it, expect } from 'vitest'
import {
    getBlockDefinition,
    listBlockDefinitions,
    defaultBlockTitle,
    defaultBlockContent,
    defaultBlockConfig,
    getBlockOutputs,
    blockProducesVariable,
    blockCategories,
} from '../index'
import type { FlowBlockType } from '@/types/flow'

// Все типы, которые реально должны быть зарегистрированы на сегодня.
// Этот список — намеренная "защита от забывчивости": если кто-то добавит
// новый FlowBlockType в types/flow.ts, но забудет завести запись в
// registry.ts, getBlockDefinition() бросит понятную ошибку в рантайме —
// а этот тест ловит то же самое ещё на этапе CI, до рантайма.
const knownTypes: FlowBlockType[] = ['text', 'input', 'button', 'condition']

describe('реестр блоков: полнота', () => {
    it('содержит запись для каждого известного типа блока', () => {
        for (const type of knownTypes) {
            expect(() => getBlockDefinition(type)).not.toThrow()
        }
    })

    it('listBlockDefinitions возвращает ровно известные типы, без дублей', () => {
        const types = listBlockDefinitions().map((d) => d.type)
        expect(new Set(types)).toEqual(new Set(knownTypes))
        expect(types).toHaveLength(knownTypes.length)
    })

    it('каждый блок ссылается на существующую категорию из blockCategories', () => {
        const categoryKeys = new Set(blockCategories.map((c) => c.key))
        for (const def of listBlockDefinitions()) {
            expect(categoryKeys.has(def.category)).toBe(true)
        }
    })

    it('каждый блок имеет непустые label/hint/icon и оба компонента отображения хотя бы для render', () => {
        for (const def of listBlockDefinitions()) {
            expect(def.label).toBeTruthy()
            expect(def.hint).toBeTruthy()
            expect(def.icon).toBeTruthy()
            expect(def.renderComponent).toBeTruthy()
        }
    })

    it('бросает понятную ошибку для несуществующего типа', () => {
        expect(() => getBlockDefinition('unknown_type' as FlowBlockType)).toThrow(/Неизвестный тип блока/)
    })
})

describe('дефолты через реестр совпадают с ожидаемыми (регрессия после рефакторинга)', () => {
    it('text', () => {
        expect(defaultBlockTitle('text')).toBe('Сообщение')
        expect(defaultBlockContent('text')).toEqual({ translations: { ru: '', en: '' } })
        expect(defaultBlockConfig('text')).toEqual({})
    })

    it('input', () => {
        expect(defaultBlockTitle('input')).toBe('Вопрос')
        expect(defaultBlockContent('input')).toEqual({})
        expect(defaultBlockConfig('input')).toEqual({ variable: '' })
    })

    it('button', () => {
        expect(defaultBlockTitle('button')).toBe('Кнопки')
        expect(defaultBlockContent('button')).toEqual({ buttons: [] })
        expect(defaultBlockConfig('button')).toEqual({})
    })

    it('condition', () => {
        expect(defaultBlockTitle('condition')).toBe('Условие')
        expect(defaultBlockContent('condition')).toEqual({})
        expect(defaultBlockConfig('condition')).toEqual({ conditionOperator: '==' })
    })
})

describe('getBlockOutputs', () => {
    it('обычный блок (text/input/button) имеет один выход без handle', () => {
        expect(getBlockOutputs('text')).toEqual([{ handle: null }])
        expect(getBlockOutputs('input')).toEqual([{ handle: null }])
        expect(getBlockOutputs('button')).toEqual([{ handle: null }])
    })

    it('condition имеет два выхода: false и true', () => {
        const outputs = getBlockOutputs('condition')
        expect(outputs.map((o) => o.handle)).toEqual(['false', 'true'])
        expect(outputs.every((o) => o.label)).toBe(true)
    })

    it('для отсутствующего блока (undefined type, пустая группа) — один обычный выход', () => {
        expect(getBlockOutputs(undefined)).toEqual([{ handle: null }])
    })
})

describe('blockProducesVariable', () => {
    it('input и button дают переменную', () => {
        expect(blockProducesVariable('input')).toBe(true)
        expect(blockProducesVariable('button')).toBe(true)
    })

    it('text и condition переменную не дают', () => {
        expect(blockProducesVariable('text')).toBe(false)
        expect(blockProducesVariable('condition')).toBe(false)
    })
})
