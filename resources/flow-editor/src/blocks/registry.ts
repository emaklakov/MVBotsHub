import type { BlockConfig, FlowBlockType } from '@/types/flow'
import type { BlockDefinition, BlockCategoryMeta } from './types'

import TextBlock from '@/components/blocks/TextBlock.vue'
import InputBlock from '@/components/blocks/InputBlock.vue'
import ButtonBlock from '@/components/blocks/ButtonBlock.vue'
import ConditionBlock from '@/components/blocks/ConditionBlock.vue'

import TextBlockEditor from '@/components/properties/TextBlockEditor.vue'
import InputBlockEditor from '@/components/properties/InputBlockEditor.vue'
import ButtonsBlockEditor from '@/components/properties/ButtonsBlockEditor.vue'
import ConditionBlockEditor from '@/components/properties/ConditionBlockEditor.vue'

/**
 * Категории в библиотеке блоков (Sidebar). Порядок в массиве — порядок
 * отображения аккордеонов. Новая категория добавляется тут один раз,
 * дальше блоки сами группируются по полю `category`.
 */
export const blockCategories: BlockCategoryMeta[] = [
    { key: 'bubbles', label: 'Bubbles' },
    { key: 'inputs', label: 'Входные данные' },
    { key: 'logic', label: 'Логика' },
]

/**
 * Единая точка регистрации типа блока. Чтобы добавить новый тип, нужно:
 *  1. Добавить его в union FlowBlockType (src/types/flow.ts).
 *  2. Написать компонент отображения (components/blocks) и, если нужно
 *     редактирование, компонент свойств (components/properties).
 *  3. Добавить одну запись сюда.
 * BlockRenderer, PropertiesPanel, Sidebar и сериализатор ничего не знают
 * о конкретных типах — они целиком работают через этот реестр.
 */
export const blockRegistry: Record<FlowBlockType, BlockDefinition> = {
    text: {
        type: 'text',
        category: 'bubbles',
        label: 'Текст',
        hint: 'Сообщение от бота',
        icon: '💬',
        renderComponent: TextBlock,
        editorComponent: TextBlockEditor,
        defaultTitle: 'Сообщение',
        defaultContent: () => ({ translations: { ru: '', en: '' } }),
        defaultConfig: () => ({}),
    },
    input: {
        type: 'input',
        category: 'inputs',
        label: 'Вопрос',
        hint: 'Ждём текстовый ответ пользователя',
        icon: '✏️',
        renderComponent: InputBlock,
        editorComponent: InputBlockEditor,
        defaultTitle: 'Вопрос',
        // 'input' хранит свой вопрос тоже в content.translations, но пустой
        // объект здесь ок — InputBlockEditor заполнит его при редактировании.
        defaultContent: () => ({}),
        defaultConfig: () => ({ variable: '' }),
        producesVariable: true,
    },
    button: {
        type: 'button',
        category: 'inputs',
        label: 'Кнопки',
        hint: 'Выбор одного из вариантов',
        icon: '🔘',
        renderComponent: ButtonBlock,
        editorComponent: ButtonsBlockEditor,
        defaultTitle: 'Кнопки',
        defaultContent: () => ({ buttons: [] }),
        defaultConfig: () => ({}),
        producesVariable: true,
    },
    condition: {
        type: 'condition',
        category: 'logic',
        label: 'Условие',
        hint: 'Ветвление по переменной (True/False)',
        icon: '🔀',
        renderComponent: ConditionBlock,
        editorComponent: ConditionBlockEditor,
        defaultTitle: 'Условие',
        // У condition нет собственного контента — вся суть в config.
        defaultContent: () => ({}),
        defaultConfig: () => ({ conditionOperator: '==' }),
        outputs: (_config?: BlockConfig) => [
            { handle: 'false', label: 'False', tone: 'error' },
            { handle: 'true', label: 'True', tone: 'success' },
        ],
    },
}
