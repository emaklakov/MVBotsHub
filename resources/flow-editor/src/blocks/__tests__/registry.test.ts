import { describe, it, expect } from 'vitest'
import {
    getBlockDefinition,
    listBlockDefinitions,
    listBlockDefinitionsForChannel,
    defaultBlockTitle,
    defaultBlockContent,
    defaultBlockConfig,
    getBlockOutputs,
    blockProducesVariable,
    blockCategories,
} from '../index'
import { getChannelProfile } from '@/channels'
import type { FlowBlockType } from '@/types/flow'

// Все типы, которые реально должны быть зарегистрированы на сегодня.
// Этот список — намеренная "защита от забывчивости": если кто-то добавит
// новый FlowBlockType в types/flow.ts, но забудет завести запись в
// registry.ts, getBlockDefinition() бросит понятную ошибку в рантайме —
// а этот тест ловит то же самое ещё на этапе CI, до рантайма.
const knownTypes: FlowBlockType[] = [
    'text',
    'input',
    'button',
    'condition',
    'image',
    'video',
    'audio',
    'file',
    'number',
    'email',
    'phone',
    'date',
    'geolocation',
    'contact',
    'poll',
]
const mediaTypes: FlowBlockType[] = ['image', 'video', 'audio', 'file']
const validatedInputTypes: FlowBlockType[] = ['number', 'email', 'phone', 'date']
const requestTypes: FlowBlockType[] = ['geolocation', 'contact']

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

    it.each(['image', 'video', 'audio'] as const)('%s: mediaUrl + двуязычная подпись, без config', (type) => {
        expect(defaultBlockContent(type)).toEqual({ mediaUrl: '', translations: { ru: '', en: '' } })
        expect(defaultBlockConfig(type)).toEqual({})
    })

    it('file: дополнительно mediaFileName в дефолтном контенте', () => {
        expect(defaultBlockContent('file')).toEqual({ mediaUrl: '', mediaFileName: '', translations: { ru: '', en: '' } })
        expect(defaultBlockConfig('file')).toEqual({})
    })

    it.each(validatedInputTypes)('%s: как input — пустой content, config.variable (Фаза 2)', (type) => {
        expect(defaultBlockContent(type)).toEqual({})
        expect(defaultBlockConfig(type)).toEqual({ variable: '' })
    })

    it.each(requestTypes)('%s: content с translations, config.variable (Фаза 2)', (type) => {
        expect(defaultBlockContent(type)).toEqual({ translations: { ru: '', en: '' } })
        expect(defaultBlockConfig(type)).toEqual({ variable: '' })
    })

    it('poll: content с translations и пустым списком вариантов, config пуст', () => {
        expect(defaultBlockContent('poll')).toEqual({ translations: { ru: '', en: '' }, buttons: [] })
        expect(defaultBlockConfig('poll')).toEqual({})
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

    it('медиа-блоки (image/video/audio/file) — один обычный выход, как обычные bubble-блоки', () => {
        for (const type of mediaTypes) {
            expect(getBlockOutputs(type)).toEqual([{ handle: null }])
        }
    })

    it('все новые блоки Фазы 2 (валидируемые input, request, poll) — один обычный выход', () => {
        for (const type of [...validatedInputTypes, ...requestTypes, 'poll' as const]) {
            expect(getBlockOutputs(type)).toEqual([{ handle: null }])
        }
    })
})

describe('blockProducesVariable', () => {
    it('input и button дают переменную', () => {
        expect(blockProducesVariable('input')).toBe(true)
        expect(blockProducesVariable('button')).toBe(true)
    })

    it('text, condition и медиа-блоки переменную не дают', () => {
        expect(blockProducesVariable('text')).toBe(false)
        expect(blockProducesVariable('condition')).toBe(false)
        for (const type of mediaTypes) {
            expect(blockProducesVariable(type)).toBe(false)
        }
    })

    it('валидируемые input-блоки и request-блоки дают переменную (Фаза 2)', () => {
        for (const type of [...validatedInputTypes, ...requestTypes]) {
            expect(blockProducesVariable(type)).toBe(true)
        }
    })

    it('poll переменную не даёт — не блокирует диалог (Фаза 2)', () => {
        expect(blockProducesVariable('poll')).toBe(false)
    })
})

describe('медиа-блоки требуют возможность file_upload канала (Фаза 1)', () => {
    it('у каждого медиа-блока задан requiresCapabilities: [\'file_upload\']', () => {
        for (const type of mediaTypes) {
            expect(getBlockDefinition(type).requiresCapabilities).toEqual(['file_upload'])
        }
    })

    it('Telegram умеет file_upload — все медиа-блоки видны в его библиотеке', () => {
        const telegram = getChannelProfile('telegram')
        const availableTypes = listBlockDefinitionsForChannel(telegram).map((d) => d.type)
        for (const type of mediaTypes) {
            expect(availableTypes).toContain(type)
        }
    })

    it('универсальные блоки (text/input/button/condition + валидируемые input Фазы 2) не требуют возможностей канала', () => {
        for (const type of ['text', 'input', 'button', 'condition', ...validatedInputTypes] as const) {
            expect(getBlockDefinition(type).requiresCapabilities).toBeUndefined()
        }
    })
})

describe('request-блоки требуют свою нативную возможность канала (Фаза 2)', () => {
    it('geolocation требует возможность geolocation, contact — contact_share', () => {
        expect(getBlockDefinition('geolocation').requiresCapabilities).toEqual(['geolocation'])
        expect(getBlockDefinition('contact').requiresCapabilities).toEqual(['contact_share'])
    })

    it('poll требует возможность poll', () => {
        expect(getBlockDefinition('poll').requiresCapabilities).toEqual(['poll'])
    })

    it('Telegram умеет всё это — все три блока видны в его библиотеке', () => {
        const telegram = getChannelProfile('telegram')
        const availableTypes = listBlockDefinitionsForChannel(telegram).map((d) => d.type)
        expect(availableTypes).toEqual(expect.arrayContaining(['geolocation', 'contact', 'poll']))
    })
})
