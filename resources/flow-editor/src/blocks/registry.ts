import type { BlockConfig, FlowBlockType } from '@/types/flow'
import type { BlockDefinition, BlockCategoryMeta } from './types'

import TextBlock from '@/components/blocks/TextBlock.vue'
import InputBlock from '@/components/blocks/InputBlock.vue'
import ButtonBlock from '@/components/blocks/ButtonBlock.vue'
import ConditionBlock from '@/components/blocks/ConditionBlock.vue'
import MediaBlock from '@/components/blocks/MediaBlock.vue'
import RequestBlock from '@/components/blocks/RequestBlock.vue'

import TextBlockEditor from '@/components/properties/TextBlockEditor.vue'
import InputBlockEditor from '@/components/properties/InputBlockEditor.vue'
import ButtonsBlockEditor from '@/components/properties/ButtonsBlockEditor.vue'
import ConditionBlockEditor from '@/components/properties/ConditionBlockEditor.vue'
import MediaBlockEditor from '@/components/properties/MediaBlockEditor.vue'
import RequestBlockEditor from '@/components/properties/RequestBlockEditor.vue'
import PollBlockEditor from '@/components/properties/PollBlockEditor.vue'

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

    // --- Медиа-блоки (Фаза 1) ------------------------------------------
    // Все четыре — один и тот же компонент отображения/редактора
    // (MediaBlock/MediaBlockEditor), различаются только метаданными.
    // requiresCapabilities: ['file_upload'] — сейчас это ничего не
    // фильтрует (Telegram — единственный канал и умеет file_upload), но
    // если появится канал без загрузки файлов, эти блоки сами перестанут
    // предлагаться в его библиотеке (см. src/channels).
    image: {
        type: 'image',
        category: 'bubbles',
        label: 'Изображение',
        hint: 'Фото с необязательной подписью',
        icon: '🖼️',
        renderComponent: MediaBlock,
        editorComponent: MediaBlockEditor,
        defaultTitle: 'Изображение',
        defaultContent: () => ({ mediaUrl: '', translations: { ru: '', en: '' } }),
        defaultConfig: () => ({}),
        requiresCapabilities: ['file_upload'],
    },
    video: {
        type: 'video',
        category: 'bubbles',
        label: 'Видео',
        hint: 'Видео с необязательной подписью',
        icon: '🎬',
        renderComponent: MediaBlock,
        editorComponent: MediaBlockEditor,
        defaultTitle: 'Видео',
        defaultContent: () => ({ mediaUrl: '', translations: { ru: '', en: '' } }),
        defaultConfig: () => ({}),
        requiresCapabilities: ['file_upload'],
    },
    audio: {
        type: 'audio',
        category: 'bubbles',
        label: 'Аудио',
        hint: 'Аудиофайл с необязательной подписью',
        icon: '🎵',
        renderComponent: MediaBlock,
        editorComponent: MediaBlockEditor,
        defaultTitle: 'Аудио',
        defaultContent: () => ({ mediaUrl: '', translations: { ru: '', en: '' } }),
        defaultConfig: () => ({}),
        requiresCapabilities: ['file_upload'],
    },
    file: {
        type: 'file',
        category: 'bubbles',
        label: 'Файл',
        hint: 'Документ произвольного типа',
        icon: '📎',
        renderComponent: MediaBlock,
        editorComponent: MediaBlockEditor,
        defaultTitle: 'Файл',
        defaultContent: () => ({ mediaUrl: '', mediaFileName: '', translations: { ru: '', en: '' } }),
        defaultConfig: () => ({}),
        requiresCapabilities: ['file_upload'],
    },

    // --- Валидируемые input-блоки (Фаза 2) ------------------------------
    // Та же механика, что у 'input' (вопрос → переменная), только с
    // проверкой формата ответа в симуляторе (useFlowSimulator.ts). Один
    // компонент (InputBlock/InputBlockEditor) на все варианты — он сам
    // смотрит на block.type, чтобы показать нужную иконку/подсказку.
    // Универсальны для любого канала (в отличие от request-блоков ниже,
    // которым нужна конкретная нативная возможность канала).
    number: {
        type: 'number',
        category: 'inputs',
        label: 'Число',
        hint: 'Ждём числовой ответ, с необязательными мин/макс',
        icon: '🔢',
        renderComponent: InputBlock,
        editorComponent: InputBlockEditor,
        defaultTitle: 'Число',
        defaultContent: () => ({}),
        defaultConfig: () => ({ variable: '' }),
        producesVariable: true,
    },
    email: {
        type: 'email',
        category: 'inputs',
        label: 'Email',
        hint: 'Ждём ответ в формате email',
        icon: '📧',
        renderComponent: InputBlock,
        editorComponent: InputBlockEditor,
        defaultTitle: 'Email',
        defaultContent: () => ({}),
        defaultConfig: () => ({ variable: '' }),
        producesVariable: true,
    },
    phone: {
        type: 'phone',
        category: 'inputs',
        label: 'Телефон',
        hint: 'Ждём номер телефона',
        icon: '📱',
        renderComponent: InputBlock,
        editorComponent: InputBlockEditor,
        defaultTitle: 'Телефон',
        defaultContent: () => ({}),
        defaultConfig: () => ({ variable: '' }),
        producesVariable: true,
    },
    date: {
        type: 'date',
        category: 'inputs',
        label: 'Дата',
        hint: 'Ждём дату в формате ГГГГ-ММ-ДД',
        icon: '📅',
        renderComponent: InputBlock,
        editorComponent: InputBlockEditor,
        defaultTitle: 'Дата',
        defaultContent: () => ({}),
        defaultConfig: () => ({ variable: '' }),
        producesVariable: true,
    },

    // --- Запрос через нативную кнопку Telegram (Фаза 2) -----------------
    geolocation: {
        type: 'geolocation',
        category: 'inputs',
        label: 'Геолокация',
        hint: 'Запрос геолокации нативной кнопкой Telegram',
        icon: '📍',
        renderComponent: RequestBlock,
        editorComponent: RequestBlockEditor,
        defaultTitle: 'Геолокация',
        defaultContent: () => ({ translations: { ru: '', en: '' } }),
        defaultConfig: () => ({ variable: '' }),
        producesVariable: true,
        requiresCapabilities: ['geolocation'],
    },
    contact: {
        type: 'contact',
        category: 'inputs',
        label: 'Контакт',
        hint: 'Запрос номера телефона нативной кнопкой Telegram',
        icon: '☎️',
        renderComponent: RequestBlock,
        editorComponent: RequestBlockEditor,
        defaultTitle: 'Контакт',
        defaultContent: () => ({ translations: { ru: '', en: '' } }),
        defaultConfig: () => ({ variable: '' }),
        producesVariable: true,
        requiresCapabilities: ['contact_share'],
    },

    // --- Опрос (Фаза 2) --------------------------------------------------
    // Category 'bubbles', а не 'inputs': в отличие от 'button' опрос не
    // ждёт ответа в рамках этого сообщения (голоса Telegram присылает
    // асинхронно), поэтому это блок "отправить и продолжить" — как text
    // или image, — а не блок, останавливающий диалог до ответа.
    poll: {
        type: 'poll',
        category: 'bubbles',
        label: 'Опрос',
        hint: 'Вопрос с вариантами ответа (sendPoll)',
        icon: '📊',
        renderComponent: ButtonBlock,
        editorComponent: PollBlockEditor,
        defaultTitle: 'Опрос',
        defaultContent: () => ({ translations: { ru: '', en: '' }, buttons: [] }),
        defaultConfig: () => ({}),
        requiresCapabilities: ['poll'],
    },
}
